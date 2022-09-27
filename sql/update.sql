UPDATE llx_c_action_trigger SET elementtype = 'informationssharing@digiriskdolibarr'    WHERE elementtype = 'informationssharing';
UPDATE llx_c_action_trigger SET elementtype = 'legaldisplay@digiriskdolibarr'           WHERE elementtype = 'legaldisplay';
UPDATE llx_c_action_trigger SET elementtype = 'preventionplandocument@digiriskdolibarr' WHERE elementtype = 'preventionplandocument';
UPDATE llx_c_action_trigger SET elementtype = 'firepermitdocument@digiriskdolibarr'     WHERE elementtype = 'firepermitdocument';
UPDATE llx_c_action_trigger SET elementtype = 'groupmentdocument@digiriskdolibarr'      WHERE elementtype = 'groupmentdocument';
UPDATE llx_c_action_trigger SET elementtype = 'workunitdocument@digiriskdolibarr'       WHERE elementtype = 'workunitdocument';
UPDATE llx_c_action_trigger SET elementtype = 'listingrisksphoto@digiriskdolibarr'      WHERE elementtype = 'listingrisksphoto';
UPDATE llx_c_action_trigger SET elementtype = 'listingrisksaction@digiriskdolibarr'     WHERE elementtype = 'listingrisksaction';
UPDATE llx_c_action_trigger SET elementtype = 'riskassessmentdocument@digiriskdolibarr' WHERE elementtype = 'riskassessmentdocument';

UPDATE llx_extrafields SET type='sellist', param = 'a:1:{s:7:"options";a:1:{s:25:"digiriskdolibarr_risk:ref";N;}}', list='1' WHERE label='fk_risk';

-- 8.1.0
UPDATE `llx_digiriskdolibarr_digiriskelement` SET `status`= 1 WHERE `status` IS NULL;
UPDATE `llx_digiriskdolibarr_risksign` SET `status`= 1 WHERE `status` IS NULL;
UPDATE `llx_digiriskdolibarr_evaluator` SET `status`= 1 WHERE `status` IS NULL;
UPDATE `llx_digiriskdolibarr_risk` SET `status`= 1 WHERE `status` IS NULL;

-- 8.1.1
ALTER TABLE llx_digiriskdolibarr_preventionplan ADD prior_visit_date datetime;
ALTER TABLE llx_digiriskdolibarr_preventionplan ADD fk_project integer;

-- 8.2.0
ALTER TABLE `llx_digiriskdolibarr_digiriskelement` CHANGE `status` `status` SMALLINT(6) NULL DEFAULT '1';
ALTER TABLE `llx_digiriskdolibarr_risksign` CHANGE `status` `status` SMALLINT(6) NULL DEFAULT '1';
ALTER TABLE `llx_digiriskdolibarr_evaluator` CHANGE `status` `status` SMALLINT(6) NULL DEFAULT '1';
ALTER TABLE `llx_digiriskdolibarr_risk` CHANGE `status` `status` SMALLINT(6) NULL DEFAULT '1';

-- 8.2.1
ALTER TABLE llx_digiriskdolibarr_riskassessment ADD date_riskassessment datetime;
ALTER TABLE llx_digiriskdolibarr_digiriskelement ADD photo varchar(255);
ALTER TABLE llx_digiriskdolibarr_preventionplan_signature ADD stamp varchar(255);
ALTER TABLE llx_digiriskdolibarr_preventionplan ADD last_email_sent_date datetime;

-- 8.2.2
ALTER TABLE llx_digiriskdolibarr_digiriskelement ADD `rank` integer;

-- 8.3.0
ALTER TABLE llx_digiriskdolibarr_firepermit ADD last_email_sent_date datetime;
ALTER TABLE llx_digiriskdolibarr_firepermit ADD fk_project integer;

-- 8.5.0

DROP TABLE llx_digiriskdolibarr_preventionplan_signature;
DROP TABLE llx_digiriskdolibarr_firepermit_signature;

-- 9.0.0
ALTER TABLE llx_digiriskdolibarr_digiriskelement CHANGE rank `rank` integer;

-- 9.0.1
ALTER TABLE llx_digiriskdolibarr_accident ADD accident_location text;
ALTER TABLE llx_digiriskdolibarr_accident CHANGE `external_accident` `external_accident` SMALLINT(6) NULL DEFAULT '1';
-- ALTER TABLE llx_digiriskdolibarr_digiriskelement_extrafields ADD entity integer DEFAULT 1 NOT NULL;

-- 9.2.0
ALTER TABLE llx_digiriskdolibarr_digiriskelement CHANGE `rank` `ranks` INT(11) NULL DEFAULT NULL;

-- 9.3.0
ALTER TABLE llx_digiriskdolibarr_digiriskelement DROP COLUMN `rank`;
ALTER TABLE llx_digiriskdolibarr_accident ADD fk_standard integer AFTER fk_element;
ALTER TABLE llx_digiriskdolibarr_digiriskelement ADD UNIQUE uk_digiriskelement_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_risk ADD UNIQUE uk_risk_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_riskassessment ADD UNIQUE uk_riskassessment_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_risksign ADD UNIQUE uk_risksign_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_evaluator ADD UNIQUE uk_evaluator_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_accident ADD UNIQUE uk_accident_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_accident_lesion ADD UNIQUE uk_accident_lesion_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_accident_workstop ADD UNIQUE uk_accident_workstop_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_preventionplan ADD UNIQUE uk_preventionplan_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_preventionplandet ADD UNIQUE uk_preventionplandet_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_firepermit ADD UNIQUE uk_firepermit_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_firepermitdet ADD UNIQUE uk_firepermitdet_ref (ref, entity);
ALTER TABLE llx_digiriskdolibarr_digiriskdocuments ADD UNIQUE uk_digiriskdocuments_ref (ref, entity);

-- 9.4.0
ALTER TABLE llx_digiriskdolibarr_accident_workstop ADD date_start_workstop DATETIME NULL AFTER workstop_days;

-- 9.5.0
ALTER TABLE llx_digiriskdolibarr_accident_workstop ADD date_end_workstop DATETIME NULL AFTER date_start_workstop;

-- 9.5.1
ALTER TABLE llx_digiriskdolibarr_object_signature CHANGE `signature` `signature` LONGTEXT;

-- 9.6.0
ALTER TABLE llx_digiriskdolibarr_evaluator ADD job VARCHAR(80) AFTER assignment_date;
ALTER TABLE llx_digiriskdolibarr_digiriskelement ADD show_in_selector BOOLEAN NOT NULL DEFAULT TRUE AFTER photo;
ALTER TABLE llx_digiriskdolibarr_accident_workstop ADD declaration_link text AFTER date_end_workstop;
