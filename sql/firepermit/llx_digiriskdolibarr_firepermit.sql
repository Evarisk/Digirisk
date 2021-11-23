-- Copyright (C) 2021 EOXIA <dev@eoxia.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

CREATE TABLE llx_digiriskdolibarr_firepermit(
	rowid                integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref                  varchar(128) NOT NULL,
	ref_ext              varchar(128),
	entity               integer DEFAULT 1 NOT NULL,
	date_creation        datetime NOT NULL,
	tms                  timestamp,
	status               smallint,
	label                varchar(255) NOT NULL,
    date_start           datetime NOT NULL,
	date_end             datetime,
	last_email_sent_date datetime DEFAULT NULL,
    fk_project           integer,
	fk_user_creat        integer NOT NULL,
	fk_user_modif        integer,
	fk_preventionplan    integer
) ENGINE=innodb;
