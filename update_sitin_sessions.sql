-- Add feedback and rating columns to sitin_sessions table
ALTER TABLE sitin_sessions
ADD COLUMN feedback TEXT NULL,
ADD COLUMN rating INT NULL CHECK (rating BETWEEN 1 AND 5); 