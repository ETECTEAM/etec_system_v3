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
    private const MARGIN_X = 90;
    private const TABLE_X = 157;
    private const TABLE_W = 1440;
    private const TABLE_TOP = 230;
    private const TABLE_HEADER_H = 64;
    private const TABLE_BODY_TOP = 294;
    private const TABLE_BODY_BOTTOM = 1076;
    private const ROW_HEIGHT = 46;
    private const ROWS_PER_PAGE = 17;

    private const FONT_LATIN_REG = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    private const FONT_LATIN_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    private array $pangoCache = [];

    public function generate(array $classData, Collection $students): string
    {
        $sortedStudents = $this->sortStudents($students)->values()->all();
        $pages = array_chunk($sortedStudents, self::ROWS_PER_PAGE);

        if ($pages === []) {
            $pages = [[]];
        }

        $tempDir = sys_get_temp_dir() . '/class-result-' . Str::uuid();
        $pdfPath = sys_get_temp_dir() . '/class-result-' . Str::uuid() . '.pdf';
        File::makeDirectory($tempDir, 0755, true);

        try {
            $pageImages = [];

            foreach ($pages as $index => $rows) {
                $pageImages[] = $this->renderPage($classData, $rows, $index + 1, count($pages), $tempDir);
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

    private function renderPage(array $classData, array $rows, int $pageNumber, int $pageCount, string $tempDir): string
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
        $softFailBg = imagecolorallocate($canvas, 255, 244, 244);

        imagefill($canvas, 0, 0, $white);

        $this->drawHeader($canvas, $classData, $pageNumber, $pageCount, $tempDir, $black, $muted, $border, $headerBg, $tableHeadBg);
        $this->drawTableHeader($canvas, $black, $border, $headerBg, $tableHeadBg);

        $y = self::TABLE_BODY_TOP;

        if ($rows === []) {
            $this->drawRowBox($canvas, $y, self::ROW_HEIGHT, $border, null);
            $this->drawLatinTextCentered($canvas, 'No students found.', self::TABLE_X, $y, self::TABLE_W, self::ROW_HEIGHT, 16, $muted);
        } else {
            foreach ($rows as $index => $student) {
                $passed = $this->resultTotalScore($student) >= 50;
                $rowFill = $passed ? null : $softFailBg;

                $this->drawRowBox($canvas, $y, self::ROW_HEIGHT, $border, $rowFill);

                $values = [
                    (string) ($index + 1),
                    (string) ($student['id'] ?? '-'),
                    (string) ($student['name'] ?? '-'),
                    ucfirst(strtolower((string) ($student['gender'] ?? '-'))),
                    $this->formatNumber($student['scores']['attendance'] ?? 0),
                    $this->formatNumber($student['scores']['activity'] ?? 0),
                    $this->formatNumber($student['scores']['exam'] ?? 0),
                    $this->formatNumber($this->resultTotalScore($student)),
                    $passed ? 'Pass' : 'Fail',
                ];

                $this->drawLatinTextCentered($canvas, $values[0], self::TABLE_X, $y, 70, self::ROW_HEIGHT, 15, $passed ? $black : $failRed, true);
                $this->drawLatinTextCentered($canvas, $values[1], self::TABLE_X + 70, $y, 90, self::ROW_HEIGHT, 15, $passed ? $black : $failRed);
                $this->drawPangoText($canvas, $tempDir, $values[2], $this->pangoFontKhmerBold(), self::TABLE_X + 160, $y + 4, 530 - 16, self::ROW_HEIGHT - 8, 'left', '#111111', false, true, 'km');
                $this->drawLatinTextCentered($canvas, $values[3], self::TABLE_X + 690, $y, 130, self::ROW_HEIGHT, 15, $passed ? $black : $failRed);
                $this->drawLatinTextCentered($canvas, $values[4], self::TABLE_X + 820, $y, 120, self::ROW_HEIGHT, 15, $passed ? $black : $failRed);
                $this->drawLatinTextCentered($canvas, $values[5], self::TABLE_X + 940, $y, 120, self::ROW_HEIGHT, 15, $passed ? $black : $failRed);
                $this->drawLatinTextCentered($canvas, $values[6], self::TABLE_X + 1060, $y, 120, self::ROW_HEIGHT, 15, $passed ? $black : $failRed);
                $this->drawLatinTextCentered($canvas, $values[7], self::TABLE_X + 1180, $y, 120, self::ROW_HEIGHT, 15, $passed ? $black : $failRed, true);
                $this->drawLatinTextCentered($canvas, $values[8], self::TABLE_X + 1300, $y, 140, self::ROW_HEIGHT, 15, $passed ? $passGreen : $failRed, true);

                if (! $passed) {
                    imageline($canvas, self::TABLE_X, $y + (int) (self::ROW_HEIGHT / 2), self::TABLE_X + self::TABLE_W, $y + (int) (self::ROW_HEIGHT / 2), $failRed);
                }

                $y += self::ROW_HEIGHT;
            }
        }

        $this->drawFooter($canvas, $classData, $tempDir, $black, $failRed);

        $pagePath = $tempDir . '/page-' . $pageNumber . '.jpg';
        imagejpeg($canvas, $pagePath, 92);
        imagedestroy($canvas);

        return $pagePath;
    }

    private function drawHeader($canvas, array $classData, int $pageNumber, int $pageCount, string $tempDir, int $black, int $muted, int $border, int $headerBg, int $tableHeadBg): void
    {
        $logoPath = public_path('assets/etec_logo.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo !== false) {
                imagealphablending($logo, true);
                imagesavealpha($logo, true);
                imagecopyresampled($canvas, $logo, self::MARGIN_X, 58, 0, 0, 92, 92, imagesx($logo), imagesy($logo));
                imagedestroy($logo);
            }
        }

        $this->drawLatinTextCentered($canvas, 'ETEC Center', self::MARGIN_X, 62, 180, 24, 18, $black, true);
        $this->drawLatinTextCentered($canvas, 'Build your IT', self::MARGIN_X, 88, 180, 20, 13, $muted);
        $this->drawPangoText($canvas, $tempDir, 'លទ្ធផលនៃការប្រលងបញ្ចប់', $this->pangoFontKhmerBold(), 300, 64, self::PAGE_WIDTH - 600, 42, 'center', '#111111', false, true, 'km');

        $course = (string) ($classData['course'] ?? '-');
        $time = (string) ($classData['time'] ?? '-');
        $teacher = (string) ($classData['teacher'] ?? '-');
        $date = now()->timezone('Asia/Phnom_Penh')->format('d-m-Y');

        $this->drawPangoText(
            $canvas,
            $tempDir,
            'វគ្គសិក្សា៖ ' . $course . '    ម៉ោងសិក្សា៖ ' . $time . '    ថ្ងៃទី៖ ' . $date,
            $this->pangoFontKhmerRegular(),
            260,
            112,
            self::PAGE_WIDTH - 520,
            26,
            'center',
            '#5c6370',
            false,
            true,
            'km'
        );
        $this->drawPangoText(
            $canvas,
            $tempDir,
            'គ្រូបង្រៀន៖ ' . $teacher,
            $this->pangoFontKhmerRegular(),
            260,
            138,
            self::PAGE_WIDTH - 520,
            26,
            'center',
            '#111111',
            false,
            true,
            'km'
        );

        $this->drawLatinTextCentered($canvas, 'Page ' . $pageNumber . ' / ' . $pageCount, self::PAGE_WIDTH - 170, 24, 130, 18, 12, $muted, true);
    }

    private function drawTableHeader($canvas, int $black, int $border, int $headerBg, int $tableHeadBg): void
    {
        imagefilledrectangle($canvas, self::TABLE_X, self::TABLE_TOP, self::TABLE_X + self::TABLE_W, self::TABLE_BODY_BOTTOM, $headerBg);
        imagerectangle($canvas, self::TABLE_X, self::TABLE_TOP, self::TABLE_X + self::TABLE_W, self::TABLE_BODY_BOTTOM, $border);

        $top = self::TABLE_TOP;
        $mid = self::TABLE_TOP + 32;
        $bottom = self::TABLE_BODY_TOP;

        $this->drawHeaderCell($canvas, self::TABLE_X, $top, 70, 64, 'No', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 70, $top, 90, 64, 'ID', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 160, $top, 530, 64, 'Full Name', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 690, $top, 130, 64, 'Gender', $black, $border);
        $this->drawHeaderCell($canvas, self::TABLE_X + 820, $top, 480, 32, 'Attendance', $black, $border, true);
        $this->drawHeaderCell($canvas, self::TABLE_X + 1300, $top, 140, 64, 'Result', $black, $border);

        $subHeaders = ['ATT', 'ACT', 'EXAM', 'Total'];
        $x = self::TABLE_X + 820;
        for ($i = 0; $i < 4; $i++) {
            $this->drawHeaderCell($canvas, $x, $mid, 120, 32, $subHeaders[$i], $black, $border, false, $tableHeadBg);
            $x += 120;
        }

        // draw the row-span cell borders so the header looks like one table
        imagerectangle($canvas, self::TABLE_X + 820, $top, self::TABLE_X + 1300, $mid, $border);
    }

    private function drawHeaderCell($canvas, int $x, int $y, int $width, int $height, string $label, int $textColor, int $borderColor, bool $merged = false, ?int $fillColor = null): void
    {
        if ($fillColor !== null) {
            imagefilledrectangle($canvas, $x, $y, $x + $width, $y + $height, $fillColor);
        }
        imagerectangle($canvas, $x, $y, $x + $width, $y + $height, $borderColor);
        $this->drawLatinTextCentered($canvas, $label, $x, $y, $width, $height, 14, $textColor, true);
    }

    private function drawRowBox($canvas, int $y, int $height, int $borderColor, ?int $fillColor): void
    {
        if ($fillColor !== null) {
            imagefilledrectangle($canvas, self::TABLE_X, $y, self::TABLE_X + self::TABLE_W, $y + $height, $fillColor);
        }

        $x = self::TABLE_X;
        foreach ([70, 90, 530, 130, 120, 120, 120, 120, 140] as $width) {
            imagerectangle($canvas, $x, $y, $x + $width, $y + $height, $borderColor);
            $x += $width;
        }
    }

    private function drawFooter($canvas, array $classData, string $tempDir, int $black, int $failRed): void
    {
        $teacher = (string) ($classData['teacher'] ?? '-');
        $date = now()->timezone('Asia/Phnom_Penh')->format('d-m-Y');

        $this->drawPangoText(
            $canvas,
            $tempDir,
            'ចំណាំ៖ លទ្ធផលនេះត្រូវបានបង្កើតដោយស្វ័យប្រវត្តិពីទិន្នន័យដែលបានរក្សាទុករួច។',
            $this->pangoFontKhmerRegular(),
            120,
            1090,
            self::PAGE_WIDTH - 240,
            24,
            'center',
            '#d11d1d',
            false,
            true,
            'km'
        );

        $this->drawPangoText($canvas, $tempDir, 'បានឃើញ និង ឯកភាព', $this->pangoFontKhmerRegular(), 180, 1128, 260, 24, 'center', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'នាយកមជ្ឈមណ្ឌល', $this->pangoFontKhmerRegular(), 180, 1156, 260, 24, 'center', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'ធ្វើនៅភ្នំពេញ, ថ្ងៃទី ' . $date, $this->pangoFontKhmerRegular(), self::PAGE_WIDTH - 430, 1128, 270, 24, 'right', '#111111', false, true, 'km');
        $this->drawPangoText($canvas, $tempDir, 'គ្រូបង្រៀន៖ ' . $teacher, $this->pangoFontKhmerBold(), self::PAGE_WIDTH - 430, 1160, 270, 24, 'right', '#111111', false, true, 'km');
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
    ): void {
        $path = $this->pangoTextPath($tempDir, $text, $font, $width, $height, $align, $foreground, $wrap, $ellipsize, $language);
        $image = @imagecreatefrompng($path);
        if ($image === false) {
            throw new RuntimeException('Unable to read rendered text image.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $image, $x, $y, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
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
            '/usr/bin/pango-view',
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

    private function drawLatinTextCentered($canvas, string $text, int $x, int $y, int $width, int $height, int $fontSize, int $color, bool $bold = false): void
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
        $textHeight = abs($box[5] - $box[1]);
        $drawX = $x + max(0, (int) (($width - $textWidth) / 2));
        $drawY = $y + max(0, (int) (($height - $textHeight) / 2)) - $box[1];
        imagettftext($canvas, $fontSize, 0, $drawX, $drawY, $color, $font, $text);
    }

    private function fitLatinText(string $text, string $font, int $fontSize, int $maxWidth): string
    {
        if ($maxWidth <= 0) {
            return $text;
        }

        $candidate = $text;
        while ($candidate !== '' && $this->latinTextWidth($candidate . '...', $font, $fontSize) > $maxWidth) {
            $candidate = mb_substr($candidate, 0, mb_strlen($candidate) - 1);
        }

        return $candidate === '' ? '...' : $candidate;
    }

    private function latinTextWidth(string $text, string $font, int $fontSize): int
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
            self::FONT_LATIN_REG,
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            'DejaVu Sans',
            'Liberation Sans',
        ], 'Latin regular');
    }

    private function latinBoldFont(): string
    {
        return $this->resolveFont([
            self::FONT_LATIN_BOLD,
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            'DejaVu Sans Bold',
            'Liberation Sans Bold',
        ], 'Latin bold');
    }

    private function pangoFontKhmerRegular(): string
    {
        return 'Noto Sans Khmer 18';
    }

    private function pangoFontKhmerBold(): string
    {
        return 'Noto Sans Khmer Bold 18';
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

            $contentStream = "q\n" . self::PAGE_WIDTH . " 0 0 " . self::PAGE_HEIGHT . " 0 0 cm\n/Im{$pageNumber} Do\nQ\n";
            $objects[$contentObject] = '<< /Length ' . strlen($contentStream) . " >>\nstream\n" . $contentStream . "endstream";
            $objects[$imageObject] = '<< /Type /XObject /Subtype /Image /Width ' . $width . ' /Height ' . $height . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpegData) . " >>\nstream\n" . $jpegData . "\nendstream";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Contents ' . $contentObject . ' 0 R /Resources << /XObject << /Im' . $pageNumber . ' ' . $imageObject . ' 0 R >> /ProcSet [/PDF /ImageC] >> >>';
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

    private function resolveFont(array $candidates, string $label): string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '' || str_starts_with($candidate, '/')) {
                continue;
            }

            $resolved = $this->resolveViaFontConfig($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new RuntimeException("Unable to locate a usable font for {$label}.");
    }

    private function resolveViaFontConfig(string $family): ?string
    {
        $process = Process::timeout(10)->run(['fc-match', '-f', '%{file}\\n', $family]);
        $output = trim($process->output());

        return $process->successful() && $output !== '' && is_file($output) ? $output : null;
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
}
