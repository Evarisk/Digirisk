<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */


/**
 * \file       htdocs/custom/digiriskdolibarr/core/modules/digiriskdolibarr/riskanalysis/mod_riskassessment_jarnsaxa.php
 * \ingroup    digiriskelement
 * \brief      File that contains the numbering module rules Jarnsaxa
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 * Class of file that contains the numbering module rules Jarnsaxa
 */
class mod_riskassessment_jarnsaxa extends ModeleNumRefSaturne
{

    /**
     * @var string model name
     */
    public string $name = 'Jarnsaxa';

    public function __construct()
    {
        global $conf;
        $refMod = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_JARNSAXA_ADDON;
        if (dol_strlen($refMod)) {
            $refModSplitted = preg_split('/\{/', $refMod);
            if (is_array($refModSplitted) && !empty($refModSplitted)) {
                $suffix = preg_replace('/\}/', '', $refModSplitted[1]);
                $this->prefix = $refModSplitted[0];
                $this->suffix = $suffix;
            }
        }
    }
    /**
     *  Return description of module
     *
     *  @return     string      Texte descripif
     */
    public function info(): string
    {

        global $conf, $langs, $db;

        $langs->load("bills");

        $form = new Form($db);

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $texte .= '<input type="hidden" name="token" value="'.newToken().'">';
        $texte .= '<input type="hidden" name="action" value="updateMask">';
        $texte .= '<input type="hidden" name="mask" value="DIGIRISKDOLIBARR_RISKASSESSMENT_JARNSAXA_ADDON">';
        $texte .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("DigiriskElement"), $langs->transnoentities("DigiriskElement"));
        $tooltip .= $langs->trans("GenericMaskCodes2");
        $tooltip .= $langs->trans("GenericMaskCodes3");
        $tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("DigiriskElement"), $langs->transnoentities("DigiriskElement"));
        $tooltip .= $langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
        $texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="addon_value" value="'.$conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_JARNSAXA_ADDON.'">', $tooltip, 1, 1).'</td>';

        $texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit" name="Button"value="'.$langs->trans("Modify").'"></td>';

        $texte .= '</tr>';

        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }
}
