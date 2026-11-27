-- Copyright (C) 2026 EVARISK <technique@evarisk.com>

ALTER TABLE llx_digiriskdolibarr_rpssession ADD INDEX idx_rpssession_entity (entity);
ALTER TABLE llx_digiriskdolibarr_rpssession ADD UNIQUE INDEX uk_rpssession_public_key (public_key);
