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

CREATE TABLE llx_digiriskdolibarr_object_signature(
	rowid                integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity               integer DEFAULT 1 NOT NULL,
	date_creation        datetime NOT NULL,
	tms                  timestamp,
	import_key           integer DEFAULT NULL,
	status               smallint,
	role                 varchar(255),
    firstname            varchar(255),
	lastname             varchar(255),
	email                varchar(255),
	phone                varchar(255),
	society_name         varchar(255),
	signature_date       datetime DEFAULT NULL,
	signature_location   varchar(255),
	signature_comment    text DEFAULT NULL,
	element_id           integer NOT NULL,
	element_type         varchar(255),
    signature            text,
    stamp                text,
	last_email_sent_date datetime DEFAULT NULL,
	signature_url        varchar(255),
	transaction_url      varchar(255),
    object_type          varchar(255),
    fk_object            integer NOT NULL
) ENGINE=innodb;
