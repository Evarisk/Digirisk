<?php
/**
 * Protection cell of a risk row: the protections of the element of the risk, see RiskSign::getProtectionsByElement()
 *
 * Needs $risk, $protections, $i and $totalarray
 */

print '<td class="risk-protection">';
if (empty($protections['byElement'][(int) $risk->fk_element])) {
    print '<span class="risk-protection-none">' . $langs->trans('None') . '</span>';
}
// One picto per category: an element may hold several risk signs of the same one, whose descriptions share its tooltip
$protectionDescriptions = [];
foreach ($protections['byElement'][(int) $risk->fk_element] ?? [] as $protectionRiskSign) {
    $protectionDescriptions[(int) $protectionRiskSign->category][] = trim(preg_replace('/\s+/', ' ', (string) $protectionRiskSign->description));
}
foreach ($protectionDescriptions as $protectionCategoryPosition => $protectionCategoryDescriptions) {
    $protectionCategory = $protections['categories'][$protectionCategoryPosition];
    // The name of the sign, then what its risk signs say about it, on one line
    $protectionLabel    = implode(' / ', array_unique(array_filter($protectionCategoryDescriptions, 'strlen')));
    $protectionLabel    = $protectionCategory['name'] . ($protectionLabel !== '' ? ' : ' . $protectionLabel : '');
    print '<img class="risk-protection-picto" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $protectionCategory['name_thumbnail'] . '" alt="' . dol_escape_htmltag($protectionCategory['name']) . '" title="' . dol_escape_htmltag($protectionLabel) . '" loading="lazy">';
}
print '</td>';
if ( ! $i) $totalarray['nbfield']++;
