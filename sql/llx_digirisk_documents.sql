create table llx_digirisk_documents
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  ref               varchar(128) NOT NULL,
  ref_ext           varchar(255) default NULL,
  entity            integer default 1,
  date_creation     datetime default NULL,
  tms               timestamp,
  json              text,
  import_key        integer default NULL,
  status            smallint,
  fk_user_creat     integer default NULL,
  last_main_doc     varchar(255),
  model_pdf         varchar(255),
  model_odt         varchar(255),
  type              varchar(50)
)ENGINE=innodb;
