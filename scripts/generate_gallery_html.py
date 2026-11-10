import os
import glob
from PIL import Image

folder = r"D:\sig_minsel\frontend\public\images\minsel"
output_html = os.path.join(folder, "gallery_preview.html")

files = sorted(glob.glob(os.path.join(folder, "*.png")) + glob.glob(os.path.join(folder, "*.jpg")))

html = """<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Galeri Foto Minahasa Selatan</title>
<style>
body { font-family: sans-serif; background: #0f172a; color: #fff; padding: 20px; }
h1 { color: #38bdf8; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.card { background: #1e293b; border-radius: 8px; overflow: hidden; border: 1px solid #334155; }
.card img { width: 100%; height: 180px; object-fit: cover; }
.card-body { padding: 10px; font-size: 12px; }
.card-title { font-weight: bold; color: #7dd3fc; margin-bottom: 4px; word-break: break-all; }
</style>
</head>
<body>
<h1>Koleksi Foto Nyata Minahasa Selatan ({len(files)} Foto)</h1>
<div class="grid">
"""

for f in files:
    fname = os.path.basename(f)
    try:
        with Image.open(f) as img:
            w, h = img.size
            html += f"""
<div class="card">
  <img src="{fname}" alt="{fname}" loading="lazy">
  <div class="card-body">
    <div class="card-title">{fname}</div>
    <div>Dimensi: {w}x{h} px</div>
  </div>
</div>
"""
    except Exception as e:
        pass

html += """
</div>
</body>
</html>
"""

with open(output_html, "w", encoding="utf-8") as out:
    out.write(html)

print("Generated:", output_html)
