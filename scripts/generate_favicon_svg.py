import base64
from PIL import Image

src = Image.open('frontend/public/images/logo_minsel.png').convert('RGBA')
w, h = src.size

# Create a high quality 256x256 square version
canvas = Image.new('RGBA', (256, 256), (0, 0, 0, 0))
scale = 236.0 / h
new_w = int(w * scale)
new_h = int(h * scale)
resized = src.resize((new_w, new_h), Image.Resampling.LANCZOS)
canvas.paste(resized, ((256 - new_w) // 2, (256 - new_h) // 2), resized)
canvas.save('frontend/public/images/logo_minsel_256.png', optimize=True)

with open('frontend/public/images/logo_minsel_256.png', 'rb') as f:
    b64 = base64.b64encode(f.read()).decode('utf-8')

svg_content = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="256" height="256">
  <title>Lambang Resmi Kabupaten Minahasa Selatan</title>
  <image href="data:image/png;base64,{b64}" x="0" y="0" width="256" height="256" preserveAspectRatio="xMidYMid meet" />
</svg>'''

with open('frontend/public/favicon.svg', 'w', encoding='utf-8') as f:
    f.write(svg_content)

print('Generated frontend/public/favicon.svg successfully!')
