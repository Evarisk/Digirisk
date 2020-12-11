ALTER TABLE llx_digirisk_documents ADD UNIQUE INDEX uk_digirisk_documents_ref (ref, entity);

ALTER TABLE llx_digirisk_documents ADD INDEX idx_digirisk_documents_fk_user_creat (fk_user_creat);

ALTER TABLE llx_digirisk_documents ADD CONSTRAINT fk_digirisk_documents_fk_user_creat (fk_user_creat) FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);

