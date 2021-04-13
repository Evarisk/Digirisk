-- Copyright (C) 2021 EOXIA <dev@eoxia.com>
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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_digiriskdolibarr_digirisksignalisation ADD INDEX idx_digiriskdolibarr_digirisksignalisation_rowid (rowid);
ALTER TABLE llx_digiriskdolibarr_digirisksignalisation ADD INDEX idx_digiriskdolibarr_digirisksignalisation_ref (ref);
ALTER TABLE llx_digiriskdolibarr_digirisksignalisation ADD CONSTRAINT llx_digiriskdolibarr_digirisksignalisation_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_digiriskdolibarr_digirisksignalisation ADD UNIQUE INDEX uk_digiriskdolibarr_digirisksignalisation_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_digiriskdolibarr_digirisksignalisation ADD CONSTRAINT llx_digiriskdolibarr_digirisksignalisation_fk_field FOREIGN KEY (fk_field) REFERENCES llx_digiriskdolibarr_myotherobject(rowid);

