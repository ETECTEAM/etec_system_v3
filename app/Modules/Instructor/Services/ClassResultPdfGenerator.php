<?php

namespace App\Modules\Instructor\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class ClassResultPdfGenerator
{
    private const PAGE_WIDTH = 1754;
    private const PAGE_HEIGHT = 1240;
    private const TABLE_X = 56;
    private const TABLE_W = 1642;
    private const TABLE_TOP = 255;
    private const TABLE_SCORE_TOP_H = 67;
    private const TABLE_HEADER_H = 118;
    private const TABLE_BODY_TOP = self::TABLE_TOP + self::TABLE_HEADER_H;
    private const ROW_HEIGHT = 51;
    private const COLUMN_WIDTHS = [206, 411, 205, 123, 123, 123, 123, 140, 188];
    private const PDF_A4_LANDSCAPE_WIDTH = 841.89;
    private const PDF_A4_LANDSCAPE_HEIGHT = 595.28;

    // Pagination
    private const ROWS_PER_PAGE = 14;        // pages that only contain the table
    private const ROWS_ON_LAST_PAGE = 6;     // last page also holds the note + signature block
    // This is the largest table that can retain the closing block below it
    // without forcing an otherwise empty second page.
    private const FOOTER_ON_FIRST_PAGE_MAX_ROWS = 12;

    // Look. Sizes are in pixels on the 1754px-wide canvas (converted for GD by px()).
    private const FAIL_STRIKETHROUGH = true; // red line through failed rows, like the reference
    private const TEXT_PAD = 16;
    private const BODY_PX = 21;
    private const HEADER_PX = 21;
    private const SUB_HEADER_PX = 18;
    private const OTHER_PX = 18;

    private const FONT_LATIN_REG = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    private const FONT_LATIN_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    private array $pangoCache = [];

    private array $fontCache = [];

    private ?float $gdScale = null;

    private bool $pangoResolved = false;

    private ?string $pangoBinary = null;

    public function generate(array $classData, Collection $students): string
    {
        $sortedStudents = $this->sortStudents($students)->values()->all();
        $pages = $this->paginate($sortedStudents);

        $tempDir = sys_get_temp_dir() . '/class-result-' . Str::uuid();
        $pdfPath = sys_get_temp_dir() . '/class-result-' . Str::uuid() . '.pdf';
        File::makeDirectory($tempDir, 0755, true);

        try {
            $pageImages = [];

            foreach ($pages as $index => $page) {
                $pageImages[] = $this->renderPage(
                    $classData,
                    $page['rows'],
                    $page['offset'],
                    $index + 1,
                    count($pages),
                    $tempDir,
                    $page['footerOnly'] ?? false,
                );
            }

            $this->writePdf($pageImages, $pdfPath);

            foreach ($pageImages as $pageImage) {
                if (File::exists($pageImage)) {
                    File::delete($pageImage);
                }
            }

            return File::get($pdfPath);
        } finally {
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * @return array<int, array{rows: array, offset: int, footerOnly?: bool}>
     */
    private function paginate(array $students): array
    {
        if ($students === []) {
            return [['rows' => [], 'offset' => 0]];
        }

        $chunks = array_chunk($students, self::ROWS_PER_PAGE);

        $pages = [];
        $offset = 0;
        foreach ($chunks as $chunk) {
            $pages[] = ['rows' => $chunk, 'offset' => $offset];
            $offset += count($chunk);
        }

        // Only move the closing block when the table has genuinely filled the
        // first page. Shorter tables keep their note and signatures directly
        // below the last row.
        if (count($pages) === 1 && count($students) > self::FOOTER_ON_FIRST_PAGE_MAX_ROWS) {
            $pages[] = ['rows' => [], 'offset' => $offset, 'footerOnly' => true];
        }

        return $pages;
    }

    private function sortStudents(Collection $students): Collection
    {
        return $students
            ->sort(function (array $left, array $right): int {
                $bucketDifference = $this->resultSortBucket($left) <=> $this->resultSortBucket($right);

                if ($bucketDifference !== 0) {
                    return $bucketDifference;
                }

                return $this->resultTotalScore($right) <=> $this->resultTotalScore($left);
            })
            ->values();
    }

    private function resultSortBucket(array $student): int
    {
        return $this->resultTotalScore($student) < 50 ? 1 : 0;
    }

    private function resultTotalScore(array $student): float
    {
        return (float) ($student['scores']['attendance'] ?? 0)
            + (float) ($student['scores']['activity'] ?? 0)
            + (float) ($student['scores']['exam'] ?? 0);
    }

    /**
     * GD builds differ: some treat the font size as pixels (72 dpi), others as
     * points at 96 dpi. Measure once and convert so table text is the same
     * size everywhere.
     */
    private function px(float $pixels): float
    {
        if ($this->gdScale === null) {
            $box = @imagettfbbox(100, 0, $this->latinRegularFont(), 'H');
            $capHeight = $box === false ? 72 : abs($box[7] - $box[1]);
            // cap height is ~0.7em: ~70 means size == pixels, ~95 means points @ 96 dpi
            $this->gdScale = $capHeight > 84 ? 0.75 : 1.0;
        }

        return $pixels * $this->gdScale;
    }

    private function renderPage(array $classData, array $rows, int $offset, int $pageNumber, int $pageCount, string $tempDir, bool $footerOnly = false): string
    {
        $canvas = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        if ($canvas === false) {
            throw new RuntimeException('Unable to create PDF canvas.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 17, 17, 17);
        $muted = imagecolorallocate($canvas, 92, 99, 112);
        $border = imagecolorallocate($canvas, 17, 17, 17);
        $headerBg = imagecolorallocate($canvas, 231, 231, 231);
        $tableHeadBg = imagecolorallocate($canvas, 242, 242, 242);
        $passGreen = imagecolorallocate($canvas, 19, 138, 19);
        $failRed = imagecolorallocate($canvas, 225, 29, 29);

        imagefill($canvas, 0, 0, $white);

        $showReportHeader = $pageNumber === 1 && ! $footerOnly;
        if ($showReportHeader) {
            $this->drawHeader($canvas, $classData, $tempDir, $black, $muted);
            $this->drawTableHeader($canvas, $black, $border, $headerBg, $tableHeadBg);
        }

        // Continuation pages are data-only: no repeated logo, report title,
        // course details, or column header.
        $y = $showReportHeader ? self::TABLE_BODY_TOP : 60;
        $bodyFs = $this->px(self::BODY_PX);
        $otherFs = $this->px(self::OTHER_PX);

        if ($footerOnly) {
            // The final page contains only the note and signature area.
        } elseif ($rows === []) {
            $this->drawRowBox($canvas, $y, self::ROW_HEIGHT, $border, null);
            $this->drawLatinTextCentered($canvas, 'No students found.', self::TABLE_X, $y, self::TABLE_W, self::ROW_HEIGHT, $bodyFs, $muted);
            $y += self::ROW_HEIGHT;
        } else {
            foreach ($rows as $index => $student) {
                $total = $this->resultTotalScore($student);
                $passed = $total >= 50;
                $rowColor = $passed ? $black : $failRed;
                $this->drawRowBox($canvas, $y, self::ROW_HEIGHT, $border, null);

                $x = self::TABLE_X;
                $cells = [
                    [(string) ($offset + $index + 1), 206, false],
                    [null, 411, false], // name, drawn with pango below
                    [ucfirst(strtolower((string) ($student['gender'] ?? '-'))), 205, false],
                    [$this->formatNumber($student['scores']['attendance'] ?? 0), 123, false],
                    [$this->formatNumber($student['scores']['activity'] ?? 0), 123, false],
                    [$this->formatNumber($student['scores']['exam'] ?? 0), 123, false],
                    [$this->formatNumber($total), 123, true],
                    [$passed ? 'Pass' : 'Fail', 140, true],
                    [$this->otherScoreLabel($total, $passed), 188, false],
                ];

                foreach ($cells as $col => [$text, $w, $bold]) {
                    if ($col === 1) {
                        // Name: centred horizontally and vertically like the other table values.
                        $this->drawPangoText(
                            $canvas,
                            $tempDir,
                            (string) ($student['name'] ?? '-'),
                            $this->pangoFontTableName(),
                            $x,
                            $y,
                            $w,
                            self::ROW_HEIGHT,
                            'center',
                            $passed ? '#111111' : '#e11d1d',
                            false,
                            true,
                            'km',
                            true
                        );
                    } else {
                        $color = $col === 7 ? ($passed ? $passGreen : $failRed) : $rowColor;
                        $size = $col === 8 ? $otherFs : $bodyFs;
                        $this->drawLatinTextCentered($canvas, (string) $text, $x, $y, $w, self::ROW_HEIGHT, $size, $color, $bold);
                    }

                    $x += $w;
                }

                if (! $passed && self::FAIL_STRIKETHROUGH) {
                    $lineY = $y + intdiv(self::ROW_HEIGHT, 2);
                    imagesetthickness($canvas, 2);
                    imageline($canvas, self::TABLE_X, $lineY, self::TABLE_X + self::TABLE_W, $lineY, $failRed);
                    imagesetthickness($canvas, 1);
                }

                $y += self::ROW_HEIGHT;
            }
        }

        // Note + signatures only on the last page
        if ($pageNumber === $pageCount) {
            $this->drawFooter($canvas, $classData, $tempDir, $black, $failRed, $y);
        }

        if ($pageCount > 1) {
            $this->drawLatinTextCentered(
                $canvas,
                'Page ' . $pageNumber . ' / ' . $pageCount,
                self::TABLE_X + self::TABLE_W - 200,
                self::PAGE_HEIGHT - 70,
                200,
                30,
                $this->px(16),
                $muted
            );
        }

        $pagePath = $tempDir . '/page-' . $pageNumber . '.jpg';
        imagejpeg($canvas, $pagePath, 92);
        imagedestroy($canvas);

        return $pagePath;
    }

    private function drawHeader($canvas, array $classData, string $tempDir, int $black, int $muted): void
    {
        $logoPath = public_path('assets/etec_logo.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo !== false) {
                imagealphablending($logo, true);
                imagesavealpha($logo, true);
                imagecopyresampled($canvas, $logo, 125, 30, 0, 0, 120, 120, imagesx($logo), imagesy($logo));
                imagedestroy($logo);
            }
        }

        $this->drawLatinTextCentered($canvas, 'ETEC CENTER', 75, 180, 220, 28, 18, $black, true);
        $this->drawLatinTextCentered($canvas, 'Build your IT', 75, 224, 220, 24, 14, $muted);
        $this->drawPangoText($canvas, $tempDir, 'លទ្ធផលប្រឡងបញ្ចប់', 'Noto Sans Khmer Bold 24', 620, 78, 520, 42, 'center', '#111111', false, true, 'km');

        $course = (string) ($classData['course'] ?? '-');
        $time = (string) ($classData['time'] ?? '-');
        $date = $this->toKhmerDigits(now()->timezone('Asia/Phnom_Penh')->format('d-m-Y'));

        $this->drawPangoText(
            $canvas,
            $tempDir,
            'វគ្គសិក្សា៖ ' . $course . '    ម៉ោងសិក្សា៖ ' . $time . '    ថ្ងៃទី៖ ' . $date,
            $this->pangoFontKhmerRegular(),
            350,
            151,
            1120,
            34,
            'center',
            '#111111',
            false,
            true,
            'km'
        );
    }

    private function drawTableHeader($canvas, int $black, int $border, int $headerBg, int $tableHeadBg): void
    {
        imagefilledrectangle($canvas, self::TABLE_X, self::TABLE_TOP, self::TABLE_X + self::TABLE_W, self::TABLE_BODY_TOP, $headerBg);

        $top = self::TABLE_TOP;
        $mid = self::TABLE_TOP + self::TABLE_SCORE_TOP_H;

        $this->drawHeaderCell($canvas, self::TABLE_X, $top, 206, self::TABLE_HEADER_H, 'No', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 206, $top, 411, self::TABLE_HEADER_H, 'Full Name', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 617, $top, 205, self::TABLE_HEADER_H, 'Gender', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 822, $top, 492, self::TABLE_SCORE_TOP_H, 'Score', $black, $border, true);
        $this->drawHeaderCell($canvas, self::TABLE_X + 1314, $top, 140, self::TABLE_HEADER_H, 'Result', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 1454, $top, 188, self::TABLE_HEADER_H, 'Other', $black, $border);

        $subHeaders = ['AT&T', 'ACT', 'EXAM', 'Total'];
        $x = self::TABLE_X + 822;
        for ($i = 0; $i < 4; $i++) {
            $this->drawHeaderCell(
                $canvas,
                $x,
                $mid,
                123,
                self::TABLE_HEADER_H - self::TABLE_SCORE_TOP_H,
                $subHeaders[$i],
                $black,
                $border,
                false,
                $tableHeadBg,
                $this->px(self::SUB_HEADER_PX)
            );
            $x += 123;
        }

        // draw the row-span cell borders so the header looks like one table
        imagerectangle($canvas, self::TABLE_X + 822, $top, self::TABLE_X + 1314, $mid, $border);
    }

    private function drawHeaderCell($canvas, int $x, int $y, int $width, int $height, string $label, int $textColor, int $borderColor, bool $merged = false, ?int $fillColor = null, ?float $fontSize = null): void
    {
        if ($fillColor !== null) {
            imagefilledrectangle($canvas, $x, $y, $x + $width, $y + $height, $fillColor);
        }
        imagerectangle($canvas, $x, $y, $x + $width, $y + $height, $borderColor);
        $this->drawLatinTextCentered($canvas, $label, $x, $y, $width, $height, $fontSize ?? $this->px(self::HEADER_PX), $textColor, true);
    }

    private function drawRowBox($canvas, int $y, int $height, int $borderColor, ?int $fillColor): void
    {
        if ($fillColor !== null) {
            imagefilledrectangle($canvas, self::TABLE_X, $y, self::TABLE_X + self::TABLE_W, $y + $height, $fillColor);
        }

        $x = self::TABLE_X;
        foreach (self::COLUMN_WIDTHS as $width) {
            imagerectangle($canvas, $x, $y, $x + $width, $y + $height, $borderColor);
            $x += $width;
        }
    }

    private function drawFooter($canvas, array $classData, string $tempDir, int $black, int $failRed, int $tableBottom): void
    {
        $teacher = (string) ($classData['teacher'] ?? '-');
        $reportDate = now()->timezone('Asia/Phnom_Penh');
        // Keep the closing section directly below a short final table instead
        // of wasting a mostly empty footer-only page.
        $noteTop = min(self::PAGE_HEIGHT - 160, $tableBottom + 22);
        $date = $reportDate->format('d-m-Y');

        // Khmer must go through Pango (or its Khmer-font GD fallback). Drawing
        // it with the Latin helper causes the missing-glyph squares shown in PDF.
        $this->drawPangoText($canvas, $tempDir, 'ចំណាំ៖', $this->pangoFontKhmerRegular(16), 56, $noteTop, self::TABLE_W, 22, 'left', '#e11d1d', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, '- ការបញ្ចុះតម្លៃមានពលភាពចាប់ពីថ្ងៃទី ' . $date . ' ដល់ថ្ងៃទី ' . $reportDate->copy()->addDays(14)->format('d-m-Y') . ' (២ សប្តាហ៍)។', $this->pangoFontKhmerRegular(16), 56, $noteTop + 26, self::TABLE_W, 22, 'left', '#e11d1d', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, '- រាល់ការបញ្ចុះតម្លៃទាំងអស់ត្រូវបានគណនាចេញពីតម្លៃដើមនៃវគ្គសិក្សាទាំងអស់ដែលមាននៅមជ្ឈមណ្ឌល។', $this->pangoFontKhmerRegular(16), 56, $noteTop + 48, self::TABLE_W, 22, 'left', '#e11d1d', false, true, 'km');

        // Reserve enough height for a readable approval stamp on compact
        // one-page reports; do not shrink the stamp simply to fit the edge.
        $signatureTop = min(self::PAGE_HEIGHT - 165, $noteTop + 65);
        $this->drawPangoText($canvas, $tempDir, 'បានឃើញ និង ឯកភាព', $this->pangoFontKhmerRegular(), 130, $signatureTop, 250, 22, 'left', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'នាយកមជ្ឈមណ្ឌល', $this->pangoFontKhmerRegular(), 130, $signatureTop + 30, 250, 22, 'left', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, $this->khmerLongDate($reportDate), $this->pangoFontKhmerRegular(), 1190, $signatureTop, 420, 22, 'right', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'ហត្ថលេខា និងឈ្មោះគ្រូបង្រៀន', $this->pangoFontKhmerRegular(), 1190, $signatureTop + 30, 420, 22, 'right', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'គ្រូបង្រៀន៖ ' . $teacher, $this->pangoFontKhmerBold(), 1190, $signatureTop + 78, 420, 24, 'right', '#111111', false, true, 'km');

        $stampPath = public_path('assets/etec_stamp.png');
        // On a full first page the closing block is intentionally compact.
        // Keep the stamp below the approval text without allowing it to run
        // past the landscape page boundary.
        $stampY = min(self::PAGE_HEIGHT - 112, $signatureTop + 48);
        if (is_file($stampPath) && $stampY + 100 <= self::PAGE_HEIGHT - 12) {
            $stamp = @imagecreatefrompng($stampPath);
            if ($stamp !== false) {
                imagealphablending($stamp, true);
                imagesavealpha($stamp, true);
                imagecopyresampled($canvas, $stamp, 125, $stampY, 0, 0, 108, 100, imagesx($stamp), imagesy($stamp));
                imagedestroy($stamp);
            }
        }
    }

    private function drawPangoText(
        $canvas,
        string $tempDir,
        string $text,
        string $font,
        int $x,
        int $y,
        int $width,
        int $height,
        string $align,
        string $foreground,
        bool $wrap,
        bool $ellipsize,
        ?string $language = null,
        bool $vcenter = false,
    ): void {
        if ($this->pangoViewBinary() === null) {
            $this->drawGdKhmerText($canvas, $text, $font, $x, $y, $width, $height, $align, $foreground);
            return;
        }

        $path = $this->pangoTextPath($tempDir, $text, $font, $width, $height, $align, $foreground, $wrap, $ellipsize, $language);
        $image = @imagecreatefrompng($path);
        if ($image === false) {
            throw new RuntimeException('Unable to read rendered text image.');
        }

        $textImage = $vcenter ? $this->trimTransparentPadding($image) : $image;
        $offsetY = $vcenter ? max(0, intdiv($height - imagesy($textImage), 2)) : 0;

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $textImage, $x, $y + $offsetY, 0, 0, imagesx($textImage), imagesy($textImage));
        if ($textImage !== $image) {
            imagedestroy($textImage);
        }
        imagedestroy($image);
    }

    /**
     * pango-view honours the requested height by returning transparent padding
     * above and below the glyphs. Trim that padding before positioning a table
     * name so the visible letters, rather than the transparent PNG canvas,
     * are vertically centred in the row.
     */
    private function trimTransparentPadding($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $top = $height;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7f;
                if ($alpha < 127) {
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }
        }

        if ($bottom < $top) {
            return $image;
        }

        $cropped = imagecrop($image, [
            'x' => 0,
            'y' => $top,
            'width' => $width,
            'height' => $bottom - $top + 1,
        ]);

        return $cropped === false ? $image : $cropped;
    }

    /**
     * Production images do not always include pango-view. The app ships Khmer
     * fonts, so GD provides a dependency-free fallback for the PDF renderer.
     */
    private function drawGdKhmerText($canvas, string $text, string $pangoFont, int $x, int $y, int $width, int $height, string $align, string $foreground): void
    {
        $text = $this->normalizeUtf8Text($text);
        if ($text === '') {
            return;
        }

        $font = str_contains(strtolower($pangoFont), 'bold')
            ? $this->khmerBoldFont()
            : $this->khmerRegularFont();
        $fontSize = max(11, min(28, (int) floor($height * 0.72)));
        $text = $this->fitLatinText($text, $font, $fontSize, max(1, $width - 8));
        $box = imagettfbbox($fontSize, 0, $font, $text);

        if ($box === false) {
            return;
        }

        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[7] - $box[1]);
        $drawX = $x + 4;

        if ($align === 'center') {
            $drawX = $x + max(0, (int) (($width - $textWidth) / 2));
        } elseif ($align === 'right') {
            $drawX = $x + max(0, $width - $textWidth - 4);
        }

        // baseline = top of the text block + its ascent ($box[7] is negative)
        $drawY = $y + max(0, (int) (($height - $textHeight) / 2)) - $box[7];
        [$red, $green, $blue] = sscanf($foreground, '#%02x%02x%02x');
        $color = imagecolorallocate($canvas, $red ?? 17, $green ?? 17, $blue ?? 17);
        imagettftext($canvas, $fontSize, 0, $drawX, $drawY, $color, $font, $text);
    }

    private function pangoTextPath(
        string $tempDir,
        string $text,
        string $font,
        int $width,
        int $height,
        string $align,
        string $foreground,
        bool $wrap,
        bool $ellipsize,
        ?string $language = null,
    ): string {
        $text = $this->normalizeUtf8Text($text);

        $key = sha1(json_encode([
            'text' => $text,
            'font' => $font,
            'width' => $width,
            'height' => $height,
            'align' => $align,
            'foreground' => $foreground,
            'wrap' => $wrap,
            'ellipsize' => $ellipsize,
            'language' => $language,
        ], JSON_UNESCAPED_UNICODE));

        if (isset($this->pangoCache[$key]) && File::exists($this->pangoCache[$key])) {
            return $this->pangoCache[$key];
        }

        $path = $tempDir . '/pango-' . $key . '.png';
        if (File::exists($path)) {
            $this->pangoCache[$key] = $path;
            return $path;
        }

        $sourcePath = $this->writePangoSourceFile($tempDir, $key, $text);

        $command = [
            $this->pangoViewBinary(),
            '--no-display',
            '--background=transparent',
            '--margin=0',
            '--pixels',
            '--font=' . $font,
            '--foreground=' . $foreground,
            '--align=' . $align,
            '--width=' . $width,
            '--height=' . $height,
            '--output=' . $path,
            $sourcePath,
        ];

        if ($wrap) {
            $command[] = '--wrap=word-char';
        }

        if ($ellipsize) {
            $command[] = '--ellipsize=end';
        }

        if ($language !== null) {
            $command[] = '--language=' . $language;
        }

        $process = Process::timeout(30)
            ->env([
                'LANG' => 'C.UTF-8',
                'LC_ALL' => 'C.UTF-8',
            ])
            ->run($command);

        if (! $process->successful() || ! File::exists($path)) {
            if (File::exists($path)) {
                File::delete($path);
            }

            if (File::exists($sourcePath)) {
                File::delete($sourcePath);
            }

            $errorOutput = trim($process->errorOutput());
            throw new RuntimeException($errorOutput !== '' ? $errorOutput : 'Unable to render PDF text.');
        }

        if (File::exists($sourcePath)) {
            File::delete($sourcePath);
        }

        $this->pangoCache[$key] = $path;

        return $path;
    }

    private function drawLatinTextCentered($canvas, string $text, int $x, int $y, int $width, int $height, float $fontSize, int $color, bool $bold = false): void
    {
        $font = $bold ? $this->latinBoldFont() : $this->latinRegularFont();
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $text = $this->fitLatinText($text, $font, $fontSize, $width - 8);
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        $textWidth = abs($box[4] - $box[0]);

        // Centre on cap height (not on this string's own bbox) so every cell in a
        // row shares one baseline, whether or not the text has descenders.
        $ref = imagettfbbox($fontSize, 0, $font, 'H');
        $capHeight = $ref === false ? $fontSize : abs($ref[7] - $ref[1]);

        $drawX = $x + max(0, (int) (($width - $textWidth) / 2));
        $baseline = $y + (int) round(($height + $capHeight) / 2);
        imagettftext($canvas, $fontSize, 0, $drawX, $baseline, $color, $font, $text);
    }

    private function drawLatinTextLeft($canvas, string $text, int $x, int $y, float $fontSize, int $color, bool $bold = false): void
    {
        $font = $bold ? $this->latinBoldFont() : $this->latinRegularFont();
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        imagettftext($canvas, $fontSize, 0, $x, $y - $box[1], $color, $font, $text);
    }

    private function fitLatinText(string $text, string $font, float $fontSize, int $maxWidth): string
    {
        if ($maxWidth <= 0) {
            return $text;
        }

        // Fits as-is: no ellipsis needed.
        if ($this->latinTextWidth($text, $font, $fontSize) <= $maxWidth) {
            return $text;
        }

        $candidate = $text;
        while ($candidate !== '' && $this->latinTextWidth($candidate . '...', $font, $fontSize) > $maxWidth) {
            $candidate = mb_substr($candidate, 0, mb_strlen($candidate) - 1);
        }

        return $candidate === '' ? '...' : $candidate . '...';
    }

    private function latinTextWidth(string $text, string $font, float $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return 0;
        }

        return abs($box[4] - $box[0]);
    }

    private function latinRegularFont(): string
    {
        return $this->resolveFont([
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            'Liberation Sans',
            self::FONT_LATIN_REG,
            'DejaVu Sans',
        ], 'Latin regular');
    }

    private function latinBoldFont(): string
    {
        return $this->resolveFont([
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            'Liberation Sans:bold',
            self::FONT_LATIN_BOLD,
            'DejaVu Sans:bold',
        ], 'Latin bold');
    }

    private function pangoFontKhmerRegular(int $size = 18): string
    {
        return 'Noto Sans Khmer ' . $size;
    }

    private function pangoFontKhmerBold(): string
    {
        return 'Noto Sans Khmer Bold 18';
    }

    private function pangoFontTableName(): string
    {
        // Latin letters use Liberation Sans (falls back to DejaVu Sans), Khmer uses Noto Sans Khmer
        return 'Liberation Sans,DejaVu Sans,Noto Sans Khmer Bold ' . self::BODY_PX;
    }

    private function khmerRegularFont(): string
    {
        return $this->resolveFont([
            public_path('assets/fonts/Battambang-Regular.ttf'),
            '/usr/share/fonts/truetype/noto/NotoSansKhmer-Regular.ttf',
            'Noto Sans Khmer',
        ], 'Khmer regular');
    }

    private function khmerBoldFont(): string
    {
        return $this->resolveFont([
            public_path('assets/fonts/KhmerUIb.ttf'),
            '/usr/share/fonts/truetype/noto/NotoSansKhmer-Bold.ttf',
            'Noto Sans Khmer:bold',
        ], 'Khmer bold');
    }

    private function pangoViewBinary(): ?string
    {
        if ($this->pangoResolved) {
            return $this->pangoBinary;
        }

        $this->pangoResolved = true;
        foreach (['/usr/bin/pango-view', '/usr/local/bin/pango-view'] as $candidate) {
            if (is_executable($candidate)) {
                return $this->pangoBinary = $candidate;
            }
        }

        return null;
    }

    private function writePdf(array $pageImages, string $pdfPath): void
    {
        $pageCount = count($pageImages);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        for ($page = 1; $page <= $pageCount; $page++) {
            $pageObject = 2 + (($page - 1) * 3) + 1;
            $kids[] = $pageObject . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

        foreach ($pageImages as $index => $pageImage) {
            $pageNumber = $index + 1;
            $pageObject = 2 + (($pageNumber - 1) * 3) + 1;
            $contentObject = $pageObject + 1;
            $imageObject = $pageObject + 2;

            [$width, $height] = getimagesize($pageImage);
            if ($width === false || $height === false) {
                throw new RuntimeException('Unable to read generated page image.');
            }

            $jpegData = file_get_contents($pageImage);
            if ($jpegData === false) {
                throw new RuntimeException('Unable to read generated page image.');
            }

            $contentStream = "q\n" . self::PDF_A4_LANDSCAPE_WIDTH . " 0 0 " . self::PDF_A4_LANDSCAPE_HEIGHT . " 0 0 cm\n/Im{$pageNumber} Do\nQ\n";
            $objects[$contentObject] = '<< /Length ' . strlen($contentStream) . " >>\nstream\n" . $contentStream . "endstream";
            $objects[$imageObject] = '<< /Type /XObject /Subtype /Image /Width ' . $width . ' /Height ' . $height . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpegData) . " >>\nstream\n" . $jpegData . "\nendstream";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PDF_A4_LANDSCAPE_WIDTH . ' ' . self::PDF_A4_LANDSCAPE_HEIGHT . '] /Contents ' . $contentObject . ' 0 R /Resources << /XObject << /Im' . $pageNumber . ' ' . $imageObject . ' 0 R >> /ProcSet [/PDF /ImageC] >> >>';
        }

        $handle = fopen($pdfPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to create PDF file.');
        }

        fwrite($handle, "%PDF-1.4\n");

        $offsets = [0 => 0];
        ksort($objects);

        foreach ($objects as $objectNumber => $body) {
            $offsets[$objectNumber] = ftell($handle);
            fwrite($handle, $objectNumber . " 0 obj\n");
            fwrite($handle, $body . "\n");
            fwrite($handle, "endobj\n");
        }

        $xrefPosition = ftell($handle);
        $maxObject = max(array_keys($objects));
        fwrite($handle, "xref\n0 " . ($maxObject + 1) . "\n");
        fwrite($handle, sprintf("%010d 65535 f \n", 0));

        for ($objectNumber = 1; $objectNumber <= $maxObject; $objectNumber++) {
            $offset = $offsets[$objectNumber] ?? 0;
            fwrite($handle, sprintf("%010d 00000 n \n", $offset));
        }

        fwrite($handle, "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF");
        fclose($handle);
    }

    /**
     * Candidates are tried in order: an absolute path must exist as a file, anything
     * else is treated as a fontconfig pattern ("Family" or "Family:bold").
     */
    private function resolveFont(array $candidates, string $label): string
    {
        $cacheKey = $label . '|' . md5(json_encode($candidates));
        if (isset($this->fontCache[$cacheKey])) {
            return $this->fontCache[$cacheKey];
        }

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (str_starts_with($candidate, '/')) {
                if (is_file($candidate)) {
                    return $this->fontCache[$cacheKey] = $candidate;
                }

                continue;
            }

            $resolved = $this->resolveViaFontConfig($candidate);
            if ($resolved !== null) {
                return $this->fontCache[$cacheKey] = $resolved;
            }
        }

        throw new RuntimeException("Unable to locate a usable font for {$label}.");
    }

    private function resolveViaFontConfig(string $pattern): ?string
    {
        $process = Process::timeout(10)->run(['fc-match', '-f', "%{family}\n%{file}\n", $pattern]);
        if (! $process->successful()) {
            return null;
        }

        $lines = preg_split('/\R/', trim($process->output())) ?: [];
        $family = strtolower($lines[0] ?? '');
        $file = trim($lines[1] ?? '');
        $wanted = strtolower(trim(explode(':', $pattern)[0]));

        // fc-match always returns *something*; only accept it if it is the family we asked for
        if ($file === '' || ! is_file($file) || ! str_contains($family, $wanted)) {
            return null;
        }

        return $file;
    }

    private function writePangoSourceFile(string $tempDir, string $key, string $text): string
    {
        $path = $tempDir . '/pango-' . $key . '.txt';
        File::put($path, $text);

        return $path;
    }

    private function normalizeUtf8Text(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        $converted = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return is_string($converted) ? $converted : '';
    }

    private function formatNumber(float|int|string $value): string
    {
        $numeric = (float) $value;
        return rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');
    }

    private function otherScoreLabel(float $total, bool $passed): string
    {
        if (! $passed) {
            return '';
        }

        if ($total >= 95) {
            return '(70% off)';
        }

        if ($total >= 85) {
            return '(50% discount)';
        }

        return '';
    }

    private function toKhmerDigits(string $value): string
    {
        return strtr($value, [
            '0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤',
            '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩',
        ]);
    }

    private function khmerLongDate($date): string
    {
        $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];

        return 'ធ្វើនៅភ្នំពេញ, ថ្ងៃទី ' . $this->toKhmerDigits($date->format('j'))
            . ' ខែ ' . $months[((int) $date->format('n')) - 1]
            . ' ឆ្នាំ ' . $this->toKhmerDigits($date->format('Y'));
    }
}
