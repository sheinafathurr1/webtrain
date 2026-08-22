<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Track;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseContentSeeder extends Seeder
{
    public function run(): void
    {
        $track = Track::updateOrCreate(
            ['slug' => 'frontend-fundamentals'],
            [
                'title' => 'Frontend Fundamentals',
                'description' => 'Jalur belajar dasar-dasar pengembangan web sisi client: HTML, CSS, dan JavaScript.',
                'order' => 1,
                'is_published' => true,
            ]
        );

        $course = Course::updateOrCreate(
            ['slug' => 'belajar-web-dev-dari-nol'],
            [
                'track_id' => $track->id,
                'title' => 'Belajar Web Dev dari Nol',
                'description' => 'Course pengantar untuk siapa pun yang baru mulai belajar web development: HTML dasar, CSS dasar, sampai JavaScript dasar.',
                'order' => 1,
                'is_published' => true,
                'lock_lessons_sequentially' => true,
            ]
        );

        $this->seedHtmlModule($course);
        $this->seedCssModule($course);
        $this->seedJsModule($course);
    }

    private function seedHtmlModule(Course $course): void
    {
        $module = Module::updateOrCreate(
            ['slug' => 'modul-1-html-dasar'],
            [
                'course_id' => $course->id,
                'title' => 'Modul 1: HTML Dasar',
                'description' => 'Mengenal HTML sebagai fondasi setiap halaman web.',
                'order' => 1,
            ]
        );

        $this->lesson($module, 1, 'Apa itu HTML?', Lesson::TYPE_TEXT, content: <<<'MD'
# Apa itu HTML?

**HTML** (HyperText Markup Language) adalah bahasa markup yang digunakan untuk menyusun struktur
sebuah halaman web. Setiap halaman yang kamu buka di browser — termasuk halaman ini — dibangun dari
elemen-elemen HTML.

HTML terdiri dari **tag**, biasanya berpasangan (pembuka dan penutup):

```html
<p>Ini adalah sebuah paragraf.</p>
```

- `<p>` adalah tag pembuka
- `</p>` adalah tag penutup
- "Ini adalah sebuah paragraf." adalah konten di dalamnya

## Kenapa HTML penting?

HTML memberi **makna** dan **struktur** pada konten — mana yang judul, mana yang paragraf, mana yang
gambar, mana yang tautan. Tanpa struktur ini, browser tidak tahu cara menampilkan halaman dengan benar.

Di lesson berikutnya kita akan lihat struktur dasar sebuah dokumen HTML lengkap.
MD);

        $this->lesson($module, 2, 'Struktur Dasar Dokumen HTML', Lesson::TYPE_TEXT, content: <<<'MD'
# Struktur Dasar Dokumen HTML

Setiap dokumen HTML punya kerangka standar seperti ini:

```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Judul Halaman</title>
  </head>
  <body>
    <h1>Selamat Datang</h1>
    <p>Ini adalah isi halaman.</p>
  </body>
</html>
```

## Penjelasan tiap bagian

- `<!DOCTYPE html>` — memberi tahu browser bahwa ini dokumen HTML5.
- `<html>` — elemen pembungkus seluruh halaman.
- `<head>` — berisi informasi tentang halaman (judul, metadata) yang **tidak** tampil langsung di halaman.
- `<body>` — berisi semua konten yang **tampil** di browser: teks, gambar, tombol, dll.

## Elemen umum di dalam `<body>`

| Tag | Fungsi |
|---|---|
| `<h1>` s/d `<h6>` | Judul/heading, `<h1>` paling penting |
| `<p>` | Paragraf |
| `<a href="...">` | Tautan/link |
| `<img src="...">` | Gambar |
| `<ul>` / `<li>` | Daftar tidak berurutan |

Selanjutnya, coba langsung praktik di lesson latihan berikut.
MD);

        $this->exercise(
            $module,
            3,
            'Latihan: Membuat Heading & Paragraf',
            instructions: 'Buat sebuah elemen `<h1>` berisi teks "Halo, Dunia!" dan sebuah elemen `<p>` di bawahnya berisi teks "Ini halaman pertama saya.".',
            starterCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan HTML</title>
  </head>
  <body>
    <!-- Tulis kode kamu di bawah ini -->

  </body>
</html>
HTML,
            solutionCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan HTML</title>
  </head>
  <body>
    <h1>Halo, Dunia!</h1>
    <p>Ini halaman pertama saya.</p>
  </body>
</html>
HTML,
            expectedOutput: 'Halaman menampilkan judul besar "Halo, Dunia!" diikuti paragraf "Ini halaman pertama saya." di bawahnya.',
        );

        $quizLesson = $this->lesson($module, 4, 'Quiz: HTML Dasar', Lesson::TYPE_QUIZ);

        $quiz = Quiz::updateOrCreate(
            ['lesson_id' => $quizLesson->id],
            [
                'title' => 'Quiz: HTML Dasar',
                'description' => 'Uji pemahamanmu tentang dasar-dasar HTML dari module ini.',
            ]
        );

        $q1 = Question::updateOrCreate(
            ['quiz_id' => $quiz->id, 'order' => 1],
            [
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Apa kepanjangan dari HTML?',
                'explanation' => 'HTML adalah singkatan dari HyperText Markup Language, bahasa markup untuk menyusun struktur halaman web.',
            ]
        );
        $q1->options()->delete();
        $q1->options()->createMany([
            ['option_text' => 'HyperText Markup Language', 'is_correct' => true, 'order' => 1],
            ['option_text' => 'High Tech Modern Language', 'is_correct' => false, 'order' => 2],
            ['option_text' => 'Home Tool Markup Language', 'is_correct' => false, 'order' => 3],
        ]);

        $q2 = Question::updateOrCreate(
            ['quiz_id' => $quiz->id, 'order' => 2],
            [
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Tag HTML mana yang digunakan untuk membuat tautan/link?',
                'explanation' => 'Tag <a> (anchor) dengan atribut href digunakan untuk membuat tautan ke halaman lain.',
            ]
        );
        $q2->options()->delete();
        $q2->options()->createMany([
            ['option_text' => '<link>', 'is_correct' => false, 'order' => 1],
            ['option_text' => '<a>', 'is_correct' => true, 'order' => 2],
            ['option_text' => '<href>', 'is_correct' => false, 'order' => 3],
        ]);

        Question::updateOrCreate(
            ['quiz_id' => $quiz->id, 'order' => 3],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question_text' => 'Tag HTML apa yang digunakan untuk judul level 1 (paling penting)? Tulis tanpa tanda kurung siku, mis. "p".',
                'correct_answer' => 'h1',
                'explanation' => 'Tag <h1> adalah heading level 1, biasanya judul utama sebuah halaman.',
            ]
        );
    }

    private function seedCssModule(Course $course): void
    {
        $module = Module::updateOrCreate(
            ['slug' => 'modul-2-css-dasar'],
            [
                'course_id' => $course->id,
                'title' => 'Modul 2: CSS Dasar',
                'description' => 'Belajar mengatur tampilan halaman HTML dengan CSS.',
                'order' => 2,
            ]
        );

        $this->lesson($module, 1, 'Apa itu CSS?', Lesson::TYPE_TEXT, content: <<<'MD'
# Apa itu CSS?

**CSS** (Cascading Style Sheets) adalah bahasa yang digunakan untuk mengatur **tampilan** elemen HTML:
warna, ukuran, jarak, posisi, font, dan lainnya.

Kalau HTML adalah kerangka rumah, CSS adalah cat, furnitur, dan dekorasinya.

## Cara menulis CSS

```css
p {
  color: blue;
  font-size: 18px;
}
```

- `p` adalah **selector** — elemen mana yang ingin di-styling.
- `color` dan `font-size` adalah **property**.
- `blue` dan `18px` adalah **value**.

CSS bisa ditulis langsung di dalam tag `<style>` pada `<head>`, di file `.css` terpisah, atau langsung
lewat atribut `style="..."` pada elemen (cara terakhir ini sebaiknya dihindari untuk project besar).
MD);

        $this->lesson($module, 2, 'Selector & Property Dasar CSS', Lesson::TYPE_TEXT, content: <<<'MD'
# Selector & Property Dasar CSS

## Jenis selector yang sering dipakai

```css
/* Selector tag: semua elemen <p> */
p { color: black; }

/* Selector class: elemen dengan class="highlight" */
.highlight { background-color: yellow; }

/* Selector id: elemen dengan id="header" */
#header { font-weight: bold; }
```

## Property umum

| Property | Fungsi |
|---|---|
| `color` | Warna teks |
| `background-color` | Warna latar belakang |
| `font-size` | Ukuran teks |
| `margin` | Jarak di luar elemen |
| `padding` | Jarak di dalam elemen (antara border dan konten) |

Coba langsung praktikkan di lesson latihan berikutnya.
MD);

        $this->exercise(
            $module,
            3,
            'Latihan: Styling Sederhana',
            language: 'mixed',
            instructions: 'Ubah warna teks elemen `<h1>` menjadi biru (`blue`) dan beri warna latar belakang `#f0f0f0` pada elemen `<body>`.',
            starterCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan CSS</title>
    <style>
      /* Tulis CSS kamu di sini */

    </style>
  </head>
  <body>
    <h1>Judul Halaman</h1>
  </body>
</html>
HTML,
            solutionCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan CSS</title>
    <style>
      body {
        background-color: #f0f0f0;
      }
      h1 {
        color: blue;
      }
    </style>
  </head>
  <body>
    <h1>Judul Halaman</h1>
  </body>
</html>
HTML,
            expectedOutput: 'Latar belakang halaman berwarna abu-abu muda (#f0f0f0) dan teks "Judul Halaman" berwarna biru.',
        );
    }

    private function seedJsModule(Course $course): void
    {
        $module = Module::updateOrCreate(
            ['slug' => 'modul-3-javascript-dasar'],
            [
                'course_id' => $course->id,
                'title' => 'Modul 3: JavaScript Dasar',
                'description' => 'Menambahkan interaktivitas ke halaman web dengan JavaScript.',
                'order' => 3,
            ]
        );

        $this->lesson($module, 1, 'Apa itu JavaScript?', Lesson::TYPE_TEXT, content: <<<'MD'
# Apa itu JavaScript?

**JavaScript** adalah bahasa pemrograman yang membuat halaman web menjadi **interaktif** — merespons
klik, mengubah konten secara dinamis, memvalidasi form, dan banyak lagi.

Kalau HTML adalah kerangka dan CSS adalah tampilan, JavaScript adalah **perilaku**/**logika** halaman.

```html
<button onclick="alert('Halo!')">Klik saya</button>
```

Kode di atas menampilkan pesan pop-up "Halo!" ketika tombol diklik — itu JavaScript bekerja langsung
di HTML lewat atribut `onclick`. Cara yang lebih rapi adalah menulis JavaScript di dalam tag `<script>`.
MD);

        $this->lesson($module, 2, 'Variabel & Tipe Data di JavaScript', Lesson::TYPE_TEXT, content: <<<'MD'
# Variabel & Tipe Data di JavaScript

## Mendeklarasikan variabel

```js
let nama = "Budi";       // bisa diubah nilainya
const umur = 25;         // tidak bisa diubah setelah didefinisikan
```

Gunakan `let` untuk nilai yang bisa berubah, dan `const` untuk nilai yang tetap.

## Tipe data dasar

| Tipe | Contoh |
|---|---|
| String | `"Halo dunia"` |
| Number | `42`, `3.14` |
| Boolean | `true`, `false` |
| Array | `[1, 2, 3]` |
| Object | `{ nama: "Budi", umur: 25 }` |

## Menampilkan output

```js
console.log("Halo dari JavaScript!"); // tampil di console browser
document.write("Halo!");              // tampil langsung di halaman
```

Yuk langsung praktik di lesson latihan berikutnya.
MD);

        $this->exercise(
            $module,
            3,
            'Latihan: Menampilkan Pesan dengan JavaScript',
            language: 'mixed',
            instructions: 'Tambahkan kode JavaScript agar saat tombol "Sapa" diklik, muncul alert bertuliskan "Halo, Web Dev!".',
            starterCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan JavaScript</title>
  </head>
  <body>
    <button id="sapa-btn">Sapa</button>

    <script>
      // Tulis kode kamu di sini
    </script>
  </body>
</html>
HTML,
            solutionCode: <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <title>Latihan JavaScript</title>
  </head>
  <body>
    <button id="sapa-btn">Sapa</button>

    <script>
      document.getElementById('sapa-btn').addEventListener('click', function () {
        alert('Halo, Web Dev!');
      });
    </script>
  </body>
</html>
HTML,
            expectedOutput: 'Saat tombol "Sapa" diklik, muncul kotak alert bertuliskan "Halo, Web Dev!".',
        );
    }

    private function lesson(Module $module, int $order, string $title, string $type, ?string $content = null, ?string $videoUrl = null): Lesson
    {
        // Model events (incl. slug auto-generation) are disabled while seeding, so set it explicitly.
        return Lesson::updateOrCreate(
            ['slug' => Str::slug($title)],
            [
                'module_id' => $module->id,
                'title' => $title,
                'type' => $type,
                'order' => $order,
                'is_published' => true,
                'content' => $content,
                'video_url' => $videoUrl,
            ]
        );
    }

    private function exercise(
        Module $module,
        int $order,
        string $title,
        string $instructions,
        string $starterCode,
        string $solutionCode,
        string $expectedOutput,
        string $language = 'html',
    ): Lesson {
        $lesson = $this->lesson($module, $order, $title, Lesson::TYPE_EXERCISE);

        $lesson->exercise()->updateOrCreate([], [
            'language' => $language,
            'instructions' => $instructions,
            'starter_code' => $starterCode,
            'solution_code' => $solutionCode,
            'expected_output' => $expectedOutput,
        ]);

        return $lesson;
    }
}
