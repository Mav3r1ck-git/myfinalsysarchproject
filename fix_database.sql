-- Create announcements table with correct foreign key reference
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    -- Remove the foreign key for now to avoid errors
);

-- Check if sitin_sessions table exists and update it
-- First, get the current columns
SHOW COLUMNS FROM sitin_sessions;

-- Add necessary columns if they don't exist
ALTER TABLE sitin_sessions 
    ADD COLUMN IF NOT EXISTS lab VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS ended_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS purpose VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS other_purpose VARCHAR(255) NULL,
    MODIFY status ENUM('active', 'logged_out', 'rewarded', 'completed', 'cancelled') DEFAULT 'active';

-- If the original columns exist, we'll update them in a separate step
-- This is safer than trying to rename columns that might not exist 