create table llx_digirisk_resources
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  ref                varchar(128) NOT NULL,
  entity             integer default 1,
  date_creation      datetime default NULL,
  tms                timestamp,
  status             smallint,
  element_type       varchar(50),
  element            integer default NULL,
  fk_user_creat      integer default NULL
)ENGINE=innodb;
