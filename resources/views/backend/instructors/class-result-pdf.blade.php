@php
    use Illuminate\Support\Collection;

    $title = $classData['title'] ?? 'Class Result';
    $course = $classData['course'] ?? '-';
    $time = $classData['time'] ?? '-';
    $teacher = $classData['teacher'] ?? '-';
    $teacherDisplay = trim(preg_split('/\s*(?:·|\|| - )\s*/u', $teacher, 2)[0] ?? $teacher);
    $date = $reportDate ?? now()->timezone('Asia/Phnom_Penh');
    $pages = collect($pages ?? [collect($students ?? [])]);
    $pages = $pages->isEmpty() ? collect([collect()]) : $pages;
    $classType = strtolower((string) ($classData['class_type'] ?? ''));
    $classTypeLabel = strtolower((string) ($classData['class_type_label'] ?? $classData['status'] ?? ''));
    $isInternship = str_contains($classType, 'internship') || str_contains($classTypeLabel, 'internship');

    $fontPath = public_path('assets/fonts/Battambang-Regular.ttf');
    $fontData = is_file($fontPath) ? base64_encode(file_get_contents($fontPath)) : '';
    $logoPath = public_path('assets/etec_logo.png');
    $logoData = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    $stampPath = public_path($isInternship ? 'assets/kru_it_internship_stamp.jpg' : 'assets/etec_stamp.png');
    $stampData = is_file($stampPath) ? base64_encode(file_get_contents($stampPath)) : '';

    $logoSrc = $logoData ? 'data:image/png;base64,' . $logoData : '';
    $stampSrc = $stampData ? 'data:image/png;base64,' . $stampData : '';
    $khmerMonths = [
        'មករា',
        'កុម្ភៈ',
        'មីនា',
        'មេសា',
        'ឧសភា',
        'មិថុនា',
        'កក្កដា',
        'សីហា',
        'កញ្ញា',
        'តុលា',
        'វិច្ឆិកា',
        'ធ្នូ',
    ];

    $formatNumber = static function (float $value): string {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    };

    $toKhmerDigits = static function (int|string $value): string {
        return strtr((string) $value, [
            '0' => '០',
            '1' => '១',
            '2' => '២',
            '3' => '៣',
            '4' => '៤',
            '5' => '៥',
            '6' => '៦',
            '7' => '៧',
            '8' => '៨',
            '9' => '៩',
        ]);
    };

    $khmerDate = static function ($date) use ($khmerMonths, $toKhmerDigits): string {
        return 'ធ្វើនៅភ្នំពេញ, ថ្ងៃទី '
            . $toKhmerDigits($date->format('j'))
            . ' ខែ '
            . $khmerMonths[((int) $date->format('n')) - 1]
            . ' ឆ្នាំ '
            . $toKhmerDigits($date->format('Y'));
    };

    $otherScoreLabel = static function (float $total, bool $passed): string {
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
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        @if ($fontData !== '')
            @font-face {
                font-family: "BattambangPdf";
                src: url("data:font/truetype;charset=utf-8;base64,{{ $fontData }}") format("truetype");
                font-weight: 400;
                font-style: normal;
            }
        @endif

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            color: #111;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .page {
            width: 297mm;
            height: 210mm;
            padding: 10mm 9.5mm 8mm;
            page-break-after: always;
            overflow: hidden;
            background: #fff;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            display: grid;
            grid-template-columns: 43mm 1fr 43mm;
            align-items: start;
            min-height: 33mm;
        }

        .brand {
            text-align: center;
        }

        .brand img {
            width: 14mm;
            height: 14mm;
            object-fit: contain;
            display: block;
            margin: 0 auto 4mm;
        }

        .brand-title {
            margin: 0;
            font-size: 15px;
            line-height: 1;
            font-weight: 800;
            text-transform: uppercase;
        }

        .brand-subtitle {
            margin: 3mm 0 0;
            font-size: 12px;
            color: #333;
        }

        .heading {
            padding-top: 5mm;
            text-align: center;
            font-family: Georgia, "Times New Roman", serif;
        }

        .heading h1 {
            margin: 0 0 7mm;
            font-size: 18px;
            line-height: 1;
            font-style: italic;
            font-weight: 700;
        }

        .heading .khmer-title {
            font-family: BattambangPdf, Arial, Helvetica, sans-serif;
            font-style: normal;
            font-weight: 700;
        }

        .meta {
            display: flex;
            justify-content: center;
            align-items: baseline;
            gap: 5mm;
            white-space: nowrap;
            font-size: 14px;
            line-height: 1.2;
            font-style: italic;
        }

        .meta strong {
            font-weight: 800;
        }

        .meta.khmer {
            font-family: BattambangPdf, Arial, Helvetica, sans-serif;
            font-style: normal;
        }

        .date {
            color: #e73543;
            font-weight: 800;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #111;
            text-align: center;
            vertical-align: middle;
        }

        th {
            height: 11.5mm;
            padding: 1.5mm 2mm;
            background: #e3e3e3;
            font-size: 13px;
            font-weight: 800;
        }

        .subhead th {
            height: 8.5mm;
            background: #eeeeee;
            font-size: 12px;
        }

        td {
            height: 8.8mm;
            padding: 1mm 2mm;
            font-size: 12px;
            line-height: 1.15;
        }

        .name {
            text-align: left;
            font-size: 13px;
            font-weight: 800;
        }

        .pass {
            color: #008000;
            font-weight: 700;
        }

        .fail-row td {
            color: #ff1111;
            background-image: linear-gradient(#ff1111, #ff1111);
            background-position: 0 50%;
            background-repeat: no-repeat;
            background-size: 100% 1px;
        }

        .score-total {
            font-weight: 800;
        }

        .other {
            font-size: 12px;
            white-space: nowrap;
        }

        .note {
            margin-top: 7mm;
            color: #ef3347;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.8;
        }

        .note-title {
            margin-bottom: 1mm;
        }

        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 8mm;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12px;
            font-style: italic;
        }

        .director {
            padding-left: 13mm;
            text-align: left;
        }

        .director p,
        .teacher p {
            margin: 0 0 3mm;
        }

        .teacher {
            padding-right: 22mm;
            text-align: right;
        }

        .stamp {
            display: block;
            width: 28mm;
            height: 27mm;
            object-fit: contain;
            margin-top: 8mm;
        }

        .teacher-name {
            margin-top: 18mm !important;
            font-size: 13px;
        }

        .khmer {
            font-family: BattambangPdf, Arial, Helvetica, sans-serif;
            font-style: normal;
        }
    </style>
</head>
<body>
    @foreach ($pages as $pageIndex => $pageStudents)
        @php
            $pageStudents = $pageStudents instanceof Collection ? $pageStudents->values() : collect($pageStudents)->values();
            $isLastPage = $loop->last;
            $startNumber = ($pageIndex * 15) + 1;
        @endphp

        <section class="page">
            <header class="header">
                <div class="brand">
                    @if ($logoSrc !== '')
                        <img src="{{ $logoSrc }}" alt="ETEC Center">
                    @endif
                    <p class="brand-title">ETEC CENTER</p>
                    <p class="brand-subtitle">Build your IT</p>
                </div>

                <div class="heading">
                    <h1 class="khmer-title">លទ្ធផលប្រឡងបញ្ចប់</h1>
                    <div class="meta khmer">
                        <span><strong>វគ្គសិក្សា៖</strong> {{ $course }}</span>
                        <span><strong>ម៉ោងសិក្សា៖</strong> {{ $time }}</span>
                        <span><strong>កាលបរិច្ឆេទ៖</strong> <span class="date">{{ $toKhmerDigits($date->format('d-m-Y')) }}</span></span>
                    </div>
                </div>

                <div></div>
            </header>

            <table>
                <colgroup>
                    <col style="width: 12.5%;">
                    <col style="width: 25%;">
                    <col style="width: 12.5%;">
                    <col style="width: 7.5%;">
                    <col style="width: 7.5%;">
                    <col style="width: 7.5%;">
                    <col style="width: 7.5%;">
                    <col style="width: 8.5%;">
                    <col style="width: 11.5%;">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">Full Name</th>
                        <th rowspan="2">Gender</th>
                        <th colspan="4">Score</th>
                        <th rowspan="2">Result</th>
                        <th rowspan="2">Other</th>
                    </tr>
                    <tr class="subhead">
                        <th>AT&amp;T</th>
                        <th>ACT</th>
                        <th>EXAM</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pageStudents as $studentIndex => $student)
                        @php
                            $attendance = (float) ($student['scores']['attendance'] ?? 0);
                            $activity = (float) ($student['scores']['activity'] ?? 0);
                            $exam = (float) ($student['scores']['exam'] ?? 0);
                            $total = $attendance + $activity + $exam;
                            $passed = $total >= 50;
                        @endphp
                        <tr class="{{ $passed ? '' : 'fail-row' }}">
                            <td>{{ $startNumber + $studentIndex }}</td>
                            <td class="name">{{ $student['name'] ?? '-' }}</td>
                            <td>{{ ucfirst(strtolower((string) ($student['gender'] ?? '-'))) }}</td>
                            <td>{{ $formatNumber($attendance) }}</td>
                            <td>{{ $formatNumber($activity) }}</td>
                            <td>{{ $formatNumber($exam) }}</td>
                            <td class="score-total">{{ $formatNumber($total) }}</td>
                            <td class="{{ $passed ? 'pass' : '' }}">{{ $passed ? 'Pass' : 'Fail' }}</td>
                            <td class="other">{{ $otherScoreLabel($total, $passed) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($isLastPage)
                <div class="note">
                    <div class="note-title">Note:</div>
                    <div>- Discount is valid from {{ $date->format('d-m-Y') }} to {{ $date->copy()->addDays(14)->format('d-m-Y') }} ( 2 weeks).</div>
                    <div>- All discounts are calculated from the original price of all courses available at the center.</div>
                </div>

                <footer class="footer">
                    <div class="director">
                        <p class="khmer">បានឃើញ និង ឯកភាព</p>
                        <p class="khmer">នាយកមជ្ឈមណ្ឌល</p>
                        @if ($stampSrc !== '')
                            <img class="stamp" src="{{ $stampSrc }}" alt="ETEC Center stamp">
                        @endif
                    </div>

                    <div class="teacher">
                        <p class="khmer">{{ $khmerDate($date) }}</p>
                        <p class="khmer">ហត្ថលេខា និងឈ្មោះគ្រូបង្រៀន</p>
                        <p class="teacher-name khmer">គ្រូបង្រៀន៖ &nbsp;{{ $teacherDisplay }}</p>
                    </div>
                </footer>
            @endif
        </section>
    @endforeach
</body>
</html>
