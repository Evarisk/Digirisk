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

-- Une reponse par critere de la grille RPS-DU, rattachee a l'evaluation qui la porte.
-- Le libelle du critere est fige a la saisie : une evaluation signee reste lisible meme
-- si la grille evolue.
CREATE TABLE llx_digiriskdolibarr_rpsanswer(
  rowid             integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,
  date_creation     datetime NOT NULL,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_riskassessment integer NOT NULL,
  criterion         integer NOT NULL,
  criterion_label   varchar(255),
  sub_category      integer NOT NULL,
  scale             smallint NOT NULL,
  cotation          integer NOT NULL,
  comment           text,
  fk_user_creat     integer NOT NULL
) ENGINE=innodb;
