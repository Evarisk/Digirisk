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

ALTER TABLE llx_digiriskdolibarr_risklibrary_element ADD UNIQUE INDEX uk_risklibrary_element (fk_risklibrary, fk_element);
ALTER TABLE llx_digiriskdolibarr_risklibrary_element ADD INDEX idx_risklibrary_element_fk_element (fk_element);
ALTER TABLE llx_digiriskdolibarr_risklibrary_element ADD INDEX idx_risklibrary_element_fk_risk (fk_risk);
ALTER TABLE llx_digiriskdolibarr_risklibrary_element ADD CONSTRAINT fk_risklibrary_element_risklibrary FOREIGN KEY (fk_risklibrary) REFERENCES llx_digiriskdolibarr_risklibrary (rowid);
ALTER TABLE llx_digiriskdolibarr_risklibrary_element ADD CONSTRAINT fk_risklibrary_element_digiriskelement FOREIGN KEY (fk_element) REFERENCES llx_digiriskdolibarr_digiriskelement (rowid);
