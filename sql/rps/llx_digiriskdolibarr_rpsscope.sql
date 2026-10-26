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

-- Qualification d'une unite de travail face aux risques psychosociaux, prise une seule fois
-- sur le groupement de tete. concerned : 1 concernee, 0 non concernee (motif obligatoire),
-- -1 a qualifier. Une unite non concernee reste dans le perimetre, avec son motif.
CREATE TABLE llx_digiriskdolibarr_rpsscope(
  rowid          integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,
  date_creation  datetime NOT NULL,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_element     integer NOT NULL,
  concerned      smallint DEFAULT -1 NOT NULL,
  reason         text,
  fk_user_modif  integer
) ENGINE=innodb;
