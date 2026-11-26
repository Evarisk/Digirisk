-- Copyright (C) 2026 EVARISK <technique@evarisk.com>
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

-- Part d'un groupe d'exposition dans un GP/UT (#5644), en pourcentage de son temps de travail : une
-- ligne par groupe et GP/UT, aucune pour une part nulle. La part donnee a un GP vaut pour le GP
-- lui-meme, elle ne se repartit pas sur ses unites de travail.
CREATE TABLE llx_digiriskdolibarr_exposuregroup_element(
  rowid             integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,
  date_creation     datetime NOT NULL,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_exposuregroup  integer NOT NULL,
  fk_element        integer NOT NULL,
  percentage        double(24,8) NOT NULL,
  fk_user_modif     integer
) ENGINE=innodb;
