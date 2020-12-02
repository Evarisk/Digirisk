create table llx_digirisk_const
(
  rowid                         integer AUTO_INCREMENT PRIMARY KEY,
  name                          varchar(128) NOT NULL,
  entity                        integer default 1,
  value                         text,
  type                          varchar(64),
  visible                       tinyint(4),
  note                          text,
  tms                           timestamp

)ENGINE=innodb;
