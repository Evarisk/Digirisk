/* Copyright (C) 2019-2020 Eoxia <dev@eoxia.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/custom/doliwpshop/lib/doliwpshop.lib.php
 *	\ingroup	doliwpshop
 *	\brief		Library files with common functions for DoliWPshop
 */

create table llx_legal_display
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  ref           varchar(128) NOT NULL,
  ref_ext       varchar(255) default NULL,
  entity        integer default 1,
  date_creation datetime default NULL,
  tms           timestamp,
  date_valid    datetime,
  description   text,
  import_key    integer,
  status        smallint,
  fk_user_creat integer default NULL,
  fk_user_modif integer default NULL,
  fk_user_valid integer default NULL,
  model_pdf     varchar(128),
  model_odt     varchar(128),
  note_affich   varchar(128)
)ENGINE=innodb;
