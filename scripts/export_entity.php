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
 * \file    scripts/export_entity.php
 * \ingroup digiriskdolibarr
 * \brief   Export every table of one entity into a dump replayable on a mono entity Dolibarr
 *
 * Usage: php scripts/export_entity.php --entity=3 [options]
 * The same export runs from the Tools page of the module, both call
 * digirisk_entity_transfer_export().
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

if (isset($arguments['help']) || !isset($arguments['entity'])) {
    print "\n";
    print "Export every table of one entity into a dump replayable on a mono entity Dolibarr.\n";
    print "\n";
    print "Usage: php scripts/export_entity.php --entity=<id> [options]\n";
    print "\n";
    print "  --entity=<id>            Entity to export (required)\n";
    print "  --target-entity=<id>     Entity written in the dump (default 1)\n";
    print "  --scope=<scope>          digirisk : DigiRisk and Saturne tables only (default)\n";
    print "                           core     : adds users, third parties, projects, tickets, events, categories, ECM\n";
    print "                           full     : every table holding an entity column\n";
    print "  --extra-entities=<list>  Also export the rows of those entities, for the data shared\n";
    print "                           by Multicompany (users, third parties...). Example: --extra-entities=1\n";
    print "  --with-modules=<list>    Also export the tables of those modules, comma separated. DigiRisk leans\n";
    print "                           on some of them: --with-modules=doliletter,digiquali\n";
    print "  --with-dictionaries      Also export the DigiRisk dictionaries (llx_c_xxx)\n";
    print "  --with-files             Copy the documents of the entity next to the dump\n";
    print "  --purge                  Write the DELETE statements of the target entity before the INSERT\n";
    print "  --insert-mode=<mode>     insert (default), ignore or replace\n";
    print "  --include-table=<list>   Additional tables, comma separated, without the llx_ prefix\n";
    print "  --exclude-table=<list>   Tables to skip, comma separated, without the llx_ prefix\n";
    print "  --output=<dir>           Output directory (default " . DOL_DATA_ROOT . "/digiriskdolibarr/entity_export/...)\n";
    print "  --chunk=<n>              Rows per INSERT statement (default 200)\n";
    print "  --dry-run                Only print the plan and the number of rows\n";
    print "  --help                   Print this help\n";
    print "\n";
    exit(0);
}

$sourceEntity = (int) $arguments['entity'];
$targetEntity = (int) ($arguments['target-entity'] ?? 1);
$scope        = (string) ($arguments['scope'] ?? 'digirisk');
$insertMode   = (string) ($arguments['insert-mode'] ?? 'insert');
$dryRun       = isset($arguments['dry-run']);

if ($sourceEntity <= 0) {
    print "Error: --entity must be a positive integer.\n";
    exit(1);
}

if (!in_array($scope, ['digirisk', 'core', 'full'], true)) {
    print "Error: --scope must be digirisk, core or full.\n";
    exit(1);
}

if (!in_array($insertMode, ['insert', 'ignore', 'replace'], true)) {
    print "Error: --insert-mode must be insert, ignore or replace.\n";
    exit(1);
}

$sourceEntities = [$sourceEntity];
if (!empty($arguments['extra-entities'])) {
    foreach (explode(',', (string) $arguments['extra-entities']) as $extraEntity) {
        $extraEntity = (int) trim($extraEntity);
        if ($extraEntity > 0 && !in_array($extraEntity, $sourceEntities, true)) {
            $sourceEntities[] = $extraEntity;
        }
    }
}

$options = [
    'entities'          => $sourceEntities,
    'target_entity'     => $targetEntity,
    'scope'             => $scope,
    'insert_mode'       => $insertMode,
    'chunk'             => max(1, (int) ($arguments['chunk'] ?? 200)),
    'modules'           => array_filter(array_map('trim', explode(',', (string) ($arguments['with-modules'] ?? '')))),
    'with_dictionaries' => isset($arguments['with-dictionaries']),
    'with_files'        => isset($arguments['with-files']),
    'purge'             => isset($arguments['purge']),
    'include'           => array_filter(array_map('trim', explode(',', (string) ($arguments['include-table'] ?? '')))),
    'exclude'           => array_filter(array_map('trim', explode(',', (string) ($arguments['exclude-table'] ?? '')))),
    'output_dir'        => rtrim(str_replace('\\', '/', (string) ($arguments['output'] ?? DOL_DATA_ROOT . '/digiriskdolibarr/entity_export/entity_' . $sourceEntity . '_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S'))), '/')
];

$plan    = digirisk_entity_transfer_build_plan($db, $options);
$counted = digirisk_entity_transfer_count_plan($db, $plan, $sourceEntities);

print "DigiRisk entity export\n";
print '  Database      : ' . $db->database_name . ' (prefix ' . MAIN_DB_PREFIX . ")\n";
print '  Source entity : ' . implode(', ', $sourceEntities) . "\n";
print '  Target entity : ' . $targetEntity . "\n";
print '  Scope         : ' . $scope . "\n";
print '  Tables        : ' . count($plan['tables']) . "\n";
print "\n";

foreach ($counted['counts'] as $short => $count) {
    printf("  %-6d %s\n", $count, MAIN_DB_PREFIX . $short);
}

print "\n  Total rows: " . $counted['total'] . "\n";

if (!empty($counted['skipped'])) {
    print "\n  Tables holding rows but not exported:\n";
    foreach ($counted['skipped'] as $short => $reason) {
        print '    - ' . MAIN_DB_PREFIX . $short . ' : ' . $reason . "\n";
    }
}

if ($dryRun) {
    print "\nDry run: nothing written.\n";
    exit(0);
}

$options['plan']    = $plan;
$options['counted'] = $counted;

$export = digirisk_entity_transfer_export($db, $options);

foreach ($export['errors'] as $message) {
    print '  ! ' . $message . "\n";
}

if (empty($export['sql_path'])) {
    print "\nExport failed.\n";
    exit(1);
}

print "\n  Dump written  : " . $export['sql_path'] . ' (' . dol_print_size(dol_filesize($export['sql_path'])) . ")\n";
print '  Rows written  : ' . $export['rows'] . "\n";

if (!empty($export['documents']['included'])) {
    print '  Documents     : ' . $export['documents']['files'] . ' files in ' . $export['dir'] . "/documents\n";
}

print '  Manifest      : ' . $export['dir'] . "/manifest.json\n";
print "\nDone. Import it with:\n";
print '  php scripts/import_entity.php --input=' . $export['dir'] . " --confirm\n";

$db->close();

exit(empty($export['errors']) ? 0 : 1);
