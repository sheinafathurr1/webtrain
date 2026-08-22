<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat - {{ $course->title }}</title>
    <style>
        @page {
            size: landscape;
            margin: 0;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }

        .frame {
            border: 3px solid #4338ca;
            padding: 20px;
            margin: 20px;
        }

        .frame-inner {
            border: 1px solid #a5b4fc;
            padding: 60px 50px;
            text-align: center;
        }

        .brand {
            font-size: 16px;
            letter-spacing: 4px;
            color: #4338ca;
            text-transform: uppercase;
            margin-bottom: 40px;
        }

        .title {
            font-size: 32px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        .given-to {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #4338ca;
            margin-bottom: 30px;
            border-bottom: 1px solid #d1d5db;
            display: inline-block;
            padding-bottom: 10px;
        }

        .description {
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .course-name {
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0 40px;
        }

        .footer-table {
            width: 100%;
            margin-top: 50px;
        }

        .footer-table td {
            width: 50%;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="frame-inner">
            <div class="brand">WebTrain</div>

            <div class="title">Sertifikat Penyelesaian</div>

            <div class="given-to">Diberikan kepada</div>
            <div class="student-name">{{ $user->name }}</div>

            <div class="description">
                Atas keberhasilan menyelesaikan seluruh materi pada course
            </div>
            <div class="course-name">{{ $course->title }}</div>

            <table class="footer-table">
                <tr>
                    @php
                        $bulanIndonesia = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ];
                    @endphp
                    <td style="text-align: left;">
                        Diselesaikan pada<br>
                        <strong>{{ $completedAt->format('d') }} {{ $bulanIndonesia[$completedAt->month] }} {{ $completedAt->format('Y') }}</strong>
                    </td>
                    <td style="text-align: right;">
                        WebTrain Learning Platform<br>
                        <strong>{{ config('app.url') }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
