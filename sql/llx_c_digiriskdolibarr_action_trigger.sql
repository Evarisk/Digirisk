-- Copyright (C) 2022 EOXIA <dev@eoxia.com>
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

create table llx_c_digiriskdolibarr_action_trigger(
    rowid       integer AUTO_INCREMENT PRIMARY KEY,
    entity      integer      DEFAULT 1,
    elementtype varchar(64)  NOT NULL,
    ref         varchar(64)  NOT NULL,
    label       varchar(128) NOT NULL,
    description varchar(255),
    active      tinyint(4)   DEFAULT 1,
    position    integer      DEFAULT 0
) ENGINE=innodb;
