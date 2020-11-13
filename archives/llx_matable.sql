create table llx_matable
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  field_one   integer,
  field_two   integer NOT NULL,
  fk_field    integer,
  field_date  datetime,
  tms         timestamp
)ENGINE=innodb;
