"""CityFix AI service: guesses the complaint category from a photo.

It uses CLIP (a pretrained model), so no training data is needed.
Run:  python app.py      (it listens on http://127.0.0.1:5001)
"""
import io
from flask import Flask, request, jsonify
from PIL import Image

app = Flask(__name__)

# What to look for. Each category has a few plain-English descriptions.
PROMPTS = {
    "Road Damage": [
        "a photo of a pothole on a road",
        "a photo of a cracked and damaged road",
        "a photo of a broken road surface",
    ],
    "Garbage": [
        "a photo of garbage dumped on the street",
        "a photo of a pile of rubbish and litter",
        "a photo of an overflowing garbage bin",
    ],
    "Streetlights": [
        "a photo of a broken street light pole",
        "a photo of a street lamp on a pole",
        "a photo of a dark street with a faulty light",
    ],
    "Water Leakage": [
        "a photo of a leaking water pipe",
        "a photo of water flowing on a road from a burst pipe",
        "a photo of a flooded street with standing water",
    ],
}

MIN_CONFIDENCE = 0.40  # below this the AI says "not sure"
_model = None


def _load():
    """Load the model the first time it is needed (about 600 MB download once)."""
    global _model
    if _model is None:
        import torch
        from transformers import CLIPModel, CLIPProcessor

        name = "openai/clip-vit-base-patch32"
        _model = (CLIPModel.from_pretrained(name).eval(), CLIPProcessor.from_pretrained(name), torch)
    return _model


def classify(image):
    """Return (category, confidence) for a PIL image."""
    model, processor, torch = _load()
    texts, owners = [], []
    for category, prompts in PROMPTS.items():
        for p in prompts:
            texts.append(p)
            owners.append(category)
    inputs = processor(text=texts, images=image, return_tensors="pt", padding=True)
    with torch.no_grad():
        probs = model(**inputs).logits_per_image.softmax(dim=1)[0].tolist()
    totals = {c: 0.0 for c in PROMPTS}
    for owner, p in zip(owners, probs):
        totals[owner] += p
    best = max(totals, key=totals.get)
    return best, totals[best]


@app.route("/")
def home():
    return "CityFix AI service is running. The website sends photos to it automatically. You can also check /health"


@app.route("/health")
def health():
    return jsonify(status="ok")


@app.route("/predict", methods=["POST"])
def predict():
    f = request.files.get("image")
    if not f:
        return jsonify(error="no image"), 400
    try:
        image = Image.open(io.BytesIO(f.read())).convert("RGB")
    except Exception:
        return jsonify(error="not a valid image"), 400
    category, confidence = classify(image)
    if confidence < MIN_CONFIDENCE:
        return jsonify(error="not sure", confidence=round(confidence, 2)), 422
    return jsonify(category=category, confidence=round(confidence, 2))


if __name__ == "__main__":
    print("Loading the AI model (the first time this downloads about 600 MB)...")
    _load()
    print("Model ready. Waiting for photos on http://127.0.0.1:5001")
    app.run(host="127.0.0.1", port=5001)
