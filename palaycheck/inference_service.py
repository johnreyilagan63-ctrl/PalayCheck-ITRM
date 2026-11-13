import os
import hmac
from pathlib import Path

from flask import Flask, jsonify, request
from PIL import Image, UnidentifiedImageError

try:
    from ultralytics import YOLO
except ImportError:  # allows the service to start and report a clear error
    YOLO = None

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 13 * 1024 * 1024  # 12 MB image plus multipart overhead
MODEL_PATH = Path(os.getenv("MODEL_PATH", Path(__file__).parent / "models" / "palaycheck_best.pt"))
SERVICE_TOKEN = os.getenv("INFERENCE_TOKEN", "")
model = None


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
    return jsonify(ready=MODEL_PATH.is_file() and YOLO is not None,
                   model=MODEL_PATH.name, runtime=YOLO is not None)


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
        if YOLO is None:
            return jsonify(error="The ultralytics package is not installed on the inference service."), 503
        if not MODEL_PATH.is_file():
            return jsonify(error="The detection model is not installed on the inference service."), 503
        if model is None:
            model = YOLO(str(MODEL_PATH))
        classes = None
        if requested_classes:
            classes = [index for index, name in model.names.items() if name in requested_classes]
            if not classes:
                return jsonify(detections=[])
        result = model.predict(image, conf=confidence, classes=classes, verbose=False)[0]
    except Exception as error:
        app.logger.exception("Model inference failed")
        return jsonify(error=f"Model inference failed: {error}"), 503

    width, height = image.size
    detections = []
    for box in result.boxes:
        x1, y1, x2, y2 = box.xyxy[0].tolist()
        detections.append({
            "label": result.names[int(box.cls[0])],
            "confidence": round(float(box.conf[0]), 4),
            "box": [x1 / width, y1 / height, x2 / width, y2 / height],
        })
    detections.sort(key=lambda item: -item["confidence"])
    return jsonify(detections=detections)


if __name__ == "__main__":
    app.run(host=os.getenv("INFERENCE_HOST", "127.0.0.1"),
            port=int(os.getenv("INFERENCE_PORT", "5001")))
