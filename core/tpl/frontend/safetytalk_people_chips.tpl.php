<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/frontend/safetytalk_people_chips.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   People of a cycle of safety talks, one chip each, with or without Dolibarr account alike.
 *          Expects: $people (digiriskSafetyTalkGetPeopleNames()).
 */
?>
<div class="digirisk-safetytalk-people">
    <?php foreach ($people as $person) { ?>
    <span class="digirisk-safetytalk-people__chip"><?php print dol_escape_htmltag($person['name']); ?></span>
    <?php } ?>
</div>
