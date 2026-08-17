CREATE TABLE llx_digiriskdolibarr_product_risk(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL,
    entity integer DEFAULT 1 NOT NULL,
    date_creation datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status tinyint DEFAULT 1 NOT NULL,
    fk_product integer NOT NULL,
    danger_category integer NOT NULL,
    description text,
    protections_json text,
    fk_user_creat integer,
    fk_user_modif integer
) ENGINE=innodb;
