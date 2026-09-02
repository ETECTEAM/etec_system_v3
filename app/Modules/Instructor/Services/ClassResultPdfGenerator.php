<?php

namespace App\Modules\Instructor\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClassResultPdfGenerator
{
    private const ROWS_PER_PAGE = 15;

    /**
     * Generate a downloadable PDF for the class result sheet.
     */
    public function generate(array $classData, Collection $students): string
    {
        $sortedStudents = $this->sortStudents($students);
        $reportDate = now()->timezone('Asia/Phnom_Penh');
        $tempDir = sys_get_temp_dir().'/class-result-'.Str::uuid();
        $htmlPath = $tempDir.'/class-result.html';
        $pdfPath = sys_get_temp_dir().'/class-result-'.Str::uuid().'.pdf';

        File::makeDirectory($tempDir, 0755, true);

        try {
            $pages = $sortedStudents->chunk(self::ROWS_PER_PAGE)->values();
            $pages = $pages->isEmpty() ? collect([collect()]) : $pages;

            File::put($htmlPath, view('backend.instructors.class-result-pdf', [
                'classData' => $classData,
                'students' => $sortedStudents,
                'pages' => $pages,
                'reportDate' => $reportDate,
            ])->render());

            $this->printHtmlToPdf($htmlPath, $pdfPath);

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

    private function printHtmlToPdf(string $htmlPath, string $pdfPath): void
    {
        $chrome = $this->chromeBinary();
        $process = new Process([
            $chrome,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--user-data-dir='.dirname($htmlPath).'/chrome',
            '--no-pdf-header-footer',
            '--print-to-pdf='.$pdfPath,
            'file://'.$htmlPath,
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
            throw new RuntimeException('Unable to render class result PDF: '.$process->getErrorOutput());
        }
    }

    private function chromeBinary(): string
    {
        foreach (['google-chrome', 'chromium', 'chromium-browser'] as $binary) {
            $resolved = trim((string) shell_exec('command -v '.escapeshellarg($binary).' 2>/dev/null'));

            if ($resolved !== '' && is_executable($resolved)) {
                return $resolved;
            }
        }

        throw new RuntimeException('Unable to locate Google Chrome or Chromium for PDF rendering.');
    }
}
