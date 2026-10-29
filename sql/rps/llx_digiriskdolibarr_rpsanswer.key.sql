-- Copyright (C) 2026 EVARISK <technique@evarisk.com>

ALTER TABLE llx_digiriskdolibarr_rpsanswer ADD INDEX idx_rpsanswer_fk_riskassessment (fk_riskassessment);
ALTER TABLE llx_digiriskdolibarr_rpsanswer ADD UNIQUE INDEX uk_rpsanswer_assessment_criterion (fk_riskassessment, criterion);
