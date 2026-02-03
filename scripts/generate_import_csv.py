import csv
import sys

# Output file
output_file = 'wc-product-import-kayarine-v2.csv'

# Mapping: Old ID (from Export) -> Target ID (for Config/Import)
# If Target ID is None, we keep the Old ID (update existing).
# If Target ID is set, we try to create/update that specific ID.
id_mapping = {
    '81': 101,   # Single Kayak -> Config ID 101
    '82': 102,   # Double Kayak -> Config ID 102
    '84': 106,   # SUP -> Config ID 106
    # Tours: Keep original IDs to update the existing products to 'Simple'
    '198': 198,
    '221': 221,
    '225': 225,
    '233': 233,
    '251': 251,
    '5308': 5308,
    '5309': 5309, # Family Kayak
    '5325': 5325,
    '5824': 5824
}

# New Add-ons (Hardcoded IDs from Config)
addons = [
    {
        'ID': 104,
        'Type': 'simple',
        'SKU': 'addon-snorkel',
        'Name': '浮潛面罩 (Snorkel Mask)',
        'Published': 1,
        'Visibility in catalog': 'visible',
        'Short description': '加購浮潛面罩。',
        'Description': '租借浮潛面罩，探索水下世界。',
        'Tax status': 'taxable',
        'In stock?': 1,
        'Sold individually?': 0,
        'Regular price': 30,
        'Categories': '加購項目',
        'Images': 'https://kayarine.club/wp-content/uploads/2025/05/浮潛front-pic.jpg'
    },
    {
        'ID': 105,
        'Type': 'simple',
        'SKU': 'addon-phone',
        'Name': '手機防水袋 (Phone Case)',
        'Published': 1,
        'Visibility in catalog': 'visible',
        'Short description': '加購手機防水袋。',
        'Description': '保護您的手機，安心享受水上活動。',
        'Tax status': 'taxable',
        'In stock?': 1,
        'Sold individually?': 0,
        'Regular price': 50,
        'Categories': '加購項目',
        'Images': '' 
    }
]

# Columns to keep/map
fieldnames = ['ID', 'Type', 'SKU', 'Name', 'Published', 'Is featured?', 'Visibility in catalog', 
              'Short description', 'Description', 'Tax status', 'In stock?', 'Stock', 
              'Low stock amount', 'Backorders allowed?', 'Sold individually?', 
              'Regular price', 'Categories', 'Images']

# Known Prices mapping (Fallback if export is empty)
price_map = {
    '101': 100,
    '102': 200,
    '106': 150,
    '198': 680,
    '221': 550,
    '225': 500,
    '233': 600,
    '251': 600,
    '5308': 700,
    '5309': 250,
    '5325': 900,
    '5824': 700
}

print("Starting CSV generation...")

try:
    with open('wc-product-export.csv', newline='', encoding='utf-8-sig') as csvfile:
        reader = csv.DictReader(csvfile)
        
        with open(output_file, 'w', newline='', encoding='utf-8') as outfile:
            writer = csv.DictWriter(outfile, fieldnames=fieldnames)
            writer.writeheader()
            
            # Process existing products from export
            for row in reader:
                old_id = row['ID']
                
                if old_id in id_mapping:
                    target_id = id_mapping[old_id]
                    
                    # Create new row
                    new_row = {}
                    
                    # Set ID
                    new_row['ID'] = target_id
                    
                    # Force Type to simple
                    new_row['Type'] = 'simple'
                    
                    # Copy content
                    for field in fieldnames:
                        if field in row:
                            new_row[field] = row[field]
                    
                    # Ensure ID and Type are set correctly (overwrite copy)
                    new_row['ID'] = target_id
                    new_row['Type'] = 'simple'
                    
                    # Fix Price
                    if str(target_id) in price_map:
                         new_row['Regular price'] = price_map[str(target_id)]
                    elif not new_row.get('Regular price'):
                         new_row['Regular price'] = 0 # Safety fallback
                         
                    writer.writerow(new_row)
                    print(f"Mapped {old_id} -> {target_id}: {new_row['Name']}")

            # Add Add-ons
            for addon in addons:
                # Fill missing
                for field in fieldnames:
                    if field not in addon:
                        addon[field] = ''
                writer.writerow(addon)
                print(f"Added Add-on {addon['ID']}: {addon['Name']}")

    print(f"Successfully created {output_file}")

except Exception as e:
    print(f"Error: {e}")
