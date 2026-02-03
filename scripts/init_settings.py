from sheet_manager import SheetManager
import logging

# Setup Logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def force_init_settings():
    print(">>> Connecting to Google Sheets...")
    try:
        sm = SheetManager()
        if not sm.sheet:
            print("❌ Error: Could not connect to Google Sheets. Check credentials.json")
            return

        print(f">>> Connected to Sheet: {sm.sheet.title}")
        
        # Check existing worksheets
        current_worksheets = {ws.title: ws for ws in sm.sheet.worksheets()}
        
        if "Settings" in current_worksheets:
            print(">>> 'Settings' sheet already exists.")
            ws = current_worksheets["Settings"]
            existing_data = ws.get_all_values()
            if not existing_data:
                print(">>> 'Settings' sheet is empty. Populating defaults...")
            else:
                print(">>> 'Settings' sheet has data. Skipping overwrite.")
                print("Current Data:", existing_data)
                return
        else:
            print(">>> Creating 'Settings' sheet...")
            ws = sm.sheet.add_worksheet(title="Settings", rows=20, cols=5)
            # Add Headers
            ws.append_row(["Key", "Value", "Description"])
        
        # Default Data
        defaults = [
            ["Single Kayak Max", "50", "Total fleet size for Single Kayak"],
            ["Double Kayak Max", "20", "Total fleet size for Double Kayak"],
            ["Family Kayak Max", "10", "Total fleet size for Family Kayak"],
            ["SUP Max", "20", "Total fleet size for SUP"],
            ["Snorkel Mask Max", "50", "Inventory for masks"],
            ["Phone Case Max", "50", "Inventory for phone cases"]
        ]
        
        print(">>> Writing default values...")
        for row in defaults:
            # Check if key exists to avoid duplicates if partially populated
            # Simple append for this script
            ws.append_row(row)
            
        print("✅ Success! 'Settings' sheet created/updated.")
        print("You should see it in your Google Sheet now.")

    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    force_init_settings()
