<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_digiriskstandard.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for digirisk standard
 */

/**
 * Prepare digirisk standard pages header
 *
 * @param  DigiriskStandard $object Digirisk standard
 * @return array            $head   Array of tabs
 * @throws Exception
 */
function digiriskstandard_prepare_head(DigiriskStandard $object): array
{
    // Global variables definitions
    global $conf, $langs, $user;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Informations') : '<i class="fas fa-info-circle"></i>';
    $head[$h][2] = 'standardCard';
    $h++;

    // Qualification des unites de travail face aux risques psychosociaux, prise une seule fois pour toute l'organisation
    if ($user->rights->digiriskdolibarr->risk->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_psychosocial.php', 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-brain pictofixedwidth"></i>' . $langs->trans('PsychosocialRisks') : '<i class="fas fa-brain"></i>';
        $head[$h][2] = 'standardPsychosocial';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->legaldisplay->read) {
        $head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_legaldisplay.php", 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file pictofixedwidth"></i>' . $langs->trans('LegalDisplay') : '<i class="fas fa-file"></i>';
        $head[$h][2] = 'standardLegalDisplay';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->informationssharing->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_informationssharing.php', 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-comment-dots pictofixedwidth"></i>' . $langs->trans('InformationsSharing') : '<i class="fas fa-comment-dots"></i>';
        $head[$h][2] = 'standardInformationsSharing';
        $h++;
    }

    if ($user->rights->ticket->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_registerdocument.php', 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-ticket-alt pictofixedwidth"></i>' . $langs->trans('RegisterDocument') : '<i class="fas fa-ticket-alt"></i>';
        $head[$h][2] = 'standardRegisterDocument';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->listingrisksaction->read && $user->rights->digiriskdolibarr->listingrisksphoto->read  && $user->rights->digiriskdolibarr->listingrisksdocument->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksdocument.php', 1) . '?type=standard';
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('ListingRisksHeader', 'PRO') : '<i class="fas fa-list"></i>';
        $head[$h][2] = 'elementListingRisksDocument';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->riskassessmentdocument->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_riskassessmentdocument.php', 1) . '?id=' . $object->id;
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans("RiskAssessmentDocument") : '<i class="fas fa-file-alt"></i>';
        $head[$h][2] = 'standardRiskAssessmentDocument';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->auditreportdocument->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_auditreportdocument.php', 1) . '?id=' . $object->id;
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('AuditReportDocument') : '<i class="fas fa-file-alt"></i>';
        $head[$h][2] = 'standardAuditReportDocument';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->listingrisksenvironmentalaction->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_environment.php', 1) . '?type=standard';
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('ListingRisksHeader', 'ENV') : '<i class="fas fa-list"></i>';
        $head[$h][2] = 'elementEnvironment';
        $h++;
    }

    $head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=digiriskstandard&show_nav=0&handle_photo=true';
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') : '<i class="fas fa-calendar-alt"></i>';
    $head[$h][2] = 'agenda';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskstandard@digiriskdolibarr');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskstandard@digiriskdolibarr', 'remove');

    return $head;
}

/**
 * Folder of the site plans of the risk assessment document, relative to the module folder: the ODT and
 * PDF documents read their plans there
 */
const DIGIRISKSTANDARD_SITE_PLAN_SUBDIR = 'riskassessmentdocument/siteplans';

/**
 * Saturne media block of the site plans, on the risk assessment document card
 *
 * @param  bool   $canAdd True when plans can be added, from the computer or from the media library
 * @return string         HTML of the block
 */
function digiriskstandard_site_plan_media_block(bool $canAdd): string
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once __DIR__ . '/digiriskdolibarr_media.lib.php';
    dol_include_once('/saturne/lib/medias.lib.php');

    // The block creates its folder when shown: looking at the card leaves no empty folder behind
    $missingDirs = digiriskMediaMissingDirs($conf->digiriskdolibarr->dir_output . '/' . DIGIRISKSTANDARD_SITE_PLAN_SUBDIR);

    $block = saturne_render_media_block('digiriskdolibarr', DIGIRISKSTANDARD_SITE_PLAN_SUBDIR, 'siteplans', '', [
        'show_photo'   => true,
        'show_audio'   => false,
        'show_file'    => false,
        'show_upload'  => $canAdd,
        'show_library' => $canAdd,
    ]);

    foreach ($missingDirs as $missingDir) {
        dol_delete_dir($missingDir, 1);
    }

    return $block;
}

/**
 * Plan added, taken from the media library or removed from the media block of the risk assessment
 * document card: the Saturne media block posts on the page and expects itself back, refreshed. The
 * folder comes from the browser, so only the one of the site plans is writable.
 *
 * @param  string $action    Requested action: anything but uploadPhoto, linkPhoto and deletePhoto returns at once
 * @param  bool   $canAdd    True when plans can be added
 * @param  bool   $canDelete True when plans can be removed
 * @return void              Ends the request when it handled the action
 * @throws Exception
 */
function digiriskstandard_site_plan_media_actions(string $action, bool $canAdd, bool $canDelete): void
{
    global $conf, $db, $langs, $user;

    if (!in_array($action, ['uploadPhoto', 'linkPhoto', 'deletePhoto'], true) || GETPOST('module_name', 'alpha') !== 'digiriskdolibarr') {
        return;
    }

    if (GETPOST('sub_dir', 'alphanohtml') !== DIGIRISKSTANDARD_SITE_PLAN_SUBDIR) {
        http_response_code(403);
        exit;
    }

    if ($action == 'deletePhoto' ? $canDelete : $canAdd) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
        dol_include_once('/saturne/lib/medias.lib.php');

        // Writes the plan with its thumbs and puts it in the library, copies it from the library, or
        // removes it with its thumbs
        require dol_buildpath('/saturne/core/tpl/actions/media_block_actions.tpl.php');
    }

    print digiriskstandard_site_plan_media_block($canAdd);
    exit;
}

/**
 * Plain text of a content typed in the editor: line breaks and list items are kept, tags and entities
 * removed
 *
 * @param  string $html Content
 * @return string       Text
 */
function digiriskstandard_html_to_text(string $html): string
{
    // In HTML a line break of the source is only a space: only <br> and the ends of blocks are breaks.
    // A text typed without the editor keeps its own
    $text = dol_textishtml($html) ? str_replace(["\r\n", "\r", "\n"], ' ', $html) : $html;
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    // Every list item on its own line, the first one included
    $text = preg_replace('/<li\b[^>]*>/i', "\n- ", $text);
    $text = preg_replace('/<\/li>/i', '', $text);
    $text = preg_replace('/<\/(p|div|h[1-6]|tr|table|ul|ol)>/i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xC2\xA0", "\r\n", "\r", "\t"], [' ', "\n", "\n", ' '], $text);
    $text = preg_replace('/ {2,}/', ' ', $text);
    $text = preg_replace("/ *\n */", "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

/**
 * Lines of a text typed on the risk assessment document card (methodology, sources, important note),
 * typed from the way it was written: "* Step 1: title" is a numbered step, "- " a bullet, a line all in
 * bold a heading, a date at the start of a line a dated entry, a web address a link, a short line ending
 * with a colon at the start of a block a lead-in. The rest is plain text, an empty line a gap.
 *
 * Shared by the card and the PDF document so that both lay the text out the same way.
 *
 * @param  string $html Typed content
 * @return array        Lines: ['type' => step (number, text) | bullet | heading | lead | dated (date, text) | link (text, url) | text | gap]
 */
function digiriskstandard_rich_text_lines(string $html): array
{
    // A line all in bold is a heading: marked before the tags are dropped
    $marked = preg_replace('/<(strong|b)\b[^>]*>(.*?)<\/\1>/is', "\x01$2\x02", $html);
    $text   = digiriskstandard_html_to_text((string) $marked);

    // Older content typed on a single line: the steps and the bullets run into the text
    if (strpos($text, "\n") === false && preg_match('/(^|\s)\*\s|\s-\s+\p{Lu}/u', $text)) {
        $text = preg_replace('/\s+(?=\*\s)/u', "\n", $text);
        $text = preg_replace('/\s+-\s+(?=\p{Lu})/u', "\n- ", $text);
    }

    $lines = [];
    foreach (explode("\n", $text) as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            if (!empty($lines) && end($lines)['type'] != 'gap') {
                $lines[] = ['type' => 'gap'];
            }
            continue;
        }

        if (preg_match('/^\x01([^\x01\x02]+)\x02$/u', $raw, $matches)) {
            $lines[] = ['type' => 'heading', 'text' => trim($matches[1])];
            continue;
        }
        $raw = trim(str_replace(["\x01", "\x02"], '', $raw));

        if (preg_match('/^[*•]\s*(.*)$/u', $raw, $matches)) {
            $title  = trim($matches[1]);
            $number = '';
            if (preg_match('/^(?:[ÉEé]tape|Step)\s*(\d+)\s*[:\-–.]?\s*(.*)$/iu', $title, $stepMatches)) {
                $number = $stepMatches[1];
                $title  = dol_strlen(trim($stepMatches[2])) ? trim($stepMatches[2]) : $title;
            }
            $lines[] = ['type' => 'step', 'number' => $number, 'text' => $title];
        } elseif (preg_match('/^[-–]\s*(.+)$/u', $raw, $matches)) {
            $lines[] = ['type' => 'bullet', 'text' => trim($matches[1])];
        } elseif (preg_match('/^(\d{1,2}[\/.]\d{1,2}[\/.]\d{2,4})\s*[,:\-–]?\s*(.*)$/u', $raw, $matches) && dol_strlen(trim($matches[2]))) {
            $lines[] = ['type' => 'dated', 'date' => $matches[1], 'text' => trim($matches[2])];
        } elseif (preg_match('/^(https?:\/\/|www\.)\S+$/i', $raw)) {
            $lines[] = ['type' => 'link', 'text' => $raw, 'url' => (stripos($raw, 'www.') === 0 ? 'https://' : '') . $raw];
        } elseif (preg_match('/:$/u', $raw) && dol_strlen($raw) <= 80 && (empty($lines) || end($lines)['type'] == 'gap')) {
            // Lead-in at the start of a block ("Important notes:")
            $lines[] = ['type' => 'lead', 'text' => $raw];
        } else {
            $lines[] = ['type' => 'text', 'text' => $raw];
        }
    }

    if (!empty($lines) && end($lines)['type'] == 'gap') {
        array_pop($lines);
    }

    return $lines;
}

/**
 * HTML of a text typed on the risk assessment document card, laid out as in the PDF document: numbered
 * steps with their bullets, headings, dated entries and links. See digiriskstandard_rich_text_lines()
 *
 * @param  string $html Typed content
 * @return string       HTML, empty when there is nothing to show
 */
function digiriskstandard_rich_text_html(string $html): string
{
    // Lines grouped by step: what follows a step up to the next empty line belongs to it
    $blocks = [];
    $step   = null;
    foreach (digiriskstandard_rich_text_lines($html) as $line) {
        if ($line['type'] == 'step') {
            $blocks[] = ['line' => $line, 'items' => []];
            $step     = array_key_last($blocks);
        } elseif ($line['type'] == 'gap') {
            $blocks[] = ['line' => $line];
            $step     = null;
        } elseif ($step !== null) {
            $blocks[$step]['items'][] = $line;
        } else {
            $blocks[] = ['line' => $line];
        }
    }
    if (empty($blocks)) {
        return '';
    }

    $out   = '<div class="du-richtext">';
    $lines = [];
    foreach ($blocks as $block) {
        if ($block['line']['type'] != 'step') {
            $lines[] = $block['line'];
            continue;
        }

        $out  .= digiriskstandard_rich_text_lines_html($lines);
        $lines = [];

        $number = $block['line']['number'];
        $out   .= '<div class="du-richtext__step' . (!empty($block['items']) ? ' du-richtext__step--with-items' : '') . '">';
        $out   .= '<span class="du-richtext__step-number' . ($number === '' ? ' du-richtext__step-number--dot' : '') . '">' . dol_escape_htmltag($number) . '</span>';
        $out   .= '<div class="du-richtext__step-body">';
        $out   .= '<div class="du-richtext__step-title">' . dol_escape_htmltag($block['line']['text']) . '</div>';
        $out   .= digiriskstandard_rich_text_lines_html($block['items']);
        $out   .= '</div></div>';
    }
    $out .= digiriskstandard_rich_text_lines_html($lines);

    return $out . '</div>';
}

/**
 * Site and rest of a web address, as the link chips show them: "inrs.fr" and "/media.html?refINRS=outil37"
 *
 * @param  string $url Address
 * @return array       ['host' => site without www., 'path' => path, query and fragment, empty for the home page]
 */
function digiriskstandard_link_parts(string $url): array
{
    $parts = parse_url($url);
    $host  = preg_replace('/^www\./i', '', (string) ($parts['host'] ?? ''));
    if ($host === '') {
        return ['host' => $url, 'path' => ''];
    }

    $path = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

    return ['host' => $host, 'path' => $path === '/' ? '' : $path];
}

/**
 * HTML of lines of a typed text other than steps, the bullets that follow each other in one list
 *
 * @param  array  $lines Lines of digiriskstandard_rich_text_lines()
 * @return string        HTML
 */
function digiriskstandard_rich_text_lines_html(array $lines): string
{
    $out    = '';
    $inList = false;
    foreach ($lines as $line) {
        if ($inList && $line['type'] != 'bullet') {
            $out   .= '</ul>';
            $inList = false;
        }

        $text = dol_escape_htmltag($line['text'] ?? '');
        switch ($line['type']) {
            case 'gap':
                $out .= '<div class="du-richtext__gap"></div>';
                break;
            case 'bullet':
                if (!$inList) {
                    $out   .= '<ul class="du-richtext__list">';
                    $inList = true;
                }
                $out .= '<li>' . $text . '</li>';
                break;
            case 'heading':
                $out .= '<div class="du-richtext__heading">' . $text . '</div>';
                break;
            case 'lead':
                $out .= '<div class="du-richtext__lead">' . $text . '</div>';
                break;
            case 'dated':
                $out .= '<div class="du-richtext__dated"><span class="du-richtext__date">' . dol_escape_htmltag($line['date']) . '</span><span>' . $text . '</span></div>';
                break;
            case 'link':
                // A chip: the external link icon, the site in bold, then the rest of the address
                $parts = digiriskstandard_link_parts($line['url']);
                $out  .= '<div class="du-richtext__line"><a class="du-richtext__link" href="' . dol_escape_htmltag($line['url']) . '" target="_blank" rel="noopener noreferrer" title="' . $text . '">';
                $out  .= '<span class="du-richtext__link-icon fas fa-external-link-alt"></span>';
                $out  .= '<span class="du-richtext__link-host">' . dol_escape_htmltag($parts['host']) . '</span>';
                if ($parts['path'] !== '') {
                    $out .= '<span class="du-richtext__link-path">' . dol_escape_htmltag($parts['path']) . '</span>';
                }
                $out .= '</a></div>';
                break;
            default:
                $out .= '<div class="du-richtext__line">' . $text . '</div>';
                break;
        }
    }

    return $out . ($inList ? '</ul>' : '');
}
