-- Add status column to receipts table if it doesn't exist
ALTER TABLE receipts ADD COLUMN status ENUM('completed', 'saved') DEFAULT 'completed';
