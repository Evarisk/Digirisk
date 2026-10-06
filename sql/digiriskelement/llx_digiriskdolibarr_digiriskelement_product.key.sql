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

ALTER TABLE llx_digiriskdolibarr_digiriskelement_product ADD INDEX idx_digiriskelement_product_fk_digiriskelement (fk_digiriskelement);
ALTER TABLE llx_digiriskdolibarr_digiriskelement_product ADD INDEX idx_digiriskelement_product_fk_product (fk_product);
ALTER TABLE llx_digiriskdolibarr_digiriskelement_product ADD CONSTRAINT fk_digiriskelement_product_digiriskelement FOREIGN KEY (fk_digiriskelement) REFERENCES llx_digiriskdolibarr_digiriskelement (rowid);
ALTER TABLE llx_digiriskdolibarr_digiriskelement_product ADD CONSTRAINT fk_digiriskelement_product_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
