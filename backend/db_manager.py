import sqlite3
import logging
from datetime import datetime
import json

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

DB_NAME = "chat_history.db"

class DBManager:
    def __init__(self, db_path=DB_NAME):
        self.db_path = db_path
        self._init_db()

    def _init_db(self):
        """Initializes the SQLite database with necessary tables."""
        try:
            conn = sqlite3.connect(self.db_path)
            c = conn.cursor()
            
            # Messages Table
            # id, phone (remote user), direction (inbound/outbound), type (text/image), content, media_url, timestamp, status
            c.execute('''
                CREATE TABLE IF NOT EXISTS messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    phone TEXT NOT NULL,
                    direction TEXT NOT NULL, 
                    msg_type TEXT DEFAULT 'text',
                    content TEXT,
                    media_url TEXT,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    status TEXT DEFAULT 'received'
                )
            ''')
            
            # Contacts Table (Synced from Sheets roughly, or just last seen)
            c.execute('''
                CREATE TABLE IF NOT EXISTS contacts (
                    phone TEXT PRIMARY KEY,
                    name TEXT,
                    last_seen DATETIME,
                    tags TEXT
                )
            ''')
            
            conn.commit()
            conn.close()
            logger.info("Database initialized.")
        except Exception as e:
            logger.error(f"DB Init Error: {e}")

    def save_message(self, phone, direction, content, msg_type="text", media_url=None):
        """Saves a message to the history."""
        try:
            conn = sqlite3.connect(self.db_path)
            c = conn.cursor()
            c.execute('''
                INSERT INTO messages (phone, direction, msg_type, content, media_url, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ''', (phone, direction, msg_type, content, media_url, 'saved'))
            
            # Update Last Seen
            c.execute('''
                INSERT INTO contacts (phone, last_seen) VALUES (?, CURRENT_TIMESTAMP)
                ON CONFLICT(phone) DO UPDATE SET last_seen=CURRENT_TIMESTAMP
            ''', (phone,))
            
            conn.commit()
            conn.close()
            return True
        except Exception as e:
            logger.error(f"Error saving message: {e}")
            return False

    def get_chats(self):
        """
        Returns a list of unique chats with the last message preview.
        """
        try:
            conn = sqlite3.connect(self.db_path)
            conn.row_factory = sqlite3.Row
            c = conn.cursor()
            
            # Get unique phones and their last message
            query = '''
                SELECT m.phone, c.name, m.content, m.timestamp, m.msg_type
                FROM messages m
                LEFT JOIN contacts c ON m.phone = c.phone
                WHERE m.id IN (
                    SELECT MAX(id) FROM messages GROUP BY phone
                )
                ORDER BY m.timestamp DESC
            '''
            c.execute(query)
            rows = c.fetchall()
            conn.close()
            
            return [dict(row) for row in rows]
        except Exception as e:
            logger.error(f"Error fetching chats: {e}")
            return []

    def get_messages(self, phone):
        """
        Returns full history for a specific phone number.
        """
        try:
            conn = sqlite3.connect(self.db_path)
            conn.row_factory = sqlite3.Row
            c = conn.cursor()
            
            c.execute('SELECT * FROM messages WHERE phone = ? ORDER BY timestamp ASC', (phone,))
            rows = c.fetchall()
            conn.close()
            
            return [dict(row) for row in rows]
        except Exception as e:
            logger.error(f"Error fetching messages for {phone}: {e}")
            return []

if __name__ == "__main__":
    # Test
    db = DBManager()
    db.save_message("85212345678", "inbound", "Hello world")
    print(db.get_chats())
