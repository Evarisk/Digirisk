<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskdolibarrdocuments/jobsheetdocument.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class for the job sheet document ("fiche de poste", INRS ED 126 model) of a GP/UT
 */

// Load DigiriskDolibarr libraries
dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');

/**
 * Class JobSheetDocument
 *
 * The machine of the sheet is kept in the JSON of the document ({"fk_product": id}, 0 for the work station alone):
 * the versions of a sheet are the documents of the same GP/UT and machine
 */
class JobSheetDocument extends DigiriskDocuments
{
    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object
     */
    public $element = 'jobsheetdocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Number of sheets already generated for a GP/UT and a machine, removed files included: a version number is never given twice
     *
     * @param  DigiriskElement $element   GP/UT
     * @param  int             $productId Machine, 0 for the work station alone
     * @return int                        Number of documents
     */
    public function countVersions(DigiriskElement $element, int $productId): int
    {
        $count = 0;

        $sql  = 'SELECT json FROM ' . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE type = '" . $this->db->escape($this->element) . "' AND module_name = '" . $this->db->escape($this->module) . "'";
        $sql .= ' AND parent_id = ' . ((int) $element->id) . " AND parent_type = '" . $this->db->escape($element->element_type) . "'";
        $sql .= ' AND entity IN (' . getEntity($this->element) . ')';
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $json = json_decode((string) $obj->json, true);
                if ((int) ($json['fk_product'] ?? 0) == $productId) {
                    $count++;
                }
            }
            $this->db->free($resql);
        }

        return $count;
    }
}
