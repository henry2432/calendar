# Private WhatsApp CRM Design Plan

## 1. Analysis
*   **Goal**: Replace Wati with a self-hosted "Inbox" on GCP.
*   **VM Capacity**: Current VM (`wordpress-2025-vm`) is likely an `e2-micro` or `e2-small`.
    *   *Flask App*: Lightweight.
    *   *Worker*: Lightweight.
    *   *Chat UI*: Lightweight (serving static HTML/JS).
    *   *Database*: SQLite is file-based, very light.
    *   **Verdict**: **Affordable & Feasible**. The current VM can handle this easily if we manage media correctly (offload to Drive).

## 2. Architecture

### A. Data Storage
*   **Messages**: `chat_history.db` (SQLite).
    *   Tables: `messages` (id, phone, direction, type, content, media_url, timestamp, status).
*   **Media**: Google Drive.
    *   Incoming images -> Upload to specific Drive Folder -> Save public link in DB.
*   **Customers**: Continue using Google Sheets (`Customer List`) as the master record. Sync basic info to DB for fast lookup.

### B. Backend (`app.py` & `chat_handler.py`)
*   **Webhook**:
    *   Endpoint: `/webhook/whatsapp` (Meta will POST here).
    *   Action: Parse message -> Save to DB -> Emit to UI (via simple polling or SSE/SocketIO). *Polling is easier for now.*
*   **API**:
    *   `GET /api/chats`: List of active conversations (grouped by phone).
    *   `GET /api/messages?phone=...`: History for a specific user.
    *   `POST /api/send`: Send reply via `whatsapp_handler.py`.

### C. Frontend (The "Inbox")
*   **Stack**: Simple HTML + Vanilla JS + Tailwind CSS (Single Page App).
*   **Features**:
    *   Sidebar: List of chats (Phone + Name from Sheet + Last Msg).
    *   Main Window: Chat bubble view.
    *   Input: Text box + Image Upload button.
    *   Multi-agent: It's just a web page. Multiple staff can open it. (State sync via polling).

## 3. Google Drive Integration
*   Need to enable **Google Drive API** in GCP Console.
*   Update `SheetManager` or create `DriveManager` to handle file uploads.
*   Authentication: Can reuse the same `credentials.json` service account if we give it Drive permissions.

## 4. Implementation Steps

1.  **Backend Setup**:
    *   Create `db_manager.py` (SQLite handling).
    *   Create `drive_manager.py` (Media handling).
    *   Update `app.py` with new routes.
2.  **Frontend Creation**:
    *   Create `templates/inbox.html`.
    *   Create `static/js/inbox.js`.
3.  **Webhook Connection**:
    *   Configure Meta Webhook to point to `https://kayarine.com/webhook/whatsapp`.
    *   Verify token logic.

## 5. Feasibility Check
*   **Hard?**: The "Inbox" UI is the biggest work. Replicating Wati's polish is hard, but a functional chat interface is moderate (~1-2 days work).
*   **Cost**: $0 extra (uses existing VM).
*   **Media**: Free (15GB Drive) or cheap storage.

---

## Todo List
- [ ] **Config**: Enable Drive API.
- [ ] **Code**: `db_manager.py` setup.
- [ ] **Code**: `app.py` webhook receiver.
- [ ] **Code**: `inbox.html` UI.
