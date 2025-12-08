import sqlite3
import math
import pandas as pd

# Paths (same folder as this script)
DB_PATH = r"database.sqlite"            # SQLite file
EXCEL_PATH = r"product_datasheet.xlsx"  # Excel file (renamed)


def is_blank(val):
    """Return True if a cell is empty / NaN / whitespace."""
    if val is None:
        return True
    if isinstance(val, float) and math.isnan(val):
        return True
    if isinstance(val, str) and val.strip() == "":
        return True
    return False


print("Loading Excel...")
df = pd.read_excel(EXCEL_PATH)

print("Connecting to SQLite...")
conn = sqlite3.connect(DB_PATH)
cur = conn.cursor()
cur.execute("PRAGMA foreign_keys = ON;")

products_cache = {}

# 1) Insert parent products (rows where Type = 'variable')
print("Importing parent products...")

for _, row in df.iterrows():
    row_type = str(row.get("Type", "")).strip().lower()
    if row_type != "variable":
        continue

    name = (row.get("Name") or "").strip()
    product_type = (row.get("Categories") or "").strip()

    # Description handling
    desc_raw = row.get("Description")
    has_description = not is_blank(desc_raw)

    if has_description:
        # Use description from Excel
        description = str(desc_raw).strip()
        short_desc = description

        # Insert INCLUDING description column
        cur.execute(
            """
            INSERT INTO products (
                sku, name, category, description, thumbnail_url, colour,
                unit, price_per_unit, tile_length_mm, tile_width_mm,
                is_active, product_type, short_description
            )
            VALUES (
                NULL, ?, 'tile', ?, NULL, NULL,
                'unit', 0.00, NULL, NULL,
                1, ?, ?
            )
            """,
            (name, description, product_type, short_desc),
        )
    else:
        # No description in Excel:
        # omit 'description' so SQLite uses its DEFAULT lorem ipsum
        cur.execute(
            """
            INSERT INTO products (
                sku, name, category, thumbnail_url, colour,
                unit, price_per_unit, tile_length_mm, tile_width_mm,
                is_active, product_type
            )
            VALUES (
                NULL, ?, 'tile', NULL, NULL,
                'unit', 0.00, NULL, NULL,
                1, ?
            )
            """,
            (name, product_type),
        )

    product_id = cur.lastrowid
    products_cache[(name, product_type)] = product_id
    print(f"Inserted product: {name} ({product_type}) -> id={product_id}")

conn.commit()

# 2) Insert variants (rows where Type = 'variation')
print("Importing variants...")

current_product_id = None
current_parent_name = None
current_parent_type = None

for _, row in df.iterrows():
    row_type = str(row.get("Type") or "").strip().lower()

    if row_type == "variable":
        current_parent_name = (row.get("Name") or "").strip()
        current_parent_type = (row.get("Categories") or "").strip()
        current_product_id = products_cache.get(
            (current_parent_name, current_parent_type)
        )
        continue

    if row_type != "variation":
        continue

    if current_product_id is None:
        print("Skipping variant without parent (no variable row found).")
        continue

    sku = (row.get("SKU") or "").strip()

    # Prices with defaults
    price_retail = row.get("Price RETAIL")
    if is_blank(price_retail):
        price_retail = 1000.0

    price_trade = row.get("Price TRADE (-10%)")
    if is_blank(price_trade):
        price_trade = 6.7

    price_retail = float(price_retail)
    price_trade = float(price_trade)

    # Attribute helper
    def clean(v):
        if is_blank(v):
            return None
        return str(v).strip()

    colour = clean(row.get("Attribute 3 value(s)"))  # Colour
    finish = clean(row.get("Attribute 1 value(s)"))  # Drainage / finish
    polish = clean(row.get("Attribute 2 value(s)"))  # Polish / finish
    size_label = None  # no explicit size column in this sheet

    # Insert variant (no 'price' column, only price_retail & price_trade)
    cur.execute(
        """
        INSERT INTO product_variants (
            product_id, sku, colour, finish, polish, size_label,
            thickness_mm, width_mm, length_mm,
            price_retail, price_trade,
            image_url, is_active
        )
        VALUES (
            ?, ?, ?, ?, ?, ?,
            NULL, NULL, NULL,
            ?, ?,
            NULL, 1
        )
        """,
        (
            current_product_id,
            sku,
            colour,
            finish,
            polish,
            size_label,
            price_retail,
            price_trade,
        ),
    )

    print(f"    Variant: {sku} -> product_id={current_product_id}")

conn.commit()
conn.close()

print("\nImport complete!")
