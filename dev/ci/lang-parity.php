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
 * \file    dev/ci/lang-parity.php
 * \ingroup digiriskdolibarr
 * \brief   Fail when a key exists in en_US but not in fr_FR.
 *
 * Dolibarr falls back to en_US when the current language file has no entry for a key. A key
 * that only exists in en_US therefore stays in English, and since a language file is loaded
 * for the whole request, the English wording leaks into every module that translates the same
 * generic key. The consumer always loses that race, so it has to be fixed at the source.
 *
 * The other direction - a French key with no English counterpart - is only a missing
 * translation, never a bug: it is counted and printed, never fatal.
 *
 * Usage: php dev/ci/lang-parity.php
 */

$moduleRoot = realpath(__DIR__ . '/../..');
$reference  = $moduleRoot . '/langs/en_US/digiriskdolibarr.lang';
$target     = $moduleRoot . '/langs/fr_FR/digiriskdolibarr.lang';

/**
 * Read the keys of a Dolibarr language file.
 *
 * The parser of Dolibarr accepts spaces around the equal sign, and the files of this module
 * use them for alignment: matching `^KEY=` alone would miss almost every line.
 *
 * @param  string   $file Absolute path of the .lang file
 * @return string[]       Keys, in the order they appear
 */
function langKeys(string $file): array
{
    if (!is_readable($file)) {
        fwrite(STDERR, "Cannot read $file\n");
        exit(1);
    }

    $keys = [];
    foreach (file($file) as $line) {
        if (preg_match('/^([A-Za-z0-9_\/-]+)[ \t]*=/', $line, $matches)) {
            $keys[] = $matches[1];
        }
    }

    return $keys;
}

$referenceKeys = langKeys($reference);
$targetKeys    = langKeys($target);

$missing      = array_values(array_diff($referenceKeys, $targetKeys));
$untranslated = array_values(array_diff($targetKeys, $referenceKeys));

printf("en_US: %d keys, fr_FR: %d keys\n", count($referenceKeys), count($targetKeys));

if (!empty($untranslated)) {
    printf("%d key(s) missing from en_US - untranslated, not fatal: %s\n", count($untranslated), implode(', ', array_slice($untranslated, 0, 10)));
}

if (empty($missing)) {
    echo "Every en_US key has a fr_FR counterpart.\n";
    exit(0);
}

printf("\n%d key(s) exist in en_US but not in fr_FR:\n", count($missing));
foreach ($missing as $key) {
    echo '  ' . $key . "\n";
}
echo "\nAdd them to langs/fr_FR/digiriskdolibarr.lang, or remove them from en_US when they are dead.\n";

exit(1);
