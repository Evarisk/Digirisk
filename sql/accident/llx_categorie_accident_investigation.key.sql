-- Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

ALTER TABLE llx_categorie_accident_investigation ADD PRIMARY KEY pk_categorie_accident_investigation (fk_categorie, fk_accident_investigation);
ALTER TABLE llx_categorie_accident_investigation ADD INDEX idx_categorie_accident_investigation_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_accident_investigation ADD INDEX idx_categorie_accident_investigation_fk_accident_investigation (fk_accident_investigation);
ALTER TABLE llx_categorie_accident_investigation ADD CONSTRAINT fk_categorie_accident_investigation_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_accident_investigation ADD CONSTRAINT llx_categorie_accident_investigation_digiriskdolibarr_accident_investigation_rowid FOREIGN KEY (fk_accident_investigation) REFERENCES llx_digiriskdolibarr_accident_investigation (rowid);
