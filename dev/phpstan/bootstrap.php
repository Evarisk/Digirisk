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
 * PHPStan bootstrap for the Digirisk module.
 *
 * Defines the Dolibarr constants that module code reads at analysis time.
 * Dolibarr and Saturne classes are NOT stubbed here: they are resolved from the real sources
 * through the scanDirectories of phpstan.neon. Declaring stubs in a bootstrap file makes them
 * win over the real classes and turns every method call into a false "undefined method".
 */

// Dolibarr root is four directories above this file (htdocs/).
$dolibarrRoot = realpath(__DIR__ . '/../../../../');

define('DOL_DOCUMENT_ROOT', $dolibarrRoot);
define('DOL_DATA_ROOT', dirname($dolibarrRoot) . '/documents');
define('DOL_URL_ROOT', '/');
define('DOL_MAIN_URL_ROOT', 'http://localhost');
define('DOL_VERSION', '0.0.0');
define('MAIN_DB_PREFIX', 'llx_');
define('GETPOST_ALLOWHTML', 1);
define('ODTPHP_PATH', $dolibarrRoot . '/includes/odtphp/');
define('TCPDF_PATH', $dolibarrRoot . '/includes/tecnickcom/tcpdf/');
