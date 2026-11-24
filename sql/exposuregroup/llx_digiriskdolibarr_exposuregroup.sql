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

-- Groupe d'exposition (#5644) : des personnes qui occupent les memes postes, comme les agents de
-- collecte, avec leur effectif. Il se repartit en pourcentage de son temps de travail sur les GP/UT
-- (llx_digiriskdolibarr_exposuregroup_element).
CREATE TABLE llx_digiriskdolibarr_exposuregroup(
  rowid          integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,
  date_creation  datetime NOT NULL,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  label          varchar(255) NOT NULL,
  description    text,
  nb_people      integer DEFAULT 0 NOT NULL,
  fk_user_creat  integer NOT NULL,
  fk_user_modif  integer
) ENGINE=innodb;
