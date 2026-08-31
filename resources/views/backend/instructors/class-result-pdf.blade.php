@php
    $title = $classData['title'] ?? 'Class Result';
    $course = $classData['course'] ?? '-';
    $time = $classData['time'] ?? '-';
    $teacher = $classData['teacher'] ?? '-';
    $date = now()->timezone('Asia/Phnom_Penh')->format('d-m-Y');
    $logoPath = public_path('assets/etec_logo.png');
    $logoData = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    $logoSrc = $logoData ? 'data:image/png;base64,' . $logoData : '';

    $students = collect($students)->values();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            width: 100%;
            background: #fff;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }

        .brand {
            width: 18%;
            text-align: center;
        }

        .brand img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }

        .brand-title {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .brand-subtitle {
            margin: 0;
            font-size: 11px;
        }

        .heading {
            width: 64%;
            text-align: center;
            padding-top: 6px;
        }

        .heading h1 {
            margin: 0 0 10px;
            font-family: "Times New Roman", serif;
            font-size: 24px;
            font-style: italic;
            font-weight: 700;
        }

        .heading p {
            margin: 0;
            font-family: "Times New Roman", serif;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .heading .date {
            color: #d11;
        }

        .table-wrap {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111;
            padding: 7px 8px;
            font-size: 12px;
            vertical-align: middle;
        }

        th {
            background: #e7e7e7;
            text-align: center;
            font-weight: 700;
        }

        td {
            text-align: center;
        }

        td.name {
            text-align: left;
            padding-left: 10px;
            font-weight: 700;
        }

        .student-id {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #666;
        }

        .pass {
            color: #138a13;
            font-weight: 700;
        }

        .fail-row td {
            color: #e11d1d;
            background-image: linear-gradient(#e11d1d, #e11d1d);
            background-size: 100% 1px;
            background-position: 0 50%;
            background-repeat: no-repeat;
        }

        .note {
            margin: 10px 0 0;
            color: #d11;
            font-size: 12px;
            line-height: 1.7;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin-top: 18px;
        }

        .sign-block {
            width: 32%;
            text-align: center;
        }

        .sign-label {
            margin: 0;
            font-family: "Times New Roman", serif;
            font-size: 14px;
            font-style: italic;
        }

        .sign-role {
            margin: 0;
            font-family: "Times New Roman", serif;
            font-size: 13px;
            font-style: italic;
        }

        .right-sign {
            width: 38%;
            text-align: right;
        }

        .right-sign p {
            margin: 0;
            font-family: "Times New Roman", serif;
            font-size: 14px;
            font-style: italic;
        }

        .teacher-name {
            margin-top: 14px !important;
            font-size: 15px !important;
            font-style: normal !important;
            font-weight: 700;
        }

        .table-head-2 th {
            background: #f2f2f2;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <div class="brand">
                @if ($logoSrc !== '')
                    <img src="{{ $logoSrc }}" alt="ETEC Center">
                @endif
                <p class="brand-title">ETEC Center</p>
                <p class="brand-subtitle">Build your IT</p>
            </div>

            <div class="heading">
                <h1>លទ្ធផលនៃការប្រលងបញ្ចប់</h1>
                <p>
                    វគ្គសិក្សា៖ <span>{{ $course }}</span>
                    &nbsp;&nbsp; ម៉ោងសិក្សា៖ <span>{{ $time }}</span>
                    &nbsp;&nbsp; ថ្ងៃទី៖ <span class="date">{{ $date }}</span>
                </p>
            </div>

            <div style="width: 18%;"></div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 6%;">No</th>
                        <th rowspan="2" style="width: 8%;">ID</th>
                        <th rowspan="2" style="width: 23%; text-align: left; padding-left: 10px;">Full Name</th>
                        <th rowspan="2" style="width: 12%;">Gender</th>
                        <th colspan="4" style="width: 29%;">Attendance</th>
                        <th rowspan="2" style="width: 10%;">Result</th>
                    </tr>
                    <tr class="table-head-2">
                        <th>Attendance</th>
                        <th>ACT</th>
                        <th>EXAM</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $index => $student)
                        @php
                            $attendance = (float) ($student['scores']['attendance'] ?? 0);
                            $activity = (float) ($student['scores']['activity'] ?? 0);
                            $exam = (float) ($student['scores']['exam'] ?? 0);
                            $total = $attendance + $activity + $exam;
                            $passed = $total >= 50;
                        @endphp
                        <tr class="{{ $passed ? '' : 'fail-row' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student['id'] ?? '-' }}</td>
                            <td class="name">
                                {{ $student['name'] ?? '-' }}
                            </td>
                            <td>{{ ucfirst(strtolower((string) ($student['gender'] ?? '-'))) }}</td>
                            <td>{{ rtrim(rtrim(number_format($attendance, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($activity, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($exam, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') }}</td>
                            <td class="{{ $passed ? 'pass' : '' }}">{{ $passed ? 'Pass' : 'Fail' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="note">
            ចំណាំ៖ លទ្ធផលនេះត្រូវបានបង្កើតដោយស្វ័យប្រវត្តិពីទិន្នន័យដែលបានរក្សាទុករួច។
        </div>

        <div class="footer">
            <div class="sign-block">
                <p class="sign-label">បានឃើញ និង ឯកភាព</p>
                <p class="sign-role">នាយកមជ្ឈមណ្ឌល</p>
            </div>

            <div class="right-sign">
                <p>ធ្វើនៅភ្នំពេញ, ថ្ងៃទី {{ now()->timezone('Asia/Phnom_Penh')->format('d-m-Y') }}</p>
                <p class="teacher-name">គ្រូបង្រៀន៖ {{ $teacher }}</p>
            </div>
        </div>
    </div>
</body>
</html>
