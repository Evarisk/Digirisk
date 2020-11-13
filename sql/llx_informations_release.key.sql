ALTER TABLE llx_informations_release ADD UNIQUE INDEX uk_informationsrelease_ref (ref, entity);

ALTER TABLE llx_informations_release ADD INDEX idx_informationsrelease_fk_soc_labour_doctor (fk_soc_labour_doctor);

ALTER TABLE llx_informations_release ADD CONSTRAINT fk_informationsrelease_fk_soc_labour_doctor (fk_soc_labour_doctor)                FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
