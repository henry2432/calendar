# Copilot Instructions for Calendar Project

## Overview
This project manages bookings for various water sports, integrating with Google Sheets and WooCommerce for order management. The architecture is centered around handling orders, updating availability, and managing customer interactions through a series of Python scripts.

## Architecture
- **Main Components**: The project consists of several key scripts:
  - `tour.py`: Handles fetching and processing new orders from WooCommerce.
  - `archive_orders.py`: Manages archiving and updating existing orders in Google Sheets.
  - `availability.py`: Updates and manages equipment availability based on bookings.

- **Data Flow**: Orders are fetched from the WooCommerce API, processed, and then written to Google Sheets for record-keeping and availability management. The scripts communicate with Google Sheets using the `gspread` library.

## Developer Workflows
- **Fetching New Orders**: Use the `fetch_new_orders()` function in `tour.py` to retrieve orders from WooCommerce. Ensure the API credentials are set in the environment variables.
- **Updating Availability**: The `update_availability()` function in `availability.py` should be called after processing orders to reflect current equipment availability.
- **Archiving Orders**: Use `archive_orders.py` to move completed orders to an archive sheet, ensuring that only active orders are displayed in the main sheet.

## Project-Specific Conventions
- **Order Statuses**: Only certain statuses are valid for processing (`completed`, `processing`, `on-hold`). Ensure to check these before processing orders.
- **Google Sheets Structure**: The expected headers for Google Sheets are defined in each script. Ensure that any updates to the headers are consistent across all scripts.

## Integration Points
- **WooCommerce API**: The project relies on the WooCommerce REST API for order management. Ensure that the API keys are correctly set in the environment.
- **Google Sheets**: The project uses Google Sheets for data storage. Ensure that the correct sheet IDs and names are used in the scripts.

## External Dependencies
- **Python Libraries**: The project requires the following libraries:
  - `gspread`
  - `oauth2client`
  - `httpx`
  - `requests`
  - `pytz`

Install these using the command:
```
pip install -r requirements.txt
```

## Examples
- **Fetching Orders**: To fetch new orders, call:
```python
new_orders = fetch_new_orders()
```
- **Updating Sheets**: To update the Google Sheets with new orders:
```python
sheet.append_row(new_order_data)
```

## Conclusion
This document serves as a guide for AI agents to understand the structure and workflows of the Calendar project. For any further clarifications, refer to the specific script documentation or the project README.