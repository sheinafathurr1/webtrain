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
            color: #1C1914;
            background: #FFFBF5;
        }

        .frame {
            border: 3px solid #0F766E;
            margin: 20px;
        }

        .frame-inner {
            border: 1px solid #B45309;
            padding: 50px 50px 40px;
            text-align: center;
        }

        .brand {
            font-size: 16px;
            letter-spacing: 4px;
            color: #0F766E;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .brand-tagline {
            font-size: 10px;
            letter-spacing: 2px;
            color: #C2410C;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        .seal {
            width: 56px;
            height: 56px;
            line-height: 52px;
            border: 2px solid #B45309;
            border-radius: 50%;
            color: #B45309;
            font-size: 26px;
            text-align: center;
            margin: 0 auto 20px;
        }

        .title {
            font-size: 30px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 26px;
            color: #1C1914;
        }

        .given-to {
            font-size: 13px;
            color: #5C574F;
            margin-bottom: 8px;
        }

        .student-name {
            font-size: 34px;
            font-weight: bold;
            color: #0F766E;
            margin-bottom: 26px;
            border-bottom: 1px solid #E5E0D6;
            display: inline-block;
            padding-bottom: 10px;
        }

        .description {
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .course-name {
            font-size: 20px;
            font-weight: bold;
            color: #C2410C;
            margin: 12px 0 36px;
        }

        .footer-table {
            width: 100%;
            margin-top: 30px;
        }

        .footer-table td {
            width: 33.33%;
            font-size: 11px;
            color: #5C574F;
            vertical-align: top;
        }

        .footer-table strong {
            color: #1C1914;
        }

        .code {
            font-family: monospace;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="frame-inner">
            <div class="brand">WebTrain</div>
            <div class="brand-tagline">Learning Platform</div>

            <div class="seal">&#10003;</div>

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
                    <td style="text-align: center;">
                        Kode Sertifikat<br>
                        <strong class="code">{{ $code }}</strong>
                    </td>
                    <td style="text-align: right;">
                        Verifikasi keaslian di<br>
                        <strong>{{ $verifyUrl }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
