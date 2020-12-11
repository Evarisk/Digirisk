ALTER TABLE llx_digirisk_resources ADD INDEX idx_digirisk_resources_fk_user_creat (fk_user_creat);

ALTER TABLE llx_digirisk_resources ADD CONSTRAINT fk_digirisk_resources_fk_user_creat (fk_user_creat) FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
