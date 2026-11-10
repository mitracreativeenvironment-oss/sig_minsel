import os
import glob
from PIL import Image

folder = r"D:\sig_minsel\frontend\public\images\minsel"
# Check 6 diverse screenshots
samples = [
    "Screenshot 2026-10-27 120411.png",
    "Screenshot 2026-10-27 121450.png",
    "Screenshot 2026-10-27 121902.png",
    "Screenshot 2026-10-27 122800.png",
    "Screenshot 2026-10-27 123015.png",
    "Screenshot 2026-10-27 123557.png"
]

print("Analyzing sample images:")
for s in samples:
    p = os.path.join(folder, s)
    if os.path.exists(p):
        with Image.open(p) as img:
            # Let's inspect small thumbnail info
            print(f"- {s}: size={img.size}, format={img.format}")
