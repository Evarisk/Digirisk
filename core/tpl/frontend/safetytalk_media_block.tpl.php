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
 * \file    core/tpl/frontend/safetytalk_media_block.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Media block of Saturne for the files of a safety talk: photos through its editor, videos and
 *          documents through its file picker. The wrapper carries the biggest file the server takes, checked
 *          before sending, as an empty request would come back without a word.
 *          Expects: $langs, $mediaContext, $mediaPrefix, $maxUploadKb.
 */

global $langs;
?>
<div class="digirisk-safetytalk-media"
     data-max-upload="<?php print $maxUploadKb * 1024; ?>"
     data-too-big-label="<?php print dol_escape_htmltag($langs->transnoentities('SafetyTalkErrorTooBig', dol_print_size($maxUploadKb * 1024, 1, 1))); ?>">
    <?php print digiriskSafetyTalkMediaBlock($mediaContext, $mediaPrefix); ?>
</div>
<div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkFilesHelp', dol_print_size($maxUploadKb * 1024, 1, 1)); ?></div>
