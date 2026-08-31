<?php

namespace App\Modules\Instructor\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class ClassResultPdfGenerator
{
    private const PAGE_WIDTH = 1754;
    private const PAGE_HEIGHT = 1240;
    private const MARGIN_X = 90;
    private const HEADER_TOP = 52;
    private const TABLE_TOP = 230;
    private const TABLE_HEIGHT = 840;
    private const ROW_HEIGHT = 46;
    private const HEADER_ROW_HEIGHT = 64;
    private const ROWS_PER_PAGE = 17;

    private const FONT_REGULAR = '/usr/share/fonts/truetype/noto/NotoSansKhmer-Regular.ttf';
    private const FONT_BOLD = '/usr/share/fonts/truetype/noto/NotoSansKhmer-Bold.ttf';
    private const LATIN_REGULAR = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    private const LATIN_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf';

    /**
     * Generate a downloadable PDF for the class result sheet.
     */
    public function generate(array $classData, Collection $students): string
    {
        $sortedStudents = $this->sortStudents($students);

        $tempDir = sys_get_temp_dir() . '/class-result-' . Str::uuid();
        $pdfPath = sys_get_temp_dir() . '/class-result-' . Str::uuid() . '.pdf';

        File::makeDirectory($tempDir, 0755, true);

        try {
            $pages = max(1, (int) ceil($sortedStudents->count() / self::ROWS_PER_PAGE));
            $pageImages = [];

            for ($page = 0; $page < $pages; $page++) {
                $slice = $sortedStudents->slice($page * self::ROWS_PER_PAGE, self::ROWS_PER_PAGE)->values();
                $pageImages[] = $this->renderPage($classData, $slice, $page + 1, $pages, $tempDir);
            }

            $this->writePdf($pageImages, $pdfPath);

            foreach ($pageImages as $pageImage) {
                if (File::exists($pageImage)) {
                    File::delete($pageImage);
                }
            }

            return $pdfPath;
        } finally {
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

    private function renderPage(array $classData, Collection $students, int $pageNumber, int $pageCount, string $tempDir): string
    {
        $image = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        if ($image === false) {
            throw new RuntimeException('Unable to create PDF canvas.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 17, 17, 17);
        $muted = imagecolorallocate($image, 92, 99, 112);
        $border = imagecolorallocate($image, 17, 17, 17);
        $headerBg = imagecolorallocate($image, 231, 231, 231);
        $tableHeadBg = imagecolorallocate($image, 242, 242, 242);
        $pass = imagecolorallocate($image, 19, 138, 19);
        $fail = imagecolorallocate($image, 225, 29, 29);
        $softFailBg = imagecolorallocate($image, 255, 244, 244);

        imagefill($image, 0, 0, $white);

        $tableX = 157;
        $tableW = 1440;
        $columnWidths = [70, 90, 530, 130, 120, 120, 120, 120, 140];
        $columnLabels = ['No', 'ID', 'Full Name', 'Gender', 'Attendance', 'ACT', 'EXAM', 'Total', 'Result'];
        $columnAlignments = ['center', 'center', 'left', 'center', 'center', 'center', 'center', 'center', 'center'];

        $this->drawHeader($image, $classData, $pageNumber, $pageCount, $white, $black, $muted, $border, $tableX, $tableW);
        $this->drawTableHeader($image, $tableX, self::TABLE_TOP, $columnWidths, $columnLabels, $headerBg, $tableHeadBg, $black, $border);

        $rowY = self::TABLE_TOP + self::HEADER_ROW_HEIGHT;

        if ($students->isEmpty()) {
            $this->drawRow($image, $tableX, $rowY, $columnWidths, array_fill(0, count($columnWidths), ''), $columnAlignments, $black, $border, null, true);
            $this->drawCenteredText($image, 'No students found.', $this->fontRegular(), 16, $tableX, $rowY, $tableW, self::ROW_HEIGHT, $muted);
        } else {
            foreach ($students as $index => $student) {
                $attendance = (float) ($student['scores']['attendance'] ?? 0);
                $activity = (float) ($student['scores']['activity'] ?? 0);
                $exam = (float) ($student['scores']['exam'] ?? 0);
                $total = $attendance + $activity + $exam;
                $passed = $total >= 50;

                $values = [
                    (string) ($index + 1),
                    (string) ($student['id'] ?? '-'),
                    (string) ($student['name'] ?? '-'),
                    ucfirst(strtolower((string) ($student['gender'] ?? '-'))),
                    $this->formatNumber($attendance),
                    $this->formatNumber($activity),
                    $this->formatNumber($exam),
                    $this->formatNumber($total),
                    $passed ? 'Pass' : 'Fail',
                ];

                $rowFill = $passed ? null : $softFailBg;
                $textColor = $passed ? $black : $fail;
                $this->drawRow($image, $tableX, $rowY, $columnWidths, $values, $columnAlignments, $textColor, $border, $rowFill);

                if (! $passed) {
                    $this->drawLine($image, $tableX, $rowY + (int) (self::ROW_HEIGHT / 2), $tableX + $tableW, $rowY + (int) (self::ROW_HEIGHT / 2), $fail);
                }

                $this->drawCellText($image, $values[0], $this->fontBold(), 15, $tableX, $rowY, $columnWidths[0], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[1], $this->fontRegular(), 15, $tableX + $columnWidths[0], $rowY, $columnWidths[1], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $this->fitText($values[2], $this->fontBold(), 18, $columnWidths[2] - 18), $this->fontBold(), 18, $tableX + array_sum(array_slice($columnWidths, 0, 2)) + 8, $rowY, $columnWidths[2] - 16, self::ROW_HEIGHT, $textColor, 'left');
                $this->drawCellText($image, $values[3], $this->fontRegular(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 3)), $rowY, $columnWidths[3], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[4], $this->latinRegular(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 4)), $rowY, $columnWidths[4], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[5], $this->latinRegular(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 5)), $rowY, $columnWidths[5], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[6], $this->latinRegular(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 6)), $rowY, $columnWidths[6], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[7], $this->latinBold(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 7)), $rowY, $columnWidths[7], self::ROW_HEIGHT, $textColor, 'center');
                $this->drawCellText($image, $values[8], $this->fontBold(), 15, $tableX + array_sum(array_slice($columnWidths, 0, 8)), $rowY, $columnWidths[8], self::ROW_HEIGHT, $passed ? $pass : $fail, 'center');

                $rowY += self::ROW_HEIGHT;
            }
        }

        $this->drawFooter($image, $classData, $black, $pageNumber, $pageCount);

        $pagePath = $tempDir . '/page-' . $pageNumber . '.jpg';
        imagejpeg($image, $pagePath, 92);
        imagedestroy($image);

        return $pagePath;
    }

    private function drawHeader($image, array $classData, int $pageNumber, int $pageCount, $white, $black, $muted, $border, int $tableX, int $tableW): void
    {
        $logoPath = public_path('assets/etec_logo.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo !== false) {
                $logoWidth = imagesx($logo);
                $logoHeight = imagesy($logo);
                $target = 92;
                imagecopyresampled($image, $logo, self::MARGIN_X, 58, 0, 0, $target, $target, $logoWidth, $logoHeight);
                imagedestroy($logo);
            }
        }

        $this->drawCenteredText($image, 'ETEC Center', $this->latinBold(), 18, self::MARGIN_X, 62, 160, 24, $black);
        $this->drawCenteredText($image, 'Build your IT', $this->latinRegular(), 13, self::MARGIN_X, 88, 160, 20, $muted);
        $this->drawCenteredText($image, 'លទ្ធផលនៃការប្រលងបញ្ចប់', $this->fontBold(), 28, 300, 64, self::PAGE_WIDTH - 600, 42, $black);

        $infoY = 112;
        $this->drawCenteredText(
            $image,
            'វគ្គសិក្សា៖ ' . ($classData['course'] ?? '-') . '    ម៉ោងសិក្សា៖ ' . ($classData['time'] ?? '-') . '    ថ្ងៃទី៖ ' . now()->timezone('Asia/Phnom_Penh')->format('d-m-Y'),
            $this->fontRegular(),
            16,
            260,
            $infoY,
            self::PAGE_WIDTH - 520,
            26,
            $muted
        );

        $this->drawCenteredText(
            $image,
            'គ្រូបង្រៀន៖ ' . ($classData['teacher'] ?? '-'),
            $this->fontRegular(),
            16,
            260,
            140,
            self::PAGE_WIDTH - 520,
            26,
            $black
        );

        imagesetthickness($image, 2);
        $this->drawRectangle($image, $tableX, self::TABLE_TOP, $tableW, self::TABLE_HEIGHT, $border);

        $pageText = 'Page ' . $pageNumber . ' / ' . $pageCount;
        $this->drawRightText($image, $pageText, $this->latinRegular(), 12, self::PAGE_WIDTH - 120, 32, 90, 18, $muted);
    }

    private function drawTableHeader($image, int $tableX, int $tableY, array $columnWidths, array $labels, $headerBg, $tableHeadBg, $black, $border): void
    {
        $this->drawFilledRect($image, $tableX, $tableY, array_sum($columnWidths), self::HEADER_ROW_HEIGHT, $headerBg);
        $x = $tableX;

        foreach ($labels as $index => $label) {
            $width = $columnWidths[$index];
            $this->drawRectangle($image, $x, $tableY, $width, self::HEADER_ROW_HEIGHT, $border);
            $this->drawCenteredText(
                $image,
                $label,
                $this->latinBold(),
                14,
                $x + 6,
                $tableY + 6,
                $width - 12,
                self::HEADER_ROW_HEIGHT - 12,
                $black
            );
            $x += $width;
        }
    }

    private function drawRow($image, int $tableX, int $tableY, array $columnWidths, array $values, array $alignments, $textColor, $border, ?int $fill = null, bool $empty = false): void
    {
        $rowWidth = array_sum($columnWidths);
        if ($fill !== null) {
            $this->drawFilledRect($image, $tableX, $tableY, $rowWidth, self::ROW_HEIGHT, $fill);
        }

        $x = $tableX;
        foreach ($columnWidths as $index => $width) {
            $this->drawRectangle($image, $x, $tableY, $width, self::ROW_HEIGHT, $border);
            $x += $width;
        }
    }

    private function drawFooter($image, array $classData, $black, int $pageNumber, int $pageCount): void
    {
        $this->drawCenteredText(
            $image,
            'ចំណាំ៖ លទ្ធផលនេះត្រូវបានបង្កើតដោយស្វ័យប្រវត្តិពីទិន្នន័យដែលបានរក្សាទុករួច។',
            $this->fontRegular(),
            14,
            120,
            1090,
            self::PAGE_WIDTH - 240,
            24,
            imagecolorallocate($image, 209, 17, 17)
        );

        $this->drawCenteredText($image, 'បានឃើញ និង ឯកភាព', $this->fontRegular(), 16, 180, 1128, 260, 24, $black);
        $this->drawCenteredText($image, 'នាយកមជ្ឈមណ្ឌល', $this->fontRegular(), 15, 180, 1156, 260, 24, $black);

        $this->drawRightText($image, 'ធ្វើនៅភ្នំពេញ, ថ្ងៃទី ' . now()->timezone('Asia/Phnom_Penh')->format('d-m-Y'), $this->fontRegular(), 16, self::PAGE_WIDTH - 210, 1128, 160, 24, $black);
        $this->drawRightText($image, 'គ្រូបង្រៀន៖ ' . ($classData['teacher'] ?? '-'), $this->fontBold(), 16, self::PAGE_WIDTH - 210, 1160, 160, 24, $black);
    }

    private function drawFilledRect($image, int $x, int $y, int $w, int $h, int $color): void
    {
        imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $color);
    }

    private function drawRectangle($image, int $x, int $y, int $w, int $h, int $color): void
    {
        imagerectangle($image, $x, $y, $x + $w, $y + $h, $color);
    }

    private function drawLine($image, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        imageline($image, $x1, $y1, $x2, $y2, $color);
    }

    private function drawCellText($image, string $text, string $font, int $fontSize, int $x, int $y, int $w, int $h, int $color, string $align = 'center'): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $text = $this->fitText($text, $font, $fontSize, $w);
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);

        $drawX = $x + 4;
        if ($align === 'center') {
            $drawX = $x + (int) max(0, (($w - $textWidth) / 2));
        } elseif ($align === 'right') {
            $drawX = $x + max(0, $w - $textWidth - 4);
        }

        $drawY = $y + (int) (($h + $textHeight) / 2) - 4;
        imagettftext($image, $fontSize, 0, $drawX, $drawY, $color, $font, $text);
    }

    private function drawCenteredText($image, string $text, string $font, int $fontSize, int $x, int $y, int $w, int $h, int $color): void
    {
        $this->drawCellText($image, $text, $font, $fontSize, $x, $y, $w, $h, $color, 'center');
    }

    private function drawRightText($image, string $text, string $font, int $fontSize, int $x, int $y, int $w, int $h, int $color): void
    {
        $this->drawCellText($image, $text, $font, $fontSize, $x, $y, $w, $h, $color, 'right');
    }

    private function fitText(string $text, string $font, int $fontSize, int $maxWidth): string
    {
        if ($this->textWidth($text, $font, $fontSize) <= $maxWidth) {
            return $text;
        }

        $ellipsis = '...';
        $candidate = $text;

        while ($candidate !== '' && $this->textWidth($candidate . $ellipsis, $font, $fontSize) > $maxWidth) {
            $candidate = mb_substr($candidate, 0, mb_strlen($candidate) - 1);
        }

        return rtrim($candidate) . $ellipsis;
    }

    private function textWidth(string $text, string $font, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return 0;
        }

        return abs($box[4] - $box[0]);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function writePdf(array $pageImages, string $pdfPath): void
    {
        $pageWidth = self::PAGE_WIDTH;
        $pageHeight = self::PAGE_HEIGHT;
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

            $contentStream = "q\n{$pageWidth} 0 0 {$pageHeight} 0 0 cm\n/Im{$pageNumber} Do\nQ\n";
            $objects[$contentObject] = '<< /Length ' . strlen($contentStream) . " >>\nstream\n" . $contentStream . "endstream";
            $objects[$imageObject] = '<< /Type /XObject /Subtype /Image /Width ' . $width . ' /Height ' . $height . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpegData) . " >>\nstream\n" . $jpegData . "\nendstream";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Contents ' . $contentObject . ' 0 R /Resources << /XObject << /Im' . $pageNumber . ' ' . $imageObject . ' 0 R >> /ProcSet [/PDF /ImageC] >> >>';
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

    private function fontRegular(): string
    {
        return self::FONT_REGULAR;
    }

    private function fontBold(): string
    {
        return self::FONT_BOLD;
    }

    private function latinRegular(): string
    {
        return self::LATIN_REGULAR;
    }

    private function latinBold(): string
    {
        return self::LATIN_BOLD;
    }
}
