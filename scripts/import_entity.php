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
 * \file    scripts/import_entity.php
 * \ingroup digiriskdolibarr
 * \brief   Replay on this Dolibarr the dump produced by scripts/export_entity.php
 *
 * Usage: php scripts/import_entity.php --input=<dir|file.sql> --confirm
 * The same import runs from the Tools page of the module, both call
 * digirisk_entity_transfer_import().
 */

if (php_sapi_name() !== 'cli') {
    print 'This script must be run from the command line.' . "\n";
    exit(1);
}

define('INC_FROM_CRON_SCRIPT', true);

// Load Dolibarr environment
$res = @include __DIR__ . '/../../../master.inc.php';
if (!$res) {
    $res = @include __DIR__ . '/../../../../master.inc.php';
}
if (!$res) {
    die("Include of main fails\n");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/../lib/digiriskdolibarr_entity_transfer.lib.php';

global $db;

$arguments = digirisk_entity_transfer_parse_args($argv);

if (isset($arguments['help']) || !isset($arguments['input'])) {
    print "\n";
    print "Replay on this Dolibarr the dump produced by scripts/export_entity.php.\n";
    print "\n";
    print "Usage: php scripts/import_entity.php --input=<dir|file.sql> --confirm\n";
    print "\n";
    print "  --input=<path>       Export directory or SQL dump (required)\n";
    print "  --purge              Empty the imported tables for the target entity before the import.\n";
    print "                       Needs the manifest.json of the export directory\n";
    print "  --with-files         Copy the documents of the export into the documents directory\n";
    print "  --stop-on-error      Stop on the first SQL error (default: keep going and report)\n";
    print "  --dry-run            Only print what would be done\n";
    print "  --confirm            Really write into the database\n";
    print "  --help               Print this help\n";
    print "\n";
    exit(0);
}

$input  = rtrim(str_replace('\\', '/', (string) $arguments['input']), '/');
$dryRun = isset($arguments['dry-run']);

if (is_dir($input)) {
    $inputDir     = $input;
    $manifestFile = $input . '/manifest.json';
} elseif (is_file($input)) {
    $inputDir     = dirname($input);
    $manifestFile = $inputDir . '/manifest.json';
} else {
    print 'Error: ' . $input . " not found.\n";
    exit(1);
}

$manifest = [];
if (is_file($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true);
    if (!is_array($manifest)) {
        print 'Error: ' . $manifestFile . " is not readable JSON.\n";
        exit(1);
    }
}

if (is_dir($input)) {
    if (empty($manifest['sql_file'])) {
        print 'Error: no manifest.json in ' . $input . ", point --input at the SQL file directly.\n";
        exit(1);
    }
    $sqlFile = $inputDir . '/' . $manifest['sql_file'];
} else {
    $sqlFile = $input;
}

if (!is_file($sqlFile)) {
    print 'Error: ' . $sqlFile . " not found.\n";
    exit(1);
}

$sourcePrefix = (string) ($manifest['source']['prefix'] ?? MAIN_DB_PREFIX);
$targetEntity = (int) ($manifest['target_entity'] ?? 1);

print "DigiRisk entity import\n";
print '  Dump          : ' . $sqlFile . ' (' . dol_print_size(dol_filesize($sqlFile)) . ")\n";
print '  Database      : ' . $db->database_name . ' (prefix ' . MAIN_DB_PREFIX . ")\n";

if (!empty($manifest)) {
    print '  Export        : ' . $manifest['generated'] . ' from ' . $manifest['source']['database']
        . ' entity ' . implode(', ', $manifest['source']['entities']) . "\n";
    print '  Scope         : ' . $manifest['scope'] . "\n";
    print '  Target entity : ' . $targetEntity . "\n";
    print '  Rows          : ' . $manifest['rows'] . ' in ' . count($manifest['tables']) . " tables\n";
}

if ($sourcePrefix !== MAIN_DB_PREFIX) {
    print '  ! Prefix      : the dump uses ' . $sourcePrefix . ', statements are rewritten to ' . MAIN_DB_PREFIX . "\n";
}

if (isModEnabled('multicompany')) {
    print '  ! Multicompany is enabled here: check that entity ' . $targetEntity . " is really the one you target.\n";
}

if (!isModEnabled('digiriskdolibarr')) {
    print "\nError: the DigiRisk module is not enabled on this install. Enable it first, so that\n";
    print "the tables, the rights, the menus and the document models are created.\n";
    exit(1);
}

print "\n";

if (!$dryRun && !isset($arguments['confirm'])) {
    print "Nothing done: add --confirm to write into the database, or --dry-run to simulate.\n";
    exit(0);
}

$result = digirisk_entity_transfer_import($db, $sqlFile, [
    'manifest'      => $manifest,
    'purge'         => isset($arguments['purge']),
    'dry_run'       => $dryRun,
    'stop_on_error' => isset($arguments['stop-on-error']),
    'documents_dir' => (isset($arguments['with-files']) ? $inputDir . '/documents' : ''),
    'target_entity' => $targetEntity
]);

if ($result['purged'] > 0) {
    print 'Purge         : ' . $result['purged'] . " table(s) emptied\n\n";
}

print "Replay of the dump:\n";
print '  Statements    : ' . $result['statements'] . "\n";
print '  Executed      : ' . $result['executed'] . "\n";
print '  Errors        : ' . $result['errors'] . "\n";

foreach ($result['messages'] as $message) {
    print '  ! ' . $message . "\n";
}

if ($result['documents'] > 0) {
    print "\nDocuments:\n";
    print '  ' . $result['documents'] . " files copied\n";
}

if (!empty($result['checks'])) {
    print "\nCheck of the imported rows:\n";
    foreach ($result['checks'] as $check) {
        $status = ($check['found'] >= $check['expected'] ? 'OK' : 'MISSING');
        printf("  %-8s %-6d / %-6d %s\n", $status, $check['found'], $check['expected'], $check['name']);
    }
}

if ($dryRun) {
    print "\nDry run: nothing written.\n";
} elseif ($result['errors'] > 0) {
    print "\nImport finished with " . $result['errors'] . " error(s).\n";
} else {
    print "\nImport finished. Clear the Dolibarr cache and reload the module setup page.\n";
}

$db->close();

exit($result['errors'] > 0 ? 1 : 0);
