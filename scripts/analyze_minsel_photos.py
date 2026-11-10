import os
from PIL import Image

folder = r"D:\sig_minsel\frontend\public\images\minsel"
files = [f for f in os.listdir(folder) if f.lower().endswith(('.jpg', '.png', '.webp'))]

print(f"Total files: {len(files)}")
print("-" * 75)
print(f"{'Filename':<35} | {'Dimensions':<12} | {'Size (KB)':<10} | {'Tone':<12}")
print("-" * 75)

for f in sorted(files):
    p = os.path.join(folder, f)
    try:
        with Image.open(p) as img:
            w, h = img.size
            size_kb = os.path.getsize(p) // 1024
            
            # Simple dominant tone sampling (resize to 1x1 or sample)
            small = img.convert('RGB').resize((10, 10))
            pixels = list(small.getdata())
            r = sum(px[0] for px in pixels) / len(pixels)
            g = sum(px[1] for px in pixels) / len(pixels)
            b = sum(px[2] for px in pixels) / len(pixels)
            
            tone = "Neutral"
            if b > r + 20 and b > g + 10:
                tone = "Blue/Sea/Sky"
            elif g > r + 15 and g > b + 15:
                tone = "Green/Lush"
            elif r > b + 30 and g > b + 20:
                tone = "Warm/Golden"
            elif r > g + 25 and r > b + 25:
                tone = "Red/Earthy"
                
            print(f"{f:<35} | {f'{w}x{h}':<12} | {size_kb:<10} | {tone:<12}")
    except Exception as e:
        print(f"{f:<35} | ERROR: {e}")
