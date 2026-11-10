#!/usr/bin/env python3
"""
Segment the authentic Lambang_Kabupaten_Minahasa_Selatan.png into clean 3D assembly layers.
"""

import os
import numpy as np
from PIL import Image

SRC = "frontend/public/images/Lambang_Kabupaten_Minahasa_Selatan.png"
OUT_DIR = "frontend/public/images/minsel_logo_3d"
os.makedirs(OUT_DIR, exist_ok=True)

img = Image.open(SRC).convert("RGBA")
w, h = img.size
arr = np.array(img)
alpha = arr[:, :, 3]

# 1. Full Emblem (clean optimized PNG)
img.save(os.path.join(OUT_DIR, "layer_emblem_complete.png"))
print("[OK] Saved layer_emblem_complete.png")

# 2. Chest Emblem (Open Book, Sea & Coconut Palm Tree)
# Located around X: 335..595, Y: 505..765
chest_arr = np.zeros_like(arr)
# Mask within bounding box, excluding brown plumage outside the book
for y in range(500, 770):
    for x in range(330, 600):
        if alpha[y, x] > 50:
            r, g, b, a = arr[y, x]
            # Blue sea, green tree, white book pages, black book frame/text, green coconut crown
            # Brown plumage has r > 120, g in 50..120, b < 90
            is_plumage = (r > 130 and 50 < g < 130 and b < 90)
            if not is_plumage:
                # Part of book / tree / sea
                chest_arr[y, x] = arr[y, x]

chest_img = Image.fromarray(chest_arr)
chest_img.save(os.path.join(OUT_DIR, "layer_chest_emblem.png"))
print("[OK] Saved layer_chest_emblem.png")

# 3. Ribbon Banner ('Cita Waya Esa')
# Located in lower half Y: 750..990, X: 130..800
ribbon_arr = np.zeros_like(arr)
for y in range(740, 1000):
    for x in range(130, 800):
        if alpha[y, x] > 50:
            r, g, b, a = arr[y, x]
            # Yellow ribbon (r > 160, g > 130, b < 90) or black text / outline (r < 70, g < 70, b < 70)
            is_yellow = (r > 160 and g > 130 and b < 90)
            is_text_or_border = (r < 65 and g < 65 and b < 65)
            # Exclude brown talons / tail:
            is_plumage = (r > 130 and 50 < g < 130 and b < 90)
            if (is_yellow or is_text_or_border) and not is_plumage:
                ribbon_arr[y, x] = arr[y, x]

ribbon_img = Image.fromarray(ribbon_arr)
ribbon_img.save(os.path.join(OUT_DIR, "layer_ribbon.png"))
print("[OK] Saved layer_ribbon.png")

# 4. Left Wing
wing_left_arr = np.zeros_like(arr)
for y in range(210, 740):
    for x in range(20, 385):
        if alpha[y, x] > 50:
            r, g, b, a = arr[y, x]
            # Exclude red shield background (r > 170, g < 70, b < 70)
            is_red_bg = (r > 170 and g < 70 and b < 70)
            if not is_red_bg:
                wing_left_arr[y, x] = arr[y, x]

wing_left_img = Image.fromarray(wing_left_arr)
wing_left_img.save(os.path.join(OUT_DIR, "layer_wing_left.png"))
print("[OK] Saved layer_wing_left.png")

# 5. Right Wing
wing_right_arr = np.zeros_like(arr)
for y in range(210, 740):
    for x in range(545, 915):
        if alpha[y, x] > 50:
            r, g, b, a = arr[y, x]
            is_red_bg = (r > 170 and g < 70 and b < 70)
            if not is_red_bg:
                wing_right_arr[y, x] = arr[y, x]

wing_right_img = Image.fromarray(wing_right_arr)
wing_right_img.save(os.path.join(OUT_DIR, "layer_wing_right.png"))
print("[OK] Saved layer_wing_right.png")

# 6. Body & Head (Owl head with eyes, ears, beak, torso, tail, and talons)
body_arr = np.zeros_like(arr)
for y in range(350, 1000):
    for x in range(200, 730):
        if alpha[y, x] > 50:
            r, g, b, a = arr[y, x]
            # Exclude red shield bg
            is_red_bg = (r > 170 and g < 70 and b < 70)
            # Exclude book pixels (already in chest layer)
            is_book = (chest_arr[y, x, 3] > 0)
            # Exclude ribbon pixels (already in ribbon layer)
            is_ribbon = (ribbon_arr[y, x, 3] > 0)
            # Exclude outer wing pixels
            is_outer_wing = (x < 370 and y < 650) or (x > 560 and y < 650)
            if not is_red_bg and not is_book and not is_ribbon and not is_outer_wing:
                body_arr[y, x] = arr[y, x]

body_img = Image.fromarray(body_arr)
body_img.save(os.path.join(OUT_DIR, "layer_body_head.png"))
print("[OK] Saved layer_body_head.png")

# 7. Shield Base (Header 'MINAHASA SELATAN' + Red/White Field + Black Shield Contour)
shield_arr = np.zeros_like(arr)

# Scan each row to find the left and right black border of the shield
for y in range(h):
    row_alpha = alpha[y, :] > 50
    if np.any(row_alpha):
        xs = np.where(row_alpha)[0]
        x_min, x_max = xs[0], xs[-1]
        
        # Header area (Y < 195): copy exact authentic pixels from logo (white header + text 'MINAHASA SELATAN')
        if y < 195:
            shield_arr[y, x_min:x_max+1] = arr[y, x_min:x_max+1]
        else:
            # Shield body:
            # Draw black border on left and right edges (width 14px)
            border_w = 14
            shield_arr[y, x_min:min(w, x_min + border_w)] = [31, 26, 23, 255]
            shield_arr[y, max(0, x_max - border_w):x_max+1] = [31, 26, 23, 255]
            
            # Fill inside:
            # Upper field (y in 195..625) -> Red #DA251D
            # Lower field (y >= 625) -> White #FFFFFF
            inner_start = x_min + border_w
            inner_end = x_max - border_w
            if inner_end > inner_start:
                if y < 625:
                    shield_arr[y, inner_start:inner_end] = [218, 37, 29, 255]
                else:
                    # Bottom point tip black border
                    if y > 1205:
                        shield_arr[y, inner_start:inner_end] = [31, 26, 23, 255]
                    else:
                        shield_arr[y, inner_start:inner_end] = [255, 255, 255, 255]

shield_img = Image.fromarray(shield_arr)
shield_img.save(os.path.join(OUT_DIR, "layer_shield_base.png"))
print("[OK] Saved layer_shield_base.png")

print("All authentic Minahasa Selatan heraldic layers generated successfully!")

