-- Copyright (C) 2026 EVARISK <technique@evarisk.com>

ALTER TABLE llx_digiriskdolibarr_rpssession_element ADD UNIQUE INDEX uk_rpssession_element (fk_session, fk_element);
ALTER TABLE llx_digiriskdolibarr_rpssession_element ADD INDEX idx_rpssession_element_fk_element (fk_element);
