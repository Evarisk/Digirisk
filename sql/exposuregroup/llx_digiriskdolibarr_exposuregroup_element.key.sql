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

ALTER TABLE llx_digiriskdolibarr_exposuregroup_element ADD UNIQUE INDEX uk_exposuregroup_element (fk_exposuregroup, fk_element);
ALTER TABLE llx_digiriskdolibarr_exposuregroup_element ADD INDEX idx_exposuregroup_element_fk_element (fk_element);
ALTER TABLE llx_digiriskdolibarr_exposuregroup_element ADD CONSTRAINT fk_exposuregroup_element_exposuregroup FOREIGN KEY (fk_exposuregroup) REFERENCES llx_digiriskdolibarr_exposuregroup (rowid);
ALTER TABLE llx_digiriskdolibarr_exposuregroup_element ADD CONSTRAINT fk_exposuregroup_element_digiriskelement FOREIGN KEY (fk_element) REFERENCES llx_digiriskdolibarr_digiriskelement (rowid);
