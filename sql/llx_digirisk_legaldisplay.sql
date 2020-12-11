create table llx_digirisk_legaldisplay
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  ref                         varchar(128) NOT NULL,
  ref_ext                     varchar(255) default NULL,
  entity                      integer default 1,
  date_creation               datetime default NULL,
  tms                         timestamp,
  json                        text,
  import_key                  integer default NULL,
  status                      smallint,
  fk_user_creat               integer default NULL,
  fk_user_modif               integer default NULL,
  last_main_doc               varchar(255),
  model_pdf                   varchar(255),
  model_odt                   varchar(255),
  note_affich                 varchar(255)

)ENGINE=innodb;
