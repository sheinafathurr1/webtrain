# WebTrain — Platform Belajar Web Development

Platform belajar web development (dasar sampai advance) berbasis Laravel monolith, dengan struktur konten
`Track > Course > Module > Lesson`, code playground interaktif, progress tracking, quiz, dan admin panel.

Proyek ini dikembangkan bertahap. Status saat ini: **Fase 5 — Quiz & Assessment** selesai (Fase 1: auth +
role, Fase 2: struktur course + admin CRUD, Fase 3: halaman student + progress tracking, Fase 4: code
playground, sudah selesai sebelumnya).

## Tech Stack

- **Backend**: Laravel (stable terbaru, saat ini v13.x) + PHP 8.3+
- **Database**: MySQL
- **Frontend**: **Livewire 3 + Volt + Tailwind CSS v3** (lihat alasan pemilihan stack di bawah)
- **Auth**: Laravel Breeze (stack `livewire-functional`, dengan dukungan dark mode)
- **Role & Permission**: [spatie/laravel-permission](https://spatie.be/docs/laravel-permission)

### Kenapa Livewire + Volt + Tailwind?

Platform ini didominasi interaksi yang perlu reaktif ke server: progress lesson yang harus tersimpan saat
ditandai selesai, quiz dengan auto-grading, dashboard admin dengan banyak form CRUD (Track/Course/Module/
Lesson/Quiz), dan nantinya gamification (XP, badge, streak) yang semuanya bergantung pada state di database.

Livewire + Volt dipilih dibanding Blade + Alpine murni karena:

- **Tidak perlu membangun API terpisah.** Blade + Alpine akan butuh endpoint JSON begitu ada interaksi yang
  menyentuh data (submit quiz, tandai lesson selesai, CRUD admin) — Livewire menghilangkan lapisan itu karena
  komponennya reaktif langsung ke server.
- **Volt** memberi sintaks single-file component yang ringkas, cocok untuk banyak komponen kecil yang perlu
  dibuat cepat (form quiz, progress bar, tabel CRUD admin, kartu course).
- **Alpine.js tetap ada** — ia dibundel bersama Livewire, jadi micro-interaction murni client-side (dropdown,
  modal, toggle dark mode, kontrol UI code playground) tetap memakai Alpine seperti biasa.
- Trade-off: sedikit overhead per-request dibanding Blade statis. Untuk skala platform belajar pribadi/kecil
  ini bukan masalah, dan kecepatan pengembangan jauh lebih penting mengingat besarnya cakupan fitur.
- **Pengecualian**: Code Playground (live preview HTML/CSS/JS) berjalan murni di browser lewat iframe
  sandboxed + Alpine.js — bagian ini memang tidak boleh bolak-balik ke server agar preview terasa instan,
  jadi tidak memakai Livewire sama sekali. Elemennya ditandai `wire:ignore` supaya re-render Livewire di
  komponen sekitarnya (mis. klik "Tandai Selesai") tidak menghapus editor/preview yang sedang dikerjakan
  siswa.

## Struktur Role

Menggunakan `spatie/laravel-permission` dengan role berbasis nama (guard `web`):

- **Admin** — akses penuh ke admin panel (`/admin`).
- **Student** — role default saat user register sendiri.
- **Instructor** — sudah disiapkan di `RoleSeeder` walau belum dipakai fitur apa pun, supaya penambahan role
  ini nanti tidak perlu migration baru.

Gate `access-admin-panel` (didefinisikan di `AppServiceProvider`) mengontrol siapa yang boleh melihat link
"Admin Panel" di navigasi dan mengakses route `/admin/*` (middleware `role:Admin`).

## Struktur Konten & Admin CMS

Hierarki konten mengikuti `Track > Course > Module > Lesson`, masing-masing punya `slug` (auto-generate dari
judul lewat trait `App\Models\Concerns\HasSlug`), `order`, dan (kecuali Module) flag `is_published`.

- **Track** — jalur belajar (mis. "Frontend Fundamentals").
- **Course** — punya flag tambahan `lock_lessons_sequentially` (toggle admin: siswa wajib selesaikan lesson
  berurutan atau bebas). Ditegakkan di halaman lesson: lesson berikutnya terkunci (403) sampai lesson
  sebelumnya (dalam urutan module → lesson) ditandai selesai.
- **Module** — bab di dalam course.
- **Lesson** — punya `type`: `text` (markdown di kolom `content`), `video` (URL YouTube di `video_url`),
  `exercise` (relasi one-to-one ke `lesson_exercises`), atau `quiz` (relasi one-to-one ke `quizzes`).

Admin CRUD-nya di `/admin/tracks` → `/admin/tracks/{track}/courses` → `/admin/courses/{course}/modules` →
`/admin/modules/{module}/lessons`, masing-masing halaman Volt dengan list + modal form create/edit + delete
(cascade: hapus track akan menghapus course/module/lesson di dalamnya). Lesson tipe `quiz` punya halaman
builder terpisah, `/admin/lessons/{lesson}/quiz` — lihat bagian Quiz & Assessment di bawah.

### Course contoh yang otomatis ter-seed

`CourseContentSeeder` membuat course **"Belajar Web Dev dari Nol"** di bawah track "Frontend Fundamentals",
lengkap dengan 3 module (HTML Dasar, CSS Dasar, JavaScript Dasar). Module HTML Dasar punya 4 lesson (2 teks +
1 latihan coding + 1 quiz), module CSS & JavaScript masing-masing 3 lesson (2 teks + 1 latihan coding) — total
10 lesson siap pakai begitu `migrate --seed` dijalankan. Seeder ini idempotent (aman dijalankan ulang, tidak
menduplikasi data).

## Halaman Student & Progress Tracking

- **`/courses`** — daftar course yang dipublikasikan, dikelompokkan per track. Bisa diakses tanpa login.
- **`/courses/{course}`** — detail course: deskripsi, progress bar (untuk user login), daftar module & lesson
  dengan status (✓ selesai / 🔒 terkunci / ○ belum), tombol "Mulai/Lanjutkan Belajar" ke lesson berikutnya
  yang belum selesai. Bisa diakses tanpa login (CTA mengarah ke halaman login).
- **`/courses/{course}/lessons/{lesson}`** — halaman lesson (butuh login + email terverifikasi). Render
  konten sesuai tipe (markdown untuk `text` via `Str::markdown()`, embed YouTube untuk `video`, instruksi +
  starter/solution code untuk `exercise`), tombol "Tandai Selesai" (toggle, bisa dibatalkan), navigasi
  sebelumnya/selanjutnya, dan mengembalikan 403 kalau lesson masih terkunci oleh `lock_lessons_sequentially`.
- **`/dashboard`** — course yang sedang diambil (punya progress) dengan progress bar & tombol "Lanjut
  Belajar", plus riwayat lesson yang sudah diselesaikan.

Progress disimpan di tabel `user_progress` (`user_id`, `lesson_id`, unik per pasangan). Model `Course` punya
helper `publishedLessons()`, `progressPercentFor()`, `nextLessonFor()`, dan `isLessonLockedFor()` yang dipakai
di semua halaman ini agar logikanya konsisten di satu tempat.

## Code Playground

Lesson bertipe `exercise` menampilkan editor kode interaktif ([CodeMirror 6](https://codemirror.net/)) di
samping preview langsung (iframe `sandbox="allow-scripts"`, tanpa `allow-same-origin` — origin iframe jadi
opaque sehingga kode yang ditulis siswa tidak bisa mengakses cookie/localStorage/DOM halaman utama).

- Perubahan di editor otomatis mengisi ulang preview (debounce 400ms).
- **Reset** mengembalikan editor ke starter code; **Muat Solusi** mengisi editor dengan solution code.
- Karena schema `lesson_exercises` hanya punya satu kolom `starter_code`, satu exercise = satu dokumen HTML
  penuh (boleh berisi `<style>`/`<script>` di dalamnya) — bukan panel HTML/CSS/JS terpisah. Ini konsisten
  dengan konten yang sudah di-seed.
- CodeMirror **tidak** dibundel di entry JS global — komponen Alpine (`resources/js/app.js`, terdaftar lewat
  `alpine:init`) me-*lazy-load*-nya lewat dynamic `import()`, jadi halaman lain (login, dashboard, admin, dst.)
  tidak ikut memuat ~190KB (gzip) library ini.
- Untuk materi PHP/Laravel (bukan HTML/CSS/JS), eksekusi live di browser tidak memungkinkan — sesuai catatan
  awal, itu tetap jadi code viewer read-along; sandbox eksekusi PHP server-side adalah fase opsional terpisah,
  di luar 6 fase utama ini.

## Quiz & Assessment

Skema: `quizzes` (1:1 dengan lesson tipe `quiz`) → `questions` (`multiple_choice` atau `short_answer`, dengan
`explanation`/pembahasan opsional) → `question_options` (untuk `multiple_choice`, satu `is_correct`). Setiap
percobaan siswa tersimpan sebagai `quiz_attempts` + `quiz_answers` — histori tidak dihapus saat mengulang,
jadi setiap attempt tercatat.

- **Admin** (`/admin/lessons/{lesson}/quiz`): edit judul/deskripsi quiz, tambah/edit/hapus soal. Untuk
  `multiple_choice`, opsi jawaban dikelola sebagai baris dinamis dengan radio "jawaban benar" (harus pilih
  tepat satu); untuk `short_answer`, satu field jawaban benar.
- **Auto-grading**: `multiple_choice` dicocokkan ke opsi yang `is_correct`; `short_answer` dicocokkan exact
  match tanpa membedakan huruf besar/kecil (`Question::isAnswerCorrect()`). Skor = persentase soal benar.
- **Siswa**: jawab semua soal (validasi menolak submit kalau ada yang kosong) → submit → langsung lihat skor,
  jawaban benar/salah per soal, dan pembahasan. Kunjungan ulang ke lesson menampilkan hasil attempt terakhir
  (bukan form kosong lagi); tombol **Ulangi Quiz** mulai attempt baru tanpa menghapus riwayat sebelumnya.
- Progress lesson (`user_progress`) untuk quiz tetap pakai tombol "Tandai Selesai" yang sama seperti tipe
  lesson lain — submit quiz tidak otomatis menandai lesson selesai, supaya perilakunya konsisten di semua tipe.

## Instalasi

### Kebutuhan

- PHP 8.3+
- Composer
- Node.js 18+ & npm
- MySQL 8+ (buat database kosong terlebih dahulu, mis. `webtrain`)

### Langkah

```bash
git clone <repo-url> webtrain
cd webtrain

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan koneksi database (default sudah mengarah ke MySQL):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webtrain
DB_USERNAME=root
DB_PASSWORD=
```

Lalu jalankan migrasi + seeder:

```bash
php artisan migrate --seed
npm run build   # atau `npm run dev` saat development
php artisan serve
```

Buka `http://127.0.0.1:8000`.

### Akun default (dari seeder)

| Role    | Email                  | Password   |
|---------|-------------------------|-----------|
| Admin   | admin@webtrain.test      | password  |
| Student | student@webtrain.test    | password  |

> Ganti password ini sebelum deploy ke production.

### Menjalankan test

```bash
php artisan test
```

## Struktur Folder Penting

```
app/
  Livewire/          Komponen Livewire class-based (Actions/Logout, dst.)
  Models/            Eloquent models (Track, Course, Module, Lesson, LessonExercise, UserProgress,
                     Quiz, Question, QuestionOption, QuizAttempt, QuizAnswer, User)
  Models/Concerns/   Trait HasSlug (auto slug generation)
  Providers/          Service providers (Gate access-admin-panel didefinisikan di AppServiceProvider)
routes/
  web.php            Route dasar (home, dashboard, profile) + require admin.php & courses.php
  auth.php           Route autentikasi (Breeze)
  admin.php          Route admin panel (prefix /admin, middleware role:Admin) — dashboard + CRUD konten
  courses.php        Route student: /courses, /courses/{course}, /courses/{course}/lessons/{lesson}
resources/views/
  livewire/pages/admin/    Halaman full-page Volt CRUD: tracks, courses, modules, lessons, quizzes/builder
  livewire/pages/courses/  Halaman course listing & detail (student)
  livewire/pages/lessons/  Halaman lesson viewer (student, termasuk UI pengerjaan quiz)
  livewire/pages/auth/     Halaman full-page Volt auth (Breeze)
  livewire/pages/dashboard.blade.php  Dashboard student (progress + riwayat)
  livewire/layout/         Komponen navigasi (guest-aware)
  layouts/                 Layout Blade (app, guest)
resources/js/
  app.js              Komponen Alpine `codePlayground` (Code Playground, lazy-load CodeMirror)
database/
  seeders/            RoleSeeder, AdminUserSeeder, CourseContentSeeder, DatabaseSeeder
```

## Roadmap Fase Berikutnya

- ~~**Fase 1** — Fondasi: auth + role.~~ ✅
- ~~**Fase 2** — Migration & model Track/Course/Module/Lesson, admin CRUD, seed course contoh.~~ ✅
- ~~**Fase 3** — Halaman student, dashboard, progress tracking, penegakan `lock_lessons_sequentially`.~~ ✅
- ~~**Fase 4** — Code playground (CodeMirror + live preview) untuk lesson tipe `exercise`.~~ ✅
- ~~**Fase 5** — Quiz & assessment dengan auto-grading (tipe lesson `quiz`).~~ ✅
- **Fase 6** — Gamification (XP, badge, streak), sertifikat PDF, toggle dark mode manual, polish.
