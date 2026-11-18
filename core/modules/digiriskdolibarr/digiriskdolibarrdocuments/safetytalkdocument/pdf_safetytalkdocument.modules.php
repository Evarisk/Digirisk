<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/safetytalkdocument/pdf_safetytalkdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Planning of a cycle of safety talks, A4 landscape, in the charter of the DigiRisk access notice:
 *          blue gradient band, cards, pills, QR tiles. One card per talk on its line of a month grid, like a
 *          Gantt chart, with where its signatures stand and the QR code and short link of its spread.
 *
 *          Generated on demand from the screen of the cycle in the mobile application, saved with the
 *          documents of the project.
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';

/**
 * Class to build the planning of a cycle of safety talks.
 */
class pdf_safetytalkdocument extends ModelePDFDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'safetytalkdocument';

    /**
     * Height of the card of a talk, sized for its QR code, in mm.
     */
    const ROW_HEIGHT = 18;

    /**
     * Space between two cards, in mm.
     */
    const ROW_GAP = 2;

    /**
     * Side of the QR tiles, in mm.
     */
    const QR_SIZE = 15;

    /**
     * Ordinate under which nothing is written, above the footer, in mm.
     */
    const CONTENT_LIMIT = 194;

    /**
     * @var array Widths of the columns around the month grid: the talk on the left, its spread on the right
     */
    protected array $columns = ['talk' => 88, 'spread' => 56, 'gap' => 4];

    /**
     * @var array Colours of the charter of the access notice
     */
    protected array $backgroundFrom = [22, 70, 196];
    protected array $backgroundTo   = [74, 152, 238];
    protected array $navy           = [11, 36, 98];
    protected array $navyDeep       = [6, 24, 70];
    protected array $navyInk        = [16, 52, 132];
    protected array $pale           = [205, 218, 243];
    protected array $kickerFill     = [44, 104, 222];
    protected array $teal           = [42, 157, 143];
    protected array $orange         = [247, 147, 30];
    protected array $white          = [255, 255, 255];
    protected array $cardBorder     = [214, 224, 243];
    protected array $monthTint      = [243, 246, 252];
    protected array $footerInk      = [110, 120, 135];
    protected array $amberFill      = [254, 243, 199];
    protected array $amberInk       = [146, 64, 14];
    protected array $greenFill      = [220, 245, 238];
    protected array $greenInk       = [22, 101, 82];

    /**
     * @var Translate Lang object of the document
     */
    protected Translate $outputLangs;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name         = 'safetytalkdocument';
        $this->description  = $langs->trans('SafetyTalkPlanningTitle');
        $this->orientation  = 'L';
        $this->marge_gauche = 12;
        $this->marge_droite = 12;
        $this->marge_haute  = 10;
    }

    /**
     * Build the planning.
     *
     * @param  Project   $cycle       Cycle, fetched
     * @param  Task[]    $talks       Talks of the cycle, in the order they are held
     * @param  Translate $outputLangs Lang object
     * @param  User      $user        User generating the planning, author of the short links
     * @return TCPDF                  PDF to output
     */
    public function build(Project $cycle, array $talks, Translate $outputLangs, User $user)
    {
        global $mysoc;

        $outputLangs->loadLangs(['main', 'projects', 'digiriskdolibarr@digiriskdolibarr']);
        $this->outputLangs = $outputLangs;

        $pdf = $this->openPdf($cycle, $outputLangs, $outputLangs->transnoentities('SafetyTalkPlanningTitle') . ' - ' . $cycle->title);
        $pdf->SetAutoPageBreak(false);
        $pdf->setCellPaddings(0, 0, 0, 0);

        $talks  = array_values($talks);
        $months = $this->months($talks);
        $width  = $this->contentWidth($pdf);

        $y = $this->band($pdf, $cycle, $talks, $width);
        $y = $this->gridHeader($pdf, $y, $months, $width);
        foreach ($talks as $index => $talk) {
            if ($y + self::ROW_HEIGHT > self::CONTENT_LIMIT) {
                $this->newSheet($pdf);
                $y = $this->gridHeader($pdf, $this->compactBand($pdf, $cycle, $width), $months, $width);
            }
            $y = $this->talkRow($pdf, $y, $index + 1, $talk, $months, $width, $user) + self::ROW_GAP;
        }

        if ($y + 13 > self::CONTENT_LIMIT) {
            $this->newSheet($pdf);
            $y = $this->compactBand($pdf, $cycle, $width);
        }
        $this->howTo($pdf, $y + 1, $width);

        $this->footers($pdf, $outputLangs->transnoentities('SafetyTalkPlanningFooter', $mysoc->name, dol_print_date(dol_now(), 'day', 'tzuserrel')));

        return $pdf;
    }

    /**
     * Open a new page, without automatic break: everything is laid out at set coordinates.
     *
     * @param  TCPDF $pdf PDF handler
     * @return void
     */
    protected function newSheet($pdf)
    {
        $this->newPage($pdf);
        $pdf->SetAutoPageBreak(false);
    }

    /**
     * Months shown by the grid: the calendar year of the talks when they all fall in one, else from the month
     * of the first one to the month of the last one, twelve at least.
     *
     * Months are read in the time zone of the user, the one the dates of the talks were typed in.
     *
     * @param  Task[] $talks Talks of the cycle
     * @return array         Each month: ['start' => timestamp of its first day, 'month', 'year', 'days']
     */
    protected function months(array $talks): array
    {
        $dates = array_filter(array_map(fn ($talk) => (int) $talk->date_start, $talks));
        if (empty($dates)) {
            $dates = [dol_now()];
        }

        $firstMonth = (int) dol_print_date(min($dates), '%m', 'tzuserrel');
        $firstYear  = (int) dol_print_date(min($dates), '%Y', 'tzuserrel');
        $lastMonth  = (int) dol_print_date(max($dates), '%m', 'tzuserrel');
        $lastYear   = (int) dol_print_date(max($dates), '%Y', 'tzuserrel');

        if ($firstYear == $lastYear) {
            $startMonth = 1;
            $count      = 12;
        } else {
            $startMonth = $firstMonth;
            $count      = max(12, min(24, ($lastYear - $firstYear) * 12 + $lastMonth - $startMonth + 1));
        }

        $months = [];
        for ($index = 0; $index < $count; $index++) {
            $month    = ($startMonth - 1 + $index) % 12 + 1;
            $year     = $firstYear + intdiv($startMonth - 1 + $index, 12);
            $months[] = [
                'start' => (int) dol_mktime(12, 0, 0, $month, 1, $year, 'tzuserrel'),
                'month' => $month,
                'year'  => $year,
                'days'  => (int) date('t', mktime(0, 0, 0, $month, 1, $year)),
            ];
        }

        return $months;
    }

    /**
     * Gradient band of the first page: logo tile, kicker, name of the cycle, its period and its people, and
     * three figures on the right.
     *
     * @param  TCPDF   $pdf   PDF handler
     * @param  Project $cycle Cycle
     * @param  Task[]  $talks Talks of the cycle
     * @param  float   $width Width of the content
     * @return float          Ordinate under the band
     */
    protected function band($pdf, Project $cycle, array $talks, float $width): float
    {
        global $db;

        $x = $this->marge_gauche;
        $this->gradient($pdf, 0, 36);
        $this->logoTile($pdf, $cycle, $x, 7, 21);

        // Users of the cycle and its people without Dolibarr account
        $participants = array_column(digiriskSafetyTalkGetPeopleNames($db, $cycle, $this->outputLangs), 'name');
        $heldCount = count(array_filter($talks, fn ($talk) => digiriskSafetyTalkGetPeriod($talk) == 'done'));

        // Figures on the right, the text takes what is left
        $chipWidth = 29;
        $chipGap   = 4;
        $chipsX    = $x + $width - (3 * $chipWidth) - (2 * $chipGap);
        $chips     = [
            [count($talks), $this->outputLangs->transnoentities('SafetyTalkPlanningStatTalks')],
            [$heldCount, $this->outputLangs->transnoentities('SafetyTalkPlanningStatHeld')],
            [count($participants), $this->outputLangs->transnoentities('SafetyTalkPlanningStatPeople')],
        ];
        foreach ($chips as $index => $chip) {
            $this->statChip($pdf, $chipsX + $index * ($chipWidth + $chipGap), 7, $chipWidth, 21, (string) $chip[0], $chip[1]);
        }

        $textX     = $x + 21 + 7;
        $textWidth = $chipsX - $textX - 8;

        $this->tag($pdf, $textX, 6.3, 6.2, $this->outputLangs->transnoentities('SafetyTalkPlanningTitle'), $this->kickerFill, $this->white, 7.5, $textWidth);

        $this->fitFontSize($pdf, $cycle->title, $textWidth, 'B', 21, 15);
        $pdf->SetTextColor($this->white[0], $this->white[1], $this->white[2]);
        $pdf->SetXY($textX, 12.8);
        $pdf->Cell($textWidth, 10, $cycle->title, 0, 0, 'L', false, '', 1);

        $pdf->SetFont('', '', 9.5);
        $pdf->SetTextColor($this->pale[0], $this->pale[1], $this->pale[2]);
        $pdf->SetXY($textX + 0.3, 22.8);
        $pdf->Cell($textWidth, 5, $cycle->ref . '   ·   ' . $this->periodLabel($talks), 0, 0, 'L', false, '', 1);

        if (!empty($participants)) {
            $pdf->SetFont('', '', 8);
            $pdf->SetXY($textX + 0.3, 27.8);
            $pdf->Cell($textWidth, 4.5, $this->fitText($pdf, $this->outputLangs->transnoentities('SafetyTalkPlanningParticipants', implode(', ', $participants)), $textWidth), 0, 0, 'L', false, '', 1);
        }
        $pdf->SetTextColor(0, 0, 0);

        return 42;
    }

    /**
     * Thin gradient band of the next pages: the kicker and the name of the cycle.
     *
     * @param  TCPDF   $pdf   PDF handler
     * @param  Project $cycle Cycle
     * @param  float   $width Width of the content
     * @return float          Ordinate under the band
     */
    protected function compactBand($pdf, Project $cycle, float $width): float
    {
        $x = $this->marge_gauche;
        $this->gradient($pdf, 0, 20);

        $tagWidth = $this->tag($pdf, $x, 6.8, 6.4, $this->outputLangs->transnoentities('SafetyTalkPlanningTitle'), $this->kickerFill, $this->white, 7.5, $width / 2);

        $pdf->SetFont('', 'B', 13);
        $pdf->SetTextColor($this->white[0], $this->white[1], $this->white[2]);
        $pdf->SetXY($x + $tagWidth + 5, 5.5);
        $pdf->Cell($width - $tagWidth - 5, 9, $this->fitText($pdf, $cycle->title, $width - $tagWidth - 5), 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);

        return 27;
    }

    /**
     * Period of the cycle, from the first talk to the last one.
     *
     * @param  Task[] $talks Talks of the cycle
     * @return string        Period, empty when no talk is dated
     */
    protected function periodLabel(array $talks): string
    {
        $dates = array_filter(array_map(fn ($talk) => (int) $talk->date_start, $talks));
        if (empty($dates)) {
            return '';
        }

        return $this->outputLangs->transnoentities('SafetyTalkPlanningPeriod', $this->dayLabel(min($dates), '%d %b %Y'), $this->dayLabel(max($dates), '%d %b %Y'));
    }

    /**
     * Date written inside a sentence: the month in lower case, as French writes it ("15 janv. 2026").
     *
     * @param  int    $date   Timestamp
     * @param  string $format Format of dol_print_date()
     * @return string
     */
    protected function dayLabel(int $date, string $format): string
    {
        return dol_strtolower(dol_print_date($date, $format, 'tzuserrel', $this->outputLangs));
    }

    /**
     * Short link split in its root and its code, to print them on two lines.
     *
     * @param  string $shortUrl Short link, or the full address when Saturne could not shorten it
     * @return array            [root without scheme, code with its "?"], the code empty for a full address
     */
    protected function splitShortUrl(string $shortUrl): array
    {
        $code = class_exists('SaturneRedirection') ? SaturneRedirection::getCodeFromShortUrl($shortUrl) : '';
        if (!dol_strlen($code)) {
            return [preg_replace('#^https?://#', '', $shortUrl), ''];
        }

        return [preg_replace('#^https?://#', '', substr($shortUrl, 0, -dol_strlen($code) - 1)), '?' . $code];
    }

    /**
     * Gradient of the bands, with the light triangles of the access notice.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $y      Ordinate
     * @param  float $height Height
     * @return void
     */
    protected function gradient($pdf, float $y, float $height)
    {
        $width = $pdf->GetPageWidth();

        $pdf->LinearGradient(0, $y, $width, $height, $this->backgroundFrom, $this->backgroundTo, [0, 1, 1, 0]);

        $pdf->StartTransform();
        $pdf->Rect(0, $y, $width, $height, 'CNZ');
        $pdf->SetAlpha(0.07);
        $pdf->Polygon([$width * 0.58, $y + ($height * 0.05), $width * 0.86, $y + $height, $width * 0.3, $y + $height], 'F', [], $this->white);
        $pdf->Polygon([0, $y + ($height * 0.35), $width * 0.12, $y + $height, 0, $y + $height], 'F', [], $this->white);
        $pdf->SetAlpha(1);
        $pdf->StopTransform();
    }

    /**
     * White tile with the logo of the company, the DigiRisk one when the company has none.
     *
     * @param  TCPDF   $pdf   PDF handler
     * @param  Project $cycle Cycle
     * @param  float   $x     Abscissa
     * @param  float   $y     Ordinate
     * @param  float   $size  Side of the tile
     * @return void
     */
    protected function logoTile($pdf, Project $cycle, float $x, float $y, float $size)
    {
        $this->card($pdf, $x, $y, $size, $size, $this->white, 4.5);

        $logo = $this->companyLogoPath($cycle);
        if (empty($logo)) {
            $logo = dol_buildpath('/digiriskdolibarr/img/digiriskdolibarr_color_512.png', 0);
        }
        if (is_readable($logo)) {
            [$logoWidth, $logoHeight] = $this->companyLogoSize($logo, $size - 6, $size - 6);
            $pdf->Image($logo, $x + ($size - $logoWidth) / 2, $y + ($size - $logoHeight) / 2, $logoWidth, $logoHeight);
        }
    }

    /**
     * Figure of the band: a white card, the number large, its label under it.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  float  $x      Abscissa
     * @param  float  $y      Ordinate
     * @param  float  $width  Width
     * @param  float  $height Height
     * @param  string $value  Figure
     * @param  string $label  Label
     * @return void
     */
    protected function statChip($pdf, float $x, float $y, float $width, float $height, string $value, string $label)
    {
        $this->card($pdf, $x, $y, $width, $height, $this->white, 3.5);

        $pdf->SetFont('', 'B', 18);
        $pdf->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($width, 9, $value, 0, 0, 'C');

        $pdf->SetFont('', 'B', 6.5);
        $pdf->setFontSpacing(0.3);
        $pdf->SetTextColor($this->kickerFill[0], $this->kickerFill[1], $this->kickerFill[2]);
        $pdf->SetXY($x + 1, $y + 14.5);
        $pdf->Cell($width - 2, 4, dol_strtoupper($label), 0, 0, 'C', false, '', 1);
        $pdf->setFontSpacing(0);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Rounded pill with its text in capitals, as the kickers of the access notice.
     *
     * @param  TCPDF  $pdf      PDF handler
     * @param  float  $x        Abscissa
     * @param  float  $y        Ordinate
     * @param  float  $height   Height
     * @param  string $text     Text
     * @param  array  $fill     Background
     * @param  array  $ink      Text colour
     * @param  float  $fontSize Font size
     * @param  float  $maxWidth Widest the pill may be
     * @return float            Width of the pill
     */
    protected function tag($pdf, float $x, float $y, float $height, string $text, array $fill, array $ink, float $fontSize, float $maxWidth): float
    {
        $text = dol_strtoupper($text);

        $pdf->SetFont('', 'B', $fontSize);
        $pdf->setFontSpacing(0.3);
        $width = min($maxWidth, $pdf->GetStringWidth($text) + (2 * $height));

        $pdf->RoundedRect($x, $y, $width, $height, $height / 2, '1111', 'F', [], $fill);
        $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $pdf->SetXY($x + ($height / 2), $y);
        $pdf->Cell($width - $height, $height, $text, 0, 0, 'C', false, '', 1);
        $pdf->setFontSpacing(0);
        $pdf->SetTextColor(0, 0, 0);

        return $width;
    }

    /**
     * Abscissa and width of the month grid.
     *
     * @param  float $width Width of the content
     * @return array        [abscissa, width]
     */
    protected function gridBox(float $width): array
    {
        $x = $this->marge_gauche + $this->columns['talk'] + $this->columns['gap'];

        return [$x, $width - $this->columns['talk'] - $this->columns['spread'] - (2 * $this->columns['gap'])];
    }

    /**
     * Abscissa of a date on the month grid, null when the date is out of it.
     *
     * @param  int   $date   Timestamp
     * @param  array $months Months of the grid, built by months()
     * @param  float $width  Width of the content
     * @return float|null
     */
    protected function dateX(int $date, array $months, float $width): ?float
    {
        list($gridX, $gridWidth) = $this->gridBox($width);
        $monthWidth = $gridWidth / count($months);

        $dateMonth = (int) dol_print_date($date, '%m', 'tzuserrel');
        $dateYear  = (int) dol_print_date($date, '%Y', 'tzuserrel');
        foreach ($months as $index => $month) {
            if ($month['month'] == $dateMonth && $month['year'] == $dateYear) {
                $day = (int) dol_print_date($date, '%d', 'tzuserrel');
                return $gridX + $index * $monthWidth + (($day - 0.5) / $month['days']) * $monthWidth;
            }
        }

        return null;
    }

    /**
     * Header of the grid, on every page: the titles of the side columns and the months, the year under the
     * first month and under each January.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $y      Ordinate
     * @param  array $months Months of the grid, built by months()
     * @param  float $width  Width of the content
     * @return float         Ordinate under the header
     */
    protected function gridHeader($pdf, float $y, array $months, float $width): float
    {
        $x = $this->marge_gauche;
        list($gridX, $gridWidth) = $this->gridBox($width);
        $monthWidth = $gridWidth / count($months);

        $pdf->SetFont('', 'B', 7);
        $pdf->setFontSpacing(0.4);
        $pdf->SetTextColor($this->navyInk[0], $this->navyInk[1], $this->navyInk[2]);
        $pdf->SetXY($x + 2, $y + 2);
        $pdf->Cell($this->columns['talk'] - 2, 5, dol_strtoupper($this->outputLangs->transnoentities('SafetyTalkPlanningColTalk')), 0, 0, 'L');
        $pdf->SetXY($x + $width - $this->columns['spread'], $y + 2);
        $pdf->Cell($this->columns['spread'], 5, dol_strtoupper($this->outputLangs->transnoentities('SafetyTalkPlanningColLink')), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        foreach ($months as $index => $month) {
            $monthX = $gridX + $index * $monthWidth;
            if ($index % 2 == 0) {
                $pdf->RoundedRect($monthX, $y, $monthWidth, 9.5, 1.5, '1111', 'F', [], $this->monthTint);
            }

            $pdf->SetFont('', 'B', $monthWidth < 9 ? 6.3 : 7);
            $pdf->SetTextColor($this->navyInk[0], $this->navyInk[1], $this->navyInk[2]);
            $pdf->SetXY($monthX, $y + 1.2);
            $pdf->Cell($monthWidth, 4, dol_print_date($month['start'], '%b', 'tzuserrel', $this->outputLangs), 0, 0, 'C', false, '', 1);

            if ($index == 0 || $month['month'] == 1) {
                $pdf->SetFont('', '', 6);
                $pdf->SetTextColor($this->footerInk[0], $this->footerInk[1], $this->footerInk[2]);
                $pdf->SetXY($monthX, $y + 5);
                $pdf->Cell($monthWidth, 3.5, (string) $month['year'], 0, 0, 'C');
            }
        }
        $pdf->SetTextColor(0, 0, 0);

        $this->todayLine($pdf, $y, $y + 9.5, $months, $width);

        return $y + 11.5;
    }

    /**
     * Colour of a talk by when it stands: teal once held, orange today, blue still to come.
     *
     * @param  string $period Period, from digiriskSafetyTalkGetPeriod()
     * @return array
     */
    protected function periodColor(string $period): array
    {
        $colors = ['done' => $this->teal, 'today' => $this->orange, 'upcoming' => $this->kickerFill];

        return $colors[$period] ?? $this->footerInk;
    }

    /**
     * Card of one talk: number, theme, title and date on the left, its mark on the months, its signatures,
     * short link and QR code on the right.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  float  $y      Ordinate
     * @param  int    $number Number of the talk in the cycle
     * @param  Task   $talk   Talk
     * @param  array  $months Months of the grid, built by months()
     * @param  float  $width  Width of the content
     * @param  User   $user   User generating the planning
     * @return float          Ordinate under the card
     */
    protected function talkRow($pdf, float $y, int $number, Task $talk, array $months, float $width, User $user): float
    {
        global $db;

        $x      = $this->marge_gauche;
        $height = self::ROW_HEIGHT;
        $middle = $y + $height / 2;
        $period = digiriskSafetyTalkGetPeriod($talk);
        $color  = $this->periodColor($period);
        list($gridX, $gridWidth) = $this->gridBox($width);
        $monthWidth = $gridWidth / count($months);

        // Card, with the stripe of its state along the rounded edge
        $pdf->RoundedRect($x, $y, $width, $height, 3, '1111', 'DF', ['width' => 0.3, 'dash' => 0, 'color' => $this->cardBorder], $this->white);
        $pdf->StartTransform();
        $pdf->RoundedRect($x, $y, $width, $height, 3, '1111', 'CNZ');
        $pdf->Rect($x, $y, 2.2, $height, 'F', [], $color);
        $pdf->StopTransform();

        // Number, in the colour of the state
        $pdf->Circle($x + 8, $middle, 3.4, 0, 360, 'F', [], $color);
        $pdf->SetFont('', 'B', 8.5);
        $pdf->SetTextColor($this->white[0], $this->white[1], $this->white[2]);
        $pdf->SetXY($x + 4.6, $middle - 2.5);
        $pdf->Cell(6.8, 5, (string) $number, 0, 0, 'C');

        // Theme picto on a white tile, a speech bubble for a talk without theme
        $tileX = $x + 14;
        $tileY = $middle - 6.5;
        $pdf->RoundedRect($tileX, $tileY, 13, 13, 2.2, '1111', 'DF', ['width' => 0.3, 'dash' => 0, 'color' => $this->cardBorder], $this->white);
        $theme     = digiriskSafetyTalkGetTheme((string) ($talk->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] ?? ''));
        $pictoPath = $theme !== null ? dol_buildpath('/digiriskdolibarr/img/categorieDangers/' . $theme['thumbnail_name'] . '.png', 0) : '';
        if (dol_strlen($pictoPath) && is_readable($pictoPath)) {
            $pdf->Image($pictoPath, $tileX + 1.2, $tileY + 1.2, 10.6, 10.6);
        } else {
            $pdf->RoundedRect($tileX + 2.5, $tileY + 3.4, 8, 5.4, 1.5, '1111', 'F', [], $this->kickerFill);
            $pdf->Polygon([$tileX + 4.3, $tileY + 8.6, $tileX + 7, $tileY + 8.6, $tileX + 4.3, $tileY + 10.6], 'F', [], $this->kickerFill);
        }

        // Title (two lines at most), theme, date. The line height leaves room for the font, TCPDF drops a
        // line taller than the height it is given
        $lineHeight = 4.3;
        $textX      = $x + 31;
        $textWidth  = $this->columns['talk'] - 33;
        $pdf->SetFont('', 'B', 9);
        $title      = $this->fitLines($pdf, (string) $talk->label, $textWidth, 2);
        $titleLines = min(2, $pdf->getNumLines($title, $textWidth));
        $blockTop   = $middle - (($titleLines * $lineHeight) + ($theme !== null ? 3.4 : 0) + 4) / 2;

        $pdf->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $pdf->MultiCell($textWidth, $lineHeight, $title, 0, 'L', false, 1, $textX, $blockTop, true, 0, false, false, $titleLines * $lineHeight + 0.6, 'T');
        $lineY = $blockTop + $titleLines * $lineHeight;
        if ($theme !== null) {
            $pdf->SetFont('', '', 7);
            $pdf->SetTextColor($this->footerInk[0], $this->footerInk[1], $this->footerInk[2]);
            $pdf->SetXY($textX, $lineY + 0.2);
            $pdf->Cell($textWidth, 3.2, $this->fitText($pdf, $theme['name'], $textWidth), 0, 0, 'L');
            $lineY += 3.4;
        }
        $pdf->SetFont('', 'B', 7.5);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY($textX, $lineY + 0.5);
        $pdf->Cell($textWidth, 3.5, !empty($talk->date_start) ? $this->dayLabel((int) $talk->date_start, '%d %b %Y') . '  ·  ' . dol_print_date($talk->date_start, '%H:%M', 'tzuserrel') : $this->outputLangs->transnoentities('SafetyTalkPeriodUndated'), 0, 0, 'L');

        // Months: tinted every other one, a track across, the mark of the talk on it
        foreach ($months as $index => $month) {
            if ($index % 2 == 0) {
                $pdf->Rect($gridX + $index * $monthWidth, $y + 0.3, $monthWidth, $height - 0.6, 'F', [], $this->monthTint);
            }
        }
        $pdf->Line($gridX, $middle, $gridX + $gridWidth, $middle, ['width' => 0.5, 'dash' => 0, 'color' => $this->cardBorder]);
        $this->todayLine($pdf, $y + 0.3, $y + $height - 0.3, $months, $width);

        $markX = !empty($talk->date_start) ? $this->dateX((int) $talk->date_start, $months, $width) : null;
        if ($markX !== null) {
            $markText = $this->dayLabel((int) $talk->date_start, '%d %b');
            $pdf->SetFont('', 'B', 7);
            $markWidth = $pdf->GetStringWidth($markText) + 4.6;
            $markLeft  = min(max($markX - $markWidth / 2, $gridX), $gridX + $gridWidth - $markWidth);
            $pdf->Circle($markX, $middle + 1.5, 1.3, 0, 360, 'F', [], $color);
            $pdf->RoundedRect($markLeft, $middle - 6.6, $markWidth, 5.4, 2.7, '1111', 'F', [], $color);
            $pdf->Polygon([$markX - 1.3, $middle - 1.3, $markX + 1.3, $middle - 1.3, $markX, $middle + 0.1], 'F', [], $color);
            $pdf->SetTextColor($this->white[0], $this->white[1], $this->white[2]);
            $pdf->SetXY($markLeft, $middle - 6.6);
            $pdf->Cell($markWidth, 5.4, $markText, 0, 0, 'C');
        }

        // Signatures, short link and QR code of the spread
        $spreadX     = $x + $width - $this->columns['spread'];
        $infoWidth   = $this->columns['spread'] - self::QR_SIZE - 4;
        $signatories = digiriskSafetyTalkGetSignatories($db, (int) $talk->id);
        $signedCount = count(array_filter($signatories, 'digiriskSafetyTalkHasSigned'));
        $allSigned   = !empty($signatories) && $signedCount == count($signatories);
        $pdf->SetFont('', 'B', 7);
        $pdf->RoundedRect($spreadX, $y + 1.9, $infoWidth, 5.2, 2.6, '1111', 'F', [], $allSigned ? $this->greenFill : $this->amberFill);
        $pdf->SetTextColor(...($allSigned ? $this->greenInk : $this->amberInk));
        $pdf->SetXY($spreadX, $y + 1.9);
        $pdf->Cell($infoWidth, 5.2, $this->outputLangs->transnoentities('SafetyTalkSignedCount', (string) $signedCount, (string) count($signatories)), 0, 0, 'C', false, '', 1);

        $spreadUrl = digiriskSafetyTalkGetSpreadUrl((int) $talk->id);
        if (dol_strlen($spreadUrl)) {
            $shortUrl = digiriskSafetyTalkShortUrl($db, $spreadUrl, $user);

            $pdf->SetFont('', 'B', 5.5);
            $pdf->setFontSpacing(0.3);
            $pdf->SetTextColor($this->footerInk[0], $this->footerInk[1], $this->footerInk[2]);
            $pdf->SetXY($spreadX + 0.5, $y + 7.8);
            $pdf->Cell($infoWidth, 3, dol_strtoupper($this->outputLangs->transnoentities('SafetyTalkPlanningShortLink')), 0, 0, 'L');
            $pdf->setFontSpacing(0);

            // The root of the address, small, then the code that tells the talks apart, large: a URL cut
            // anywhere by the line break could no longer be read
            list($linkRoot, $linkCode) = $this->splitShortUrl($shortUrl);
            $pdf->SetFont('', '', 5.5);
            $pdf->SetTextColor($this->navyInk[0], $this->navyInk[1], $this->navyInk[2]);
            $pdf->SetXY($spreadX + 0.5, $y + 10.4);
            $pdf->Cell($infoWidth - 0.5, 2.6, $linkRoot, 0, 0, 'L', false, '', 1);
            if (dol_strlen($linkCode)) {
                $pdf->SetFont('', 'B', 8.5);
                $pdf->SetXY($spreadX + 0.5, $y + 12.8);
                $pdf->Cell($infoWidth - 0.5, 3.8, $linkCode, 0, 0, 'L', false, '', 1);
            }
            $pdf->Link($spreadX, $y + 7.6, $infoWidth, 9.4, $shortUrl);

            $qrX = $x + $width - self::QR_SIZE - 1.5;
            $qrY = $middle - self::QR_SIZE / 2;
            $pdf->RoundedRect($qrX, $qrY, self::QR_SIZE, self::QR_SIZE, 2.2, '1111', 'DF', ['width' => 0.3, 'dash' => 0, 'color' => $this->cardBorder], $this->white);
            $style = ['border' => false, 'padding' => 0, 'fgcolor' => $this->navyDeep, 'bgcolor' => false];
            $pdf->write2DBarcode($shortUrl, 'QRCODE,M', $qrX + 1.3, $qrY + 1.3, self::QR_SIZE - 2.6, self::QR_SIZE - 2.6, $style, 'N');
            $pdf->Link($qrX, $qrY, self::QR_SIZE, self::QR_SIZE, $shortUrl);
        }
        $pdf->SetTextColor(0, 0, 0);

        return $y + $height;
    }

    /**
     * Dashed orange line of today across the grid, when today falls in it.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $top    Top of the line
     * @param  float $bottom Bottom of the line
     * @param  array $months Months of the grid, built by months()
     * @param  float $width  Width of the content
     * @return void
     */
    protected function todayLine($pdf, float $top, float $bottom, array $months, float $width)
    {
        $todayX = $this->dateX(dol_now(), $months, $width);
        if ($todayX !== null) {
            $pdf->Line($todayX, $top, $todayX, $bottom, ['width' => 0.45, 'dash' => '1.2,1', 'color' => $this->orange]);
            $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => $this->rule]);
        }
    }

    /**
     * Navy strip under the talks, on one line: how to join a talk in three steps, and what the colours mean.
     * Kept low so that it stays on the page of the last talks instead of taking a page of its own.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  float $y     Ordinate
     * @param  float $width Width of the content
     * @return void
     */
    protected function howTo($pdf, float $y, float $width)
    {
        $x      = $this->marge_gauche;
        $height = 12;
        $middle = $y + $height / 2;

        $pdf->RoundedRect($x, $y, $width, $height, 3, '1111', 'F', [], $this->navy);
        $pdf->StartTransform();
        $pdf->RoundedRect($x, $y, $width, $height, 3, '1111', 'CNZ');
        $pdf->Rect($x, $y, 2.4, $height, 'F', [], $this->teal);
        $pdf->StopTransform();

        $tagWidth = $this->tag($pdf, $x + 6, $middle - 2.9, 5.8, $this->outputLangs->transnoentities('SafetyTalkPlanningHowTo'), $this->teal, $this->white, 7, 40);

        // Legend of the colours on the right, the steps share what is left
        $legend      = [[$this->teal, 'SafetyTalkPeriodDone'], [$this->orange, 'SafetyTalkPeriodToday'], [$this->kickerFill, 'SafetyTalkPeriodUpcoming']];
        $legendWidth = 21;
        $legendX     = $x + $width - (3 * $legendWidth) - 2;
        foreach ($legend as $index => $item) {
            $itemX = $legendX + $index * $legendWidth;
            $pdf->RoundedRect($itemX, $middle - 1.8, 7, 3.6, 1.8, '1111', 'F', [], $item[0]);
            $pdf->SetFont('', '', 7);
            $pdf->SetTextColor($this->pale[0], $this->pale[1], $this->pale[2]);
            $pdf->SetXY($itemX + 8.5, $middle - 2.3);
            $pdf->Cell($legendWidth - 9, 4.6, $this->outputLangs->transnoentities($item[1]), 0, 0, 'L', false, '', 1);
        }

        $steps     = ['SafetyTalkPlanningStep1', 'SafetyTalkPlanningStep2', 'SafetyTalkPlanningStep3'];
        $stepInk   = [$this->teal, $this->orange, $this->kickerFill];
        $stepsX    = $x + 6 + $tagWidth + 6;
        $stepWidth = ($legendX - $stepsX - 4) / 3;
        foreach ($steps as $index => $step) {
            $stepX = $stepsX + $index * $stepWidth;
            $pdf->Circle($stepX + 2.6, $middle, 2.6, 0, 360, 'F', [], $stepInk[$index]);
            $pdf->SetFont('', 'B', 7.5);
            $pdf->SetTextColor($this->white[0], $this->white[1], $this->white[2]);
            $pdf->SetXY($stepX, $middle - 2.3);
            $pdf->Cell(5.2, 4.6, (string) ($index + 1), 0, 0, 'C');
            $pdf->SetFont('', '', 7);
            $pdf->SetXY($stepX + 7, $middle - 2.3);
            $pdf->Cell($stepWidth - 9, 4.6, $this->fitText($pdf, $this->outputLangs->transnoentities($step), $stepWidth - 9), 0, 0, 'L');
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => $this->rule]);
    }

    /**
     * Text cut with an ellipsis to fit on one line.
     *
     * @param  TCPDF  $pdf   PDF handler, with the font of the text set
     * @param  string $text  Text
     * @param  float  $width Width available
     * @return string
     */
    protected function fitText($pdf, string $text, float $width): string
    {
        if ($pdf->GetStringWidth($text) <= $width) {
            return $text;
        }
        while (dol_strlen($text) > 1 && $pdf->GetStringWidth($text . '…') > $width) {
            $text = dol_substr($text, 0, dol_strlen($text) - 1);
        }

        return rtrim($text) . '…';
    }

    /**
     * Text cut with an ellipsis to fit on a number of lines.
     *
     * @param  TCPDF  $pdf      PDF handler, with the font of the text set
     * @param  string $text     Text
     * @param  float  $width    Width of a line
     * @param  int    $maxLines Lines available
     * @return string
     */
    protected function fitLines($pdf, string $text, float $width, int $maxLines): string
    {
        if ($pdf->getNumLines($text, $width) <= $maxLines) {
            return $text;
        }
        while (dol_strlen($text) > 1 && $pdf->getNumLines($text . '…', $width) > $maxLines) {
            $text = dol_substr($text, 0, dol_strlen($text) - 1);
        }

        return rtrim($text) . '…';
    }

    /**
     * Font size that fits a text on one line, between the wanted size and a minimum.
     *
     * @param  TCPDF  $pdf     PDF handler
     * @param  string $text    Text
     * @param  float  $width   Width available
     * @param  string $style   Font style
     * @param  float  $size    Wanted size
     * @param  float  $minSize Smallest size
     * @return float           Size set
     */
    protected function fitFontSize($pdf, string $text, float $width, string $style, float $size, float $minSize): float
    {
        $pdf->SetFont('', $style, $size);
        while ($size > $minSize && $pdf->GetStringWidth($text) > $width) {
            $size -= 0.5;
            $pdf->SetFont('', $style, $size);
        }

        return $size;
    }

    /**
     * Footer of every page: who made the document and when, and the page number.
     *
     * @param  TCPDF  $pdf  PDF handler
     * @param  string $text Text on the left
     * @return void
     */
    protected function footers($pdf, string $text)
    {
        $totalPages = $pdf->getNumPages();
        $width      = $this->contentWidth($pdf);
        $y          = $pdf->getPageHeight() - 11;

        for ($page = 1; $page <= $totalPages; $page++) {
            $pdf->setPage($page);
            $pdf->SetAutoPageBreak(false);

            $pdf->SetFont('', '', 7.5);
            $pdf->SetTextColor($this->footerInk[0], $this->footerInk[1], $this->footerInk[2]);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell($width - 40, 5, $text, 0, 0, 'L', false, '', 1);

            $pdf->SetFont('', 'B', 7.5);
            $pdf->SetTextColor($this->navyInk[0], $this->navyInk[1], $this->navyInk[2]);
            $pdf->SetXY($this->marge_gauche + $width - 40, $y);
            $pdf->Cell(40, 5, $this->outputLangs->transnoentities('SafetyTalkPlanningPageNumber', (string) $page, (string) $totalPages), 0, 0, 'R');
        }

        $pdf->SetTextColor(0, 0, 0);
    }
}
