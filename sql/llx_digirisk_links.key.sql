ALTER TABLE llx_digirisk_links ADD UNIQUE INDEX uk_digirisklinks_ref (ref, entity);

ALTER TABLE llx_digirisk_links ADD INDEX idx_digirisk_links_fk_user_author (fk_user_author);
ALTER TABLE llx_digirisk_links ADD INDEX idx_digirisk_links_fk_soc (fk_soc);
ALTER TABLE llx_digirisk_links ADD INDEX idx_digirisk_links_fk_contact (fk_contact);
ALTER TABLE llx_digirisk_links ADD INDEX idx_digirisk_links_fk_user (fk_user);

ALTER TABLE llx_digirisk_links ADD CONSTRAINT fk_digirisk_links_fk_user_author (fk_user_author) FOREIGN KEY (fk_user)       REFERENCES llx_user (rowid);
ALTER TABLE llx_digirisk_links ADD CONSTRAINT fk_digirisk_links_fk_soc (fk_soc)                 FOREIGN KEY (fk_soc)        REFERENCES llx_societe (rowid);
ALTER TABLE llx_digirisk_links ADD CONSTRAINT fk_digirisk_links_fk_contact (fk_contact)       FOREIGN KEY (fk_contact)   REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_digirisk_links ADD CONSTRAINT fk_digirisk_links_fk_contact (fk_user)       FOREIGN KEY (fk_user)   REFERENCES llx_user (rowid);
