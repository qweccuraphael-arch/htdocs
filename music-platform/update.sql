ALTER TABLE earnings
ADD COLUMN beneficiary_type ENUM('artist','admin') NOT NULL DEFAULT 'artist' AFTER amount;

CREATE INDEX idx_beneficiary ON earnings (beneficiary_type, created_at);
