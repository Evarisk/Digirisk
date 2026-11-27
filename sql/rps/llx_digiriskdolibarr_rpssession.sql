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

-- Session d'evaluation des risques psychosociaux par questionnaire (#5324) : les salaries d'une categorie
-- de poste (groupe d'exposition, 0 = le repondant choisit la sienne) repondent a la grille RPS-DU de
-- l'ED 6403 par un lien public, pour l'une des unites de travail de la session.
-- max_answers : nombre de reponses accepte par unite de travail, 0 = sans limite.
-- public_key : cle secrete du lien public. status : 1 ouverte, 2 close.
CREATE TABLE llx_digiriskdolibarr_rpssession(
  rowid             integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,
  date_creation     datetime NOT NULL,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  label             varchar(255) NOT NULL,
  fk_exposuregroup  integer DEFAULT 0 NOT NULL,
  max_answers       integer DEFAULT 0 NOT NULL,
  public_key        varchar(64) NOT NULL,
  status            smallint DEFAULT 1 NOT NULL,
  date_closure      datetime NULL,
  fk_user_creat     integer NOT NULL,
  fk_user_modif     integer
) ENGINE=innodb;
