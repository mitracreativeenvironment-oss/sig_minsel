import os
import glob
from PIL import Image, ImageStat

folder = r"D:\sig_minsel\frontend\public\images\minsel"
files = sorted(glob.glob(os.path.join(folder, "*.png")) + glob.glob(os.path.join(folder, "*.jpg")))

print(f"{'Idx':<3} | {'Filename':<35} | {'Brightness':<10} | {'R':<4} {'G':<4} {'B':<4} | {'Visual Category'}")
print("-" * 80)

for idx, f in enumerate(files):
    fname = os.path.basename(f)
    try:
        with Image.open(f) as img:
            rgb = img.convert('RGB').resize((100, 50))
            stat = ImageStat.Stat(rgb)
            r, g, b = [int(v) for v in stat.mean]
            brightness = int(0.299*r + 0.587*g + 0.114*b)
            
            # Classify
            if b > r + 15 and b > g:
                cat = "Lautan / Pesisir / Langit Biru"
            elif g > r + 10 and g > b:
                cat = "Pertanian / Perbukitan Hijau / Hutan"
            elif r > b + 25 and g > b + 15:
                cat = "Senja / Golden Hour / Pantai"
            elif brightness > 160:
                cat = "Lanskap Terang / Udara"
            elif brightness < 80:
                cat = "Malam / Kontras Gelap"
            else:
                cat = "Permukiman / Tata Kota / Infrastruktur"
                
            print(f"{idx+1:<3} | {fname:<35} | {brightness:<10} | {r:<4} {g:<4} {b:<4} | {cat}")
    except Exception as e:
        print(f"Error reading {fname}: {e}")
