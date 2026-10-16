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
 */

/**
 * \file    class/api_digiriskdolibarr.class.php
 * \ingroup digiriskdolibarr
 * \brief   File for API management of DigiriskDolibarr.
 */

use Luracast\Restler\RestException;

require_once __DIR__ . '/../core/modules/modDigiriskDolibarr.class.php';

dol_include_once('/saturne/class/saturneapi.class.php');
dol_include_once('/saturne/lib/object.lib.php');
dol_include_once('/saturne/lib/medias.lib.php');

require_once __DIR__ . '/digiriskelement.class.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../lib/digiriskdolibarr_risk.lib.php';
require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';
require_once __DIR__ . '/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/digiriskresources.class.php';
require_once __DIR__ . '/evaluator.class.php';

/**
 * API class for DigiriskDolibarr
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DigiriskDolibarr extends SaturneApi
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var modDigiriskDolibarr $mod {@type modDigiriskDolibarr}
	 */
	public $mod;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->mod    = new modDigiriskdolibarr($this->db);
		$this->module = 'digiriskdolibarr';

		$this->entities = [
			'risks' => [
				'class'       => Risk::class,
				'fields'      => ['fk_element', 'category'],
				'permissions' => [
					'read'   => 'risk->read',
					'create' => 'risk->write',
					'write'  => 'risk->write',
					'delete' => 'risk->delete',
				],
			],
			'riskassessments' => [
				'class'       => RiskAssessment::class,
				'fields'      => ['fk_risk', 'cotation'],
				'permissions' => [
					'read'   => 'risk->read',
					'create' => 'risk->write',
					'write'  => 'risk->write',
					'delete' => 'risk->delete',
				],
			],
		];
	}

	/**
	 * Set conf entity
	 *
	 * @param  integer $entity Entity ID
	 *
	 */
	public function setConfEntity($entity)
	{
		global $conf;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($this->db, $entity);
	}

	/**
	 * Verify the API user is an internal user of the current entity and holds
	 * both the DigiRisk API right and the entity-specific action permission.
	 *
	 * @param string $entityKey Entity slug
	 * @param string $action    Action key ('read', 'create', 'write', 'delete')
	 *
	 * @throws RestException 403
	 */
	protected function _checkPermission(string $entityKey, string $action): void
	{
		global $conf;

		$user = DolibarrApiAccess::$user;
		if (!empty($user->socid) || ((int) $user->entity > 0 && (int) $user->entity !== (int) $conf->entity)) {
			throw new RestException(403, 'Access not allowed in current entity');
		}

		$hasApiRead  = !empty($user->rights->digiriskdolibarr->api->read);
		$hasApiWrite = !empty($user->rights->digiriskdolibarr->api->write);
		if ($action === 'read' && !$hasApiRead) {
			throw new RestException(403, 'Missing right digiriskdolibarr->api->read');
		}
		if ($action !== 'read' && !$hasApiRead && !$hasApiWrite) {
			throw new RestException(403, 'Missing right digiriskdolibarr->api->write');
		}

		parent::_checkPermission($entityKey, $action);
	}

	/**
	 * Require API access and risk write permission in the current entity.
	 *
	 * @return void
	 * @throws RestException 403
	 */
	private function checkRiskCreationAccess(): void
	{
		$this->_checkPermission('risks', 'write');
	}

	/**
	 * Decode, validate and write a base64-encoded photo into a target directory,
	 * generating Saturne thumbnails when requested.
	 *
	 * @param string $photoBase64   Base64 string (with or without data URI prefix)
	 * @param string $targetDir     Destination directory on disk
	 * @param string $preferredName Optional filename (sanitized when provided)
	 * @param bool   $makeThumbs    Whether to generate Saturne thumbnails
	 *
	 * @return string Saved filename
	 *
	 * @throws RestException 400, 500
	 */
	private function saveValidatedPhotoToDir(string $photoBase64, string $targetDir, string $preferredName = '', bool $makeThumbs = true): string
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

		if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.*)$/s', $photoBase64, $matches)) {
			$photoBase64 = $matches[1];
		}

		if (dol_strlen($photoBase64) === 0 || strlen($photoBase64) > 6990508) {
			throw new RestException(400, 'Photo exceeds 5 MiB');
		}

		$photo = base64_decode($photoBase64, true);
		$image = $photo !== false ? @getimagesizefromstring($photo) : false;
		if (
			$photo === false ||
			strlen($photo) > 5 * 1024 * 1024 ||
			!$image ||
			!in_array($image[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true) ||
			$image[0] > 12000 ||
			$image[1] > 12000 ||
			$image[0] * $image[1] > 40000000
		) {
			throw new RestException(400, 'Expected a valid JPEG, PNG, GIF or WEBP photo, at most 5 MiB and 40 megapixels');
		}

		$extensionMap = [
			IMAGETYPE_JPEG => '.jpg',
			IMAGETYPE_PNG  => '.png',
			IMAGETYPE_GIF  => '.gif',
			IMAGETYPE_WEBP => '.webp',
		];
		$extension = $extensionMap[$image[2]] ?? '.jpg';

		if ($preferredName !== '') {
			$sanitized = dol_sanitizeFileName(dol_basename($preferredName));
			$baseName  = pathinfo($sanitized, PATHINFO_FILENAME);
			$filename  = ($baseName !== '' ? $baseName : 'api-' . bin2hex(random_bytes(16))) . $extension;
		} else {
			$filename = 'api-' . bin2hex(random_bytes(16)) . $extension;
		}

		if (!dol_is_dir($targetDir)) {
			dol_mkdir($targetDir);
		}

		$photoPath = $targetDir . '/' . $filename;
		if (file_put_contents($photoPath, $photo, LOCK_EX) !== strlen($photo)) {
			throw new RestException(500, 'Photo could not be saved');
		}

		if ($makeThumbs && image_format_supported($filename) >= 0 && function_exists('saturne_vignette')) {
			foreach (['mini', 'small', 'medium', 'large'] as $size) {
				saturne_vignette(
					$photoPath,
					getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size)),
					getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size)),
					'_' . $size
				);
			}
		}

		return $filename;
	}

	/**
	 * Resolve and validate the temporary work directory for risk assessment photos.
	 *
	 * @param string $riskRef Optional existing risk reference (e.g. RK1)
	 *
	 * @return array{0: string, 1: string} [absolute workDir, relative subDir]
	 *
	 * @throws RestException 400, 500
	 */
	private function resolveTempRiskPhotoDir(string $riskRef = ''): array
	{
		global $conf;

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? ($conf->digiriskdolibarr->dir_output ?? '');
		if ($root === '') {
			throw new RestException(500, 'DigiRisk output directory is not configured');
		}

		$subDir = 'riskassessment/tmp/RA0';
		if ($riskRef !== '') {
			if (!preg_match('/^[A-Za-z0-9_-]+$/D', $riskRef)) {
				throw new RestException(400, 'Invalid risk_ref format');
			}
			$subDir .= '/' . $riskRef;
		}

		return [$root . '/' . $subDir, $subDir];
	}

	/**
	 * Strip internal SaturneObject properties before serialisation while preserving
	 * Risk::$fk_element and keeping cached instances intact.
	 *
	 * @param object $object Object to clean
	 *
	 * @return object
	 */
	protected function _cleanObjectDatas($object)
	{
		$cloned    = clone $object;
		$fkElement = ($object instanceof Risk || (isset($object->fk_element) && is_numeric($object->fk_element))) ? $object->fk_element : null;
		$cleaned   = parent::_cleanObjectDatas($cloned);
		if ($fkElement !== null) {
			$cleaned->fk_element = (int) $fkElement;
		}

		return $cleaned;
	}

	// =================================================================
	// RISKS & TEMPORARY PHOTOS API ROUTES
	// =================================================================

	/**
	 * List active organization elements and native danger categories for risk creation.
	 *
	 * @return array
	 *
	 * @url GET risks/creation-options
	 *
	 * @throws RestException 403, 500
	 */
	public function getRiskCreationOptions()
	{
		global $conf;

		$user = DolibarrApiAccess::$user;
		if (!empty($user->rights->digiriskdolibarr->risk->read)) {
			$this->_checkPermission('risks', 'read');
		} else {
			$this->_checkPermission('risks', 'write');
		}

		$sqlQuery  = 'SELECT rowid, ref, label, element_type, fk_parent FROM ' . $this->db->prefix() . 'digiriskdolibarr_digiriskelement';
		$sqlQuery .= ' WHERE entity = ' . ((int) $conf->entity) . ' AND status = 1 ORDER BY ref';
		$result    = $this->db->query($sqlQuery);
		if (!$result) {
			throw new RestException(500, 'Unable to load organization elements');
		}

		$elements = [];
		while ($row = $this->db->fetch_object($result)) {
			$elements[] = [
				'id'        => (int) $row->rowid,
				'ref'       => $row->ref,
				'label'     => $row->label,
				'type'      => $row->element_type,
				'fk_parent' => (int) $row->fk_parent,
			];
		}

		return [
			'entity'          => (int) $conf->entity,
			'elements'        => $elements,
			'categories'      => Risk::getDangerCategories('risk'),
			'sub_categories'  => Risk::getDangerSubCategories(),
			'method'          => 'simple',
			'cotations'       => [0, 48, 51, 100],
			'photo_max_bytes' => 5 * 1024 * 1024,
			'temp_photo_dir'  => 'riskassessment/tmp/RA0',
		];
	}

	/**
	 * Upload a temporary photo while a risk (or a new risk assessment) is not yet created.
	 * The photo is stored in riskassessment/tmp/RA0 (or riskassessment/tmp/RA0/{risk_ref})
	 * with its Saturne thumbnails and automatically moved when POST /risks or POST /riskassessments is called.
	 *
	 * @param array $request_data JSON body: photo_base64, optional filename, optional risk_ref {@from body}
	 *
	 * @return array
	 *
	 * @url POST risks/temp-photo
	 *
	 * @throws RestException 400, 403, 500
	 */
	public function uploadTempRiskPhoto($request_data = null)
	{
		$this->_checkPermission('risks', 'write');

		if (!is_array($request_data) || empty($request_data['photo_base64']) || !is_string($request_data['photo_base64'])) {
			throw new RestException(400, 'photo_base64 is required');
		}

		$riskRef       = isset($request_data['risk_ref']) && is_string($request_data['risk_ref']) ? trim($request_data['risk_ref']) : '';
		$preferredName = isset($request_data['filename']) && is_string($request_data['filename']) ? trim($request_data['filename']) : '';

		[$workDir, $subDir] = $this->resolveTempRiskPhotoDir($riskRef);
		$filename           = $this->saveValidatedPhotoToDir($request_data['photo_base64'], $workDir, $preferredName, true);

		$pendingFiles = [];
		foreach (dol_dir_list($workDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC) as $file) {
			$pendingFiles[] = $file['name'];
		}

		return [
			'filename' => $filename,
			'sub_dir'  => $subDir,
			'files'    => $pendingFiles,
		];
	}

	/**
	 * List temporary photos waiting in riskassessment/tmp/RA0 before risk creation.
	 *
	 * @param string $risk_ref Optional existing risk reference
	 *
	 * @return array
	 *
	 * @url GET risks/temp-photos
	 *
	 * @throws RestException 400, 403, 500
	 */
	public function listTempRiskPhotos($risk_ref = '')
	{
		$user = DolibarrApiAccess::$user;
		if (!empty($user->rights->digiriskdolibarr->risk->read)) {
			$this->_checkPermission('risks', 'read');
		} else {
			$this->_checkPermission('risks', 'write');
		}

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		[$workDir, $subDir] = $this->resolveTempRiskPhotoDir(is_string($risk_ref) ? trim($risk_ref) : '');

		$files = [];
		if (dol_is_dir($workDir)) {
			foreach (dol_dir_list($workDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC) as $file) {
				$files[] = [
					'name' => $file['name'],
					'size' => (int) ($file['size'] ?? 0),
					'date' => (int) ($file['date'] ?? 0),
				];
			}
		}

		return [
			'sub_dir' => $subDir,
			'files'   => $files,
		];
	}

	/**
	 * Delete a temporary photo waiting in riskassessment/tmp/RA0 before risk creation.
	 *
	 * @param string $filename Photo filename to delete
	 * @param string $risk_ref Optional existing risk reference
	 *
	 * @return array
	 *
	 * @url DELETE risks/temp-photo/{filename}
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function deleteTempRiskPhoto($filename, $risk_ref = '')
	{
		$this->_checkPermission('risks', 'write');

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$sanitizedName = dol_sanitizeFileName(dol_basename((string) $filename));
		if ($sanitizedName === '' || $sanitizedName !== $filename) {
			throw new RestException(400, 'Invalid filename');
		}

		[$workDir, $subDir] = $this->resolveTempRiskPhotoDir(is_string($risk_ref) ? trim($risk_ref) : '');
		$filePath           = $workDir . '/' . $sanitizedName;

		if (!dol_is_file($filePath)) {
			throw new RestException(404, 'Temporary photo not found');
		}

		dol_delete_file($filePath);

		$pathInfo = pathinfo($sanitizedName);
		$baseName = $pathInfo['filename'] ?? '';
		$ext      = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
		foreach (['mini', 'small', 'medium', 'large'] as $size) {
			$thumbPath = $workDir . '/thumbs/' . $baseName . '_' . $size . $ext;
			if (dol_is_file($thumbPath)) {
				dol_delete_file($thumbPath);
			}
		}

		$remaining = dol_is_dir($workDir) ? dol_dir_list($workDir, 'files', 0, '', '(\.meta|_preview.*\.png)$') : [];
		if (empty($remaining)) {
			if (dol_is_dir($workDir . '/thumbs')) {
				dol_delete_dir($workDir . '/thumbs', 1);
			}
			if (dol_is_dir($workDir)) {
				dol_delete_dir($workDir, 1);
			}
		}

		return [
			'success' => [
				'code'    => 200,
				'message' => 'Temporary photo deleted',
				'sub_dir' => $subDir,
			],
		];
	}

	/**
	 * Get one risk by ID (including its active risk assessment and assessment history).
	 *
	 * @param int $id Risk ID
	 *
	 * @return object Cleaned risk object
	 *
	 * @url GET risks/{id}
	 *
	 * @throws RestException 403, 404
	 */
	public function getRisk($id)
	{
		global $conf;

		$risk = $this->_get('risks', $id);
		if ($risk->entity !== null && (int) $risk->entity !== (int) $conf->entity) {
			throw new RestException(404, 'risks not found');
		}

		$assessment  = new RiskAssessment($this->db);
		$evaluations = $assessment->fetchFromParent((int) $risk->id, 0, 'DESC');
		$cleanedEvals = [];
		$activeEval   = null;
		if (is_array($evaluations)) {
			foreach ($evaluations as $eval) {
				$cleaned = $this->_cleanObjectDatas($eval);
				$cleanedEvals[] = $cleaned;
				if ((int) $eval->status === RiskAssessment::STATUS_VALIDATED && $activeEval === null) {
					$activeEval = $cleaned;
				}
			}
		}
		$risk->last_evaluation = $activeEval;
		$risk->evaluations     = $cleanedEvals;

		return $risk;
	}

	/**
	 * List risks in the current entity.
	 *
	 * @param string $sortfield  Sort field
	 * @param string $sortorder  Sort order (ASC or DESC)
	 * @param int    $limit      Page size
	 * @param int    $page       Page index, starting at 0
	 * @param string $sqlfilters Universal Search filter
	 *
	 * @return array
	 *
	 * @url GET risks
	 *
	 * @throws RestException 400, 403, 503
	 */
	public function listRisks($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		return $this->_index('risks', $sortfield, $sortorder, $limit, $page, $sqlfilters);
	}

	/**
	 * Create a risk and its assessment, optionally using a base64 photo or photos pre-uploaded
	 * into the temporary work directory (riskassessment/tmp/RA0).
	 *
	 * @param array $request_data JSON body: fk_element, category, description, cotation, optional method, comment, photo, photo_base64 {@from body}
	 *
	 * @return array
	 *
	 * @url POST risks
	 *
	 * @throws RestException 400, 403, 404, 409, 500
	 */
	public function createRisk($request_data = null)
	{
		return $this->createRiskWithPhoto($request_data);
	}

	/**
	 * Create a risk and its simple or advanced assessment, optionally with one JPEG/PNG/GIF/WEBP photo
	 * or moving pre-uploaded temporary photos from riskassessment/tmp/RA0.
	 *
	 * @param array $request_data JSON body: fk_element, category, description, cotation, optional photo_base64 {@from body}
	 *
	 * @return array
	 *
	 * @url POST risks/create-with-photo
	 *
	 * @throws RestException 400, 403, 404, 409, 500
	 */
	public function createRiskWithPhoto($request_data = null)
	{
		global $conf;

		$this->checkRiskCreationAccess();
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$data = $request_data;
		if (!is_array($data)) {
			throw new RestException(400, 'Expected a JSON object');
		}

		$cotationProvided = array_key_exists('cotation', $data);
		$requiredInts     = $cotationProvided ? ['fk_element', 'category', 'cotation'] : ['fk_element', 'category'];
		foreach ($requiredInts as $field) {
			if (!isset($data[$field]) || !is_int($data[$field])) {
				throw new RestException(400, $field . ' must be an integer');
			}
		}

		$method = isset($data['method']) && is_string($data['method']) ? $data['method'] : 'simple';
		if (!in_array($method, ['simple', 'advanced'], true)) {
			throw new RestException(400, 'Invalid evaluation method (simple or advanced expected)');
		}

		$cotation = $cotationProvided ? $data['cotation'] : 0;
		if ($data['fk_element'] <= 0) {
			throw new RestException(400, 'Invalid element');
		}
		if ($method === 'simple' && !in_array($cotation, [0, 48, 51, 100], true)) {
			throw new RestException(400, 'Invalid element or simple score (0, 48, 51, 100 expected)');
		}
		if ($method === 'advanced' && ($cotation < 0 || $cotation > 100)) {
			throw new RestException(400, 'Invalid advanced score (0 to 100 expected)');
		}

		if (
			!isset($data['description']) ||
			!is_string($data['description']) ||
			trim($data['description']) === '' ||
			strlen($data['description']) > 20000
		) {
			throw new RestException(400, 'A description of 1 to 20000 bytes is required');
		}

		$riskType   = isset($data['type']) && $data['type'] === 'riskenvironmental' ? 'riskenvironmental' : 'risk';
		$categories = array_column(Risk::getDangerCategories($riskType), 'position');
		if (!in_array($data['category'], array_map('intval', $categories), true)) {
			throw new RestException(400, 'Unknown native risk category');
		}

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch($data['fk_element'], '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			($element->entity !== null && (int) $element->entity !== (int) $conf->entity) ||
			(int) $element->status !== 1
		) {
			throw new RestException(404, 'Active organization element not found in current entity');
		}

		$photo     = null;
		$extension = '';
		if (isset($data['photo_base64'])) {
			if (!is_string($data['photo_base64'])) {
				throw new RestException(400, 'photo_base64 must be a string');
			}
			$rawBase64 = $data['photo_base64'];
			if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.*)$/s', $rawBase64, $matches)) {
				$rawBase64 = $matches[1];
			}
			if (strlen($rawBase64) > 6990508) {
				throw new RestException(400, 'Photo exceeds 5 MiB');
			}
			$photo = base64_decode($rawBase64, true);
			$image = $photo !== false ? @getimagesizefromstring($photo) : false;
			if (
				$photo === false ||
				strlen($photo) > 5 * 1024 * 1024 ||
				!$image ||
				!in_array($image[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) ||
				$image[0] > 12000 ||
				$image[1] > 12000 ||
				$image[0] * $image[1] > 40000000
			) {
				throw new RestException(400, 'Expected a valid JPEG or PNG photo, at most 5 MiB and 40 megapixels');
			}
			$extension = $image[2] === IMAGETYPE_JPEG ? '.jpg' : '.png';
		}

		$duProjectId = $riskType === 'riskenvironmental'
			? (int) ($conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT ?? 0)
			: (int) ($conf->global->DIGIRISKDOLIBARR_DU_PROJECT ?? 0);
		if ($duProjectId <= 0) {
			throw new RestException(409, 'DigiRisk DU project is not configured');
		}

		$risk       = new Risk($this->db);
		$assessment = new RiskAssessment($this->db);
		$modules    = [
			'riskanalysis/' . $risk->element       => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON ?? 'mod_risk_standard',
			'riskanalysis/' . $assessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON ?? 'mod_riskassessment_standard',
		];
		list($riskNumbering, $assessmentNumbering) = saturne_require_objects_mod($modules, 'digiriskdolibarr');

		$photoPath     = null;
		$assessmentDir = null;
		$this->db->begin();
		try {
			$risk->entity      = (int) $conf->entity;
			$risk->type        = $riskType;
			$risk->fk_element  = $data['fk_element'];
			$risk->category    = $data['category'];
			if (isset($data['sub_category']) && is_numeric($data['sub_category'])) {
				$risk->sub_category = (int) $data['sub_category'];
			}
			$risk->description = dol_escape_htmltag($data['description']);
			$risk->status      = 1;
			$risk->fk_projet   = $duProjectId;
			$risk->ref         = $riskNumbering->getNextValue($risk);
			if ($risk->create(DolibarrApiAccess::$user) <= 0) {
				throw new RuntimeException('Risk creation failed');
			}

			$assessment->entity              = (int) $conf->entity;
			$assessment->fk_risk             = $risk->id;
			$assessment->ref                 = $assessmentNumbering->getNextValue($assessment);
			$assessment->method              = $method;
			$assessment->cotation            = $cotation;
			$assessment->comment             = isset($data['comment']) && is_string($data['comment'])
				? dol_escape_htmltag($data['comment'])
				: $risk->description;
			$assessment->status              = 1;
			$assessment->date_riskassessment = isset($data['date_riskassessment']) && is_numeric($data['date_riskassessment'])
				? (int) $data['date_riskassessment']
				: dol_now();

			if ($method === 'advanced') {
				foreach (['gravite', 'exposition', 'occurrence', 'formation', 'protection'] as $criterion) {
					if (isset($data[$criterion]) && is_numeric($data[$criterion])) {
						$assessment->{$criterion} = (int) $data[$criterion];
					}
				}
			}

			if (!empty($data['photo']) && is_string($data['photo'])) {
				$assessment->photo = dol_sanitizeFileName(dol_basename($data['photo']));
			}

			$root    = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '';
			$workDir = $root !== '' ? $root . '/riskassessment/tmp/RA0' : '';

			if ($photo !== null) {
				if (!$root || !preg_match('/^[A-Za-z0-9_-]+$/D', $assessment->ref)) {
					throw new RuntimeException('Invalid assessment media directory');
				}
				$assessmentDir = $root . '/riskassessment/' . $assessment->ref;
				dol_mkdir($assessmentDir);
				$assessment->photo = 'api-' . bin2hex(random_bytes(16)) . $extension;
				$photoPath         = $assessmentDir . '/' . $assessment->photo;
				if (file_put_contents($photoPath, $photo, LOCK_EX) !== strlen($photo)) {
					throw new RuntimeException('Photo could not be saved');
				}
				if (function_exists('image_format_supported') && function_exists('saturne_vignette') && image_format_supported($assessment->photo) >= 0) {
					foreach (['mini', 'small', 'medium', 'large'] as $size) {
						saturne_vignette(
							$photoPath,
							getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size)),
							getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size)),
							'_' . $size
						);
					}
				}
			}

			if ($workDir !== '' && function_exists('dol_is_dir') && dol_is_dir($workDir) && function_exists('digiriskRiskAssessmentMovePhotos')) {
				$assessmentDir = $root . '/riskassessment/' . $assessment->ref;
				digiriskRiskAssessmentMovePhotos($workDir, $assessment);
			}

			if ($assessment->create(DolibarrApiAccess::$user, 0) <= 0) {
				throw new RuntimeException('Assessment creation failed');
			}
			if ($this->db->commit() <= 0) {
				throw new RuntimeException('Commit failed');
			}
		} catch (Throwable $error) {
			$this->db->rollback();
			if ($photoPath !== null && is_file($photoPath)) {
				dol_delete_file($photoPath);
			}
			if ($assessmentDir !== null && function_exists('dol_delete_dir_recursive') && is_dir($assessmentDir)) {
				dol_delete_dir_recursive($assessmentDir);
			}
			dol_syslog(__METHOD__ . ': ' . $error->getMessage(), LOG_ERR);
			throw new RestException(500, 'Risk creation failed; consult server logs');
		}

		return [
			'id'             => (int) $risk->id,
			'risk_id'        => (int) $risk->id,
			'risk_ref'       => $risk->ref,
			'assessment_id'  => (int) $assessment->id,
			'assessment_ref' => $assessment->ref,
			'photo'          => $assessment->photo ?? '',
			'url'            => dol_buildpath('/digiriskdolibarr/view/risk/risk_card.php', 2) . '?id=' . (int) $risk->id,
		];
	}

	/**
	 * Update an existing risk.
	 *
	 * @param int   $id           Risk ID
	 * @param array $request_data Fields to update (category, sub_category, description, fk_element) {@from body}
	 *
	 * @return object Cleaned, freshly-fetched risk
	 *
	 * @url PUT risks/{id}
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function updateRisk($id, $request_data = null)
	{
		global $conf;

		$this->_checkPermission('risks', 'write');

		$risk = $this->_getObject('risks');
		if ($risk->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($risk->entity !== null && (int) $risk->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'risks not found');
		}

		$data = (array) $request_data;
		if (isset($data['fk_element'])) {
			$element = new DigiriskElement($this->db);
			if (
				(int) $data['fk_element'] <= 0 ||
				$element->fetch((int) $data['fk_element'], '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
				($element->entity !== null && (int) $element->entity !== (int) $conf->entity) ||
				(int) $element->status !== 1
			) {
				throw new RestException(404, 'Active organization element not found in current entity');
			}
		}

		if (isset($data['category'])) {
			$riskType   = !empty($risk->type) ? $risk->type : 'risk';
			$categories = array_column(Risk::getDangerCategories($riskType), 'position');
			if (!in_array((int) $data['category'], array_map('intval', $categories), true)) {
				throw new RestException(400, 'Unknown native risk category');
			}
		}

		if (isset($data['description'])) {
			if (!is_string($data['description']) || strlen($data['description']) > 20000) {
				throw new RestException(400, 'Description exceeds maximum length');
			}
			$data['description'] = dol_escape_htmltag($data['description']);
		}

		return $this->_put('risks', $id, $data);
	}

	/**
	 * Delete a risk and all its associated risk assessments and photos.
	 *
	 * @param int $id Risk ID
	 *
	 * @return array
	 *
	 * @url DELETE risks/{id}
	 *
	 * @throws RestException 403, 404, 500
	 */
	public function deleteRisk($id)
	{
		global $conf;

		$this->_checkPermission('risks', 'delete');
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$risk = new Risk($this->db);
		if ($risk->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($risk->entity !== null && (int) $risk->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'risks not found');
		}

		$evaluation     = new RiskAssessment($this->db);
		$assessmentList = $evaluation->fetchFromParent((int) $risk->id, 0);
		$root           = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '';

		if (is_array($assessmentList) && !empty($assessmentList)) {
			foreach ($assessmentList as $riskAssessment) {
				if ($root !== '' && !empty($riskAssessment->ref)) {
					$photoDir = $root . '/riskassessment/' . $riskAssessment->ref;
					if (dol_is_dir($photoDir)) {
						dol_delete_dir_recursive($photoDir);
					}
				}
				$riskAssessment->delete(DolibarrApiAccess::$user, 1, false);
			}
		}

		if ($risk->delete(DolibarrApiAccess::$user, 0, false) <= 0) {
			throw new RestException(500, 'Error deleting risks', $this->_collectErrors($risk));
		}

		return [
			'success' => [
				'code'    => 200,
				'message' => 'Risks deleted',
			],
		];
	}

	/**
	 * Validate a risk.
	 *
	 * @param int $id Risk ID
	 *
	 * @return object Cleaned risk
	 *
	 * @url POST risks/{id}/validate
	 */
	public function validateRisk($id)
	{
		return $this->_setStatus('risks', $id, 'validate');
	}

	/**
	 * Move a risk back to draft.
	 *
	 * @param int $id Risk ID
	 *
	 * @return object Cleaned risk
	 *
	 * @url POST risks/{id}/setdraft
	 */
	public function setDraftRisk($id)
	{
		return $this->_setStatus('risks', $id, 'setDraft');
	}

	/**
	 * Lock a risk.
	 *
	 * @param int $id Risk ID
	 *
	 * @return object Cleaned risk
	 *
	 * @url POST risks/{id}/setlocked
	 */
	public function setLockedRisk($id)
	{
		return $this->_setStatus('risks', $id, 'setLocked');
	}

	/**
	 * Archive a risk.
	 *
	 * @param int $id Risk ID
	 *
	 * @return object Cleaned risk
	 *
	 * @url POST risks/{id}/setarchived
	 */
	public function setArchivedRisk($id)
	{
		return $this->_setStatus('risks', $id, 'setArchived');
	}

	// =================================================================
	// RISK ASSESSMENTS API ROUTES
	// =================================================================

	/**
	 * Get one risk assessment by ID.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url GET riskassessments/{id}
	 *
	 * @throws RestException 403, 404
	 */
	public function getRiskAssessment($id)
	{
		global $conf;

		$assessment = $this->_get('riskassessments', $id);
		if ($assessment->entity !== null && (int) $assessment->entity !== (int) $conf->entity) {
			throw new RestException(404, 'riskassessments not found');
		}

		return $assessment;
	}

	/**
	 * List risk assessments in the current entity.
	 *
	 * @param string $sortfield  Sort field
	 * @param string $sortorder  Sort order (ASC or DESC)
	 * @param int    $limit      Page size
	 * @param int    $page       Page index, starting at 0
	 * @param string $sqlfilters Universal Search filter
	 *
	 * @return array
	 *
	 * @url GET riskassessments
	 *
	 * @throws RestException 400, 403, 503
	 */
	public function listRiskAssessments($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		return $this->_index('riskassessments', $sortfield, $sortorder, $limit, $page, $sqlfilters);
	}

	/**
	 * Create a risk assessment on an existing risk, moving any temporary photos from
	 * riskassessment/tmp/RA0/{risk_ref} or riskassessment/tmp/RA0, or saving photo_base64.
	 *
	 * @param array $request_data Risk assessment data (fk_risk, cotation, optional method, comment, photo_base64) {@from body}
	 *
	 * @return int New risk assessment ID
	 *
	 * @url POST riskassessments
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function createRiskAssessment($request_data = null)
	{
		global $conf;

		$this->_checkPermission('riskassessments', 'create');
		$this->_validate('riskassessments', $request_data);

		$data = (array) $request_data;
		$risk = new Risk($this->db);
		if ((int) $data['fk_risk'] <= 0 || $risk->fetch((int) $data['fk_risk'], '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($risk->entity !== null && (int) $risk->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'Parent risk not found in current entity');
		}

		$assessment = new RiskAssessment($this->db);
		$modules    = [
			'riskanalysis/' . $assessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON ?? 'mod_riskassessment_standard',
		];
		list($assessmentNumbering) = saturne_require_objects_mod($modules, 'digiriskdolibarr');

		$photoBase64 = isset($data['photo_base64']) && is_string($data['photo_base64']) ? $data['photo_base64'] : '';
		unset($data['photo_base64']);

		$this->_assignRequestData($assessment, $data);
		$assessment->entity              = (int) $conf->entity;
		$assessment->fk_risk             = (int) $risk->id;
		$assessment->ref                 = !empty($assessment->ref) ? $assessment->ref : $assessmentNumbering->getNextValue($assessment);
		$assessment->method              = !empty($assessment->method) ? $assessment->method : 'simple';
		$assessment->status              = isset($data['status']) ? (int) $data['status'] : RiskAssessment::STATUS_VALIDATED;
		$assessment->date_riskassessment = !empty($assessment->date_riskassessment) ? $assessment->date_riskassessment : dol_now();

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '';
		if ($photoBase64 !== '' && $root !== '') {
			$targetDir         = $root . '/riskassessment/' . $assessment->ref;
			$assessment->photo = $this->saveValidatedPhotoToDir($photoBase64, $targetDir, '', true);
		}

		if ($root !== '' && function_exists('digiriskRiskAssessmentMovePhotos')) {
			$riskTempDir = $root . '/riskassessment/tmp/RA0/' . $risk->ref;
			$baseTempDir = $root . '/riskassessment/tmp/RA0';
			if (dol_is_dir($riskTempDir)) {
				digiriskRiskAssessmentMovePhotos($riskTempDir, $assessment);
			} elseif (dol_is_dir($baseTempDir)) {
				digiriskRiskAssessmentMovePhotos($baseTempDir, $assessment);
			}
		}

		if ($assessment->create(DolibarrApiAccess::$user, 0) <= 0) {
			throw new RestException(500, 'Error creating riskassessments', $this->_collectErrors($assessment));
		}

		return (int) $assessment->id;
	}

	/**
	 * Update a risk assessment (and optionally upload a new photo via photo_base64).
	 *
	 * @param int   $id           Risk assessment ID
	 * @param array $request_data Fields to update {@from body}
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url PUT riskassessments/{id}
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function updateRiskAssessment($id, $request_data = null)
	{
		global $conf;

		$this->_checkPermission('riskassessments', 'write');

		$assessment = $this->_getObject('riskassessments');
		if ($assessment->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($assessment->entity !== null && (int) $assessment->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'riskassessments not found');
		}

		$data        = (array) $request_data;
		$photoBase64 = isset($data['photo_base64']) && is_string($data['photo_base64']) ? $data['photo_base64'] : '';
		unset($data['photo_base64']);

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '';
		if ($photoBase64 !== '' && $root !== '') {
			$targetDir      = $root . '/riskassessment/' . $assessment->ref;
			$data['photo']  = $this->saveValidatedPhotoToDir($photoBase64, $targetDir, '', true);
		}

		return $this->_put('riskassessments', $id, $data);
	}

	/**
	 * Delete a risk assessment, its photo folder, and restore the previous assessment status.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return array
	 *
	 * @url DELETE riskassessments/{id}
	 *
	 * @throws RestException 403, 404, 500
	 */
	public function deleteRiskAssessment($id)
	{
		global $conf;

		$this->_checkPermission('riskassessments', 'delete');
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$assessment = new RiskAssessment($this->db);
		if ($assessment->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($assessment->entity !== null && (int) $assessment->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'riskassessments not found');
		}

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '';
		if ($root !== '' && !empty($assessment->ref)) {
			$photoDir = $root . '/riskassessment/' . $assessment->ref;
			if (dol_is_dir($photoDir)) {
				dol_delete_dir_recursive($photoDir);
			}
		}

		$riskId = (int) $assessment->fk_risk;
		if ($assessment->delete(DolibarrApiAccess::$user, 0, false) <= 0) {
			throw new RestException(500, 'Error deleting riskassessments', $this->_collectErrors($assessment));
		}

		$assessment->updatePreviousRiskAssessmentStatus(DolibarrApiAccess::$user, $riskId);

		return [
			'success' => [
				'code'    => 200,
				'message' => 'Riskassessments deleted',
			],
		];
	}

	/**
	 * Validate a risk assessment.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url POST riskassessments/{id}/validate
	 */
	public function validateRiskAssessment($id)
	{
		return $this->_setStatus('riskassessments', $id, 'validate');
	}

	/**
	 * Move a risk assessment back to draft.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url POST riskassessments/{id}/setdraft
	 */
	public function setDraftRiskAssessment($id)
	{
		return $this->_setStatus('riskassessments', $id, 'setDraft');
	}

	/**
	 * Lock a risk assessment.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url POST riskassessments/{id}/setlocked
	 */
	public function setLockedRiskAssessment($id)
	{
		return $this->_setStatus('riskassessments', $id, 'setLocked');
	}

	/**
	 * Archive a risk assessment.
	 *
	 * @param int $id Risk assessment ID
	 *
	 * @return object Cleaned risk assessment
	 *
	 * @url POST riskassessments/{id}/setarchived
	 */
	public function setArchivedRiskAssessment($id)
	{
		return $this->_setStatus('riskassessments', $id, 'setArchived');
	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order information
	 *
	 * @return    int data without useless information
	 *
	 * @throws Exception
	 */
	public function enableModule()
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/modules/modECM.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modProjet.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modSociete.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modTicket.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modCategorie.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modFckeditor.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modApi.class.php';

		$modEcm       = new modECM($this->db);
		$modProjet    = new modProjet($this->db);
		$modSociete   = new modSociete($this->db);
		$modTicket    = new modTicket($this->db);
		$modCategorie = new modCategorie($this->db);
		$modFckeditor = new modFckeditor($this->db);
		$modApi       = new modApi($this->db);

		$modEcm->init();
		$modProjet->init();
		$modSociete->init();
		$modTicket->init();
		$modCategorie->init();
		$modFckeditor->init();
		$modApi->init();
		$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

		return $this->mod->init();
	}

	/**
	 * Get properties of an order object by ref
	 *
	 * Return an array with order information
	 *
	 * @return    int data without useless information
	 *
	 * @url GET    disableModule
	 *
	 */
	public function disableModule()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return $this->mod->remove();
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return    string data without useless information
	 *
	 * @url GET    getFilesVersion
	 *
	 */
	public function getFilesVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return $this->mod->version;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getActiveVersion
	 *
	 */
	public function getActiveVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getLatestVersion
	 *
	 */
	public function getLatestVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|false data without useless information
	 *
	 * @url GET    uploadNewModule
	 *
	 */
	public function uploadNewModule()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return exec('cd ../custom/digiriskdolibarr/shell/pull && bash update_version.sh');
	}

	// BEGIN ALL API ROUTE FOR DASHBOARD INFO

	/**
	 * Get dashboard info risks
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info risks
	 *
	 * @url    GET                   dashboard/getDashBoardRisks
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardRisks($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->risk->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$risk = new Risk($this->db);
		return $risk->load_dashboard();
	}

	/**
	 * Get dashboard info tasks
	 *
	 * @param integer $entity Entity ID
	 *
	 * @return array                 All data for dashboard info tasks
	 *
	 * @url    GET                   dashboard/getDashBoardInfoTasks
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoTasks($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->projet->lire || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digirisktask = new SaturneTask($this->db);
		return $digirisktask->load_dashboard();
	}

	/**
	 * Get dashboard info riskassessmentdocument
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info riskassessmentdocument
	 *
	 * @url    GET                   dashboard/getDashBoardInfoRiskAssessmentDocument
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoRiskAssessmentDocument($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->load_dashboard();
	}

	/**
	 * Get dashboard info accidents
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info accidents
	 *
	 * @url    GET                   dashboard/getDashBoardInfoAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->load_dashboard();
	}

	/**
	 * Get dashboard info digiriskresources
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info digiriskresources
	 *
	 * @url    GET                   dashboard/getDashBoardInfoDigiriskResources
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoDigiriskResources($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digiriskresources = new DigiriskResources($this->db);
		return $digiriskresources->load_dashboard();
	}

	/**
	 * Get dashboard info evaluators
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info evaluators
	 *
	 * @url    GET                   dashboard/getDashBoardInfoEvaluators
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoEvaluators($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->load_dashboard();
	}

	// END ALL API ROUTE FOR DASHBOARD INFO

	// BEGIN ALL UNIQUE OBJECT API ROUTE

	// RISK

	/**
	 * Get risks by cotation.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for risks by cotation.
	 *
	 * @url    GET                   risk/getRisksByCotation
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getRisksByCotation($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->risk->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf, $db;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($db, $entity);

		$risk = new Risk($db);
		return $risk->getRisksByCotation()['data'];
	}

	// TASK

	/**
	 * Get tasks by progress.
	 *
	 * @param integer $entity Entity ID
	 *
	 * @return array                 All data tasks by progress.
	 *
	 * @url    GET                   task/getTasksByProgress
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getTasksByProgress($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->projet->lire || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digirisktask = new SaturneTask($this->db);
		return $digirisktask->getTasksByProgress()['data'];
	}

	// RISKASSESSMENTDOCUMENT

	/**
	 * Get riskassessmentdocument last generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for riskassessmentdocument last generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getLastGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getLastGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getLastGenerateDate();
	}

	/**
	 * Get riskassessmentdocument next generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for riskassessmentdocument next generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNextGenerateDate();
	}

	/**
	 * Get number days before next riskassessmentdocument generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days before next riskassessmentdocument generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNbDaysBeforeNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysBeforeNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNbDaysBeforeNextGenerateDate();
	}

	/**
	 * Get number days after next riskassessmentdocument generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days before next riskassessmentdocument generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNbDaysAfterNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysAfterNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNbDaysAfterNextGenerateDate();
	}

	// ACCIDENT

	/**
	 * Get number days without accident.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days without accident.
	 *
	 * @url    GET                   accident/getNbDaysWithoutAccident
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysWithoutAccident($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbDaysWithoutAccident();
	}

	/**
	 * Get number accidents by type.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accidents by type.
	 *
	 * @url    GET                   accident/getNbAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidents()['data'];
	}

	/**
	 * Get number workstop days.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number workstop days.
	 *
	 * @url    GET                   accident/getNbWorkstopDays
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbWorkstopDays($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbWorkstopDays();
	}

	/**
	 * Get number accidents by employees.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accidents by employees.
	 *
	 * @url    GET                   accident/getNbAccidentsByEmployees
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidentsByEmployees($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidentsByEmployees();
	}

	/**
	 * Get number presqu'accidents.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number presqu'accidents.
	 *
	 * @url    GET                   accident/getNbPresquAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbPresquAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbPresquAccidents();
	}

	/**
	 * Get number accident investigations.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accident investigations.
	 *
	 * @url    GET                   accident/getNbAccidentInvestigations
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidentInvestigations($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidentInvestigations();
	}

	/**
	 * Get frequency index (number accidents with DIAT by employees) x 1000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for frequency index (number accidents with DIAT by employees) x 1000.
	 *
	 * @url    GET                   accident/getFrequencyIndex
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getFrequencyIndex($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getFrequencyIndex();
	}

	/**
	 * Get frequency rate (number accidents with DIAT by working hours) x 1 000 000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for frequency rate (number accidents with DIAT by working hours) x 1 000 000.
	 *
	 * @url    GET                   accident/getFrequencyRate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getFrequencyRate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getFrequencyRate();
	}

	/**
	 * Get gravity rate (number workstop days by working hours) x 1 000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for gravity rate (number workstop days by working hours) x 1 000.
	 *
	 * @url    GET                   accident/getGravityRate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getGravityRate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getGravityRate();
	}

	// DIGIRISKRESOURCES

	/**
	 * Get siret number.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for siret number.
	 *
	 * @url    GET                   digiriskresources/getSiretNumber
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getSiretNumber($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digiriskresources = new DigiriskResources($this->db);
		return $digiriskresources->getSiretNumber();
	}

	// EVALUATOR

	/**
	 * Get number employees involved.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number employees involved.
	 *
	 * @url    GET                   evaluator/getNbEmployeesInvolved
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbEmployeesInvolved($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->getNbEmployeesInvolved();
	}

	/**
	 * Get number employees.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number employees.
	 *
	 * @url    GET                   evaluator/getNbEmployees
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbEmployees($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->getNbEmployees();
	}

	// END ALL UNIQUE OBJECT API ROUTE

}
