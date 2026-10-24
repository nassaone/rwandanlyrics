#!/usr/bin/env python3
"""Generates favicon/app icons and the default share image into public/. No dependencies."""
import os, struct, zlib

GREEN, YELLOW, SKY, WHITE = (27, 107, 69), (250, 210, 1), (31, 160, 220), (255, 255, 255)

G = {
 'R': ["####.","#...#","#...#","####.","#.#..","#..#.","#...#"],
 'W': ["#...#","#...#","#...#","#.#.#","#.#.#","##.##","#...#"],
 'A': [".###.","#...#","#...#","#####","#...#","#...#","#...#"],
 'N': ["#...#","##..#","#.#.#","#..##","#...#","#...#","#...#"],
 'D': ["####.","#...#","#...#","#...#","#...#","#...#","####."],
 'L': ["#....","#....","#....","#....","#....","#....","#####"],
 'Y': ["#...#","#...#",".#.#.","..#..","..#..","..#..","..#.."],
 'I': ["#####","..#..","..#..","..#..","..#..","..#..","#####"],
 'C': [".###.","#...#","#....","#....","#....","#...#",".###."],
 'S': [".####","#....","#....",".###.","....#","....#","####."],
}

class Canvas:
    def __init__(s, w, h, bg):
        s.w, s.h = w, h
        s.rows = [bytearray(bytes(bg) * w) for _ in range(h)]
    def rect(s, x, y, w, h, c):
        for yy in range(max(0, y), min(s.h, y + h)):
            x0, x1 = max(0, x), min(s.w, x + w)
            s.rows[yy][x0*3:x1*3] = bytes(c) * (x1 - x0)
    def glyph(s, ch, x, y, k, c):
        for r, line in enumerate(G[ch]):
            for col, v in enumerate(line):
                if v == '#':
                    s.rect(x + col*k, y + r*k, k, k, c)
    def save(s, path):
        raw = b''.join(b'\x00' + bytes(r) for r in s.rows)
        def chunk(t, d):
            c = struct.pack('>I', len(d)) + t + d
            return c + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
        data = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', s.w, s.h, 8, 2, 0, 0, 0)) \
             + chunk(b'IDAT', zlib.compress(raw, 9)) + chunk(b'IEND', b'')
        open(path, 'wb').write(data)

def icon(size, path):
    c = Canvas(size, size, GREEN)
    k = max(1, size // 12)
    gw, gh = 5 * k, 7 * k
    x, y = (size - gw) // 2, int(size * 0.18)
    c.glyph('R', x, y, k, WHITE)
    c.rect(int(size * 0.22), y + gh + k, int(size * 0.56), max(2, k), YELLOW)
    c.save(path)

def og(path):
    c = Canvas(1200, 630, GREEN)
    k = 14
    text = [('R','W','A','N','D','A','N'), ('L','Y','R','I','C','S')]
    chars = [(ch, WHITE) for ch in text[0]] + [(ch, YELLOW) for ch in text[1]]
    total = len(chars) * 6 * k - k
    x = (1200 - total) // 2
    y = 250
    for ch, col in chars:
        c.glyph(ch, x, y, k, col)
        x += 6 * k
    c.rect(0, 606, 400, 24, GREEN); c.rect(400, 606, 400, 24, YELLOW); c.rect(800, 606, 400, 24, SKY)
    c.rect(0, 0, 400, 12, WHITE if False else GREEN)
    c.save(path)

if __name__ == '__main__':
    out = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'public')
    icon(180, os.path.join(out, 'apple-touch-icon.png'))
    icon(192, os.path.join(out, 'icon-192.png'))
    icon(512, os.path.join(out, 'icon-512.png'))
    og(os.path.join(out, 'og-default.png'))
    print('ok')
