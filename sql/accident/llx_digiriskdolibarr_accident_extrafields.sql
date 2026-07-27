-- Copyright (C) 2024 EVARISK <technique@evarisk.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.

create table llx_digiriskdolibarr_accident_extrafields(
    rowid      integer AUTO_INCREMENT PRIMARY KEY,
    tms        timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    import_key varchar(14),
    fk_object  integer NOT NULL
) ENGINE=innodb;
