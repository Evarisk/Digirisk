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
	rowid                                integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity                               integer DEFAULT 1 NOT NULL,
	date_creation                        datetime NOT NULL,
	tms                                  timestamp,
    status                               smallint,
	relative_location                    varchar(255),
	victim_activity                      text,
    accident_nature                      text,
    accident_object                      text,
    accident_nature_doubt                text,
    accident_nature_doubt_link           text,
    victim_transported_to                text,
    workhours_morning_date_start         datetime,
    workhours_morning_date_end           datetime,
    workhours_afternoon_date_start       datetime,
    workhours_afternoon_date_end         datetime,
    collateral_victim                    boolean,
    victim_workhours                     text,
    accident_noticed                     text,
    accident_notice_date                 datetime,
    accident_notice_by                   text,
    accident_described_by_victim         boolean,
    registered_in_accident_register      boolean,
    register_date                        datetime,
    register_number                      varchar(255),
    consequence                          text,
    police_report                        boolean,
    police_report_by                     text,
    first_person_noticed_is_witness      text,
	thirdparty_responsibility            boolean,
	accident_investigation               boolean,
	accident_investigation_link          text,
	cerfa_link                           text,
	json                                 text,
	fk_user_witness                      integer,
	fk_soc_responsible                   integer,
    fk_soc_responsible_insurance_society integer,
	fk_accident                          integer NOT NULL
) ENGINE=innodb;
