<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiriskdolibarr_ticket.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Ticket
 */
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 * Create category
 *
 * @param	Categorie 	$category Category
 * @return 	string		Success or fail
 */
function createTicketCategory($label, $description, $color, $visible, $type, $fk_parent = 0, $photo_name = '')
{
	global $db, $user, $conf, $langs;
	$upload_dir = $conf->categorie->multidir_output[$conf->entity?:1];

	$category     = new Categorie($db);

	$category->label       = $label;
	$category->description = $description;
	$category->color       = $color;
	$category->visible     = $visible;
	$category->type        = $type;
	$category->fk_parent   = $fk_parent;
	$result                = $category->create($user);

	if ($result < 0) {
		return 0;
	}

	if (dol_strlen($photo_name) > 0) {
		global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

		$dir = $upload_dir . '/' . get_exdir($result, 2, 0, 0, $category, 'category') . $result . "/";
		$dir .= "photos/";
		if (!file_exists($dir)) {
			dol_mkdir($dir);
		}
		$origin_file = '../../img/picto_tickets/'.$photo_name;
		dol_copy($origin_file, $dir . $photo_name);
		vignette($dir . $photo_name, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
		vignette($dir . $photo_name, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
	}
<<<<<<< HEAD
	return 1;
=======
	return $result;
>>>>>>> 9.3.0
}
