-- Copyright (C) 2024 EVARISK <technique@evarisk.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.

ALTER TABLE llx_digiriskdolibarr_accident_extrafields ADD INDEX idx_fk_object(fk_object);
