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

CREATE TABLE llx_digiriskdolibarr_accidentmetadata(
	rowid                       integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity                      integer DEFAULT 1 NOT NULL,
	date_creation               datetime NOT NULL,
	tms                         timestamp,
    status                      smallint,
	relative_location           varchar(255),
	thirdparty_responsibility   boolean,
	fatal                       boolean,
	accident_investigation      boolean,
	accident_investigation_link text,
	collateral_victim           boolean,
	police_report               boolean,
	cerfa_link                  text,
	fk_accident                 integer NOT NULL
) ENGINE=innodb;
