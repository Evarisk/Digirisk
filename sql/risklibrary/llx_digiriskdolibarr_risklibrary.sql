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

-- Risque de la bibliotheque de l'entite (#5644) : un modele de risque, avec sa categorie de danger,
-- sa description et une cotation indicative. Il s'affecte aux GP/UT concernes
-- (llx_digiriskdolibarr_risklibrary_element), chacun recevant son propre risque, evalue a part.
CREATE TABLE llx_digiriskdolibarr_risklibrary(
  rowid          integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,
  date_creation  datetime NOT NULL,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  label          varchar(255) NOT NULL,
  category       integer NOT NULL,
  description    text,
  cotation       integer DEFAULT 0 NOT NULL,
  fk_user_creat  integer NOT NULL,
  fk_user_modif  integer
) ENGINE=innodb;
