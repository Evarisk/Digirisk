<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/firepermitdocument/pdf_firepermitdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Permis de feu en PDF natif.
 */

require_once __DIR__ . '/../preventionplandocument/pdf_preventionplandocument.modules.php';

/**
 * Class to build the fire permit document as a PDF.
 *
 * Le permis de feu porte la meme structure que le plan de prevention : memes entreprises, memes
 * numeros d'urgence, memes consignes, memes signataires, meme periode d'intervention. On herite
 * donc de sa mise en page plutot que de la recopier, et on ne redefinit que ce qui differe : le
 * titre, les travaux par point chaud a la place des risques, et les risques du plan de prevention
 * rattache.
 */
class pdf_firepermitdocument extends pdf_preventionplandocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'firepermitdocument';

    /**
     * @var string Cle de traduction de l'intitule des photos d'un bloc de travaux
     */
    protected string $riskPhotosLabelKey = 'FirePermitWorkTypePhotos';

    /**
     * @var string Prefixe des cles de la phrase de periode d'intervention
     */
    protected string $interventionPeriodKey = 'FirePermitInterventionPeriod';

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db);

        $this->name        = 'firepermitdocument';
        $this->description = $langs->trans('FirePermitDocumentPDFDescription');
    }

    /**
     * Titre du document.
     *
     * @param  Translate $outputLangs Lang object
     * @return string                 Titre traduit
     */
    protected function documentTitle(Translate $outputLangs): string
    {
        return $outputLangs->transnoentities('FirePermit');
    }

    /**
     * Rappel reglementaire imprime sous le titre.
     *
     * @param  Translate $outputLangs Lang object
     * @return string                 Texte traduit
     */
    protected function legalNotice(Translate $outputLangs): string
    {
        return $outputLangs->transnoentities('FirePermitLegalNotice');
    }

    /**
     * Donnees du document, lues dans le JSON qui alimente aussi le modele ODT.
     *
     * @param  SaturneDocuments $objectDocument Document source
     * @return array                            Donnees du permis de feu
     */
    protected function documentData($objectDocument): array
    {
        global $mysoc;

        $objectDocument->DigiriskFillJSON();
        $json = json_decode($objectDocument->json);
        $data = (is_object($json) && isset($json->FirePermit)) ? (array) $json->FirePermit : [];

        // Le JSON du permis de feu ne porte que l'entreprise exterieure : l'entreprise utilisatrice
        // est celle de l'instance, comme sur le gabarit ODT
        if (empty($data['society_inside'])) {
            $data['society_inside'] = (object) [
                'name'    => $mysoc->name,
                'siret'   => $mysoc->idprof2,
                'address' => $mysoc->address,
                'postal'  => $mysoc->zip,
                'town'    => $mysoc->town,
            ];
        }

        return $data;
    }

    /**
     * Sections du document : celles du plan de prevention, sans l'inspection commune prealable que
     * le permis ne connait pas, et suivies des risques du plan de prevention rattache.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Permis de feu
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function writeSections($pdf, $object, array $data, Translate $outputLangs, float $size)
    {
        $this->sectionCompanies($pdf, $data, $outputLangs, $size);
        $this->sectionEmergency($pdf, $data, $outputLangs, $size);
        $this->sectionMeansAndRules($pdf, $data, $outputLangs, $size);
        $this->sectionIntervention($pdf, $object, $outputLangs, $size);
        $this->sectionRisks($pdf, $object, $outputLangs, $size);
        $this->sectionCertifications($pdf, $object, $outputLangs, $size);
        $this->sectionLinkedPreventionPlan($pdf, $object, $data, $outputLangs, $size);
        $this->sectionSignatures($pdf, $object, $outputLangs, $size);
    }

    /**
     * Travaux par point chaud : un bloc par type de travaux, comme les risques du plan de prevention
     * (picto, description et materiel employe, protections, photos prises sur le terrain).
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Permis de feu
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionRisks($pdf, $object, Translate $outputLangs, float $size)
    {
        require_once __DIR__ . '/../../../../../class/firepermit.class.php';

        $line  = new FirePermitLine($this->db);
        $risk  = new Risk($this->db);
        $lines = $line->fetchAll('', '', 0, 0, ['fk_firepermit' => $object->id]);

        $this->sectionTitle($pdf, $outputLangs->transnoentities('FirePermitWorkTypesAnalysis'), $size);

        // Sans ces lignes le permis ne vaut rien : leur absence est dite plutot que laissee vide
        if (!is_array($lines) || empty($lines)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('NoData'), $size - 1, 'I', [120, 120, 120]);
            return;
        }

        $this->paragraph($pdf, $outputLangs->transnoentities('FirePermitWorkTypesIntro', (string) count($lines)), $size - 2, '', [110, 110, 110]);

        $object->fetch_optionals();
        $protections = !empty($object->array_options['options_mobile_protections']) ? json_decode($object->array_options['options_mobile_protections'], true) : [];

        $signalisationFile = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
        $signalisationMap  = [];
        if (file_exists($signalisationFile)) {
            foreach ((json_decode(file_get_contents($signalisationFile), true) ?: []) as $signalisationItem) {
                $signalisationMap[$signalisationItem['position']] = $signalisationItem;
            }
        }

        $digiriskElement = new DigiriskElement($this->db);

        foreach ($lines as $workLine) {
            $category  = (int) $workLine->category;
            $thumb     = $risk->getFirePermitDangerCategory($workLine);
            $pictoPath = ($thumb != -1) ? DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/typeDeTravaux/' . $thumb . '.png' : '';
            $workName  = $risk->getFirePermitDangerCategoryName($workLine);
            $workName  = ($workName != -1) ? (string) $workName : '';

            $workProtections = [];
            if (is_array($protections)) {
                foreach ($protections as $protection) {
                    if (!isset($protection['risk_category'], $signalisationMap[$protection['position']]) || (int) $protection['risk_category'] !== $category) {
                        continue;
                    }
                    $workProtections[] = [
                        'image'   => DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/' . $signalisationMap[$protection['position']]['name_thumbnail'],
                        'name'    => $signalisationMap[$protection['position']]['name'],
                        'comment' => $protection['comment'] ?? '',
                    ];
                }
            }

            // Le materiel employe est ce qui fait le travail par point chaud : il se lit avec la description
            $description = html_entity_decode(strip_tags((string) $workLine->description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $equipment   = html_entity_decode(strip_tags((string) $workLine->used_equipment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (dol_strlen(trim($equipment))) {
                $description = trim($description . "\n" . $outputLangs->transnoentities('UsedEquipment') . ' : ' . trim($equipment));
            }
            $displayLine              = clone $workLine;
            $displayLine->description = $description;

            $workPhotos = digiriskMobileGetRiskPhotos($object->element, $object->ref, $category);

            // Le permis ne demande pas quelles entreprises chaque type de travaux concerne
            $this->drawRiskBlock($pdf, $displayLine, $workName, $pictoPath, $workProtections, $workPhotos, [], $digiriskElement, $outputLangs, $size);
        }
    }

    /**
     * Risques du plan de prevention auquel le permis est rattache.
     *
     * Le permis de feu ne vit jamais seul : les risques deja analyses sur son plan s'appliquent au
     * chantier, les rappeler ici evite d'avoir les deux documents en main.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Permis de feu
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionLinkedPreventionPlan($pdf, $object, array $data, Translate $outputLangs, float $size)
    {
        $linked = [];
        if (isset($data['PreventionPlan']) && isset(((object) $data['PreventionPlan'])->risk)) {
            $linked = (array) ((object) $data['PreventionPlan'])->risk;
        }

        if (empty($linked)) {
            return;
        }

        $preventionPlan = new PreventionPlan($this->db);
        $title          = $outputLangs->transnoentities('FirePermitLinkedPreventionPlanRisks');
        if ($object->fk_preventionplan > 0 && $preventionPlan->fetch($object->fk_preventionplan) > 0) {
            $title .= ' - ' . $preventionPlan->ref;
        }

        $this->sectionTitle($pdf, $title, $size);

        $rows = [];
        foreach ($linked as $riskLine) {
            $riskLine = (object) $riskLine;

            // Le JSON concatene "ref - label" : sans unite de travail rattachee il ne reste que le
            // tiret de separation, qu'il vaut mieux ne pas afficher du tout
            $workUnit = trim((string) ($riskLine->unite_travail ?? ''));
            if ($workUnit === '-') {
                $workUnit = '';
            }

            $riskName = (string) ($riskLine->name ?? '');
            $rows[]   = [
                $riskName === '-1' ? '' : $riskName,
                $workUnit,
                html_entity_decode(strip_tags((string) ($riskLine->description ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                html_entity_decode(strip_tags((string) ($riskLine->prevention_method ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        $width = $this->contentWidth($pdf);
        $this->table(
            $pdf,
            [
                $outputLangs->transnoentities('Risk'),
                $outputLangs->transnoentities('WorkUnit'),
                $outputLangs->transnoentities('Description'),
                $outputLangs->transnoentities('PreventionMethod'),
            ],
            $rows,
            [$width * 0.22, $width * 0.24, $width * 0.27, $width * 0.27],
            $size - 1
        );
    }
}
