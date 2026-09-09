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
 * \file    lib/digiriskdolibarr_entity_transfer.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions to move one entity to a mono entity Dolibarr
 *
 * Shared by scripts/export_entity.php and scripts/import_entity.php.
 * MySQL / MariaDB only: the generated dump uses backquoted identifiers.
 */

/**
 * Placeholder compiled twice in a WHERE template: with the source entities to
 * select the rows to export, with the target entity to purge the destination.
 */
const DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER = '{{ENTITIES}}';

/**
 * Tables never exported. Their primary keys are rebuilt by the module activation
 * on the target install, so carrying the source values over would create rows
 * pointing at the wrong definitions.
 *
 * @return array<string> Table names without the database prefix
 */
function digirisk_entity_transfer_excluded_tables(): array
{
    return [
        'rights_def', 'user_rights', 'usergroup_rights',
        'menu', 'boxes', 'boxes_def', 'document_model',
        'events', 'session', 'notify', 'cronjob',
        'accounting_bookkeeping', 'accounting_bookkeeping_tmp',
        // The list of the entities themselves has no meaning on a mono entity install
        'entity', 'entity_extrafields'
    ];
}

/**
 * Table name patterns owned by DigiRisk and by its Saturne framework.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function digirisk_entity_transfer_digirisk_patterns(): array
{
    return [
        'digiriskdolibarr_*', 'saturne_*',
        'categorie_risk', 'categorie_riskassessment', 'categorie_accident',
        'categorie_accidentinvestigation', 'categorie_firepermit',
        'categorie_preventionplan', 'categorie_ticket'
    ];
}

/**
 * Dictionary tables shipped by DigiRisk. Only exported with --with-dictionaries,
 * because the module activation already fills them on the target install.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function digirisk_entity_transfer_dictionary_patterns(): array
{
    return [
        'c_digiriskdolibarr_*', 'c_accident_*', 'c_accidentinvestigation_*',
        'c_preventionplan_*', 'c_firepermit_*', 'c_lesion_*',
        'c_relative_location', 'c_conventions_collectives'
    ];
}

/**
 * Core Dolibarr tables the DigiRisk objects point at. Exported with --scope=core
 * so that users, third parties, tickets and projects still resolve after import.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function digirisk_entity_transfer_core_patterns(): array
{
    return [
        'user', 'user_extrafields', 'usergroup', 'usergroup_user',
        'societe', 'societe_extrafields', 'socpeople', 'socpeople_extrafields',
        'ticket', 'ticket_extrafields', 'projet', 'projet_extrafields',
        'projet_task', 'projet_task_extrafields', 'projet_task_time',
        'actioncomm', 'actioncomm_extrafields', 'actioncomm_resources',
        'categorie', 'categorie_lang', 'categories_extrafields',
        'ecm_files', 'ecm_files_extrafields', 'ecm_directories',
        'c_email_templates'
    ];
}

/**
 * Satellite tables shared by every module: they hold DigiRisk rows among rows of
 * other modules, so they always need their own WHERE clause.
 *
 * @return array<string> Table names without the database prefix
 */
function digirisk_entity_transfer_satellite_tables(): array
{
    return ['const', 'extrafields', 'ecm_files', 'ecm_directories', 'element_element'];
}

/**
 * List the modules that own tables of their own next to DigiRisk, so the caller can
 * offer to take them along. DigiRisk leans on some of them (DoliLetter carries the
 * diffusion of the prevention plans, DigiQuali the controls linked to the elements),
 * and their tables belong to the entity just like the DigiRisk ones.
 *
 * @param  DoliDB                            $db     Database handler
 * @param  array<string,array<string,string>> $schema Database structure, read when not given
 * @return array<string>                             Module names, sorted
 */
function digirisk_entity_transfer_sibling_modules(DoliDB $db, array $schema = []): array
{
    global $conf;

    if (empty($schema)) {
        $schema = digirisk_entity_transfer_get_schema($db);
    }

    // Multicompany owns the entity list itself: its tables must never travel to a mono entity install
    $ignored = ['digiriskdolibarr', 'saturne', 'multicompany'];
    $modules = [];

    foreach ((array) $conf->modules as $module) {
        $module = strtolower($module);
        if (in_array($module, $ignored, true)) {
            continue;
        }

        // Only the modules installed under custom/: a core module named like the prefix of a
        // core table (user, societe, facture...) would otherwise land in the list and let the
        // caller drag half of Dolibarr into the dump
        if (!is_dir(DOL_DOCUMENT_ROOT . '/custom/' . $module)) {
            continue;
        }

        foreach ($schema as $short => $columns) {
            if (strpos($short, $module . '_') === 0 && isset($columns['entity'])) {
                $modules[] = $module;
                break;
            }
        }
    }

    sort($modules);

    return $modules;
}

/**
 * Modules ticked by default: those DigiRisk really works with, so a plain export
 * does not silently leave half of the business data behind.
 *
 * @return array<string> Module names
 */
function digirisk_entity_transfer_default_modules(): array
{
    return ['doliletter', 'digiquali'];
}

/**
 * Map a Dolibarr element type (as stored in llx_element_element or in the name of
 * a llx_categorie_xxx link table) to the table holding those objects.
 * Types not listed here are resolved by their own name, which is the convention
 * followed by every Saturne module (digiriskdolibarr_risk => llx_digiriskdolibarr_risk).
 *
 * @return array<string,string> Element type => table name without the database prefix
 */
function digirisk_entity_transfer_element_table_map(): array
{
    return [
        'action'                => 'actioncomm',
        'accident'              => 'digiriskdolibarr_accident',
        'accidentinvestigation' => 'digiriskdolibarr_accident_investigation',
        'agenda'                => 'actioncomm',
        'commande'              => 'commande',
        'contact'               => 'socpeople',
        'contrat'               => 'contrat',
        'expedition'            => 'expedition',
        'facture'               => 'facture',
        'facture_fourn'         => 'facture_fourn',
        'fichinter'             => 'fichinter',
        'firepermit'            => 'digiriskdolibarr_firepermit',
        'invoice'               => 'facture',
        'invoice_supplier'      => 'facture_fourn',
        'member'                => 'adherent',
        'order'                 => 'commande',
        'order_supplier'        => 'commande_fournisseur',
        'preventionplan'        => 'digiriskdolibarr_preventionplan',
        'product'               => 'product',
        'productlot'            => 'product_lot',
        'project'               => 'projet',
        'project_task'          => 'projet_task',
        'propal'                => 'propal',
        'reception'             => 'reception',
        'risk'                  => 'digiriskdolibarr_risk',
        'riskassessment'        => 'digiriskdolibarr_riskassessment',
        'shipping'              => 'expedition',
        'societe'               => 'societe',
        'supplier_proposal'     => 'supplier_proposal',
        'task'                  => 'projet_task',
        'thirdparty'            => 'societe',
        'ticket'                => 'ticket',
        'user'                  => 'user'
    ];
}

/**
 * Child tables whose owner cannot be guessed from their name. The xxx_extrafields
 * convention is handled separately and does not need an entry here.
 *
 * @return array<string,array{parent:string,key:string}> Child table => owner table and foreign key
 */
function digirisk_entity_transfer_child_map(): array
{
    return [
        'projet_task'         => ['parent' => 'projet',      'key' => 'fk_projet'],
        'projet_task_time'    => ['parent' => 'projet_task', 'key' => 'fk_task'],
        'usergroup_user'      => ['parent' => 'usergroup',   'key' => 'fk_usergroup'],
        'categorie_lang'      => ['parent' => 'categorie',   'key' => 'fk_category'],
        'actioncomm_resources' => ['parent' => 'actioncomm', 'key' => 'fk_actioncomm'],
        // The table is plural while its owner is not, the xxx_extrafields convention misses it
        'categories_extrafields' => ['parent' => 'categorie', 'key' => 'fk_object'],
        // Line tables whose foreign key does not repeat the name of their owner
        'commande_fournisseurdet' => ['parent' => 'commande_fournisseur', 'key' => 'fk_commande'],
        'expeditiondet_batch'     => ['parent' => 'expeditiondet',        'key' => 'fk_expeditiondet'],
        'hrm_skilldet'            => ['parent' => 'hrm_skill',            'key' => 'fk_skill'],
        'product_batch'           => ['parent' => 'product_stock',        'key' => 'fk_product_stock'],
        'receptiondet_batch'      => ['parent' => 'reception',            'key' => 'fk_reception'],
        'subscription'            => ['parent' => 'adherent',             'key' => 'fk_adherent']
    ];
}

/**
 * Read the structure of the database tables.
 *
 * @param  DoliDB                            $db       Database handler
 * @param  array<string>                     $patterns Only introspect the tables matching one of those patterns, all of them if empty
 * @return array<string,array<string,string>>           Unprefixed table name => column name => column type
 */
function digirisk_entity_transfer_get_schema(DoliDB $db, array $patterns = []): array
{
    $schema = [];

    foreach ($db->DDLListTables($db->database_name) as $table) {
        if (strpos($table, MAIN_DB_PREFIX) !== 0) {
            continue;
        }

        $short = substr($table, strlen(MAIN_DB_PREFIX));
        if (!empty($patterns) && !digirisk_entity_transfer_match($short, $patterns)) {
            continue;
        }

        $columns = [];
        foreach ($db->DDLInfoTable($table) as $info) {
            $columns[$info[0]] = strtolower($info[1]);
        }

        $schema[$short] = $columns;
    }

    ksort($schema);

    return $schema;
}

/**
 * Tell whether a table name matches one of the given patterns.
 *
 * @param  string        $table    Unprefixed table name
 * @param  array<string> $patterns Patterns understood by fnmatch()
 * @return bool
 */
function digirisk_entity_transfer_match(string $table, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        if ($table === $pattern || fnmatch($pattern, $table)) {
            return true;
        }
    }

    return false;
}

/**
 * Return the primary key of a table.
 *
 * @param  array<string,string> $columns Column name => column type
 * @return string                        Primary key name, empty if the table has none
 */
function digirisk_entity_transfer_primary_key(array $columns): string
{
    foreach (['rowid', 'id'] as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Guess the owner of a table whose name is the name of its owner plus a suffix:
 * llx_facturedet belongs to llx_facture, llx_societe_commerciaux to llx_societe.
 * The longest matching prefix wins, and the foreign key must really exist, so a
 * wrong guess ends up unresolved and reported instead of silently filtering rows.
 *
 * @param  string                            $short  Unprefixed table name
 * @param  array<string,array<string,string>> $schema Database structure
 * @return array{parent:string,key:string}|null      Owner table and foreign key, null when not resolved
 */
function digirisk_entity_transfer_guess_parent(string $short, array $schema): ?array
{
    // Foreign keys Dolibarr names after an alias rather than after the table
    $aliases = ['societe' => 'soc', 'projet' => 'project', 'adherent' => 'member'];

    for ($length = strlen($short) - 1; $length > 2; $length--) {
        $candidate = rtrim(substr($short, 0, $length), '_');

        if ($candidate === $short || !isset($schema[$candidate])) {
            continue;
        }

        $keys = ['fk_' . $candidate];
        if (isset($aliases[$candidate])) {
            $keys[] = 'fk_' . $aliases[$candidate];
        }

        foreach ($keys as $key) {
            if (isset($schema[$short][$key])) {
                return ['parent' => $candidate, 'key' => $key];
            }
        }
    }

    return null;
}

/**
 * Build the WHERE template restricting a child table to the rows owned by its parent.
 * Recursive: a grand child produces nested sub queries up to the table holding the entity.
 *
 * @param  string                            $short   Unprefixed child table name
 * @param  array<string,array<string,string>> $schema  Database structure
 * @param  int                               $depth   Current recursion depth
 * @return array{where:string,depth:int}|null         WHERE template and depth of the chain, null when unresolved
 */
function digirisk_entity_transfer_child_where(string $short, array $schema, int $depth = 0): ?array
{
    if ($depth > 4) {
        return null;
    }

    $childMap = digirisk_entity_transfer_child_map();

    if (isset($childMap[$short])) {
        $parent = $childMap[$short]['parent'];
        $key    = $childMap[$short]['key'];
    } elseif (substr($short, -12) === '_extrafields') {
        $parent = substr($short, 0, -12);
        $key    = 'fk_object';
    } else {
        $guess = digirisk_entity_transfer_guess_parent($short, $schema);
        if ($guess === null) {
            return null;
        }

        $parent = $guess['parent'];
        $key    = $guess['key'];
    }

    if (!isset($schema[$parent]) || !isset($schema[$short][$key])) {
        return null;
    }

    $parentKey = digirisk_entity_transfer_primary_key($schema[$parent]);
    if (empty($parentKey)) {
        return null;
    }

    if (isset($schema[$parent]['entity'])) {
        $parentWhere = 'entity IN (' . DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER . ')';
    } else {
        $parentResult = digirisk_entity_transfer_child_where($parent, $schema, $depth + 1);
        if ($parentResult === null) {
            return null;
        }
        $parentWhere = $parentResult['where'];
        $depth       = $parentResult['depth'];
    }

    $where = $key . ' IN (SELECT ' . $parentKey . ' FROM ' . MAIN_DB_PREFIX . $parent . ' WHERE ' . $parentWhere . ')';

    return ['where' => $where, 'depth' => $depth + 1];
}

/**
 * Build the WHERE template of a llx_categorie_xxx link table, restricted to the
 * categorised objects of the entity.
 *
 * @param  string                            $short  Unprefixed link table name
 * @param  array<string,array<string,string>> $schema Database structure
 * @return string|null                                WHERE template, null when the linked table is unknown
 */
function digirisk_entity_transfer_category_link_where(string $short, array $schema): ?string
{
    $type = substr($short, strlen('categorie_'));
    $map  = digirisk_entity_transfer_element_table_map();

    $target = $map[$type] ?? $type;
    if (!isset($schema[$target]) || !isset($schema[$target]['entity'])) {
        return null;
    }

    foreach (array_keys($schema[$short]) as $column) {
        if ($column === 'fk_categorie' || strpos($column, 'fk_') !== 0) {
            continue;
        }

        return $column . ' IN (SELECT ' . digirisk_entity_transfer_primary_key($schema[$target])
            . ' FROM ' . MAIN_DB_PREFIX . $target . ' WHERE entity IN (' . DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER . '))';
    }

    return null;
}

/**
 * Build the WHERE template of llx_element_element. Both ends of a link must be
 * exported, otherwise the import would create links pointing at rows that do not
 * exist on the target, which breaks fetchObjectLinked().
 *
 * @param  DoliDB                            $db      Database handler
 * @param  array<string,array<string,string>> $schema  Database structure
 * @param  array<string>                     $allowed Unprefixed names of the tables already selected for the export
 * @return string|null                                WHERE template, null when no element type could be resolved
 */
function digirisk_entity_transfer_element_element_where(DoliDB $db, array $schema, array $allowed): ?string
{
    $map = digirisk_entity_transfer_element_table_map();

    $sql  = 'SELECT DISTINCT sourcetype AS type FROM ' . MAIN_DB_PREFIX . 'element_element';
    $sql .= ' UNION SELECT DISTINCT targettype AS type FROM ' . MAIN_DB_PREFIX . 'element_element';

    $resql = $db->query($sql);
    if (!$resql) {
        return null;
    }

    $types = [];
    while ($obj = $db->fetch_object($resql)) {
        $types[] = $obj->type;
    }
    $db->free($resql);

    $sourceConditions = [];
    $targetConditions = [];

    foreach ($types as $type) {
        $table = $map[$type] ?? $type;

        // The end must be a table of the export, and entity scoped so the sub query can filter it
        if (!in_array($table, $allowed, true) || !isset($schema[$table]['entity'])) {
            continue;
        }

        $subQuery = 'SELECT ' . digirisk_entity_transfer_primary_key($schema[$table]) . ' FROM ' . MAIN_DB_PREFIX . $table
            . ' WHERE entity IN (' . DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER . ')';

        $sourceConditions[] = "(sourcetype = '" . $db->escape($type) . "' AND fk_source IN (" . $subQuery . '))';
        $targetConditions[] = "(targettype = '" . $db->escape($type) . "' AND fk_target IN (" . $subQuery . '))';
    }

    if (empty($sourceConditions)) {
        return null;
    }

    return '(' . implode(' OR ', $sourceConditions) . ') AND (' . implode(' OR ', $targetConditions) . ')';
}

/**
 * Build the WHERE template of the tables shared by every module.
 *
 * @param  string              $short   Unprefixed table name
 * @param  array<string,mixed> $options Export options
 * @return string|null                  WHERE template, null when the table needs no special case
 */
function digirisk_entity_transfer_satellite_where(string $short, array $options): ?string
{
    $entityWhere = 'entity IN (' . DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER . ')';

    // Only the digirisk scope narrows those tables down to the DigiRisk rows: as soon as
    // the core objects travel too, their settings, their extrafields and their files must follow
    $digiriskScope = ($options['scope'] === 'digirisk');

    switch ($short) {
        case 'const':
            // MAIN_MODULE_ constants are written by the module activation: importing them
            // would flag the modules as enabled without creating their menus and directories
            $where = $entityWhere . " AND name NOT LIKE 'MAIN_MODULE_%'";
            if ($digiriskScope) {
                $where .= " AND (name LIKE 'DIGIRISK%' OR name LIKE 'SATURNE%')";
            }
            return $where;

        case 'extrafields':
            if (!$digiriskScope) {
                return $entityWhere;
            }
            return $entityWhere . " AND (elementtype LIKE 'digiriskdolibarr%' OR elementtype LIKE 'saturne%')";

        case 'ecm_files':
        case 'ecm_directories':
            if (!$digiriskScope) {
                return $entityWhere;
            }
            $column = ($short === 'ecm_files' ? 'filepath' : 'label');
            return $entityWhere . " AND " . $column . " LIKE 'digiriskdolibarr%'";
    }

    return null;
}

/**
 * Sort weight of a table, so that the dump inserts owners before the rows pointing at them.
 *
 * @param  string                $short   Unprefixed table name
 * @param  array<string,mixed>   $table   Table description of the plan
 * @return int                            Lower is inserted first
 */
function digirisk_entity_transfer_weight(string $short, array $table): int
{
    if (strpos($short, 'c_') === 0) {
        return 10;
    }

    $priorities = [
        'user'                                => 20,
        'usergroup'                           => 20,
        'societe'                             => 21,
        'socpeople'                           => 22,
        'projet'                              => 23,
        'categorie'                           => 24,
        'digiriskdolibarr_digiriskstandard'   => 25,
        'digiriskdolibarr_digiriskelement'    => 26
    ];

    if (isset($priorities[$short])) {
        return $priorities[$short];
    }

    if ($table['origin'] === 'entity') {
        return 30;
    }

    if ($table['origin'] === 'parent') {
        return 40 + (int) $table['depth'];
    }

    return 50;
}

/**
 * Build the list of the tables to export and the WHERE template of each of them.
 *
 * @param  DoliDB              $db      Database handler
 * @param  array<string,mixed> $options Export options: scope, with_dictionaries, include, exclude
 * @return array{tables:array<string,array<string,mixed>>,skipped:array<string,string>}
 */
function digirisk_entity_transfer_build_plan(DoliDB $db, array $options): array
{
    $schema = digirisk_entity_transfer_get_schema($db);

    $patterns = digirisk_entity_transfer_digirisk_patterns();
    if ($options['scope'] !== 'digirisk') {
        $patterns = array_merge($patterns, digirisk_entity_transfer_core_patterns());
    }
    if (!empty($options['with_dictionaries'])) {
        $patterns = array_merge($patterns, digirisk_entity_transfer_dictionary_patterns());
    }

    // Modules working alongside DigiRisk: their own tables and their dictionaries
    foreach ((array) ($options['modules'] ?? []) as $module) {
        $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module));
        if (empty($module)) {
            continue;
        }

        $patterns[] = $module . '_*';
        if (!empty($options['with_dictionaries'])) {
            $patterns[] = 'c_' . $module . '_*';
        }
    }
    $patterns = array_merge($patterns, digirisk_entity_transfer_satellite_tables(), $options['include']);

    $excluded  = array_merge(digirisk_entity_transfer_excluded_tables(), $options['exclude']);
    $satellite = digirisk_entity_transfer_satellite_tables();

    $tables  = [];
    $skipped = [];

    foreach ($schema as $short => $columns) {
        if (digirisk_entity_transfer_match($short, $excluded)) {
            continue;
        }

        $selected = ($options['scope'] === 'full') || digirisk_entity_transfer_match($short, $patterns);
        if (!$selected) {
            continue;
        }

        // Deferred: its WHERE needs the list of the tables selected by this very loop
        if ($short === 'element_element') {
            continue;
        }

        $where = null;
        $origin = '';
        $depth  = 0;

        if (in_array($short, $satellite, true)) {
            $where  = digirisk_entity_transfer_satellite_where($short, $options);
            $origin = 'custom';
        }

        if ($where === null && isset($columns['entity'])) {
            $where  = 'entity IN (' . DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER . ')';
            $origin = 'entity';
        }

        if ($where === null && strpos($short, 'categorie_') === 0 && isset($columns['fk_categorie'])) {
            $where  = digirisk_entity_transfer_category_link_where($short, $schema);
            $origin = 'custom';
        }

        if ($where === null) {
            $child = digirisk_entity_transfer_child_where($short, $schema);
            if ($child !== null) {
                $where  = $child['where'];
                $origin = 'parent';
                $depth  = $child['depth'];
            }
        }

        if ($where === null) {
            // Core dictionaries are the same on every install and belong to no entity:
            // reporting them would bury the tables that really lose data
            if (strpos($short, 'c_') !== 0 || digirisk_entity_transfer_match($short, $options['include'])) {
                $skipped[$short] = 'No entity column and no owner table resolved';
            }
            continue;
        }

        $tables[$short] = [
            'name'           => MAIN_DB_PREFIX . $short,
            'short'          => $short,
            'columns'        => $columns,
            'where_template' => $where,
            'origin'         => $origin,
            'depth'          => $depth,
            'primary_key'    => digirisk_entity_transfer_primary_key($columns)
        ];
    }

    if (isset($schema['element_element']) && !digirisk_entity_transfer_match('element_element', $excluded)) {
        $where = digirisk_entity_transfer_element_element_where($db, $schema, array_keys($tables));

        if ($where === null) {
            $skipped['element_element'] = 'No exported element type on both ends of the links';
        } else {
            $tables['element_element'] = [
                'name'           => MAIN_DB_PREFIX . 'element_element',
                'short'          => 'element_element',
                'columns'        => $schema['element_element'],
                'where_template' => $where,
                'origin'         => 'custom',
                'depth'          => 0,
                'primary_key'    => digirisk_entity_transfer_primary_key($schema['element_element'])
            ];
        }
    }

    uasort($tables, function ($a, $b) {
        $weightA = digirisk_entity_transfer_weight($a['short'], $a);
        $weightB = digirisk_entity_transfer_weight($b['short'], $b);

        if ($weightA === $weightB) {
            return strcmp($a['short'], $b['short']);
        }

        return ($weightA < $weightB ? -1 : 1);
    });

    return ['tables' => $tables, 'skipped' => $skipped];
}

/**
 * Compile a WHERE template with the entities it must apply to.
 *
 * @param  string        $template WHERE template holding the entity placeholder
 * @param  array<int>    $entities Entity identifiers
 * @return string                  Ready to use WHERE clause
 */
function digirisk_entity_transfer_compile_where(string $template, array $entities): string
{
    $entities = array_map('intval', $entities);

    return str_replace(DIGIRISK_TRANSFER_ENTITY_PLACEHOLDER, implode(', ', $entities), $template);
}

/**
 * Format a value as a SQL literal. Binary columns are written as hexadecimal
 * literals, every other value is escaped, which also removes the line breaks:
 * a statement of the dump therefore always fits on a single line.
 *
 * @param  DoliDB     $db    Database handler
 * @param  string|null $value Raw value read from the database
 * @param  string     $type  Column type
 * @return string            SQL literal
 */
function digirisk_entity_transfer_format_value(DoliDB $db, ?string $value, string $type): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (preg_match('/(blob|binary)/', $type)) {
        return ($value === '' ? "''" : '0x' . bin2hex($value));
    }

    return "'" . $db->escape($value) . "'";
}

/**
 * Split the arguments of a CLI script.
 *
 * @param  array<string>       $argv Arguments of the script, $argv[0] included
 * @return array<string,mixed>       Option name without the leading dashes => value, true for a flag
 */
function digirisk_entity_transfer_parse_args(array $argv): array
{
    $arguments = [];

    foreach (array_slice($argv, 1) as $argument) {
        if (strpos($argument, '--') !== 0) {
            continue;
        }

        $argument = substr($argument, 2);
        $position = strpos($argument, '=');

        if ($position === false) {
            $arguments[$argument] = true;
        } else {
            $arguments[substr($argument, 0, $position)] = substr($argument, $position + 1);
        }
    }

    return $arguments;
}

/**
 * Return the document directory of an entity.
 *
 * @param  int    $entity Entity identifier
 * @return string         Absolute path without trailing slash
 */
function digirisk_entity_transfer_data_root(int $entity): string
{
    return DOL_DATA_ROOT . ($entity > 1 ? '/' . $entity : '');
}

/**
 * Create a directory and its parents. dol_mkdir() runs the path through
 * dol_sanitizePathName(), which rewrites it (a double dash becomes an underscore),
 * so it cannot be trusted for a directory chosen by the caller.
 *
 * @param  string $dir Absolute path
 * @return bool        True when the directory exists at the end
 */
function digirisk_entity_transfer_mkdir(string $dir): bool
{
    if (is_dir($dir)) {
        return true;
    }

    @mkdir($dir, 0755, true);

    if (!is_dir($dir)) {
        dol_mkdir($dir);
    }

    return is_dir($dir);
}

/**
 * Copy a directory tree, for the same reason as digirisk_entity_transfer_mkdir():
 * dolCopyDir() creates its destination directories with dol_mkdir().
 *
 * @param  string        $source   Directory to copy
 * @param  string        $target   Destination directory
 * @param  array<string> $excludes Directory names skipped at any level
 * @return int                     Number of files copied, -1 on failure
 */
function digirisk_entity_transfer_copy_dir(string $source, string $target, array $excludes = ['temp']): int
{
    if (!is_dir($source) || !digirisk_entity_transfer_mkdir($target)) {
        return -1;
    }

    $copied  = 0;
    $entries = scandir($source);

    if ($entries === false) {
        return -1;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || is_link($source . '/' . $entry)) {
            continue;
        }

        if (is_dir($source . '/' . $entry)) {
            if (in_array($entry, $excludes, true)) {
                continue;
            }

            $result = digirisk_entity_transfer_copy_dir($source . '/' . $entry, $target . '/' . $entry, $excludes);
            if ($result < 0) {
                return -1;
            }

            $copied += $result;
            continue;
        }

        if (!@copy($source . '/' . $entry, $target . '/' . $entry)) {
            return -1;
        }

        $copied++;
    }

    return $copied;
}

/**
 * Count the rows every table of the plan will export, and tell which tables hold
 * rows that no filter could reach. An unexportable table only matters when it is
 * not empty: saying so is the only way the caller knows what stays behind.
 *
 * @param  DoliDB              $db       Database handler
 * @param  array<string,mixed> $plan     Plan built by digirisk_entity_transfer_build_plan()
 * @param  array<int>          $entities Entities to export
 * @return array{counts:array<string,int>,exported:array<string,array<string,mixed>>,total:int,skipped:array<string,string>}
 */
function digirisk_entity_transfer_count_plan(DoliDB $db, array $plan, array $entities): array
{
    $counts   = [];
    $exported = [];
    $total    = 0;
    $skipped  = [];

    foreach ($plan['tables'] as $short => $table) {
        $where = digirisk_entity_transfer_compile_where($table['where_template'], $entities);

        $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . $table['name'] . ' WHERE ' . $where);
        if (!$resql) {
            $skipped[$short] = $db->lasterror();
            continue;
        }

        $object = $db->fetch_object($resql);
        $db->free($resql);

        $counts[$short] = (int) $object->nb;
        $total         += $counts[$short];

        if ($counts[$short] > 0) {
            $exported[$short] = $table;
        }
    }

    foreach ($plan['skipped'] as $short => $reason) {
        $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . $short);
        if (!$resql) {
            $skipped[$short] = $reason;
            continue;
        }

        $object = $db->fetch_object($resql);
        $db->free($resql);

        if ((int) $object->nb > 0) {
            $skipped[$short] = $reason . ', ' . (int) $object->nb . ' rows in the table';
        }
    }

    return ['counts' => $counts, 'exported' => $exported, 'total' => $total, 'skipped' => $skipped];
}

/**
 * Export the tables of one entity into a dump replayable on a mono entity Dolibarr.
 * Shared by scripts/export_entity.php and the Tools page.
 *
 * @param  DoliDB              $db      Database handler
 * @param  array<string,mixed> $options entities, target_entity, scope, output_dir and the flags of the CLI script
 * @return array<string,mixed>          dir, sql_file, sql_path, rows, counts, skipped, documents, manifest, errors
 */
function digirisk_entity_transfer_export(DoliDB $db, array $options): array
{
    $options += [
        'entities'          => [1],
        'target_entity'     => 1,
        'scope'             => 'digirisk',
        'with_dictionaries' => false,
        'with_files'        => false,
        'purge'             => false,
        'modules'           => [],
        'insert_mode'       => 'insert',
        'chunk'             => 200,
        'include'           => [],
        'exclude'           => [],
        'output_dir'        => '',
        'plan'              => null,
        'counted'           => null
    ];

    $result = ['dir' => $options['output_dir'], 'rows' => 0, 'errors' => [], 'documents' => ['included' => false]];

    if (!digirisk_entity_transfer_mkdir($options['output_dir'])) {
        $result['errors'][] = 'Cannot create the output directory ' . $options['output_dir'];
        return $result;
    }

    $plan    = $options['plan'] ?? digirisk_entity_transfer_build_plan($db, $options);
    $counted = $options['counted'] ?? digirisk_entity_transfer_count_plan($db, $plan, $options['entities']);

    $result['counts']  = $counted['counts'];
    $result['total']   = $counted['total'];
    $result['skipped'] = $counted['skipped'];

    $sqlFileName = 'digirisk_entity_' . ((int) $options['entities'][0]) . '.sql';
    $sqlPath     = $options['output_dir'] . '/' . $sqlFileName;

    $handle = fopen($sqlPath, 'w');
    if ($handle === false) {
        $result['errors'][] = 'Cannot write ' . $sqlPath;
        return $result;
    }

    $insertVerb = ($options['insert_mode'] === 'replace' ? 'REPLACE INTO' : ($options['insert_mode'] === 'ignore' ? 'INSERT IGNORE INTO' : 'INSERT INTO'));

    fwrite($handle, "-- DigiRisk entity export\n");
    fwrite($handle, '-- Generated     : ' . dol_print_date(dol_now(), 'standard') . "\n");
    fwrite($handle, '-- Source        : ' . $db->database_name . ' entity ' . implode(', ', $options['entities']) . ' (prefix ' . MAIN_DB_PREFIX . ")\n");
    fwrite($handle, '-- Target entity : ' . (int) $options['target_entity'] . "\n");
    fwrite($handle, '-- Scope         : ' . $options['scope'] . "\n");
    fwrite($handle, "-- Enable the DigiRisk module on the target install BEFORE replaying this dump.\n");
    fwrite($handle, "SET NAMES utf8mb4;\n");
    fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");

    if (!empty($options['purge'])) {
        fwrite($handle, "-- Purge of the target entity, children first\n");
        foreach (array_reverse($counted['exported'], true) as $table) {
            $purgeWhere = digirisk_entity_transfer_compile_where($table['where_template'], [(int) $options['target_entity']]);
            fwrite($handle, 'DELETE FROM ' . $table['name'] . ' WHERE ' . $purgeWhere . ";\n");
        }
    }

    $manifestTables = [];

    foreach ($counted['exported'] as $short => $table) {
        $where       = digirisk_entity_transfer_compile_where($table['where_template'], $options['entities']);
        $columnNames = array_keys($table['columns']);
        $columnList  = '`' . implode('`, `', $columnNames) . '`';
        $primaryKey  = $table['primary_key'];

        fwrite($handle, '-- ' . $table['name'] . ' (' . $counted['counts'][$short] . " rows)\n");

        // Read by pages, a table like llx_actioncomm does not fit in memory as a whole.
        // A table without primary key cannot be paged safely, it is read in one go
        $pageSize = 2000;
        $offset   = 0;
        $rows     = [];
        $bytes    = 0;

        do {
            $sql = 'SELECT ' . $columnList . ' FROM ' . $table['name'] . ' WHERE ' . $where;
            if (!empty($primaryKey)) {
                $sql .= ' ORDER BY ' . $primaryKey . ' LIMIT ' . $pageSize . ' OFFSET ' . $offset;
            }

            $resql = $db->query($sql);
            if (!$resql) {
                $result['errors'][] = $table['name'] . ' : ' . $db->lasterror();
                break;
            }

            $fetched = 0;
            while ($row = $db->fetch_array($resql)) {
                $values = [];
                foreach ($columnNames as $column) {
                    if ($column === 'entity') {
                        $values[] = (string) ((int) $options['target_entity']);
                        continue;
                    }

                    $values[] = digirisk_entity_transfer_format_value($db, $row[$column], $table['columns'][$column]);
                }

                $tuple  = '(' . implode(', ', $values) . ')';
                $rows[] = $tuple;
                $bytes += strlen($tuple);
                $fetched++;
                $result['rows']++;

                // Flush on the row count and on the statement size, to stay below max_allowed_packet
                if (count($rows) >= $options['chunk'] || $bytes > 2000000) {
                    fwrite($handle, $insertVerb . ' ' . $table['name'] . ' (' . $columnList . ') VALUES ' . implode(', ', $rows) . ";\n");
                    $rows  = [];
                    $bytes = 0;
                }
            }
            $db->free($resql);

            $offset += $pageSize;
        } while (!empty($primaryKey) && $fetched === $pageSize);

        if (!empty($rows)) {
            fwrite($handle, $insertVerb . ' ' . $table['name'] . ' (' . $columnList . ') VALUES ' . implode(', ', $rows) . ";\n");
        }

        $manifestTables[] = [
            'name'  => $table['name'],
            'short' => $short,
            'rows'  => $counted['counts'][$short],
            'purge' => digirisk_entity_transfer_compile_where($table['where_template'], [(int) $options['target_entity']])
        ];
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($handle);

    if (!empty($options['with_files'])) {
        $sourceRoot = digirisk_entity_transfer_data_root((int) $options['entities'][0]);
        $sourceDir  = ($options['scope'] === 'digirisk' ? $sourceRoot . '/digiriskdolibarr' : $sourceRoot);
        $targetDir  = $options['output_dir'] . '/documents' . ($options['scope'] === 'digirisk' ? '/digiriskdolibarr' : '');

        if (!is_dir($sourceDir)) {
            $result['errors'][] = $sourceDir . ' not found, no document copied';
        } else {
            $copied = digirisk_entity_transfer_copy_dir($sourceDir, $targetDir);
            if ($copied < 0) {
                $result['errors'][] = 'Copy of ' . $sourceDir . ' failed';
            } else {
                $result['documents'] = ['included' => true, 'source' => $sourceDir, 'path' => 'documents', 'files' => $copied];
            }
        }
    }

    $manifest = [
        'generated'        => dol_print_date(dol_now(), 'standard'),
        'dolibarr_version' => DOL_VERSION,
        'digirisk_version' => getDolGlobalString('DIGIRISKDOLIBARR_VERSION'),
        'source'           => [
            'database' => $db->database_name,
            'prefix'   => MAIN_DB_PREFIX,
            'entities' => array_map('intval', $options['entities'])
        ],
        'target_entity'    => (int) $options['target_entity'],
        'scope'            => $options['scope'],
        'modules'          => array_values((array) $options['modules']),
        'insert_mode'      => $options['insert_mode'],
        'sql_file'         => $sqlFileName,
        'rows'             => $result['rows'],
        'tables'           => $manifestTables,
        'skipped_tables'   => $counted['skipped'],
        'documents'        => $result['documents']
    ];

    file_put_contents($options['output_dir'] . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $result['sql_file'] = $sqlFileName;
    $result['sql_path'] = $sqlPath;
    $result['manifest'] = $manifest;

    return $result;
}

/**
 * Replay a dump produced by digirisk_entity_transfer_export().
 * Shared by scripts/import_entity.php and the Tools page.
 *
 * @param  DoliDB              $db      Database handler
 * @param  string              $sqlFile Path of the dump to replay
 * @param  array<string,mixed> $options manifest, purge, dry_run, stop_on_error, documents_dir, target_entity
 * @return array<string,mixed>          statements, executed, purged, errors, messages, checks
 */
function digirisk_entity_transfer_import(DoliDB $db, string $sqlFile, array $options): array
{
    $options += [
        'manifest'      => [],
        'purge'         => false,
        'dry_run'       => false,
        'stop_on_error' => false,
        'documents_dir' => '',
        'target_entity' => 1
    ];

    $manifest     = $options['manifest'];
    $sourcePrefix = (string) ($manifest['source']['prefix'] ?? MAIN_DB_PREFIX);

    $result = ['statements' => 0, 'executed' => 0, 'purged' => 0, 'errors' => 0, 'messages' => [], 'checks' => [], 'documents' => 0];

    $run = function (string $sql) use ($db, $options, &$result): bool {
        if (!empty($options['dry_run'])) {
            return true;
        }

        if (!$db->query($sql)) {
            $result['errors']++;
            $result['messages'][] = $db->lasterror() . ' | ' . dol_trunc($sql, 200);
            return false;
        }

        return true;
    };

    if (!empty($options['purge'])) {
        if (empty($manifest['tables'])) {
            $result['messages'][] = 'The purge needs the manifest.json of the export';
            $result['errors']++;
            return $result;
        }

        // Children first, the manifest lists the tables in insertion order
        foreach (array_reverse($manifest['tables']) as $table) {
            $name  = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['name']);
            $where = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['purge']);

            if ($run('DELETE FROM ' . $name . ' WHERE ' . $where)) {
                $result['purged']++;
            }
        }
    }

    $handle = fopen($sqlFile, 'r');
    if ($handle === false) {
        $result['messages'][] = 'Cannot read ' . $sqlFile;
        $result['errors']++;
        return $result;
    }

    $buffer = '';

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");

        if ($line === '' || strpos($line, '--') === 0) {
            continue;
        }

        // The export escapes every value, so a statement never spans several lines.
        // The buffer only protects against a dump edited by hand
        $buffer .= ($buffer === '' ? '' : "\n") . $line;
        if (substr($buffer, -1) !== ';') {
            continue;
        }

        $statement = substr($buffer, 0, -1);
        $buffer    = '';

        if ($sourcePrefix !== MAIN_DB_PREFIX) {
            $statement = preg_replace('/\b' . preg_quote($sourcePrefix, '/') . '/', MAIN_DB_PREFIX, $statement);
        }

        $result['statements']++;

        if ($run($statement)) {
            $result['executed']++;
        } elseif (!empty($options['stop_on_error'])) {
            $result['messages'][] = 'Stopped on the first error';
            break;
        }
    }

    fclose($handle);

    if (!empty($options['documents_dir']) && is_dir($options['documents_dir']) && empty($options['dry_run'])) {
        $targetRoot = digirisk_entity_transfer_data_root((int) $options['target_entity']);

        $copied = digirisk_entity_transfer_copy_dir($options['documents_dir'], $targetRoot);
        if ($copied < 0) {
            $result['errors']++;
            $result['messages'][] = 'Copy of the documents failed';
        } else {
            $result['documents'] = $copied;
        }
    }

    if (!empty($manifest['tables']) && empty($options['dry_run'])) {
        foreach ($manifest['tables'] as $table) {
            $name  = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['name']);
            $where = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['purge']);

            $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . $name . ' WHERE ' . $where);
            if (!$resql) {
                $result['messages'][] = $name . ' : ' . $db->lasterror();
                continue;
            }

            $object = $db->fetch_object($resql);
            $db->free($resql);

            $result['checks'][] = ['name' => $name, 'found' => (int) $object->nb, 'expected' => (int) $table['rows']];
        }
    }

    return $result;
}
