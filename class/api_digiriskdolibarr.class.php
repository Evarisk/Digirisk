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
			'digiriskelements' => [
				'class'       => DigiriskElement::class,
				'fields'      => ['label', 'element_type'],
				'permissions' => [
					'read'   => 'digiriskelement->read',
					'create' => 'digiriskelement->write',
					'write'  => 'digiriskelement->write',
					'delete' => 'digiriskelement->delete',
				],
			],
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
				'fields'      => ['fk_risk'],
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
	 * Set conf entity from HTTP header (DOLENTITY / DOLAPIENTITY), query/body parameter, or explicit argument.
	 *
	 * @param  integer $entity Fallback Entity ID
	 * @return void
	 */
	public function setConfEntity($entity = 0)
	{
		global $conf;

		$requestedEntity = 0;
		if (!empty($_SERVER['HTTP_DOLENTITY'])) {
			$requestedEntity = (int) $_SERVER['HTTP_DOLENTITY'];
		} elseif (!empty($_SERVER['HTTP_DOLAPIENTITY'])) {
			$requestedEntity = (int) $_SERVER['HTTP_DOLAPIENTITY'];
		} elseif (GETPOSTISSET('DOLENTITY')) {
			$requestedEntity = GETPOSTINT('DOLENTITY');
		} elseif (GETPOSTISSET('entity')) {
			$requestedEntity = GETPOSTINT('entity');
		} elseif ((int) $entity > 0) {
			$requestedEntity = (int) $entity;
		}

		if ($requestedEntity > 0 && (int) $conf->entity !== $requestedEntity) {
			$conf->setEntityValues($this->db, $requestedEntity);
			DigiriskElement::clearActiveElementsCache();
		}
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

		$this->setConfEntity((int) $conf->entity);

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
		$extension = $extensionMap[$image[2]];

		if (!dol_is_dir($targetDir)) {
			dol_mkdir($targetDir);
		}

		if ($preferredName !== '') {
			$sanitized = dol_sanitizeFileName(dol_basename($preferredName));
			$baseName  = pathinfo($sanitized, PATHINFO_FILENAME);
			if ($baseName === '') {
				$baseName = 'api-' . bin2hex(random_bytes(16));
			}
			$filename = $baseName . $extension;
			$counter  = 2;
			while (dol_is_file($targetDir . '/' . $filename)) {
				$filename = $baseName . '-' . $counter . $extension;
				$counter++;
			}
		} else {
			$filename = 'api-' . bin2hex(random_bytes(16)) . $extension;
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
	 * Extract and normalize one or more base64 media payloads from request data.
	 * Supports single 'photo_base64' (with optional 'filename') as well as arrays in
	 * 'photos_base64', 'photos', or 'medias' (each item can be a base64 string or
	 * an associative array with 'photo_base64'|'base64'|'content' and optional 'filename'|'name').
	 *
	 * @param array $data Request payload
	 *
	 * @return array<int, array{photo_base64: string, filename: string}>
	 *
	 * @throws RestException 400
	 */
	private function extractMediaPayloads(array $data): array
	{
		$items = [];

		if (isset($data['photo_base64'])) {
			if (!is_string($data['photo_base64']) || trim($data['photo_base64']) === '') {
				throw new RestException(400, 'photo_base64 must be a non-empty string');
			}
			$items[] = [
				'photo_base64' => $data['photo_base64'],
				'filename'     => isset($data['filename']) && is_string($data['filename']) ? trim($data['filename']) : '',
			];
		}

		foreach (['photos_base64', 'photos', 'medias'] as $key) {
			if (!isset($data[$key])) {
				continue;
			}
			if (!is_array($data[$key])) {
				throw new RestException(400, $key . ' must be an array');
			}
			foreach ($data[$key] as $index => $entry) {
				if (is_string($entry)) {
					if (trim($entry) === '') {
						throw new RestException(400, $key . '[' . $index . '] must be a non-empty base64 string');
					}
					$items[] = [
						'photo_base64' => $entry,
						'filename'     => '',
					];
				} elseif (is_array($entry)) {
					$b64 = $entry['photo_base64'] ?? ($entry['base64'] ?? ($entry['content'] ?? null));
					if (!is_string($b64) || trim($b64) === '') {
						throw new RestException(400, $key . '[' . $index . '].photo_base64 must be a non-empty string');
					}
					$fname   = $entry['filename'] ?? ($entry['name'] ?? '');
					$items[] = [
						'photo_base64' => $b64,
						'filename'     => is_string($fname) ? trim($fname) : '',
					];
				} else {
					throw new RestException(400, 'Invalid media entry in ' . $key . '[' . $index . ']');
				}
			}
		}

		return $items;
	}

	/**
	 * List all saved media filenames for a given risk assessment reference.
	 *
	 * @param string $assessmentRef Risk assessment reference (e.g. RA1)
	 *
	 * @return array<int, string>
	 */
	private function listAssessmentPhotos(string $assessmentRef): array
	{
		global $conf;

		if ($assessmentRef === '' || !preg_match('/^[A-Za-z0-9_-]+$/D', $assessmentRef)) {
			return [];
		}

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? ($conf->digiriskdolibarr->dir_output ?? '');
		if ($root === '') {
			return [];
		}

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$assessmentDir = $root . '/riskassessment/' . $assessmentRef;
		if (!dol_is_dir($assessmentDir)) {
			return [];
		}

		$photos = [];
		foreach (dol_dir_list($assessmentDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_ASC) as $file) {
			$photos[] = $file['name'];
		}

		return $photos;
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

	/**
	 * Verify that a fetched DigiriskElement belongs to the current entity, has STATUS_VALIDATED,
	 * and is reachable from the root (not the trash bin itself and not inside a trashed/archived groupment).
	 *
	 * @param DigiriskElement $element Fetched organization element
	 *
	 * @return bool
	 */
	private function isActiveNonTrashedElement(DigiriskElement $element): bool
	{
		global $conf;

		if (
			(int) $element->id <= 0 ||
			($element->entity !== null && (int) $element->entity !== (int) $conf->entity) ||
			(int) $element->status !== DigiriskElement::STATUS_VALIDATED
		) {
			return false;
		}

		$reachableIds = $element->getReachableDigiriskElementIds();
		return is_array($reachableIds) && isset($reachableIds[(int) $element->id]);
	}

	/**
	 * Load the Evarisk advanced evaluation configuration and transfer matrix from js/json/default.json.
	 *
	 * @return array{matrix: array<string, int>, risk_variable: array, riskenvironmental_variable: array}
	 *
	 * @throws RestException 500
	 */
	private function loadAdvancedCotationConfig(): array
	{
		static $cachedConfig = null;
		if ($cachedConfig !== null) {
			return $cachedConfig;
		}

		$jsonPath = __DIR__ . '/../js/json/default.json';
		if (!is_file($jsonPath)) {
			throw new RestException(500, 'Evaluation transfer file (default.json) is missing');
		}

		$rawJson = file_get_contents($jsonPath);
		$decoded = is_string($rawJson) ? json_decode($rawJson, true) : null;
		if (!is_array($decoded) || empty($decoded[0]['option']['matrix']) || !is_array($decoded[0]['option']['matrix'])) {
			throw new RestException(500, 'Evaluation transfer file (default.json) is invalid');
		}

		$matrix = [];
		foreach ($decoded[0]['option']['matrix'] as $product => $score) {
			$matrix[(string) $product] = (int) $score;
		}

		$cachedConfig = [
			'matrix'                     => $matrix,
			'risk_variable'              => (array) ($decoded[0]['option']['risk_variable'] ?? []),
			'riskenvironmental_variable' => (array) ($decoded[0]['option']['riskenvironmental_variable'] ?? []),
		];

		return $cachedConfig;
	}

	/**
	 * Validate and resolve the risk assessment method, 0-100 cotation, and 5 advanced criteria.
	 * Enforces mutual exclusivity between:
	 * - Simple evaluation ('standard'): 4 thresholds (0 [0-47], 48 [48-50], 51 [51-80], 100 [81-100] or scale 1..4), no advanced criteria allowed.
	 * - Advanced evaluation ('advanced'): 5 criteria (gravite 0..4, exposition/frequence 0..4, occurrence/historique 1..4, formation/maitrise 1..4, protection/milieu 1..4) transferred to 0..100 via default.json matrix.
	 *
	 * @param array               $data     Request payload
	 * @param RiskAssessment|null $existing Existing assessment when updating (null when creating)
	 *
	 * @return array{method: string, cotation: int, gravite: int|null, exposition: int|null, occurrence: int|null, formation: int|null, protection: int|null}
	 *
	 * @throws RestException 400, 500
	 */
	private function resolveAndValidateAssessmentCotation(array $data, ?RiskAssessment $existing = null): array
	{
		$criterionMapping = [
			'gravite'    => ['keys' => ['gravite'], 'min' => 0, 'max' => 4],
			'exposition' => ['keys' => ['exposition', 'frequence'], 'min' => 0, 'max' => 4],
			'occurrence' => ['keys' => ['occurrence', 'historique'], 'min' => 1, 'max' => 4],
			'formation'  => ['keys' => ['formation', 'maitrise'], 'min' => 1, 'max' => 4],
			'protection' => ['keys' => ['protection', 'milieu'], 'min' => 1, 'max' => 4],
		];

		$criteresBag = isset($data['criteres']) && is_array($data['criteres']) ? $data['criteres'] : [];
		$extractedCriteria = [];
		foreach ($criterionMapping as $canonical => $meta) {
			foreach ($meta['keys'] as $alias) {
				if (array_key_exists($alias, $data) && $data[$alias] !== null && $data[$alias] !== '') {
					$extractedCriteria[$canonical] = $data[$alias];
					break;
				}
				if (array_key_exists($alias, $criteresBag) && $criteresBag[$alias] !== null && $criteresBag[$alias] !== '') {
					$extractedCriteria[$canonical] = $criteresBag[$alias];
					break;
				}
			}
		}

		$hasAnyCriteria   = !empty($extractedCriteria);
		$cotationProvided = array_key_exists('cotation', $data) && $data['cotation'] !== null && $data['cotation'] !== '';
		$seuilProvided    = array_key_exists('seuil', $data) && $data['seuil'] !== null && $data['seuil'] !== '';
		$scaleProvided    = array_key_exists('scale', $data) && $data['scale'] !== null && $data['scale'] !== '';

		$rawMethod = isset($data['method']) && is_string($data['method']) ? strtolower(trim($data['method'])) : '';
		if ($rawMethod === 'simple') {
			$rawMethod = 'standard';
		}

		if ($rawMethod !== '' && !in_array($rawMethod, ['standard', 'advanced'], true)) {
			throw new RestException(400, 'Invalid evaluation method (standard or advanced expected)');
		}

		// Determine effective method
		if ($rawMethod !== '') {
			$method = $rawMethod;
		} elseif ($hasAnyCriteria) {
			$method = 'advanced';
		} elseif ($cotationProvided || $seuilProvided || $scaleProvided) {
			$method = 'standard';
		} elseif ($existing !== null && !empty($existing->method)) {
			$method = ($existing->method === 'advanced') ? 'advanced' : 'standard';
		} else {
			$method = 'standard';
		}

		if ($method === 'standard') {
			if ($hasAnyCriteria) {
				throw new RestException(400, 'Cannot provide advanced evaluation criteria (gravite, exposition, occurrence, formation, protection) when using standard (simple) evaluation method');
			}

			$scaleToCotation = [
				1 => 0,
				2 => 48,
				3 => 51,
				4 => 100,
			];

			if ($cotationProvided) {
				if (!is_int($data['cotation']) && !(is_string($data['cotation']) && preg_match('/^\d+$/', $data['cotation']))) {
					throw new RestException(400, 'cotation must be an integer');
				}
				$cotation = (int) $data['cotation'];
			} elseif ($seuilProvided || $scaleProvided) {
				$rawScale = $seuilProvided ? $data['seuil'] : $data['scale'];
				if (!is_int($rawScale) && !(is_string($rawScale) && preg_match('/^\d+$/', $rawScale))) {
					throw new RestException(400, 'seuil/scale must be an integer between 1 and 4');
				}
				$scaleVal = (int) $rawScale;
				if (!isset($scaleToCotation[$scaleVal])) {
					throw new RestException(400, 'Invalid simple threshold scale (1, 2, 3, or 4 expected)');
				}
				$cotation = $scaleToCotation[$scaleVal];
			} elseif ($existing !== null && $existing->method !== 'advanced' && $existing->cotation !== null) {
				$cotation = (int) $existing->cotation;
			} else {
				throw new RestException(400, 'cotation (0, 48, 51, 100) or seuil/scale (1, 2, 3, 4) is required for standard evaluation');
			}

			if (!in_array($cotation, [0, 48, 51, 100], true)) {
				throw new RestException(400, 'Invalid standard cotation: must be one of the 4 thresholds (0 [0-47], 48 [48-50], 51 [51-80], 100 [81-100])');
			}

			return [
				'method'     => 'standard',
				'cotation'   => $cotation,
				'gravite'    => null,
				'exposition' => null,
				'occurrence' => null,
				'formation'  => null,
				'protection' => null,
			];
		}

		// Advanced evaluation ('advanced')
		if ($seuilProvided || $scaleProvided) {
			throw new RestException(400, 'Cannot provide simple threshold (seuil/scale) when using advanced evaluation method');
		}

		$resolvedCriteria = [];
		foreach ($criterionMapping as $canonical => $meta) {
			if (array_key_exists($canonical, $extractedCriteria)) {
				$rawVal = $extractedCriteria[$canonical];
			} elseif ($existing !== null && $existing->method === 'advanced' && $existing->{$canonical} !== null && $existing->{$canonical} !== '') {
				$rawVal = $existing->{$canonical};
			} else {
				throw new RestException(400, 'Missing criterion "' . $canonical . '": all 5 criteria (gravite, exposition, occurrence, formation, protection) are required for advanced evaluation');
			}

			if (!is_int($rawVal) && !(is_string($rawVal) && preg_match('/^-?\d+$/', (string) $rawVal))) {
				throw new RestException(400, 'Criterion "' . $canonical . '" must be an integer');
			}

			$intVal = (int) $rawVal;
			if ($intVal < $meta['min'] || $intVal > $meta['max']) {
				throw new RestException(400, 'Criterion "' . $canonical . '" must be between ' . $meta['min'] . ' and ' . $meta['max']);
			}

			$resolvedCriteria[$canonical] = $intVal;
		}

		$product = $resolvedCriteria['gravite']
			* $resolvedCriteria['exposition']
			* $resolvedCriteria['occurrence']
			* $resolvedCriteria['formation']
			* $resolvedCriteria['protection'];

		$config = $this->loadAdvancedCotationConfig();
		$matrix = $config['matrix'];
		if (!array_key_exists((string) $product, $matrix)) {
			throw new RestException(400, 'Calculated criteria product (' . $product . ') does not exist in transfer matrix');
		}

		$transferredCotation = (int) $matrix[(string) $product];

		if ($cotationProvided) {
			if (!is_int($data['cotation']) && !(is_string($data['cotation']) && preg_match('/^-?\d+$/', (string) $data['cotation']))) {
				throw new RestException(400, 'cotation must be an integer');
			}
			if ((int) $data['cotation'] !== $transferredCotation) {
				throw new RestException(
					400,
					'Provided cotation (' . (int) $data['cotation'] . ') does not match the transfer matrix score (' . $transferredCotation . ') for the 5 advanced criteria (product = ' . $product . ')'
				);
			}
		}

		return [
			'method'     => 'advanced',
			'cotation'   => $transferredCotation,
			'gravite'    => $resolvedCriteria['gravite'],
			'exposition' => $resolvedCriteria['exposition'],
			'occurrence' => $resolvedCriteria['occurrence'],
			'formation'  => $resolvedCriteria['formation'],
			'protection' => $resolvedCriteria['protection'],
		];
	}

	// =================================================================
	// DIGIRISK ELEMENTS (GP / UT) API ROUTES
	// =================================================================

	/**
	 * List active organization elements (Groupments GP and Work Units UT) in the current entity
	 * that are not in the trash (excludes GP0, trashed elements, and children of trashed groupments).
	 *
	 * @param string $sortfield    Sort field
	 * @param string $sortorder    Sort order (ASC or DESC)
	 * @param int    $limit        Page size (0 = no limit)
	 * @param int    $page         Page index, starting at 0
	 * @param string $element_type Optional filter by element type ('groupment' or 'workunit')
	 * @param string $sqlfilters   Universal Search filter
	 *
	 * @return array<int, object>
	 *
	 * @url GET digiriskelements
	 *
	 * @throws RestException 400, 403, 503
	 */
	public function listDigiriskElements($sortfield = 't.ranks', $sortorder = 'ASC', $limit = 100, $page = 0, $element_type = '', $sqlfilters = '')
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'read');

		$elementType = is_string($element_type) ? trim($element_type) : '';
		if ($elementType !== '' && !in_array($elementType, ['groupment', 'workunit'], true)) {
			throw new RestException(400, 'Invalid element_type (groupment or workunit expected)');
		}

		$digiriskElement = new DigiriskElement($this->db);
		$reachableIds    = $digiriskElement->getReachableDigiriskElementIds();
		if (!is_array($reachableIds) || empty($reachableIds)) {
			return [];
		}

		$filterSql  = 't.entity = ' . ((int) $conf->entity);
		$filterSql .= ' AND t.status = ' . DigiriskElement::STATUS_VALIDATED;
		$filterSql .= ' AND t.rowid IN (' . implode(',', array_map('intval', $reachableIds)) . ')';

		if ($elementType !== '') {
			$filterSql .= " AND t.element_type = '" . $this->db->escape($elementType) . "'";
		}

		if (!empty($sqlfilters)) {
			$errorMessage = '';
			$customSql    = forgeSQLFromUniversalSearchCriteria($sqlfilters, $errorMessage, 1);
			if ($errorMessage !== '') {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> ' . $errorMessage);
			}
			if ($customSql !== '') {
				$filterSql .= ' AND (' . $customSql . ')';
			}
		}

		$offset  = $limit > 0 ? $limit * max(0, (int) $page) : 0;
		$records = $digiriskElement->fetchAll($sortorder, $sortfield, (int) $limit, (int) $offset, ['customsql' => $filterSql]);
		if (!is_array($records)) {
			throw new RestException(503, 'Error when retrieving digiriskelements: ' . implode(',', (array) $digiriskElement->errors));
		}

		$cleaned = [];
		foreach ($records as $record) {
			$cleaned[] = $this->_cleanObjectDatas($record);
		}

		return $cleaned;
	}

	/**
	 * Get the hierarchical ordered list of active organization elements (GP/UT) not in the trash
	 * for the current entity, with their tree depth.
	 *
	 * @param string $element_type Optional filter by element type ('groupment' or 'workunit')
	 *
	 * @return array<int, object>
	 *
	 * @url GET digiriskelements/tree
	 *
	 * @throws RestException 400, 403
	 */
	public function getDigiriskElementsTree($element_type = '')
	{
		$this->_checkPermission('digiriskelements', 'read');

		$elementType = is_string($element_type) ? trim($element_type) : '';
		if ($elementType !== '' && !in_array($elementType, ['groupment', 'workunit'], true)) {
			throw new RestException(400, 'Invalid element_type (groupment or workunit expected)');
		}

		$digiriskElement = new DigiriskElement($this->db);
		$activeElements  = $digiriskElement->getActiveDigiriskElements('current');
		if (!is_array($activeElements) || empty($activeElements)) {
			return [];
		}

		$trashID = getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH');
		if ($trashID > 0) {
			$trashedElements = $digiriskElement->fetchDigiriskElementFlat($trashID, $activeElements, 'current', true);
			$activeElements  = array_diff_key($activeElements, $trashedElements);
			if (empty($activeElements)) {
				return [];
			}
		}

		$flatEntries = $digiriskElement->fetchDigiriskElementFlat(0, $activeElements, 'current');
		$result      = [];
		foreach ($flatEntries as $flatEntry) {
			if (empty($flatEntry['object']) || !($flatEntry['object'] instanceof DigiriskElement)) {
				continue;
			}
			if ($elementType !== '' && $flatEntry['object']->element_type !== $elementType) {
				continue;
			}
			$cleaned        = $this->_cleanObjectDatas($flatEntry['object']);
			$cleaned->depth = (int) ($flatEntry['depth'] ?? 0);
			$result[]       = $cleaned;
		}

		return $result;
	}

	/**
	 * Get one active organization element (GP/UT) by ID if it belongs to the current entity and is not in the trash.
	 *
	 * @param int $id DigiriskElement ID
	 *
	 * @return object Cleaned DigiriskElement
	 *
	 * @url GET digiriskelements/{id}
	 *
	 * @throws RestException 403, 404
	 */
	public function getDigiriskElement($id)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'read');

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			!$this->isActiveNonTrashedElement($element)
		) {
			throw new RestException(404, 'digiriskelements not found');
		}

		return $this->_cleanObjectDatas($element);
	}

	/**
	 * Create a new organization element (Groupment GP or Work Unit UT) in the current entity.
	 *
	 * @param array $request_data JSON body: label, element_type ('groupment'|'workunit'), optional fk_parent, description, show_in_selector, ranks {@from body}
	 *
	 * @return int New DigiriskElement ID
	 *
	 * @url POST digiriskelements
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function createDigiriskElement($request_data = null)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'create');
		$this->_validate('digiriskelements', $request_data);

		$data        = (array) $request_data;
		$label       = isset($data['label']) && is_string($data['label']) ? trim($data['label']) : '';
		$elementType = isset($data['element_type']) && is_string($data['element_type']) ? trim($data['element_type']) : '';

		if ($label === '' || strlen($label) > 255) {
			throw new RestException(400, 'A label of 1 to 255 bytes is required');
		}
		if (!in_array($elementType, ['groupment', 'workunit'], true)) {
			throw new RestException(400, 'Invalid element_type (groupment or workunit expected)');
		}

		$fkParent = isset($data['fk_parent']) ? (int) $data['fk_parent'] : 0;
		if ($fkParent < 0) {
			throw new RestException(400, 'Invalid fk_parent');
		}
		if ($fkParent > 0) {
			$parent = new DigiriskElement($this->db);
			if (
				$parent->fetch($fkParent, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
				!$this->isActiveNonTrashedElement($parent) ||
				$parent->element_type !== 'groupment'
			) {
				throw new RestException(404, 'Active parent groupment not found in current entity');
			}
		}

		$element = new DigiriskElement($this->db);
		$this->_assignRequestData($element, $data);
		$element->entity           = (int) $conf->entity;
		$element->label            = $label;
		$element->element_type     = $elementType;
		$element->fk_parent        = $fkParent;
		$element->show_in_selector = isset($data['show_in_selector']) ? (int) (bool) $data['show_in_selector'] : 1;
		$element->ranks            = isset($data['ranks']) ? (int) $data['ranks'] : 1;

		if ($element->create(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error creating digiriskelements', $this->_collectErrors($element));
		}

		return (int) $element->id;
	}

	/**
	 * Update an existing active organization element (GP/UT).
	 *
	 * @param int   $id           DigiriskElement ID
	 * @param array $request_data Fields to update (label, description, fk_parent, show_in_selector, ranks) {@from body}
	 *
	 * @return object Cleaned updated DigiriskElement
	 *
	 * @url PUT digiriskelements/{id}
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function updateDigiriskElement($id, $request_data = null)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'write');

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			!$this->isActiveNonTrashedElement($element)
		) {
			throw new RestException(404, 'digiriskelements not found');
		}

		$data = (array) $request_data;
		if (isset($data['label'])) {
			if (!is_string($data['label']) || trim($data['label']) === '' || strlen(trim($data['label'])) > 255) {
				throw new RestException(400, 'A label of 1 to 255 bytes is required');
			}
			$data['label'] = trim($data['label']);
		}

		if (isset($data['element_type']) && !in_array($data['element_type'], ['groupment', 'workunit'], true)) {
			throw new RestException(400, 'Invalid element_type (groupment or workunit expected)');
		}

		if (array_key_exists('fk_parent', $data)) {
			$fkParent = (int) $data['fk_parent'];
			if ($fkParent < 0 || $fkParent === (int) $element->id) {
				throw new RestException(400, 'Invalid fk_parent');
			}
			if ($fkParent > 0) {
				$parent = new DigiriskElement($this->db);
				if (
					$parent->fetch($fkParent, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
					!$this->isActiveNonTrashedElement($parent) ||
					$parent->element_type !== 'groupment'
				) {
					throw new RestException(404, 'Active parent groupment not found in current entity');
				}
				$descendants = $element->getDescendants();
				if (isset($descendants[$fkParent])) {
					throw new RestException(400, 'Cannot move an element inside one of its own descendants');
				}
			}
			$data['fk_parent'] = $fkParent;
		}

		unset($data['status'], $data['entity']);

		$updated = $this->_put('digiriskelements', (int) $id, $data);
		DigiriskElement::clearActiveElementsCache();

		return $updated;
	}

	/**
	 * Soft-delete an organization element (GP/UT) by moving it to the entity trash bin (GP0).
	 *
	 * @param int $id DigiriskElement ID
	 *
	 * @return array
	 *
	 * @url DELETE digiriskelements/{id}
	 *
	 * @throws RestException 403, 404, 500
	 */
	public function deleteDigiriskElement($id)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'delete');

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			!$this->isActiveNonTrashedElement($element)
		) {
			throw new RestException(404, 'digiriskelements not found');
		}

		if ($element->delete(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error deleting digiriskelements', $this->_collectErrors($element));
		}

		return [
			'success' => [
				'code'    => 200,
				'message' => 'Digiriskelements deleted',
			],
		];
	}

	/**
	 * Archive an organization element (GP/UT) and all its validated descendants.
	 *
	 * @param int $id DigiriskElement ID
	 *
	 * @return object Cleaned archived DigiriskElement
	 *
	 * @url POST digiriskelements/{id}/archive
	 *
	 * @throws RestException 403, 404, 500
	 */
	public function archiveDigiriskElement($id)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'write');

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			!$this->isActiveNonTrashedElement($element)
		) {
			throw new RestException(404, 'digiriskelements not found');
		}

		if ($element->archive(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error archiving digiriskelements', $this->_collectErrors($element));
		}

		$element->fetch((int) $id);
		return $this->_cleanObjectDatas($element);
	}

	/**
	 * Restore an archived organization element (GP/UT) and its archived descendants.
	 *
	 * @param int $id DigiriskElement ID
	 *
	 * @return object Cleaned restored DigiriskElement
	 *
	 * @url POST digiriskelements/{id}/unarchive
	 *
	 * @throws RestException 400, 403, 404, 500
	 */
	public function unarchiveDigiriskElement($id)
	{
		global $conf;

		$this->_checkPermission('digiriskelements', 'write');

		$element = new DigiriskElement($this->db);
		if (
			$element->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 ||
			($element->entity !== null && (int) $element->entity !== (int) $conf->entity)
		) {
			throw new RestException(404, 'digiriskelements not found');
		}
		if ((int) $element->status !== DigiriskElement::STATUS_ARCHIVED) {
			throw new RestException(400, 'Element is not archived');
		}

		if ($element->unarchive(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error unarchiving digiriskelements', $this->_collectErrors($element));
		}

		$element->fetch((int) $id);
		return $this->_cleanObjectDatas($element);
	}

	// =================================================================
	// RISKS & TEMPORARY PHOTOS API ROUTES
	// =================================================================

	/**
	 * List active organization elements (excluding trash) and native danger categories for risk creation.
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

		$digiriskElement = new DigiriskElement($this->db);
		$visibleElements = $digiriskElement->getVisibleElements();

		$elements = [];
		foreach ($visibleElements as $element) {
			$elements[] = [
				'id'        => (int) $element->id,
				'ref'       => $element->ref,
				'label'     => $element->label,
				'type'      => $element->element_type,
				'fk_parent' => (int) $element->fk_parent,
			];
		}

		$advancedConfig = $this->loadAdvancedCotationConfig();

		$entityName    = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
		$entityLogoUri = '';
		$logoDir       = $conf->mycompany->dir_output ?? '';
		if ($logoDir !== '') {
			$logoCandidates = array_filter([
				getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SMALL') ? $logoDir . '/logos/thumbs/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SMALL') : '',
				getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_MINI') ? $logoDir . '/logos/thumbs/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_MINI') : '',
				getDolGlobalString('MAIN_INFO_SOCIETE_LOGO') ? $logoDir . '/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO') : '',
			]);
			foreach ($logoCandidates as $logoPath) {
				if (is_file($logoPath) && is_readable($logoPath) && filesize($logoPath) <= 2 * 1024 * 1024) {
					$rawLogo = @file_get_contents($logoPath);
					$imgInfo = $rawLogo !== false ? @getimagesizefromstring($rawLogo) : false;
					if ($rawLogo !== false && $imgInfo && !empty($imgInfo['mime'])) {
						$entityLogoUri = 'data:' . $imgInfo['mime'] . ';base64,' . base64_encode($rawLogo);
						break;
					}
				}
			}
		}

		return [
			'entity'             => (int) $conf->entity,
			'entity_name'        => $entityName,
			'entity_logo'        => $entityLogoUri,
			'du_project_id'      => getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'),
			'elements'           => $elements,
			'categories'         => Risk::getDangerCategories('risk'),
			'sub_categories'     => Risk::getDangerSubCategories(),
			'method'             => 'standard',
			'methods'            => ['standard', 'advanced'],
			'cotations'          => [0, 48, 51, 100],
			'cotation_scales'    => [
				1 => ['scale' => 1, 'cotation' => 0,   'min' => 0,  'max' => 47,  'color' => 'grey'],
				2 => ['scale' => 2, 'cotation' => 48,  'min' => 48, 'max' => 50,  'color' => 'orange'],
				3 => ['scale' => 3, 'cotation' => 51,  'min' => 51, 'max' => 80,  'color' => 'red'],
				4 => ['scale' => 4, 'cotation' => 100, 'min' => 81, 'max' => 100, 'color' => 'black'],
			],
			'advanced_criteria'  => [
				'gravite'    => ['min' => 0, 'max' => 4, 'values' => $advancedConfig['risk_variable']['gravite'] ?? []],
				'exposition' => ['min' => 0, 'max' => 4, 'alias' => 'frequence', 'values' => $advancedConfig['risk_variable']['exposition'] ?? []],
				'occurrence' => ['min' => 1, 'max' => 4, 'alias' => 'historique', 'values' => $advancedConfig['risk_variable']['occurrence'] ?? []],
				'formation'  => ['min' => 1, 'max' => 4, 'alias' => 'maitrise', 'values' => $advancedConfig['risk_variable']['formation'] ?? []],
				'protection' => ['min' => 1, 'max' => 4, 'alias' => 'milieu', 'values' => $advancedConfig['risk_variable']['protection'] ?? []],
			],
			'transfer_matrix'    => $advancedConfig['matrix'],
			'photo_max_bytes'    => 5 * 1024 * 1024,
			'temp_photo_dir'     => 'riskassessment/tmp/RA0',
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
				$cleaned         = $this->_cleanObjectDatas($eval);
				$cleaned->photos = $this->listAssessmentPhotos((string) ($eval->ref ?? ''));
				$cleanedEvals[]  = $cleaned;
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
	 * Create a risk and its assessment, optionally attaching one or multiple medias via base64
	 * (photo_base64, photos_base64, photos, medias) or photos pre-uploaded into riskassessment/tmp/RA0.
	 *
	 * @param array $request_data JSON body: fk_element, category, description, cotation, optional method, comment, photo, photo_base64, photos, photos_base64, medias {@from body}
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
	 * Create a risk and its simple or advanced assessment, optionally with one or multiple JPEG/PNG/GIF/WEBP medias
	 * (photo_base64, photos_base64, photos, medias) or moving pre-uploaded temporary photos from riskassessment/tmp/RA0.
	 *
	 * @param array $request_data JSON body: fk_element, category, description, cotation, optional photo_base64, photos, photos_base64, medias {@from body}
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

		foreach (['fk_element', 'category'] as $field) {
			if (!isset($data[$field]) || !is_int($data[$field])) {
				throw new RestException(400, $field . ' must be an integer');
			}
		}

		if ($data['fk_element'] <= 0) {
			throw new RestException(400, 'Invalid element');
		}

		$resolvedEval = $this->resolveAndValidateAssessmentCotation($data);
		$method       = $resolvedEval['method'];
		$cotation     = $resolvedEval['cotation'];

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
			!$this->isActiveNonTrashedElement($element)
		) {
			throw new RestException(404, 'Active organization element not found in current entity');
		}

		$mediaPayloads = $this->extractMediaPayloads($data);

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

			foreach (['gravite', 'exposition', 'occurrence', 'formation', 'protection'] as $criterion) {
				$assessment->{$criterion} = $resolvedEval[$criterion];
			}

			if (!empty($data['photo']) && is_string($data['photo'])) {
				$assessment->photo = dol_sanitizeFileName(dol_basename($data['photo']));
			}

			$root    = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? ($conf->digiriskdolibarr->dir_output ?? '');
			$workDir = $root !== '' ? $root . '/riskassessment/tmp/RA0' : '';

			if (!empty($mediaPayloads)) {
				if (!$root || !preg_match('/^[A-Za-z0-9_-]+$/D', $assessment->ref)) {
					throw new RuntimeException('Invalid assessment media directory');
				}
				$assessmentDir = $root . '/riskassessment/' . $assessment->ref;
				foreach ($mediaPayloads as $mediaItem) {
					$savedName = $this->saveValidatedPhotoToDir($mediaItem['photo_base64'], $assessmentDir, $mediaItem['filename'], true);
					if (empty($assessment->photo)) {
						$assessment->photo = $savedName;
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
			if ($assessmentDir !== null && function_exists('dol_delete_dir_recursive') && is_dir($assessmentDir)) {
				dol_delete_dir_recursive($assessmentDir);
			}
			if ($error instanceof RestException) {
				throw $error;
			}
			dol_syslog(__METHOD__ . ': ' . $error->getMessage(), LOG_ERR);
			throw new RestException(500, 'Risk creation failed; consult server logs');
		}

		$savedPhotos = $this->listAssessmentPhotos((string) $assessment->ref);

		return [
			'id'             => (int) $risk->id,
			'risk_id'        => (int) $risk->id,
			'risk_ref'       => $risk->ref,
			'assessment_id'  => (int) $assessment->id,
			'assessment_ref' => $assessment->ref,
			'method'         => $assessment->method,
			'cotation'       => (int) $assessment->cotation,
			'photo'          => $assessment->photo ?? '',
			'photos'         => $savedPhotos,
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
				!$this->isActiveNonTrashedElement($element)
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

		$assessment->photos = $this->listAssessmentPhotos((string) ($assessment->ref ?? ''));

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
	 * riskassessment/tmp/RA0/{risk_ref} or riskassessment/tmp/RA0, or saving one or multiple medias
	 * passed via photo_base64, photos_base64, photos, or medias.
	 *
	 * @param array $request_data Risk assessment data (fk_risk, cotation, optional method, comment, photo_base64, photos, photos_base64, medias) {@from body}
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

		$resolvedEval  = $this->resolveAndValidateAssessmentCotation($data);
		$mediaPayloads = $this->extractMediaPayloads($data);
		unset(
			$data['photo_base64'],
			$data['photos_base64'],
			$data['photos'],
			$data['medias'],
			$data['filename'],
			$data['scale'],
			$data['seuil'],
			$data['criteres'],
			$data['frequence'],
			$data['historique'],
			$data['maitrise'],
			$data['milieu'],
			$data['method'],
			$data['cotation'],
			$data['gravite'],
			$data['exposition'],
			$data['occurrence'],
			$data['formation'],
			$data['protection']
		);

		$assessment = new RiskAssessment($this->db);
		$modules    = [
			'riskanalysis/' . $assessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON ?? 'mod_riskassessment_standard',
		];
		list($assessmentNumbering) = saturne_require_objects_mod($modules, 'digiriskdolibarr');

		$this->_assignRequestData($assessment, $data);
		$assessment->entity              = (int) $conf->entity;
		$assessment->fk_risk             = (int) $risk->id;
		$assessment->ref                 = !empty($assessment->ref) ? $assessment->ref : $assessmentNumbering->getNextValue($assessment);
		$assessment->method              = $resolvedEval['method'];
		$assessment->cotation            = $resolvedEval['cotation'];
		foreach (['gravite', 'exposition', 'occurrence', 'formation', 'protection'] as $criterion) {
			$assessment->{$criterion} = $resolvedEval[$criterion];
		}
		if (empty($assessment->comment) && !empty($risk->description)) {
			$assessment->comment = $risk->description;
		}
		$assessment->status              = isset($data['status']) ? (int) $data['status'] : RiskAssessment::STATUS_VALIDATED;
		$assessment->date_riskassessment = !empty($assessment->date_riskassessment) ? $assessment->date_riskassessment : dol_now();

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? ($conf->digiriskdolibarr->dir_output ?? '');
		if (!empty($mediaPayloads) && $root !== '') {
			$targetDir = $root . '/riskassessment/' . $assessment->ref;
			foreach ($mediaPayloads as $mediaItem) {
				$savedName = $this->saveValidatedPhotoToDir($mediaItem['photo_base64'], $targetDir, $mediaItem['filename'], true);
				if (empty($assessment->photo)) {
					$assessment->photo = $savedName;
				}
			}
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
	 * Update a risk assessment (and optionally upload one or multiple new medias via photo_base64, photos_base64, photos, or medias).
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

		$assessment = new RiskAssessment($this->db);
		if ($assessment->fetch((int) $id, '', ' AND t.entity = ' . ((int) $conf->entity)) <= 0 || ($assessment->entity !== null && (int) $assessment->entity !== (int) $conf->entity)) {
			throw new RestException(404, 'riskassessments not found');
		}

		if ($assessment->method === 'simple' || empty($assessment->method)) {
			$assessment->method = 'standard';
		}

		$data          = (array) $request_data;
		$evalKeys      = ['method', 'cotation', 'scale', 'seuil', 'criteres', 'gravite', 'exposition', 'occurrence', 'formation', 'protection', 'frequence', 'historique', 'maitrise', 'milieu'];
		$touchesEval   = false;
		foreach ($evalKeys as $evalKey) {
			if (array_key_exists($evalKey, $data)) {
				$touchesEval = true;
				break;
			}
		}

		$resolvedEval = null;
		if ($touchesEval) {
			$resolvedEval = $this->resolveAndValidateAssessmentCotation($data, $assessment);
		}

		$mediaPayloads = $this->extractMediaPayloads($data);
		unset(
			$data['photo_base64'],
			$data['photos_base64'],
			$data['photos'],
			$data['medias'],
			$data['filename'],
			$data['scale'],
			$data['seuil'],
			$data['criteres'],
			$data['frequence'],
			$data['historique'],
			$data['maitrise'],
			$data['milieu'],
			$data['method'],
			$data['cotation'],
			$data['gravite'],
			$data['exposition'],
			$data['occurrence'],
			$data['formation'],
			$data['protection']
		);

		$root = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? ($conf->digiriskdolibarr->dir_output ?? '');
		if (!empty($mediaPayloads) && $root !== '') {
			$targetDir = $root . '/riskassessment/' . $assessment->ref;
			foreach ($mediaPayloads as $mediaItem) {
				$savedName = $this->saveValidatedPhotoToDir($mediaItem['photo_base64'], $targetDir, $mediaItem['filename'], true);
				if (empty($data['photo']) && empty($assessment->photo)) {
					$data['photo'] = $savedName;
				}
			}
		}

		$this->_assignRequestData($assessment, $data);
		if ($resolvedEval !== null) {
			$assessment->method   = $resolvedEval['method'];
			$assessment->cotation = $resolvedEval['cotation'];
			foreach (['gravite', 'exposition', 'occurrence', 'formation', 'protection'] as $criterion) {
				$assessment->{$criterion} = $resolvedEval[$criterion];
			}
		}

		if ($assessment->update(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error updating riskassessments', $this->_collectErrors($assessment));
		}

		$assessment->fetch((int) $id);
		$updated         = $this->_cleanObjectDatas($assessment);
		$updated->photos = $this->listAssessmentPhotos((string) ($assessment->ref ?? ''));

		return $updated;
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

		// Same counts as the graph of the dashboard: the risks of each danger category, by level of the cotation scale
		$risk                                     = new Risk($db);
		$riskByDangerCategoriesAndRiskAssessments = $risk->getRiskByDangerCategoriesAndRiskAssessments(Risk::getDangerCategories());
		return $risk->getRisksByCotation($riskByDangerCategoriesAndRiskAssessments)['data'];
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		return $accident->getNbDaysWithoutAccident($dashboardAccidents['accidents']);
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		return $accident->getNbAccidents($dashboardAccidents['accidents'], $dashboardAccidents['accidentsWithWorkStops'])['data'];
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		return $accident->getNbWorkstopDays($dashboardAccidents['accidentsWithWorkStops']);
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		$employees          = (new Evaluator($this->db))->getNbEmployees();
		return $accident->getNbAccidentsByEmployees($dashboardAccidents['accidents'], $dashboardAccidents['accidentsWithWorkStops'], $employees);
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		$employees          = (new Evaluator($this->db))->getNbEmployees();
		return $accident->getFrequencyIndex($dashboardAccidents['accidentsWithWorkStops'], $employees);
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		return $accident->getFrequencyRate($dashboardAccidents['accidentsWithWorkStops']);
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

		$accident           = new Accident($this->db);
		$dashboardAccidents = $accident->getDashboardAccidents();
		return $accident->getGravityRate($dashboardAccidents['accidentsWithWorkStops']);
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
