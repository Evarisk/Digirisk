ALTER TABLE llx_digirisk_legaldisplay ADD UNIQUE INDEX uk_digirisk_legaldisplay_ref (ref, entity);

ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_user_creat (fk_user_creat);
ALTER TABLE llx_digirisk_legaldisplay ADD INDEX idx_digirisk_legaldisplay_fk_user_modif (fk_user_modif);

ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_user_creat (fk_user_creat)                              FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
ALTER TABLE llx_digirisk_legaldisplay ADD CONSTRAINT fk_digirisk_legaldisplay_fk_user_modif (fk_user_modif)                              FOREIGN KEY (fk_user_modif) REFERENCES llx_user (rowid);

