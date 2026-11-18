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
 * \file    class/jobsheet.class.php
 * \ingroup digiriskdolibarr
 * \brief   Job sheet ("fiche de poste") of a GP/UT and one of its machines, after the INRS ED 126 model
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement_product.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskresources.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/risk.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/riskassessment.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/risksign.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/productrisk.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdolibarrdocuments/jobsheetdocument.class.php');
dol_include_once('/saturne/lib/medias.lib.php');

/**
 * Class JobSheet
 *
 * Gathers what the Document Unique already knows about a work station (the GP/UT) and about the machine
 * used there (a product linked to the GP/UT), arranged by the eight headings of the INRS ED 126 model.
 * Nothing is stored here: the sheet is rebuilt from the live data at each generation, the generated files being
 * Saturne documents (JobSheetDocument, model pdf_jobsheetdocument).
 */
class JobSheet
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string[] Errors
     */
    public $errors = [];

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Products (machines) linked to a GP/UT
     *
     * @param  DigiriskElement $element GP/UT
     * @return Product[]                Products by id
     */
    public function getLinkedProducts(DigiriskElement $element): array
    {
        $products = [];

        $association = new DigiriskElementProduct($this->db);
        foreach ($association->fetchAllByElement($element->id) as $associationSingle) {
            $product = new Product($this->db);
            if ($product->fetch($associationSingle->fk_product) > 0) {
                $products[$product->id] = $product;
            }
        }

        return $products;
    }

    /**
     * Gather the data of the sheet
     *
     * @param  DigiriskElement $element GP/UT
     * @param  Product|null    $product Machine, null for a work station without machine
     * @param  int             $version Version number, the next one of the sheet when 0
     * @return array                    Data arranged by the headings of the ED 126 model
     * @throws Exception
     */
    public function load(DigiriskElement $element, ?Product $product, int $version = 0): array
    {
        global $conf, $mysoc;

        if ($version <= 0) {
            $jobSheetDocument = new JobSheetDocument($this->db);
            $version          = $jobSheetDocument->countVersions($element, is_object($product) ? $product->id : 0) + 1;
        }

        $data = [
            'element'   => $element,
            'parent'    => null,
            'product'   => $product,
            'version'   => $version,
            'ref'       => '',
            'date'      => dol_now(),
            'company'   => ['name' => (string) $mysoc->name, 'logo' => ''],
            'machine'   => ['label' => '', 'ref' => '', 'characteristics' => '', 'usermanual' => '', 'security' => '', 'qualification' => ''],
            'photo'     => ['path' => '', 'source' => ''],
            'epi'       => ['mandatory' => [], 'forbidden' => []],
            'tasks'     => [],
            'emergency' => ['samu' => '', 'pompiers' => '', 'allemergencies' => ''],
        ];

        if ($element->fk_parent > 0) {
            $parent = new DigiriskElement($this->db);
            if ($parent->fetch($element->fk_parent) > 0) {
                $data['parent'] = $parent;
            }
        }

        // Company logo, the small one is enough for the header
        if (!empty($mysoc->logo)) {
            $logoDir = $conf->mycompany->multidir_output[$conf->entity] . '/logos';
            foreach ([$logoDir . '/thumbs/' . $mysoc->logo_small, $logoDir . '/' . $mysoc->logo] as $logoPath) {
                if (!empty($logoPath) && is_file($logoPath)) {
                    $data['company']['logo'] = $logoPath;
                    break;
                }
            }
        }

        // Identification of the machine: the instruction sheet chapters of the product (#4967)
        if (is_object($product)) {
            if (empty($product->array_options)) {
                $product->fetch_optionals();
            }
            $data['machine']['label']           = (string) $product->label;
            $data['machine']['ref']             = (string) $product->ref;
            $data['machine']['characteristics'] = $this->getProductChapter($product, 'identification');
            $data['machine']['usermanual']      = $this->getProductChapter($product, 'usermanual');
            $data['machine']['security']        = $this->getProductChapter($product, 'security');
            $data['machine']['qualification']   = $this->getProductChapter($product, 'qualification');
        } else {
            $data['machine']['label']           = (string) $element->label;
            $data['machine']['characteristics'] = (string) $element->description;
        }

        $data['photo'] = $this->getPhoto($element, $product);

        // Both JSON files are read by position, the order of their entries is not the order of the positions
        $dangerCategories = [];
        foreach (Risk::getDangerCategories('risk') as $dangerCategory) {
            $dangerCategories[(int) $dangerCategory['position']] = $dangerCategory;
        }

        $riskSign           = new RiskSign($this->db);
        $riskSignCategories = [];
        foreach ($riskSign->getRiskSignCategories() as $riskSignCategory) {
            $riskSignCategories[(int) $riskSignCategory['position']] = $riskSignCategory;
        }

        // Personal protective equipment: the protections of the machine risks, then the risk signs of the GP/UT
        $epiByKey = [];
        if (is_object($product)) {
            foreach ($this->getProductRisks($product) as $productRisk) {
                $measures = [];
                foreach ((json_decode((string) $productRisk->protections_json, true) ?: []) as $protection) {
                    $category = $riskSignCategories[(int) ($protection['position'] ?? -1)] ?? null;
                    if (empty($category)) {
                        continue;
                    }
                    $measures[] = ['label' => $category['name'], 'comment' => (string) ($protection['comment'] ?? ''), 'picto' => $this->getRiskSignPictoPath($category), 'done' => true];
                    $this->addEpi($epiByKey, $category, (string) ($protection['comment'] ?? ''), 'product');
                }

                $data['tasks'][] = [
                    'source'      => 'product',
                    'ref'         => $productRisk->ref,
                    'description' => (string) $productRisk->description,
                    'danger'      => $this->formatDangerCategory($dangerCategories[(int) $productRisk->danger_category] ?? []),
                    'cotation'    => null,
                    'measures'    => $measures,
                ];
            }
        }

        $riskSigns = $riskSign->fetchFromParent($element->id);
        foreach (is_array($riskSigns) ? $riskSigns : [] as $riskSignSingle) {
            if ($riskSignSingle->status != RiskSign::STATUS_VALIDATED) {
                continue;
            }
            $category = $riskSignCategories[(int) $riskSignSingle->category] ?? null;
            if (!empty($category)) {
                $this->addEpi($epiByKey, $category, (string) $riskSignSingle->description, 'risksign');
            }
        }
        foreach ($epiByKey as $epi) {
            $data['epi'][$epi['kind']][] = $epi;
        }

        // Tasks exposing the operator to a risk: the risks of the GP/UT in the Document Unique, the highest first
        $risk           = new Risk($this->db);
        $riskAssessment = new RiskAssessment($this->db);
        $riskTasks      = [];
        $risks          = $risk->fetchFromParent($element->id);
        foreach (is_array($risks) ? $risks : [] as $riskSingle) {
            if ($riskSingle->status != Risk::STATUS_VALIDATED || $riskSingle->type != 'risk') {
                continue;
            }

            $cotation    = null;
            $assessments = $riskAssessment->fetchFromParent($riskSingle->id, 1);
            if (is_array($assessments) && !empty($assessments)) {
                $lastAssessment = end($assessments);
                $cotation       = (int) $lastAssessment->cotation;
            }

            $measures     = [];
            $relatedTasks = $risk->getRelatedTasks($riskSingle);
            foreach (is_array($relatedTasks) ? $relatedTasks : [] as $relatedTask) {
                $measures[] = ['label' => (string) $relatedTask->label, 'comment' => '', 'picto' => '', 'done' => ((int) $relatedTask->progress >= 100)];
            }

            $riskTasks[] = [
                'source'      => 'risk',
                'ref'         => $riskSingle->ref,
                'description' => (string) $riskSingle->description,
                'danger'      => $this->formatDangerCategory($dangerCategories[(int) $riskSingle->category] ?? []),
                'cotation'    => $cotation,
                'measures'    => $measures,
            ];
        }
        usort($riskTasks, static function ($a, $b) {
            return ($b['cotation'] ?? -1) <=> ($a['cotation'] ?? -1);
        });
        $data['tasks'] = array_merge($data['tasks'], $riskTasks);

        $data['emergency'] = $this->getEmergencyNumbers();

        return $data;
    }

    /**
     * Check the data of the sheet against the headings of the ED 126 model
     *
     * Each item tells where Digirisk takes the data from and, when it is missing, where to fill it in.
     * Status: 'ok' filled, 'warning' filled partly or by default, 'missing' blank on the sheet (dotted lines),
     * 'hand' never known by Digirisk, always completed by hand on the printed sheet
     *
     * @param  array $data Data of the sheet, from load()
     * @return array       Items in the order of the model
     */
    public function getChecklist(array $data): array
    {
        global $langs;

        $element = $data['element'];
        $product = $data['product'];

        $productCardUrl    = is_object($product) ? DOL_URL_ROOT . '/product/card.php?id=' . $product->id : '';
        $productFileUrl    = is_object($product) ? DOL_URL_ROOT . '/product/document.php?id=' . $product->id : '';
        $productSheetUrl   = is_object($product) ? dol_buildpath('/digiriskdolibarr/view/product/product_digirisk.php', 1) . '?id=' . $product->id : '';
        $productRisksUrl   = is_object($product) ? dol_buildpath('/digiriskdolibarr/view/product/product_digirisk_risks.php', 1) . '?id=' . $product->id : '';
        $elementCardUrl    = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . $element->id;
        $elementRiskUrl    = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $element->id . '&risk_type=risk';
        $elementSignUrl    = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php', 1) . '?id=' . $element->id;
        $elementProductUrl = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_product.php', 1) . '?id=' . $element->id;
        $securityConfUrl   = dol_buildpath('/digiriskdolibarr/admin/securityconf.php', 1);

        $items = [];

        $items[] = [
            'num'    => 1,
            'label'  => $langs->transnoentities('JobSheetED126Version'),
            'source' => $langs->transnoentities('JobSheetSourceVersion'),
            'status' => 'ok',
            'detail' => $langs->transnoentities('JobSheetVersionNext', $data['version'], dol_print_date($data['date'], 'day')),
            'links'  => [],
        ];

        if (is_object($product)) {
            $identificationStatus = (!empty($this->toText($data['machine']['characteristics'])) ? 'ok' : 'warning');
            $items[]              = [
                'num'    => '',
                'label'  => $langs->transnoentities('JobSheetED126Machine'),
                'source' => $langs->transnoentities('JobSheetSourceMachine'),
                'status' => $identificationStatus,
                'detail' => $identificationStatus == 'ok' ? $langs->transnoentities('JobSheetMachineIdentified', $product->ref) : $langs->transnoentities('JobSheetMachineNoCharacteristics'),
                'links'  => $identificationStatus == 'ok' ? [] : [$langs->transnoentities('JobSheetFillInstructionSheet') => $productSheetUrl],
            ];
        } else {
            $items[] = [
                'num'    => '',
                'label'  => $langs->transnoentities('JobSheetED126Machine'),
                'source' => $langs->transnoentities('JobSheetSourceNoMachine'),
                'status' => 'warning',
                'detail' => $langs->transnoentities('JobSheetNoMachineSelected'),
                'links'  => [$langs->transnoentities('JobSheetLinkMachine') => $elementProductUrl],
            ];
        }

        $photoLinks = is_object($product) ? [$langs->transnoentities('JobSheetAddMachinePhoto') => $productFileUrl] : [$langs->transnoentities('JobSheetAddElementPhoto') => $elementCardUrl];
        if ($data['photo']['source'] == 'product') {
            $items[] = ['num' => 2, 'label' => $langs->transnoentities('JobSheetED126Photo'), 'source' => $langs->transnoentities('JobSheetSourcePhoto'), 'status' => 'ok', 'detail' => $langs->transnoentities('JobSheetPhotoFromMachine'), 'links' => []];
        } elseif ($data['photo']['source'] == 'element') {
            $items[] = ['num' => 2, 'label' => $langs->transnoentities('JobSheetED126Photo'), 'source' => $langs->transnoentities('JobSheetSourcePhoto'), 'status' => is_object($product) ? 'warning' : 'ok', 'detail' => $langs->transnoentities('JobSheetPhotoFromElement'), 'links' => is_object($product) ? $photoLinks : []];
        } else {
            $items[] = ['num' => 2, 'label' => $langs->transnoentities('JobSheetED126Photo'), 'source' => $langs->transnoentities('JobSheetSourcePhoto'), 'status' => 'missing', 'detail' => $langs->transnoentities('JobSheetNoPhoto'), 'links' => $photoLinks];
        }

        $epiCount   = count($data['epi']['mandatory']) + count($data['epi']['forbidden']);
        $epiLinks   = [$langs->transnoentities('RiskSigns') => $elementSignUrl];
        if (is_object($product)) {
            $epiLinks[$langs->transnoentities('JobSheetMachineRisks')] = $productRisksUrl;
        }
        $items[] = [
            'num'    => 3,
            'label'  => $langs->transnoentities('JobSheetED126Epi'),
            'source' => $langs->transnoentities('JobSheetSourceEpi'),
            'status' => $epiCount > 0 ? 'ok' : 'missing',
            'detail' => $epiCount > 0 ? $langs->transnoentities('JobSheetEpiCount', count($data['epi']['mandatory']), count($data['epi']['forbidden'])) : $langs->transnoentities('JobSheetNoEpi'),
            'links'  => $epiCount > 0 ? [] : $epiLinks,
        ];

        $productTaskCount = count(array_filter($data['tasks'], static function ($task) {
            return $task['source'] == 'product';
        }));
        $riskTaskCount  = count($data['tasks']) - $productTaskCount;
        $taskLinks      = [$langs->transnoentities('Risks') => $elementRiskUrl];
        if (is_object($product)) {
            $taskLinks[$langs->transnoentities('JobSheetMachineRisks')] = $productRisksUrl;
        }
        $items[] = [
            'num'    => '4 · 5',
            'label'  => $langs->transnoentities('JobSheetED126Tasks'),
            'source' => $langs->transnoentities('JobSheetSourceTasks'),
            'status' => count($data['tasks']) > 0 ? 'ok' : 'missing',
            'detail' => count($data['tasks']) > 0 ? $langs->transnoentities('JobSheetTasksCount', $productTaskCount, $riskTaskCount) : $langs->transnoentities('JobSheetNoTask'),
            'links'  => count($data['tasks']) > 0 ? [] : $taskLinks,
        ];

        $withoutMeasure = [];
        foreach ($data['tasks'] as $task) {
            if (empty($task['measures'])) {
                $withoutMeasure[] = $task['ref'];
            }
        }
        $items[] = [
            'num'    => 6,
            'label'  => $langs->transnoentities('JobSheetED126Measures'),
            'source' => $langs->transnoentities('JobSheetSourceMeasures'),
            'status' => empty($data['tasks']) ? 'missing' : (empty($withoutMeasure) ? 'ok' : 'warning'),
            'detail' => empty($data['tasks']) ? $langs->transnoentities('JobSheetNoTask') : (empty($withoutMeasure) ? $langs->transnoentities('JobSheetAllTasksHaveMeasures') : $langs->transnoentities('JobSheetTasksWithoutMeasure', implode(', ', $withoutMeasure))),
            'links'  => empty($withoutMeasure) ? [] : $taskLinks,
        ];

        $qualificationFilled = !empty($this->toText($data['machine']['qualification']));
        $items[]             = [
            'num'    => 7,
            'label'  => $langs->transnoentities('JobSheetED126Qualification'),
            'source' => $langs->transnoentities('JobSheetSourceQualification'),
            'status' => $qualificationFilled ? 'ok' : 'missing',
            'detail' => $qualificationFilled ? $langs->transnoentities('JobSheetQualificationFilled') : $langs->transnoentities('JobSheetQualificationEmpty'),
            'links'  => (!$qualificationFilled && is_object($product)) ? [$langs->transnoentities('JobSheetFillInstructionSheet') => $productSheetUrl] : [],
        ];

        $emergencyConfigured = !empty($data['emergency']['samu']) && !empty($data['emergency']['pompiers']);
        $items[]             = [
            'num'    => 8,
            'label'  => $langs->transnoentities('JobSheetED126Emergency'),
            'source' => $langs->transnoentities('JobSheetSourceEmergency'),
            'status' => $emergencyConfigured ? 'ok' : 'warning',
            'detail' => $emergencyConfigured ? $langs->transnoentities('JobSheetEmergencyNumbers', $data['emergency']['samu'], $data['emergency']['pompiers']) : $langs->transnoentities('JobSheetEmergencyDefault'),
            'links'  => $emergencyConfigured ? [] : [$langs->transnoentities('JobSheetConfigureEmergency') => $securityConfUrl],
        ];

        $items[] = [
            'num'    => '',
            'label'  => $langs->transnoentities('JobSheetED126HandFilled'),
            'source' => $langs->transnoentities('JobSheetSourceHandFilled'),
            'status' => 'hand',
            'detail' => $langs->transnoentities('JobSheetHandFilledDetail'),
            'links'  => [],
        ];

        return $items;
    }

    /**
     * Plain text of an HTML chapter, to test if it is filled
     *
     * @param  string $html HTML
     * @return string       Text
     */
    public function toText(string $html): string
    {
        return trim(dol_string_nohtmltag(str_replace('&nbsp;', ' ', $html), 0));
    }

    /**
     * One chapter of the instruction sheet of a product, or its default text from the setup of the products
     *
     * @param  Product $product Product
     * @param  string  $chapter Chapter: identification, security, usermanual, qualification, hygiene, maintenance
     * @return string           HTML
     */
    public function getProductChapter(Product $product, string $chapter): string
    {
        $value = (string) ($product->array_options['options_digirisk_' . $chapter] ?? '');
        if (empty($this->toText($value))) {
            $value = getDolGlobalString('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . strtoupper($chapter) . '_DESC');
        }

        return $value;
    }

    /**
     * Active risks of a product
     *
     * @param  Product       $product Product
     * @return ProductRisk[]          Risks
     */
    public function getProductRisks(Product $product): array
    {
        $productRisks = [];

        $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_product_risk';
        $sql .= ' WHERE fk_product = ' . ((int) $product->id) . ' AND status = 1 AND entity IN (' . getEntity('product') . ')';
        $sql .= ' ORDER BY rowid ASC';
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $productRisk = new ProductRisk($this->db);
                if ($productRisk->fetch((int) $obj->rowid) > 0) {
                    $productRisks[] = $productRisk;
                }
            }
            $this->db->free($resql);
        }

        return $productRisks;
    }

    /**
     * Photo of the sheet: the first image of the machine, else the photo of the GP/UT
     *
     * @param  DigiriskElement $element GP/UT
     * @param  Product|null    $product Machine
     * @return array                    'path' absolute path, 'source' product, element or empty
     */
    public function getPhoto(DigiriskElement $element, ?Product $product): array
    {
        global $conf;

        if (is_object($product)) {
            $productDir = $conf->product->multidir_output[$product->entity ?? $conf->entity] . '/' . get_exdir(0, 0, 0, 1, $product, 'product');
            $images     = dol_dir_list($productDir, 'files', 0, '\.(png|jpe?g)$', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 1);
            foreach ($images as $image) {
                if (is_file($image['fullname'])) {
                    return ['path' => $image['fullname'], 'source' => 'product'];
                }
            }
        }

        if (!empty($element->photo)) {
            $elementDir = $conf->digiriskdolibarr->multidir_output[$element->entity ?? $conf->entity] . '/' . $element->element_type . '/' . $element->ref;
            foreach ([$elementDir . '/' . $element->photo, $elementDir . '/thumbs/' . saturne_get_thumb_name($element->photo, 'medium')] as $photoPath) {
                if (is_file($photoPath) && preg_match('/\.(png|jpe?g)$/i', $photoPath)) {
                    return ['path' => $photoPath, 'source' => 'element'];
                }
            }
        }

        return ['path' => '', 'source' => ''];
    }

    /**
     * Emergency numbers of the security setup, empty when not set
     *
     * @return array 'samu', 'pompiers', 'allemergencies'
     */
    public function getEmergencyNumbers(): array
    {
        $numbers = ['samu' => '', 'pompiers' => '', 'allemergencies' => ''];

        $digiriskResources = new DigiriskResources($this->db);
        $resources         = $digiriskResources->fetchDigiriskResources();
        foreach (['SAMU' => 'samu', 'Pompiers' => 'pompiers', 'AllEmergencies' => 'allemergencies'] as $ref => $key) {
            $thirdpartyId = (int) ($resources[$ref]->id[0] ?? 0);
            if ($thirdpartyId > 0) {
                $thirdparty = new Societe($this->db);
                if ($thirdparty->fetch($thirdpartyId) > 0) {
                    $numbers[$key] = (string) $thirdparty->phone;
                }
            }
        }

        return $numbers;
    }

    /**
     * Add a risk sign category to the protective equipment of the sheet, once per category
     *
     * @param  array  $epiByKey Equipment by category, updated
     * @param  array  $category Risk sign category
     * @param  string $comment  Comment of the protection or description of the risk sign
     * @param  string $source   product or risksign
     * @return void
     */
    private function addEpi(array &$epiByKey, array $category, string $comment, string $source): void
    {
        $thumbnail = (string) ($category['name_thumbnail'] ?? '');
        if (strpos($thumbnail, 'OBLIGATION/') === 0) {
            $kind = 'mandatory';
        } elseif (strpos($thumbnail, 'INTERDICTION/') === 0) {
            $kind = 'forbidden';
        } else {
            return;
        }

        $key = (int) $category['position'];
        if (!isset($epiByKey[$key])) {
            $epiByKey[$key] = ['kind' => $kind, 'label' => $category['name'], 'picto' => $this->getRiskSignPictoPath($category), 'comments' => [], 'sources' => []];
        }
        $comment = trim($comment);
        if ($comment !== '' && !in_array($comment, $epiByKey[$key]['comments'])) {
            $epiByKey[$key]['comments'][] = $comment;
        }
        $epiByKey[$key]['sources'][$source] = $source;
    }

    /**
     * Absolute path of the picto of a risk sign category
     *
     * @param  array  $category Risk sign category
     * @return string           Path, empty when the file is missing
     */
    private function getRiskSignPictoPath(array $category): string
    {
        $path = dol_buildpath('/digiriskdolibarr/img/' . ($category['name_thumbnail'] ?? ''), 0);

        return (!empty($category['name_thumbnail']) && is_file($path)) ? $path : '';
    }

    /**
     * Name and picto of a danger category
     *
     * @param  array $dangerCategory Danger category from dangerCategories.json
     * @return array                 'name', 'picto' absolute path
     */
    private function formatDangerCategory(array $dangerCategory): array
    {
        $picto = '';
        if (!empty($dangerCategory['thumbnail_name'])) {
            $path = dol_buildpath('/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png', 0);
            if (is_file($path)) {
                $picto = $path;
            }
        }

        return ['name' => (string) ($dangerCategory['name'] ?? ''), 'picto' => $picto];
    }
}
