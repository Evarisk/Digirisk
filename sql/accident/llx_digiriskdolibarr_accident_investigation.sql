-- Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

CREATE TABLE llx_digiriskdolibarr_accident_investigation(
    rowid                      integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref                        varchar(128) NOT NULL,
    ref_ext                    varchar(128),
    entity                     integer DEFAULT 1 NOT NULL,
    date_creation              datetime NOT NULL,
    tms                        timestamp,
    status                     smallint,
    seniority_at_post          varchar(255),
    fk_usual_task              integer,
    accident_on_job            boolean,
    near_miss_on_job           boolean,
    circumstances              text,
    special_note               text,
    emergency_called           boolean,
    fk_emergency               integer,
    fk_who_called_emergency    integer,
    when_were_emergency_called timestamp,
    fk_accident                integer,
    fk_user_creat              integer NOT NULL,
    fk_user_modif              integer
) ENGINE=innodb;
