create table llx_digirisk_links
(
  rowid                         integer AUTO_INCREMENT PRIMARY KEY,
  ref                           varchar(128) NOT NULL,
  entity                        integer default 1,
  date_creation                 datetime default NULL,
  tms                           timestamp,
  fk_user_author                integer default NULL,
  fk_soc                        integer default NULL,
  fk_contact                   integer default NULL

)ENGINE=innodb;
