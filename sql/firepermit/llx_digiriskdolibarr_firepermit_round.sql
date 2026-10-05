-- Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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

CREATE TABLE llx_digiriskdolibarr_firepermit_round(
  rowid          integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,
  date_creation  datetime NOT NULL,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status         integer DEFAULT 0 NOT NULL,
  position       integer DEFAULT 0 NOT NULL,
  delay_minutes  integer NOT NULL,
  date_planned   datetime NOT NULL,
  date_done      datetime,
  watcher_name   varchar(255),
  description    text,
  latitude       double(24,8),
  longitude      double(24,8),
  fk_firepermit  integer NOT NULL,
  fk_user_creat  integer,
  fk_user_modif  integer
) ENGINE=innodb;
