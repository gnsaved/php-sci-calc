-- Calculator history schema
-- Run: sqlite3 database/calculator.db < database/schema.sql

CREATE TABLE IF NOT EXISTS calculations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expression TEXT NOT NULL,
    result TEXT NOT NULL,
    mode TEXT DEFAULT 'basic',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_created ON calculations(created_at DESC);
CREATE INDEX idx_mode ON calculations(mode);
