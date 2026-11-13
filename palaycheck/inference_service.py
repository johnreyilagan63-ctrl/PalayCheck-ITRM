import ast
import hmac
import json
import os
from pathlib import Path

from flask import Flask, jsonify, request
from PIL import Image, UnidentifiedImageError

try:
    import numpy as np
except ImportError:
    np = None

try:
    import onnxruntime as ort
except ImportError:
    ort = None

try:
    from ultralytics import YOLO
except ImportError:  # allows the service to start and report a clear error
    YOLO = None

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 13 * 1024 * 1024  # 12 MB image plus multipart overhead
MODEL_PATH = Path(os.getenv("MODEL_PATH", Path(__file__).parent / "models" / "palaycheck_best.pt"))
SERVICE_TOKEN = os.getenv("INFERENCE_TOKEN", "")
DEFAULT_CLASS_NAMES = (
    "ant", "aphid", "bees", "butterfly", "caterpillar", "cicada", "dragonfly",
    "grasshopper", "green_lacewing", "ladybug", "leafhopper", "mantis",
    "mole_cricket", "planthopper", "rhino_beetle", "rice_bug", "spider",
    "stem_borer", "stink_bug", "undefined", "weevil",
)
model = None


def onnx_class_names(session):
    encoded = session.get_modelmeta().custom_metadata_map.get("names", "")
    if encoded:
        try:
            names = json.loads(encoded)
        except json.JSONDecodeError:
            try:
                names = ast.literal_eval(encoded)
            except (ValueError, SyntaxError):
                names = None

        if isinstance(names, dict):
            indexed_names = {}
            for index, name in names.items():
                try:
                    indexed_names[int(index)] = str(name)
                except (TypeError, ValueError):
                    continue
            if indexed_names:
                return indexed_names
        elif isinstance(names, (list, tuple)):
            return dict(enumerate(str(name) for name in names))

    return dict(enumerate(DEFAULT_CLASS_NAMES))


def load_onnx_model():
    global model
    if ort is None or np is None:
        raise RuntimeError("ONNX Runtime and NumPy are required for ONNX inference.")
    if model is None:
        options = ort.SessionOptions()
        options.intra_op_num_threads = 1
        options.inter_op_num_threads = 1
        options.execution_mode = ort.ExecutionMode.ORT_SEQUENTIAL
        model = ort.InferenceSession(
            str(MODEL_PATH),
            sess_options=options,
            providers=["CPUExecutionProvider"],
        )
    return model


def onnx_detections(session, image, confidence, requested_classes):
    names = onnx_class_names(session)
    class_ids = {
        index for index, name in names.items()
        if not requested_classes or name in requested_classes
    }
    if not class_ids:
        return []

    input_meta = session.get_inputs()[0]
    shape = input_meta.shape
    input_height = shape[2] if isinstance(shape[2], int) else 640
    input_width = shape[3] if isinstance(shape[3], int) else 640
    original_width, original_height = image.size
    scale = min(input_width / original_width, input_height / original_height)
    resized_width = max(1, round(original_width * scale))
    resized_height = max(1, round(original_height * scale))
    resized = image.resize((resized_width, resized_height), Image.Resampling.BILINEAR)
    pad_left = (input_width - resized_width) // 2
    pad_top = (input_height - resized_height) // 2
    canvas = Image.new("RGB", (input_width, input_height), (114, 114, 114))
    canvas.paste(resized, (pad_left, pad_top))

    tensor = np.asarray(canvas, dtype=np.float32) / 255.0
    tensor = np.transpose(tensor, (2, 0, 1))[None, ...]
    prediction = np.asarray(session.run(None, {input_meta.name: tensor})[0])
    if prediction.ndim == 3 and prediction.shape[0] == 1:
        prediction = prediction[0]

    channel_count = len(names) + 4
    if prediction.ndim != 2:
        raise RuntimeError("The ONNX model returned an unsupported prediction shape.")
    if prediction.shape[0] == channel_count:
        prediction = prediction.T
    elif prediction.shape[1] != channel_count:
        raise RuntimeError("The ONNX model output does not match its class metadata.")

    class_scores = prediction[:, 4:channel_count]
    prediction_class_ids = np.argmax(class_scores, axis=1)
    prediction_confidences = class_scores[np.arange(len(class_scores)), prediction_class_ids]
    mask = (prediction_confidences >= confidence) & np.isin(
        prediction_class_ids, list(class_ids)
    )
    candidates = np.flatnonzero(mask)
    if not candidates.size:
        return []
    if candidates.size > 1000:
        candidates = candidates[
            np.argpartition(prediction_confidences[candidates], -1000)[-1000:]
        ]

    boxes = prediction[candidates, :4].copy()
    scores = prediction_confidences[candidates]
    selected_class_ids = prediction_class_ids[candidates]
    if np.max(np.abs(boxes)) <= 1.5:
        boxes[:, (0, 2)] *= input_width
        boxes[:, (1, 3)] *= input_height

    centers_x, centers_y, widths, heights = boxes.T
    x1 = np.clip((centers_x - widths / 2 - pad_left) / scale, 0, original_width)
    y1 = np.clip((centers_y - heights / 2 - pad_top) / scale, 0, original_height)
    x2 = np.clip((centers_x + widths / 2 - pad_left) / scale, 0, original_width)
    y2 = np.clip((centers_y + heights / 2 - pad_top) / scale, 0, original_height)
    valid = (x2 > x1) & (y2 > y1)
    x1, y1, x2, y2 = x1[valid], y1[valid], x2[valid], y2[valid]
    scores, selected_class_ids = scores[valid], selected_class_ids[valid]

    areas = (x2 - x1) * (y2 - y1)
    order = np.argsort(scores)[::-1]
    kept = []
    while order.size and len(kept) < 100:
        current = int(order[0])
        kept.append(current)
        remaining = order[1:]
        if not remaining.size:
            break

        intersection_width = np.maximum(
            0, np.minimum(x2[current], x2[remaining]) - np.maximum(x1[current], x1[remaining])
        )
        intersection_height = np.maximum(
            0, np.minimum(y2[current], y2[remaining]) - np.maximum(y1[current], y1[remaining])
        )
        intersection = intersection_width * intersection_height
        union = areas[current] + areas[remaining] - intersection
        overlap = intersection / np.maximum(union, 1e-9)
        same_class = selected_class_ids[remaining] == selected_class_ids[current]
        order = remaining[(overlap <= 0.45) | ~same_class]

    detections = []
    for index in kept:
        class_id = int(selected_class_ids[index])
        detections.append({
            "label": names.get(class_id, str(class_id)),
            "confidence": round(float(scores[index]), 4),
            "box": [
                float(x1[index] / original_width),
                float(y1[index] / original_height),
                float(x2[index] / original_width),
                float(y2[index] / original_height),
            ],
        })
    return detections


def authorized():
    if not SERVICE_TOKEN:
        # The service binds to loopback by default, so a token is optional for
        # local-only deployments.
        return request.remote_addr in (None, "127.0.0.1", "::1")
    return hmac.compare_digest(request.headers.get("Authorization", ""), f"Bearer {SERVICE_TOKEN}")


@app.errorhandler(413)
def too_large(error):
    return jsonify(error="The image must be no larger than 12 MB."), 413


@app.get("/health")
def health():
    if not authorized():
        return jsonify(error="Unauthorized."), 401
    runtime = (
        ort is not None and np is not None
        if MODEL_PATH.suffix.lower() == ".onnx"
        else YOLO is not None
    )
    return jsonify(ready=MODEL_PATH.is_file() and runtime,
                   model=MODEL_PATH.name, runtime=runtime)


@app.post("/detect")
def detect():
    global model
    if not authorized():
        return jsonify(error="Unauthorized."), 401
    uploaded = request.files.get("image")
    if uploaded is None or not uploaded.filename:
        return jsonify(error="No image received."), 400
    try:
        confidence = float(request.form.get("threshold", "0.5"))
    except (TypeError, ValueError):
        return jsonify(error="Invalid confidence threshold."), 400
    if not 0.05 <= confidence <= 0.99:
        return jsonify(error="Invalid confidence threshold."), 400
    requested_classes = {
        name.strip() for name in request.form.get("classes", "").split(",") if name.strip()
    }
    try:
        image = Image.open(uploaded.stream).convert("RGB")
    except (UnidentifiedImageError, OSError):
        return jsonify(error="Invalid image."), 400
    image.thumbnail((1280, 1280))
    try:
        if not MODEL_PATH.is_file():
            return jsonify(error="The detection model is not installed on the inference service."), 503
        if MODEL_PATH.suffix.lower() == ".onnx":
            if ort is None or np is None:
                return jsonify(error="ONNX Runtime is not installed on the inference service."), 503
            detections = onnx_detections(
                load_onnx_model(), image, confidence, requested_classes
            )
        else:
            if YOLO is None:
                return jsonify(error="The ultralytics package is not installed on the inference service."), 503
            if model is None:
                model = YOLO(str(MODEL_PATH))
            classes = None
            if requested_classes:
                classes = [index for index, name in model.names.items() if name in requested_classes]
                if not classes:
                    return jsonify(detections=[])
            result = model.predict(image, conf=confidence, classes=classes, verbose=False)[0]
            width, height = image.size
            detections = []
            for box in result.boxes:
                x1, y1, x2, y2 = box.xyxy[0].tolist()
                detections.append({
                    "label": result.names[int(box.cls[0])],
                    "confidence": round(float(box.conf[0]), 4),
                    "box": [x1 / width, y1 / height, x2 / width, y2 / height],
                })
    except Exception as error:
        app.logger.exception("Model inference failed")
        return jsonify(error=f"Model inference failed: {error}"), 503

    detections.sort(key=lambda item: -item["confidence"])
    return jsonify(detections=detections)


if __name__ == "__main__":
    if MODEL_PATH.suffix.lower() == ".onnx":
        load_onnx_model()
    app.run(host=os.getenv("INFERENCE_HOST", "127.0.0.1"),
            port=int(os.getenv("INFERENCE_PORT", "5001")),
            threaded=False)
