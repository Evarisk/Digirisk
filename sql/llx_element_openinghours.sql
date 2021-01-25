-- Copyright (C) ---Put here your own copyright and developer email---
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


CREATE TABLE llx_element_openinghours(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	ref_ext varchar(128),
	date_creation datetime NOT NULL,
	element_type varchar(50),
	element_id integer,
	status integer,
	day0 varchar(128),
	day1 varchar(128),
	day2 varchar(128),
	day3 varchar(128),
	day4 varchar(128),
	day5 varchar(128),
	day6 varchar(128),
	entity integer DEFAULT 1
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
