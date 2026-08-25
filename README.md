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

Tiap tabel juga bisa diurutkan lewat **drag-and-drop**, tapi di belakang tombol **"Ubah Urutan"** — bukan
selalu-aktif di tabel utama. Klik tombol itu ganti seluruh tabel (filter, tombol "+ Baru", pagination) jadi
panel reorder yang fokus: daftar sederhana (judul + slug, tanpa kolom lain/link aksi) yang seluruhnya
di-render & di-drag lewat Alpine `x-data`/`x-for` — bukan lewat Livewire per-baris — supaya:

- **Drag jarak jauh benar-benar jalan.** Versi awal memanggil Livewire `reorder($draggedId, $targetId)` di
  setiap `drop`, menghitung ulang urutan dari DB tiap kali — rapuh untuk drag yang melompati beberapa baris
  sekaligus. Sekarang, urutan cuma array JS lokal (`items`) yang di-splice on the fly saat `@dragover` lewat
  di atas baris lain (`items.splice(index, 0, items.splice(dragIndex, 1)[0])`), jadi drag dari baris pertama
  ke baris terakhir — atau ke mana pun — langsung kelihatan bergeser real-time, tanpa round-trip server sama
  sekali sampai di-Simpan.
- **Kelihatan jelas kalau lagi di-drag.** Baris yang sedang ditarik dapat highlight warna brand + opacity
  turun + sedikit mengecil (`opacity-50 scale-[0.98] border-brand bg-brand/5 shadow-md`), dan baris lain
  langsung geser posisi begitu dilewati — bukan cuma ikon grip statis seperti sebelumnya.
- **Halaman tetap ringan secara default.** Tabel normal tidak lagi punya kolom grip/atribut `draggable` sama
  sekali — itu semua cuma dirender saat mode reorder aktif. Tombol "Ubah Urutan" sendiri hanya muncul saat
  daftar muat dalam 1 halaman tanpa filter aktif (`! $items->hasPages() && ! $hasActiveFilters`); di luar itu,
  urutan tetap bisa diatur manual lewat field "Urutan" di modal edit seperti sebelumnya.

Klik **"Simpan Urutan"** memanggil `$wire.saveOrder(items.map(i => i.id))` — satu action Livewire per sesi
reorder (bukan satu per drop), yang menulis ulang kolom `order` 0..n sesuai urutan final dari client. `saveOrder`
memvalidasi id dengan `array_intersect` terhadap id yang benar-benar ada di scope parent-nya (semua track untuk
halaman Track, course dalam 1 track untuk halaman Course, dst) sebelum menulis apa pun, supaya payload yang
di-craft tidak bisa dipakai mengubah `order` baris di luar parent yang sedang dibuka. Tombol "Batal" keluar dari
mode reorder tanpa memanggil server sama sekali.

Setiap tabel juga punya **search & filter**: kotak pencarian (judul/slug, `wire:model.live.debounce.300ms`)
dan dropdown status (published/draft) di semua level, plus dropdown tipe lesson (text/video/exercise/quiz) khusus
di halaman Lesson. Query di-scope ke parent yang sama dengan yang dipakai `saveOrder` (mis. course di halaman
Module hanya mencari dalam course itu). Mengubah search/filter otomatis memanggil `resetPage()` lewat hook
`updated<Nama Properti>` Livewire supaya tidak nyangkut di halaman pagination yang sudah kosong, dan tombol
"Reset filter" hanya muncul saat ada filter aktif.

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

### Bookmark lesson

Tombol ★ di pojok kanan atas tiap halaman lesson menyimpan/menghapus bookmark ke tabel `bookmarks`
(`user_id`, `lesson_id`, unik per pasangan). Dashboard punya section "Lesson Tersimpan" (maks 20 terbaru)
berisi link langsung ke tiap lesson yang disimpan beserta judul course-nya, dan tombol "Hapus" per baris.
Action toggle-nya (`toggleBookmark` di halaman lesson, `removeBookmark` di dashboard) memverifikasi
kepemilikan (`where('user_id', Auth::id())`) sebelum menghapus, dan memakai `firstOrCreate` (bukan `create()`
polos) supaya klik ganda/request bersamaan tidak melanggar constraint unique dan menyebabkan exception —
pola yang sama dipakai `toggleComplete` di lesson (lihat bagian Performance & Scale).

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

## Performance & Scale

Audit singkat menutup beberapa N+1 query, celah caching, dan satu race condition nyata:

- **N+1 di sequential-lock check** — `Course::isLessonLockedFor()` dulu memanggil `publishedLessons()` (query
  module+lesson baru) dan `Lesson::isCompletedBy()` (query `user_progress` baru) di setiap pemanggilan. Karena
  method ini dipanggil sekali per lesson di halaman detail course dan di sidebar halaman lesson, course dengan
  10 lesson menghasilkan **35 query** hanya untuk loop pengecekan kunci. Sekarang `publishedLessons()`
  di-memoize per instance `Course` (dan reuse relasi `modules.lessons` yang sudah di-eager-load bila ada), dan
  `isLessonLockedFor()` menerima `$completedLessonIds` opsional (data yang sudah di-fetch sekali di `mount()`)
  supaya cek "sudah selesai?" dilakukan di memori, bukan query baru. Hasilnya: **0 query tambahan** di loop
  yang sama. `progressPercentFor()`/`nextLessonFor()` juga di-refactor supaya berbagi satu query
  `completedLessonIdsFor()` alih-alih masing-masing query sendiri.
- **Index database** — `users.total_points` (dipakai `ORDER BY` di leaderboard), `tracks(is_published, order)`,
  `courses(track_id, is_published)`, `modules(course_id, order)`, dan `lessons(module_id, is_published, order)`
  belum punya index meski jadi filter/sort utama di hampir semua listing publik & admin.
- **Caching** — Leaderboard (`Cache::remember('leaderboard:top50', 60, ...)`) dan Admin Analytics
  (`Cache::remember('admin:analytics', 300, ...)`, satu cache entry untuk 6 metrik sekaligus) sebelumnya
  menjalankan query agregat penuh di setiap page load. TTL pendek dipilih karena data ini tidak butuh akurasi
  real-time detik-per-detik — trade-off standar untuk dashboard/leaderboard skala besar.
  **Bug produksi yang sempat lolos**: implementasi awal meng-cache Eloquent Collection/model langsung.
  `config/cache.php` men-set `serializable_classes => false` (default keamanan Laravel terhadap PHP Object
  Injection lewat cache poisoning), yang bikin `unserialize()` jalan dengan `allowed_classes => false` —
  setiap object di data yang di-cache diam-diam diganti jadi stub `__PHP_Incomplete_Class` yang tak bisa
  dipakai. Cache write-nya sukses (baris pertama selalu render benar), tapi baca berikutnya (cache hit)
  melempar "The script tried to call a method on an incomplete object" persis di titik pertama kode memanggil
  method pada hasil cache — leaderboard & admin analytics keduanya kena. Diperbaiki dengan meng-cache **array
  polos** saja (`->toArray()`/`(array) $row`, bukan Collection/model), lalu merekonstruksi jadi object
  (`(object)`, atau `json_decode(json_encode(...))` untuk relasi bertingkat di `recentActivity`, plus
  `Carbon::parse()` ulang untuk kolom timestamp) setiap kali dibaca dari cache — baik saat cache miss maupun
  hit. Yang bikin ini lolos dari full test suite: `phpunit.xml` men-set `CACHE_STORE=array` untuk testing, dan
  `ArrayStore` menyimpan value langsung di memori tanpa pernah benar-benar serialize/unserialize — jadi bug
  serialisasi macam ini tidak akan pernah ketahuan lewat cache driver testing biasa. `tests/Feature/CacheSerializationTest.php`
  sengaja meng-override `cache.default` ke `database` di dalam test itu sendiri supaya round-trip serialisasi
  yang asli benar-benar teruji.
- **`GamificationService::checkBadges()`** — tiap badge dengan `criteria_type` yang sama (mis. dua badge
  "jumlah lesson selesai" di threshold berbeda) sebelumnya masing-masing menjalankan query yang identik. Sekarang
  di-memoize per pemanggilan `checkBadges()` — mengurangi query per lesson-completion dari 17 ke 12 di data uji.
- **Komentar lesson tanpa batas** — `$lesson->comments()` sebelumnya mengambil seluruh thread tanpa limit;
  lesson yang ramai didiskusikan bisa memuat ribuan baris di setiap render. Sekarang dibatasi 50 komentar
  terbaru, dengan query `COUNT` terpisah yang murah supaya angka di header "Diskusi (n)" tetap akurat walau
  sudah melewati batas tampil.
- **Race condition nyata di `toggleComplete`** — method ini memakai pola *check-then-`create()`* tanpa
  proteksi, berbeda dari `submitExercise`/`submitQuiz` yang sudah pakai `firstOrCreate` + `wasRecentlyCreated`.
  Klik ganda/dua request bersamaan bisa membuat kedua request lolos pengecekan `exists()` sebelum salah satu
  ter-commit, lalu request kedua **throw exception tak tertangani** karena melanggar constraint unique
  `(user_id, lesson_id)` di tabel `user_progress` — bukan cuma double-award poin, tapi crash. Diperbaiki supaya
  konsisten pakai `firstOrCreate` seperti dua path lainnya.
- **`StreakReminderNotification` tidak benar-benar di-queue** — class ini pakai trait `Queueable` tapi lupa
  `implements ShouldQueue`, jadi command `streak:remind` sebenarnya mengirim email secara sinkron di dalam
  loop (trait saja tidak cukup, harus implement interface-nya). Sudah diperbaiki; `QUEUE_CONNECTION=database`
  sudah tersedia jadi tinggal jalankan `php artisan queue:work` di production.
- **Rate limiting di endpoint publik** — `/sertifikat/verifikasi/{code}` (tanpa auth, by design) ditambah
  `throttle:30,1` supaya tidak jadi target scraping/enumerasi kode sertifikat.
- **Dicoba tapi di-revert**: mengganti loop `update()` per-baris di action `reorder()` (admin drag-and-drop)
  dengan satu panggilan `upsert()` batch. Bekerja di MySQL, tapi SQLite (dipakai test suite) memvalidasi
  constraint `NOT NULL` di klausa `INSERT` milik `ON CONFLICT` walau baris itu pasti akan lewat jalur `UPDATE`
  — jadi test gagal. Karena daftar yang di-reorder sudah dibatasi ≤10 item (lihat bagian drag-and-drop di
  atas), potensi penghematannya kecil dan tidak sepadan dengan risiko portabilitas across MySQL/SQLite, jadi
  dikembalikan ke loop biasa.

## Roadmap

Enam fase yang direncanakan semuanya sudah selesai:

- ~~**Fase 1** — Fondasi: auth + role.~~ ✅
- ~~**Fase 2** — Migration & model Track/Course/Module/Lesson, admin CRUD, seed course contoh.~~ ✅
- ~~**Fase 3** — Halaman student, dashboard, progress tracking, penegakan `lock_lessons_sequentially`.~~ ✅
- ~~**Fase 4** — Code playground (CodeMirror + live preview) untuk lesson tipe `exercise`.~~ ✅
- ~~**Fase 5** — Quiz & assessment dengan auto-grading (tipe lesson `quiz`).~~ ✅
- ~~**Fase 6** — Gamification (XP, badge, streak), sertifikat PDF, toggle dark mode manual, polish.~~ ✅

### Follow-up opsional (belum dikerjakan)

Semua item "Fitur Pendukung Lain" dari spesifikasi awal (search course, bookmark lesson) sudah dibangun sejak
fase-fase di atas selesai — lihat bagian pencarian course di Halaman Student & Progress Tracking dan bagian
"Bookmark lesson" tepat di bawah ini. Satu-satunya item yang tetap di luar cakupan:

- Eksekusi PHP/Laravel live di browser (disebut di catatan teknis awal) — butuh sandbox eksekusi server-side
  terpisah, bukan sekadar tambahan frontend.
