ALTER TABLE llx_digirisk_legaldisplay ADD UNIQUE INDEX uk_digirisk_legaldisplay_ref (ref, entity);

ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_socpeople_labour_doctor (fk_socpeople_labour_doctor);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_socpeople_labour_inspector (fk_socpeople_labour_inspector);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_samu (fk_soc_samu);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_pompiers (fk_soc_pompiers);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_police (fk_soc_police);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_projet (fk_soc_urgency);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_rights_defender (fk_soc_rights_defender);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_antipoison (fk_soc_antipoison);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_soc_responsible_prevent (fk_soc_responsible_prevent);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_user_creat (fk_user_creat);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_user_modif (fk_user_modif);

ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_socpeople_labour_doctor (fk_socpeople_labour_doctor)                FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_socpeople_labour_inspector (fk_socpeople_labour_inspector)          FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_samu (fk_soc_samu)                                  FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_pompiers (fk_soc_pompiers)                                  FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_police (fk_soc_police)                              FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_projet (fk_soc_urgency)                                 FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_rights_defender (fk_soc_rights_defender)            FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_antipoison (fk_soc_antipoison)                      FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_soc_responsible_prevent (fk_soc_responsible_prevent)    FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_user_creat (fk_user_creat)                              FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_user_modif (fk_user_modif)                              FOREIGN KEY (fk_user_modif) REFERENCES llx_user (rowid);

