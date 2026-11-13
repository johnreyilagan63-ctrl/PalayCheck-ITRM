import json
import unittest

import numpy as np
from PIL import Image

from inference_service import DEFAULT_CLASS_NAMES, onnx_detections


class FakeModelMetadata:
    custom_metadata_map = {
        "names": json.dumps({
            str(index): name for index, name in enumerate(DEFAULT_CLASS_NAMES)
        })
    }


class FakeInput:
    name = "images"
    shape = [1, 3, 640, 640]


class FakeSession:
    def __init__(self, prediction):
        self.prediction = prediction
        self.calls = 0

    def get_modelmeta(self):
        return FakeModelMetadata()

    def get_inputs(self):
        return [FakeInput()]

    def run(self, outputs, inputs):
        self.calls += 1
        self.asserted_input = inputs["images"]
        return [self.prediction]


class OnnxDetectionTests(unittest.TestCase):
    def test_decodes_letterboxed_boxes_and_suppresses_same_class_overlaps(self):
        prediction = np.zeros((1, 25, 3), dtype=np.float32)
        prediction[0, :4, :] = np.array([
            [320, 321, 320],
            [320, 321, 320],
            [100, 100, 100],
            [100, 100, 100],
        ])
        prediction[0, 4 + 17, 0] = 0.90
        prediction[0, 4 + 17, 1] = 0.80
        prediction[0, 4 + 10, 2] = 0.85
        session = FakeSession(prediction)

        detections = onnx_detections(
            session,
            Image.new("RGB", (640, 480)),
            0.5,
            {"stem_borer", "leafhopper"},
        )

        self.assertEqual([item["label"] for item in detections], ["stem_borer", "leafhopper"])
        self.assertEqual(len(detections), 2)
        np.testing.assert_allclose(
            detections[0]["box"],
            [0.421875, 0.3958333333, 0.578125, 0.6041666667],
            atol=1e-6,
        )
        self.assertEqual(session.asserted_input.shape, (1, 3, 640, 640))

    def test_returns_empty_without_running_model_when_no_class_matches(self):
        session = FakeSession(np.zeros((1, 25, 1), dtype=np.float32))

        detections = onnx_detections(
            session,
            Image.new("RGB", (640, 480)),
            0.5,
            {"unknown_pest"},
        )

        self.assertEqual(detections, [])
        self.assertEqual(session.calls, 0)


if __name__ == "__main__":
    unittest.main()
