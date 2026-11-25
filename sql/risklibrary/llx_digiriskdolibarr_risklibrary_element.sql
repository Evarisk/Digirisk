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

-- Affectation d'un risque de la bibliotheque a un GP/UT (#5644). concerned : 1 concerne, 0 non
-- concerne (motif), -1 a qualifier, comme une ligne absente. fk_risk est le risque cree dans le GP/UT
-- quand il est concerne : il est archive s'il ne l'est plus, et restaure s'il le redevient.
CREATE TABLE llx_digiriskdolibarr_risklibrary_element(
  rowid           integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity          integer DEFAULT 1 NOT NULL,
  date_creation   datetime NOT NULL,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_risklibrary  integer NOT NULL,
  fk_element      integer NOT NULL,
  concerned       smallint NOT NULL,
  reason          text,
  fk_risk         integer,
  fk_user_modif   integer
) ENGINE=innodb;
