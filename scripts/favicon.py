"""Draws the Vocal Vibes Nation favicon set: a green V with a sun in it, on deep green-black."""
import sys
from pathlib import Path
from PIL import Image, ImageDraw

OUT = Path(sys.argv[1]) if len(sys.argv) > 1 else Path('public')
BG, V, SUN = (14, 21, 18), (61, 220, 151), (250, 204, 21)
BASE = 1024  # draw big, then downscale for clean edges


def render(rounded: bool, margin: float = 0.0) -> Image.Image:
    k = BASE / 64
    img = Image.new('RGBA', (BASE, BASE), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    if rounded:
        d.rounded_rectangle((0, 0, BASE - 1, BASE - 1), radius=14 * k, fill=BG)
    else:
        d.rectangle((0, 0, BASE, BASE), fill=BG)

    # shrink the artwork toward the centre when a margin is wanted (iOS and maskable icons)
    def pt(x, y):
        c = 32
        s = 1 - margin
        return ((c + (x - c) * s) * k, (c + (y - c) * s) * k)

    w = 10.5 * k * (1 - margin)
    pts = [pt(14, 15), pt(32, 50), pt(50, 15)]
    d.line(pts, fill=V, width=int(w), joint='curve')
    for x, y in pts:
        d.ellipse((x - w / 2, y - w / 2, x + w / 2, y + w / 2), fill=V)
    cx, cy = pt(32, 22)
    r = 6.6 * k * (1 - margin)
    d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=SUN)
    return img


def save(img: Image.Image, name: str, size: int) -> None:
    img.resize((size, size), Image.LANCZOS).save(OUT / name)


OUT.mkdir(parents=True, exist_ok=True)
std = render(rounded=True)
for name, size in [('favicon-96.png', 96), ('favicon-48.png', 48), ('icon-192.png', 192), ('icon-512.png', 512)]:
    save(std, name, size)
# the .ico holds several sizes so tabs, bookmarks and Google all find a good one
std.resize((256, 256), Image.LANCZOS).save(OUT / 'favicon.ico', sizes=[(16, 16), (32, 32), (48, 48)])
# iOS rounds the corners itself, so it gets a full square with breathing room
render(rounded=False, margin=0.12).resize((180, 180), Image.LANCZOS).save(OUT / 'apple-touch-icon.png')
print('favicon set written to', OUT)
