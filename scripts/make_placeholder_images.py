#!/usr/bin/env python3
"""Generate the placeholder imagery used by the sample content.

These are deliberately abstract: muted paper-and-ink washes with a little grain,
so the layout can be judged without anyone mistaking a stock photo for a real
product shot. Replace them with real photography before launch.
"""

import hashlib
import math
import os
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

OUT = sys.argv[1] if len(sys.argv) > 1 else "assets/placeholders"
os.makedirs(OUT, exist_ok=True)

# Warm, low-chroma pairs. Index chosen per slug so a given name always renders
# the same way across re-runs.
PALETTES = [
    ((240, 233, 220), (186, 166, 136)),
    ((238, 236, 228), (150, 160, 142)),
    ((243, 234, 228), (186, 148, 138)),
    ((238, 233, 226), (158, 146, 128)),
    ((242, 236, 222), (172, 152, 116)),
    ((234, 236, 234), (146, 158, 156)),
    ((243, 230, 224), (188, 142, 126)),
    ((237, 238, 232), (154, 164, 142)),
]


def gradient(size, top, bottom, angle_deg):
    """Linear gradient between two RGB tuples at an arbitrary angle."""
    w, h = size
    ys, xs = np.mgrid[0:h, 0:w]
    a = math.radians(angle_deg)
    proj = xs * math.cos(a) + ys * math.sin(a)
    proj = (proj - proj.min()) / max(proj.max() - proj.min(), 1e-6)
    proj = proj[..., None]
    top = np.array(top, dtype=np.float64)
    bottom = np.array(bottom, dtype=np.float64)
    return top * (1 - proj) + bottom * proj


def render(slug, size=(1600, 1067), motif="arc"):
    seed = int(hashlib.sha256(slug.encode()).hexdigest()[:8], 16)
    rng = np.random.default_rng(seed)
    light, dark = PALETTES[seed % len(PALETTES)]

    arr = gradient(size, light, dark, 20 + (seed % 140))

    # Paper grain. Subtle — it should read as texture, not noise.
    grain = rng.normal(0.0, 3.2, (size[1], size[0], 1))
    arr = np.clip(arr + grain, 0, 255).astype(np.uint8)
    img = Image.fromarray(arr, "RGB")

    overlay = Image.new("RGBA", size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)
    w, h = size
    ink = (30, 27, 23)

    if motif == "arc":
        for i in range(3):
            r = int(min(w, h) * (0.30 + 0.13 * i))
            cx = int(w * (0.30 + 0.12 * (seed % 3)))
            cy = int(h * 0.62)
            draw.ellipse([cx - r, cy - r, cx + r, cy + r], outline=ink + (34,), width=2)
    elif motif == "still":
        # A vessel silhouette drawn from a smooth profile curve: narrow lip,
        # shoulder about two-thirds up, tapering to a small foot.
        cx = w * 0.5
        top = h * 0.20
        base = h * 0.80
        span = base - top
        lip = w * 0.052
        belly = w * 0.185
        foot = w * 0.088
        left, right = [], []
        for i in range(121):
            t = i / 120.0
            y = top + span * t
            neck_end = lip + (belly - lip) * 0.10
            if t < 0.20:                      # neck
                r = lip + (neck_end - lip) * (t / 0.20)
            elif t < 0.58:                    # shoulder swelling out
                u = (t - 0.20) / 0.38
                r = neck_end + (belly - neck_end) * math.sin(u * math.pi / 2)
            else:                             # body tapering to the foot
                u = (t - 0.58) / 0.42
                r = belly - (belly - foot) * (u ** 1.7)
            left.append((cx - r, y))
            right.append((cx + r, y))
        draw.polygon(left + right[::-1], fill=ink + (30,))
        # Lip and shelf line.
        draw.line([cx - lip * 1.5, top, cx + lip * 1.5, top], fill=ink + (60,), width=3)
        draw.line([w * 0.10, base, w * 0.90, base], fill=ink + (50,), width=3)
    elif motif == "rules":
        for i in range(7):
            y = int(h * (0.22 + 0.085 * i))
            x2 = int(w * (0.86 - 0.07 * ((seed >> i) % 5)))
            draw.line([int(w * 0.14), y, x2, y], fill=ink + (30,), width=2)

    overlay = overlay.filter(ImageFilter.GaussianBlur(0.6))
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")

    # Gentle vignette so the edges sit down against the page.
    ys, xs = np.mgrid[0:h, 0:w]
    d = np.sqrt(((xs - w / 2) / (w / 2)) ** 2 + ((ys - h / 2) / (h / 2)) ** 2)
    vig = np.clip(1.0 - 0.14 * np.clip(d - 0.55, 0, None) / 0.75, 0, 1)[..., None]
    img = Image.fromarray((np.asarray(img) * vig).astype(np.uint8), "RGB")

    path = os.path.join(OUT, f"{slug}.jpg")
    img.save(path, "JPEG", quality=84, optimize=True, progressive=True)
    return path


SPECS = [
    # Articles — wide, editorial.
    ("article-hand-set-type", "rules"),
    ("article-quiet-rooms", "arc"),
    ("article-on-repair", "still"),
    ("article-winter-light", "arc"),
    ("article-a-short-history-of-the-margin", "rules"),
    ("article-the-long-table", "still"),
    # Products — squarer, object-forward.
    ("product-stoneware-carafe", "still"),
    ("product-linen-runner", "rules"),
    ("product-brass-desk-rule", "rules"),
    ("product-oak-reading-stand", "arc"),
    ("product-cotton-throw", "rules"),
    ("product-glass-decanter", "still"),
    ("product-ceramic-bowl-set", "still"),
    ("product-leather-portfolio", "arc"),
    ("product-enamel-jug", "still"),
    # Community entries.
    ("submission-grandmothers-table", "still"),
    ("submission-the-bookbinder", "rules"),
    ("submission-a-kitchen-in-may", "arc"),
]

if __name__ == "__main__":
    for slug, motif in SPECS:
        size = (1200, 1200) if slug.startswith("product-") else (1600, 1000)
        print(render(slug, size=size, motif=motif))
