with open('database/seeds/002_seed_kecamatan_minsel.sql', 'r', encoding='utf-8') as f:
    sql = f.read()

new_sql = sql.replace(", 4326)", ", 4326, 'axis-order=long-lat')")
with open('database/seeds/002_seed_kecamatan_minsel.sql', 'w', encoding='utf-8') as f:
    f.write(new_sql)
print('Updated 002_seed_kecamatan_minsel.sql with axis-order=long-lat')

