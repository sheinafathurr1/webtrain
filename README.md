# WebTrain — Platform Belajar Web Development

Platform belajar web development (dasar sampai advance) berbasis Laravel monolith, dengan struktur konten
`Track > Course > Module > Lesson`, code playground interaktif, progress tracking, quiz, dan admin panel.

Proyek ini dikembangkan bertahap dan **keenam fase sudah selesai**: Fase 1 (auth + role), Fase 2 (struktur
course + admin CRUD), Fase 3 (halaman student + progress tracking), Fase 4 (code playground), Fase 5 (quiz &
assessment), Fase 6 (gamification, sertifikat, dark mode, polish).

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

Tiap tabel juga bisa diurutkan lewat **drag-and-drop** (ikon grip di kolom paling kiri) — pakai native HTML5
drag events lewat Alpine (`draggable`, `@dragstart`/`@dragover.prevent`/`@drop`), tanpa library tambahan. Saat
drop, Livewire memanggil action `reorder($draggedId, $targetId)` yang mengambil urutan id saat ini (scoped ke
parent-nya masing-masing — semua track untuk halaman Track, course dalam 1 track untuk halaman Course, dst),
menyisipkan id yang di-drag tepat sebelum id target, lalu menulis ulang kolom `order` 0..n secara berurutan;
`reorder` juga memverifikasi kedua id benar-benar berada dalam scope yang sama sebelum memprosesnya, supaya
drag-drop tidak bisa dipakai untuk mengubah `order` baris di luar parent yang sedang dibuka. Drag-and-drop
hanya aktif selama daftar muat dalam 1 halaman (`! $items->hasPages()`) — kalau sudah lebih dari 10 item dan
terpaginasi, ikon grip jadi non-aktif (redup) dan urutan tetap bisa diatur manual lewat field "Urutan" di
modal edit seperti sebelumnya.

### Course contoh yang otomatis ter-seed

`CourseContentSeeder` membuat course **"Belajar Web Dev dari Nol"** di bawah track "Frontend Fundamentals",
lengkap dengan 3 module (HTML Dasar, CSS Dasar, JavaScript Dasar). Module HTML Dasar punya 4 lesson (2 teks +
1 latihan coding + 1 quiz), module CSS & JavaScript masing-masing 3 lesson (2 teks + 1 latihan coding) — total
10 lesson siap pakai begitu `migrate --seed` dijalankan. Seeder ini idempotent (aman dijalankan ulang, tidak
menduplikasi data).

## Halaman Student & Progress Tracking

- **`/courses`** — daftar course yang dipublikasikan, dikelompokkan per track. Bisa diakses tanpa login.
  Ada kotak pencarian (judul/deskripsi course, live search dengan debounce 300ms) dan dropdown filter track;
  keduanya bisa dikombinasikan dan direset lewat tombol "Reset filter" yang hanya muncul saat ada filter aktif.
- **`/courses/{course}`** — detail course: deskripsi, progress bar (untuk user login), daftar module & lesson
  dengan status (✓ selesai / 🔒 terkunci / ○ belum), tombol "Mulai/Lanjutkan Belajar" ke lesson berikutnya
  yang belum selesai. Bisa diakses tanpa login (CTA mengarah ke halaman login).
- **`/courses/{course}/lessons/{lesson}`** — halaman lesson (butuh login + email terverifikasi). Render
  konten sesuai tipe (markdown untuk `text` via `Str::markdown()`, embed YouTube untuk `video`, instruksi +
  starter/solution code untuk `exercise`), tombol "Tandai Selesai" (toggle, bisa dibatalkan), navigasi
  sebelumnya/selanjutnya, dan mengembalikan 403 kalau lesson masih terkunci oleh `lock_lessons_sequentially`.
- **`/dashboard`** — course yang sedang diambil (punya progress) dengan progress bar & tombol "Lanjut
  Belajar", plus riwayat lesson yang sudah diselesaikan.

### Diskusi lesson (komentar)

Tiap halaman lesson punya bagian "Diskusi" di paling bawah (tabel `lesson_comments`, `belongsTo` lesson &
user). Semua user login bisa menulis komentar (textarea + tombol "Kirim Komentar", validasi `required`).
Komentar bisa dihapus oleh pemiliknya sendiri atau Admin — lewat `<x-confirm-delete-modal>` yang sama dengan
CRUD admin (bukan `confirm()` bawaan browser), jadi konsisten dengan pola konfirmasi hapus di seluruh
aplikasi. Daftar komentar diurutkan terbaru dulu dan otomatis refresh setelah kirim/hapus karena state-nya
computed property Livewire, bukan query yang di-cache di `mount()`.

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

### Auto-grading

Admin bisa opsional menambahkan **Auto-grading Checks** saat membuat/mengedit lesson tipe `exercise`
(kolom `checks` JSON di `lesson_exercises`). Ada tiga tipe check:

- **Teks elemen** — elemen hasil `document.querySelector(selector)` harus mengandung teks tertentu.
- **Gaya CSS** — `getComputedStyle(el)[property]` elemen harus sama dengan nilai yang diharapkan (nilai
  dinormalisasi lewat elemen probe tersembunyi, jadi admin boleh menulis `blue`, `#f0f0f0`, atau `rgb(...)`).
- **Alert saat diklik** — mensimulasikan klik pada elemen dan mengecek `alert()` yang terpanggil.

Karena preview iframe sengaja **tanpa** `allow-same-origin` (lihat di atas), parent page tidak bisa membaca
`iframe.contentDocument` secara langsung. Saat siswa klik **Cek Jawaban**, kode mereka dikirim ulang ke
iframe dengan sisipan skrip grading kecil di baris terakhir (yang meng-*override* `window.alert`, menjalankan
semua check, lalu mengirim hasilnya balik ke parent lewat `window.parent.postMessage`) — bukan lewat
`allow-same-origin`, yang justru akan membuka celah XSS karena `srcdoc` mewarisi origin halaman utama begitu
flag itu diaktifkan. Saat semua check lolos, lesson otomatis ditandai selesai (`submitExercise()`, idempotent
lewat `firstOrCreate` — retry tidak dobel memberi poin), mengikuti pola yang sama dengan auto-complete quiz.
Exercise tanpa `checks` tetap pakai tombol "Tandai Selesai" manual seperti sebelumnya.

### Leaderboard

Halaman `/leaderboard` (route `leaderboard`, auth+verified) menampilkan ranking siswa (role `Student`)
berdasarkan `total_points`, diurutkan `orderByDesc('total_points')` dengan tie-break `orderBy('id')`, dibatasi
50 siswa teratas. Tiga peringkat teratas tampil sebagai podium (medali + avatar), sisanya sebagai daftar
ranking biasa. Baris/kartu milik user yang sedang login diberi highlight (ring/background); jika user adalah
Student tapi berada di luar top 50, kartu "posisi kamu" terpisah ditampilkan di bawah daftar dengan ranking
dihitung lewat `count()` siswa yang total_points-nya lebih tinggi.

### Admin Analytics

Halaman `/admin/analytics` (role Admin) menampilkan ringkasan aktivitas platform: total siswa, siswa aktif
7 hari terakhir (punya `user_progress` baru), total lesson selesai, dan rata-rata skor quiz — semuanya
dihitung dari `user_progress` sebagai satu sumber kebenaran aktivitas (semua tipe lesson, termasuk exercise
dan quiz, mencatat penyelesaian ke tabel yang sama). Di bawahnya ada tabel engagement per course (jumlah
lesson, siswa yang terlibat, completion rate — dihitung dari total penyelesaian dibagi siswa×lesson), tabel
performa quiz (jumlah percobaan + rata-rata skor per quiz), dan feed 10 aktivitas penyelesaian lesson
terbaru.

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

## Gamification, Sertifikat & Dark Mode

### XP, streak, dan badge

- **XP**: menyelesaikan lesson apa pun (toggle "Tandai Selesai") memberi `GamificationService::POINTS_PER_LESSON`
  (10) poin sekali per lesson — mengulang toggle on/off tidak melipatgandakan poin. Riwayatnya tercatat di
  `point_transactions`; totalnya didenormalisasi ke `users.total_points` untuk tampilan cepat.
- **Streak harian**: `users.current_streak`/`longest_streak`/`last_activity_date` diperbarui tiap kali
  menyelesaikan lesson — hari berturutan menambah streak, ada jeda mereset ke 1, hari yang sama tidak
  dihitung dua kali.
- **Badge**: dicek otomatis setiap lesson selesai atau quiz disubmit (`GamificationService::checkBadges()`),
  berdasar `criteria_type` (`lessons_completed`, `course_completed`, `quiz_perfect_score`, `streak_days`).
  6 badge sudah di-seed lewat `BadgeSeeder`: Langkah Pertama, Rajin Belajar, Penakluk Course, Jagoan Quiz,
  Streak 3 Hari, Streak 7 Hari.
- **Desain yang disengaja**: poin/badge/streak adalah *one-way ratchet* — membatalkan progress lesson
  ("Tandai Selesai" → klik lagi untuk batalkan) tidak menarik kembali poin atau mencabut badge yang sudah
  didapat. Ini menghindari logika pembalikan yang rumit (rekalkulasi streak dari histori, dsb.) dan konsisten
  dengan pola umum di LMS/aplikasi gamifikasi lain.
- Semuanya tampil di `/dashboard`: total XP, streak saat ini & terpanjang, grid badge (yang belum didapat
  ditampilkan pudar/grayscale, bukan disembunyikan — supaya siswa tahu apa yang bisa dikejar).

### Streak reminder

Dua bagian yang saling melengkapi:

- **Banner in-app** di `/dashboard` — muncul kalau `current_streak > 0` tapi `last_activity_date` masih
  kemarin (siswa belum menyelesaikan apa pun hari ini), dengan CTA "Lanjut Belajar" ke lesson berikutnya
  di course yang belum selesai (atau "Jelajahi Course" kalau belum punya course yang sedang diambil).
- **Email harian** — command `streak:remind` (dijadwalkan tiap hari jam 18:00 lewat `Schedule::command()`
  di `routes/console.php`) mencari siswa dengan streak aktif yang belum belajar hari ini, lalu mengirim
  `StreakReminderNotification` (mail). Kolom `users.last_streak_reminder_sent_at` mencegah dobel kirim kalau
  command dijalankan berkali-kali di hari yang sama. Jalankan manual dengan `php artisan streak:remind`; di
  lokal (`MAIL_MAILER=log`) isi emailnya bisa dicek di `storage/logs/laravel.log`. Scheduler Laravel perlu
  cron `* * * * * php artisan schedule:run` (atau `php artisan schedule:work` saat development) supaya jadwal
  ini benar-benar jalan otomatis di production.

### Sertifikat PDF

`/courses/{course}/certificate` (route biasa lewat `CertificateController`, bukan Livewire — cocok untuk aksi
download sekali-jalan) men-generate PDF (via `barryvdh/laravel-dompdf`) berisi nama siswa, judul course, dan
tanggal lesson terakhir diselesaikan. Diblokir (403) sampai `progressPercentFor()` course tersebut mencapai
100%. Tombol **"Download Sertifikat"** otomatis muncul di halaman course begitu progress 100%. Desain PDF-nya
mengikuti palet warna brand aplikasi (teal/oranye/gold), dengan seal berupa lingkaran bertanda centang.

Setiap course yang diselesaikan menerbitkan satu baris permanen di tabel `certificates` (unique per
`user_id`+`course_id`, dibuat lewat `firstOrCreate` saat pertama kali di-download — download berikutnya
mengembalikan kode & tanggal terbit yang sama, tidak membuat baris baru). Kode sertifikatnya (format
`WT-XXXX-XXXX`, `Certificate::generateCode()`) dicetak di PDF beserta URL verifikasi publik
`/sertifikat/verifikasi/{code}` — halaman ini bisa diakses siapa saja tanpa login, untuk memverifikasi bahwa
sertifikat itu memang diterbitkan oleh WebTrain (menampilkan nama siswa, judul course, dan tanggal terbit),
atau menunjukkan "Kode Sertifikat Tidak Ditemukan" untuk kode yang salah/palsu.

### Dark Mode manual

Sebelumnya dark mode hanya ikut preferensi OS (`prefers-color-scheme`). Sekarang Tailwind pakai strategi
`darkMode: 'class'`, dengan:
- Script inline kecil di `<head>` (`layouts/partials/theme-init.blade.php`, di-include di kedua layout)
  yang membaca `localStorage` (fallback ke preferensi OS) dan langsung set class `dark` pada `<html>`
  **sebelum** CSS Tailwind dirender — mencegah flash of unstyled/wrong theme (FOUC).
  - Tombol toggle (ikon matahari/bulan) di navigasi, murni Alpine (`x-data`/`x-init`/`$watch`), menyimpan
  pilihan ke `localStorage` supaya konsisten di reload maupun navigasi antar halaman.

## Design System

UI memakai identitas colorful & playful yang terikat ke subjeknya sendiri: warna diambil dari tiga elemen
inti web development (teal untuk brand/pertumbuhan, oranye untuk aksi/CTA, emas untuk XP & reward — selaras
dengan sistem gamifikasi yang sudah ada), bukan palet AI generik. Semua token hidup di `resources/css/app.css`
(CSS variables RGB-triple untuk light/dark, di-swap lewat class `.dark`) dan `tailwind.config.js` (memetakan
token itu ke warna semantik Tailwind: `canvas`, `surface`, `border`, `ink-muted`/`ink-secondary`/`ink-primary`,
`brand`, `accent`, `gold`, `danger`).

- **Tipografi**: tiga peran font (Baloo 2 yang bulat & playful untuk display/heading, Plus Jakarta Sans untuk
  body, Fira Code untuk code block sungguhan).
- **Elemen khas**: kartu rounded-2xl dengan hover-lift, badge pill berwarna (`<x-badge>`) untuk status,
  progress bar gradien teal→oranye (`<x-progress-bar>`) untuk progres course, tombol pill dengan micro-interaction
  (hover lift, active press) yang menghormati `prefers-reduced-motion` lewat varian `motion-safe:`.
- **Komponen**: tombol primary (pill solid oranye), secondary (pill outline teal), danger (pill solid merah);
  card border tipis rounded-2xl dengan shadow saat hover; focus ring 2px solid + offset 2px di semua elemen
  interaktif untuk aksesibilitas.
- **Layout khusus**: lesson viewer memakai layout dua panel (sidebar daftar lesson + konten utama, lesson aktif
  ditandai gradien teal→oranye); dashboard menampilkan stat card bergradien lembut (XP emas, streak oranye,
  badge teal) dan grid badge gamifikasi (warna penuh saat didapat, grayscale saat terkunci).

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
  Http/Controllers/  CertificateController (download PDF sertifikat, single-action, bukan Livewire)
  Livewire/          Komponen Livewire class-based (Actions/Logout, dst.)
  Models/            Eloquent models (Track, Course, Module, Lesson, LessonExercise, LessonComment,
                     UserProgress, Quiz, Question, QuestionOption, QuizAttempt, QuizAnswer,
                     PointTransaction, Badge, UserBadge, Certificate, User)
  Models/Concerns/   Trait HasSlug (auto slug generation)
  Providers/          Service providers (Gate access-admin-panel didefinisikan di AppServiceProvider)
  Services/           GamificationService (poin, streak, badge — logic terpusat di satu tempat)
routes/
  web.php            Route dasar (home, dashboard, profile) + require admin.php & courses.php
  auth.php           Route autentikasi (Breeze)
  admin.php          Route admin panel (prefix /admin, middleware role:Admin) — dashboard + CRUD konten
  courses.php        Route student: /courses, /courses/{course}, /courses/{course}/lessons/{lesson},
                     /courses/{course}/certificate
resources/views/
  livewire/pages/admin/    Halaman full-page Volt CRUD: tracks, courses, modules, lessons, quizzes/builder
  livewire/pages/courses/  Halaman course listing & detail (student)
  livewire/pages/lessons/  Halaman lesson viewer (student, termasuk UI pengerjaan quiz)
  livewire/pages/auth/     Halaman full-page Volt auth (Breeze)
  livewire/pages/dashboard.blade.php  Dashboard student (progress, XP, streak, badge, riwayat)
  livewire/layout/         Komponen navigasi (guest-aware, tombol toggle dark mode)
  layouts/                 Layout Blade (app, guest) + partials/theme-init.blade.php (anti-FOUC)
  certificates/            Template PDF sertifikat (di-render via dompdf)
resources/js/
  app.js              Komponen Alpine `codePlayground` (Code Playground, lazy-load CodeMirror)
database/
  seeders/            RoleSeeder, AdminUserSeeder, CourseContentSeeder, BadgeSeeder, DatabaseSeeder
```

## Roadmap

Enam fase yang direncanakan semuanya sudah selesai:

- ~~**Fase 1** — Fondasi: auth + role.~~ ✅
- ~~**Fase 2** — Migration & model Track/Course/Module/Lesson, admin CRUD, seed course contoh.~~ ✅
- ~~**Fase 3** — Halaman student, dashboard, progress tracking, penegakan `lock_lessons_sequentially`.~~ ✅
- ~~**Fase 4** — Code playground (CodeMirror + live preview) untuk lesson tipe `exercise`.~~ ✅
- ~~**Fase 5** — Quiz & assessment dengan auto-grading (tipe lesson `quiz`).~~ ✅
- ~~**Fase 6** — Gamification (XP, badge, streak), sertifikat PDF, toggle dark mode manual, polish.~~ ✅

### Follow-up opsional (belum dikerjakan)

Dua item dari daftar "Fitur Pendukung Lain" di spesifikasi awal sengaja belum dibangun supaya fase-fase inti
di atas bisa selesai dengan kualitas terjaga, bukan tergesa dijejalkan:

- **Search course** — kotak pencarian di halaman `/courses` untuk filter berdasarkan judul/deskripsi.
- **Bookmark lesson** — tabel `bookmarks` (user_id, lesson_id) + tombol simpan di halaman lesson dan daftar
  "Lesson Tersimpan" di dashboard.
- Eksekusi PHP/Laravel live di browser (disebut di catatan teknis awal) tetap di luar cakupan — butuh sandbox
  eksekusi server-side terpisah, bukan sekadar tambahan frontend.
