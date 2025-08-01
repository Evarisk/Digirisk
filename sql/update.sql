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

-- 9.8.1
UPDATE llx_c_email_templates SET content = '__(QHSEService)__<br>__MYCOMPANY_NAME__<br>\r\n__MYCOMPANY_FULLADDRESS__<br>\r\n__MYCOMPANY_EMAIL__' WHERE label = '(TicketCreationSubject)';

-- 9.11.0
UPDATE llx_digiriskdolibarr_risk SET category = 20 WHERE category = 18;
UPDATE llx_digiriskdolibarr_risk SET category = 21 WHERE category = 19;

-- 9.11.1
ALTER TABLE llx_digiriskdolibarr_firepermitdet CHANGE  `use_equipment` `used_equipment` text;
DELETE FROM llx_c_action_trigger WHERE elementtype = 'informationssharing@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'preventionplandocument@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'legaldisplay@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'groupmentdocument@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'workunitdocument@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'firepermitdocument@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'listingrisksphoto@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'riskassessmentdocument@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'listingrisksaction@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'digiriskelement@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'digirisksignature@digiriskdolibarr';
DELETE FROM llx_c_action_trigger WHERE elementtype = 'preventionplan@digiriskdolibarr';

-- 9.12.0
UPDATE llx_digiriskdolibarr_object_signature SET role = 'MasterWorker'          WHERE role = 'PP_MAITRE_OEUVRE';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'ExtSocietyResponsible' WHERE role = 'PP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'ExtSocietyAttendant'   WHERE role = 'PP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'MasterWorker'          WHERE role = 'FP_MAITRE_OEUVRE';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'ExtSocietyResponsible' WHERE role = 'FP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'ExtSocietyAttendant'   WHERE role = 'FP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_digiriskdolibarr_object_signature SET role = 'Responsible'           WHERE role = 'ACC_USER_EMPLOYER';

UPDATE `llx_digiriskdolibarr_object_signature` SET module_name = 'digiriskdolibarr';
INSERT INTO `llx_saturne_object_signature` (entity, date_creation, tms, import_key, status, role, firstname, lastname, email, phone, society_name, signature_date, signature_location, signature_comment, element_id, element_type, module_name, signature, stamp, last_email_sent_date, signature_url, transaction_url, object_type, fk_object)
SELECT entity, date_creation, tms, import_key, status, role, firstname, lastname, email, phone, society_name, signature_date, signature_location, signature_comment, element_id, element_type, module_name, signature, stamp, last_email_sent_date, signature_url, transaction_url, object_type, fk_object FROM `llx_digiriskdolibarr_object_signature`;
DROP TABLE `llx_digiriskdolibarr_object_signature`;

UPDATE llx_saturne_object_signature SET role = 'ExtSocietyResponsible' WHERE role = 'PP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_saturne_object_signature SET role = 'ExtSocietyAttendant'   WHERE role = 'PP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_saturne_object_signature SET role = 'MasterWorker'          WHERE role = 'PP_MAITRE_OEUVRE';
UPDATE llx_saturne_object_signature SET role = 'MasterWorker'          WHERE role = 'FP_MAITRE_OEUVRE';
UPDATE llx_saturne_object_signature SET role = 'ExtSocietyResponsible' WHERE role = 'FP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_saturne_object_signature SET role = 'ExtSocietyAttendant'   WHERE role = 'FP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_saturne_object_signature SET role = 'Responsible'           WHERE role = 'ACC_USER_EMPLOYER';

UPDATE llx_saturne_object_signature SET role = 'MasterWorker'          WHERE role = 'MaitreOeuvre';
UPDATE llx_saturne_object_signature SET role = 'ExtSocietyResponsible' WHERE role = 'ExternalResponsible';
UPDATE llx_saturne_object_signature SET role = 'ExtSocietyAttendant'   WHERE role = 'Attendant';

UPDATE FROM llx_c_firepermit_attendants_role SET role = 'MasterWorker' WHERE ref = "InternalResponsible";
UPDATE FROM llx_c_firepermit_attendants_role SET role = 'ExtSocietyAttendant' WHERE ref = "Attendant";

UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'MasterWorker'            WHERE ref = 'PP_MAITRE_OEUVRE';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSocietyResponsible'   WHERE ref = 'PP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSocietyAttendant'     WHERE ref = 'PP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'MasterWorker'            WHERE ref = 'FP_MAITRE_OEUVRE';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSocietyResponsible'   WHERE ref = 'FP_EXT_SOCIETY_RESPONSIBLE';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSocietyAttendant'     WHERE ref = 'FP_EXT_SOCIETY_INTERVENANTS';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'Responsible'             WHERE ref = 'ACC_USER_EMPLOYER';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSociety'              WHERE ref = 'PP_EXT_SOCIETY';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'LabourInspector'         WHERE ref = 'PP_LABOUR_INSPECTOR';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'LabourInspectorAssigned' WHERE ref = 'PP_LABOUR_INSPECTOR_ASSIGNED';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'ExtSociety'              WHERE ref = 'FP_EXT_SOCIETY';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'LabourInspector'         WHERE ref = 'FP_LABOUR_INSPECTOR';
UPDATE llx_digiriskdolibarr_digiriskresources SET ref = 'LabourInspectorAssigned' WHERE ref = 'FP_LABOUR_INSPECTOR_ASSIGNED';

ALTER TABLE llx_c_digiriskdolibarr_action_trigger DROP COLUMN `rang`;
ALTER TABLE llx_c_digiriskdolibarr_action_trigger ADD entity integer DEFAULT 1 AFTER rowid;
ALTER TABLE llx_c_digiriskdolibarr_action_trigger ADD active tinyint(4) DEFAULT 1 AFTER description;
ALTER TABLE llx_c_digiriskdolibarr_action_trigger ADD position integer DEFAULT 0 AFTER description;
ALTER TABLE llx_c_digiriskdolibarr_action_trigger CHANGE `code` `ref` varchar(128) NOT NULL;
ALTER TABLE `llx_digiriskdolibarr_object_signature` ADD module_name VARCHAR(128) NULL AFTER element_type;

-- 9.13.0

ALTER TABLE llx_digiriskdolibarr_accident CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_investigation CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_lesion CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_workstop CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_workstop_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_accident_metadata CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_digiriskelement CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_digiriskelement_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_firepermit CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_firepermit_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_firepermitdet CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_firepermitdet_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_preventionplan CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_preventionplan_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_preventionplandet CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_preventionplandet_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_risk CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_risk_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_riskassessment CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_riskassessment_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_risksign CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_risksign_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_digiriskresources CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_digiriskstandard CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_digiriskstandard_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_evaluator CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_digiriskdolibarr_evaluator_extrafields CHANGE `tms` `tms` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_digiriskdolibarr_accident_investigation ADD fk_project integer NULL AFTER fk_task;

ALTER TABLE llx_c_relative_location ADD position integer DEFAULT 0 AFTER active;
ALTER TABLE llx_c_lesion_localization ADD position integer DEFAULT 0 AFTER active;
ALTER TABLE llx_c_lesion_nature ADD position integer DEFAULT 0 AFTER active;

-- ALTER TABLE llx_digiriskdolibarr_preventionplan CHANGE `status` `status` INT(11) NOT NULL;
ALTER TABLE llx_digiriskdolibarr_preventionplan CHANGE `date_start` `date_start` DATETIME NULL;
ALTER TABLE llx_digiriskdolibarr_preventionplan CHANGE `fk_project` `fk_project` INT(11) NOT NULL;
ALTER TABLE llx_digiriskdolibarr_preventionplan DROP COLUMN `last_email_sent_date`;
ALTER TABLE llx_digiriskdolibarr_preventionplandet ADD status INTEGER NOT NULL AFTER tms;

-- ALTER TABLE llx_digiriskdolibarr_firepermit CHANGE `status` `status` INT(11) NOT NULL;
ALTER TABLE llx_digiriskdolibarr_firepermit CHANGE `date_start` `date_start` DATETIME NULL;
ALTER TABLE llx_digiriskdolibarr_firepermit CHANGE `fk_project` `fk_project` INT(11) NOT NULL;
ALTER TABLE llx_digiriskdolibarr_firepermit CHANGE `fk_preventionplan` `fk_preventionplan` INT(11) NOT NULL;
ALTER TABLE llx_digiriskdolibarr_firepermit DROP COLUMN `last_email_sent_date`;
ALTER TABLE llx_digiriskdolibarr_firepermitdet ADD status INTEGER NOT NULL AFTER tms;

ALTER TABLE llx_c_accident_investigation_attendants_role RENAME TO llx_c_accidentinvestigation_attendants_role;
ALTER TABLE llx_categorie_accident_investigation RENAME TO llx_categorie_accidentinvestigation;
ALTER TABLE llx_categorie_accidentinvestigation CHANGE `fk_accident_investigation` `fk_accidentinvestigation`;
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE elementtype = 'accident_investigation@digiriskdolibarr';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_WORKSTOP_CREATE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_WORKSTOP_MODIFY';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_WORKSTOP_DELETE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_LESION_CREATE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_LESION_MODIFY';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_LESION_DELETE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'PREVENTIONPLANSIGNATURE_ADDATTENDANT';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'FIREPERMITSIGNATURE_ADDATTENDANT';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'PREVENTIONPLAN_LOCKED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'PREVENTIONPLAN_ARCHIVED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'FIREPERMIT_LOCKED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'FIREPERMIT_ARCHIVED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'DIGIRISKSIGNATURE_SIGNED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'DIGIRISKSIGNATURE_PENDING_SIGNATURE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'DIGIRISKSIGNATURE_ABSENT';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'DIGIRISKSIGNATURE_DELETED';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'OPENINGHOURS_CREATE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_INVESTIGATION_VALIDATE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_INVESTIGATION_UNVALIDATE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_INVESTIGATION_ARCHIVE';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'ACCIDENT_INVESTIGATION_LOCK';

UPDATE `llx_const` SET `value` = 'mod_accidentinvestigation_standard' WHERE `llx_const`.`name` = 'DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_ADDON';
UPDATE `llx_const` SET `value` = 'mod_accidentinvestigationdocument_standard' WHERE `llx_const`.`name` = 'DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON';
INSERT INTO llx_c_accidentinvestigation_attendants_role SELECT * FROM llx_c_accident_investigation_attendants_role;
INSERT INTO llx_categorie_accidentinvestigation SELECT * FROM llx_categorie_accident_investigation;
DROP TABLE llx_c_accident_investigation_attendants_role;
DROP TABLE llx_categorie_accident_investigation;

-- 9.14.0
UPDATE llx_digiriskdolibarr_risk SET category = 21 WHERE category = 20;
UPDATE llx_digiriskdolibarr_risk SET category = 22 WHERE category = 21;
ALTER TABLE llx_digiriskdolibarr_accident ADD fk_ticket integer;
ALTER TABLE llx_digiriskdolibarr_accident DROP COLUMN fk_user_victim;

-- 9.14.1
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'FIREPERMIT_INPROGRESS';
DELETE FROM llx_c_digiriskdolibarr_action_trigger WHERE ref = 'PREVENTIONPLAN_INPROGRESS';

-- 9.15.0
-- Check if ref are duplicate and delete them
DELETE t1 FROM llx_c_digiriskdolibarr_action_trigger t1 JOIN llx_c_digiriskdolibarr_action_trigger t2 WHERE t1.rowid > t2.rowid AND t1.ref = t2.ref;

-- 10.0.0
ALTER TABLE `llx_digiriskdolibarr_risk` ADD `type` VARCHAR(255) NOT NULL DEFAULT 'risk' AFTER `description`;

-- 10.1.1
UPDATE llx_element_element SET targettype = 'digiriskdolibarr_digiriskelement' WHERE targettype = 'digiriskdolibarr_digiriskdolibar' AND sourcetype = 'digiriskdolibarr_risksign';

-- 21.0
ALTER TABLE llx_digiriskdolibarr_risk ADD `sub_category` VARCHAR(255) NULL DEFAULT NULL AFTER `category`;
