-- Copyright (C) 2026 EVARISK <technique@evarisk.com>

ALTER TABLE llx_digiriskdolibarr_rpsscope ADD UNIQUE INDEX uk_rpsscope_element (fk_element, entity);
