-- Copyright (C) 2026 EVARISK <technique@evarisk.com>

ALTER TABLE llx_digiriskdolibarr_rpsresponse ADD INDEX idx_rpsresponse_session_element (fk_session, fk_element);
