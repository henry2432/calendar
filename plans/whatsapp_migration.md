# Migration to WhatsApp Cloud API

## Overview
Replacing the third-party Wati provider with the direct **WhatsApp Cloud API (Meta)**. This reduces costs and provides direct control.

## Prerequisites
The user needs to provide:
1.  **Meta App ID**
2.  **App Secret**
3.  **Phone Number ID** (From WhatsApp Manager)
4.  **Permanent Access Token** (System User Token)
5.  **WABA ID** (WhatsApp Business Account ID)

## Architecture Changes

### 1. `config.py`
Replace Wati credentials with:
```python
WHATSAPP_API_URL = "https://graph.facebook.com/v18.0"
WHATSAPP_PHONE_ID = "..."
WHATSAPP_ACCESS_TOKEN = "..."
```

### 2. `whatsapp_handler.py` (New)
Replaces `wati_manager.py`.
*   **Method**: `send_template_message(to, template_name, components)`
    *   **Structure**: Meta API uses `components` (header, body) instead of flat parameters.
    *   **Logic**: Needs to map the old simple `{"name": "value"}` list to Meta's specific component structure.

#### Mapping Logic
*   **Wati**: `[{name: "name", value: "John"}]`
*   **Meta**:
    ```json
    {
      "type": "body",
      "parameters": [
        { "type": "text", "text": "John" }
      ]
    }
    ```
    *   *Challenge*: Meta templates rely on **positional** parameters (`{{1}}`, `{{2}}`) rather than named variables.
    *   *Solution*: The user must ensure their `Campaigns` setup respects the order of variables in the template. OR we map names if we know the template structure (hard to do dynamically).
    *   *Compromise*: We will assume standard templates used in `campaign_worker.py` follow a fixed order: `[Name, CouponCode, Discount]`.

### 3. Workflow Updates
*   **`campaign_worker.py`**: Update `run_worker` to instantiate `WhatsAppHandler` instead of `WatiManager`.
*   **`app.py`**: Update `process_order` notifications to use `WhatsAppHandler`.

## New Campaign Workflow (No logic change, just backend)
1.  User sets `Status` -> `Generating`.
2.  Worker creates coupons.
3.  User pastes numbers.
4.  User sets `Status` -> `Ready to Send`.
5.  Worker uses **WhatsApp API** to send.

---

## Todo List
- [ ] **Config**: Add Meta credentials.
- [ ] **Code**: Create `whatsapp_handler.py`.
- [ ] **Refactor**: Update `campaign_worker.py` and `app.py` to swtich classes.
