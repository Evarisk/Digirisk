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

-- Reponse anonyme d'un salarie a un questionnaire RPS (#5324) : ni nom, ni adresse, ni compte.
-- answers : JSON {position du critere : palier de 1 a 4}, deja retourne pour un critere qui se lit a
-- l'envers (une ressource), comme la grille de l'evaluation. fk_exposuregroup : categorie de poste du
-- repondant, celle de la session ou celle qu'il a choisie (0 = non precisee).
CREATE TABLE llx_digiriskdolibarr_rpsresponse(
  rowid             integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,
  date_creation     datetime NOT NULL,
  fk_session        integer NOT NULL,
  fk_element        integer NOT NULL,
  fk_exposuregroup  integer DEFAULT 0 NOT NULL,
  answers           text NOT NULL,
  comment           text
) ENGINE=innodb;
