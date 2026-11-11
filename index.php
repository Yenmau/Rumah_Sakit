<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="RS SehatSentosa — layanan rumah sakit: pendaftaran janji temu dokter, jadwal praktik, poliklinik, dan riwayat pemeriksaan.">
<title>Beranda · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-4 sm:px-6">
    <div class="min-w-0">
      <p class="truncate text-lg font-bold tracking-tight text-slate-900">RS Sehat<span class="text-brand-700">Sentosa</span></p>
      <p class="truncate text-xs text-slate-500">Jl. Kesehatan No. 1 · Layanan pelanggan 1500-000</p>
    </div>
    <nav class="ml-auto hidden items-center gap-5 text-sm font-medium text-slate-600 md:flex" aria-label="Navigasi halaman">
      <a href="#layanan" class="hover:text-brand-700">Poliklinik</a>
      <a href="#dokter" class="hover:text-brand-700">Dokter</a>
      <a href="#alur" class="hover:text-brand-700">Alur berobat</a>
      <a href="#kontak" class="hover:text-brand-700">Kontak</a>
    </nav>
    <a id="nav-login" href="login.php" class="ml-auto rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 md:ml-0">Masuk</a>
  </div>
</header>

<main class="grow">

  <section class="border-b border-slate-200 bg-gradient-to-b from-brand-50 to-slate-50">
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:py-16">
      <div>
        <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-800 ring-1 ring-inset ring-brand-200">
          <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-brand-600"></span> Melayani 7 hari seminggu · IGD 24 jam
        </p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Selamat Datang di RS SehatSentosa</h1>
        <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-600">
          Rumah sakit kami melayani pendaftaran janji temu dengan dokter spesialis, penjadwalan praktik,
          serta penyimpanan riwayat pemeriksaan pasien dalam satu sistem yang mudah diakses.
        </p>

        <div class="mt-7 flex flex-wrap gap-3">
          <a id="cta-main" href="login.php" class="rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">Masuk ke akun</a>
          <a id="cta-register-pasien" href="register-pasien.php" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Daftar sebagai pasien</a>
        </div>
        <p id="register-line" class="mt-3 text-sm text-slate-600">
          Dokter baru?
          <a id="cta-register-dokter" href="register-dokter.php" class="font-medium text-brand-700 hover:underline">Daftar sebagai dokter</a>
        </p>
        <p id="session-note" class="mt-4 hidden text-sm text-slate-600" role="status" aria-live="polite"></p>
      </div>

      <div class="flex justify-center">
        <svg viewBox="0 0 420 320" class="w-full max-w-md" role="img" aria-label="Ilustrasi tenaga medis rumah sakit">
          <circle cx="210" cy="165" r="140" fill="#ccfbf1"/>
          <circle cx="210" cy="165" r="118" fill="#ffffff"/>
          <path d="M92 96h26M105 83v26" stroke="#5eead4" stroke-width="4" stroke-linecap="round"/>
          <path d="M330 216h22M341 205v22" stroke="#5eead4" stroke-width="4" stroke-linecap="round"/>
          <circle cx="128" cy="150" r="24" fill="#e2e8f0"/>
          <path d="M128 176c-26 0-42 15-42 40v30h84v-30c0-25-16-40-42-40z" fill="#0f766e"/>
          <rect x="110" y="140" width="36" height="14" rx="7" fill="#f8fafc"/>
          <circle cx="210" cy="134" r="28" fill="#ffffff"/>
          <path d="M210 164c-30 0-48 17-48 45v37h96v-37c0-28-18-45-48-45z" fill="#ffffff" stroke="#99f6e4" stroke-width="2"/>
          <path d="M186 164l-14 30 14 12" fill="none" stroke="#0d9488" stroke-width="3" stroke-linecap="round"/>
          <path d="M234 164l14 30-14 12" fill="none" stroke="#0d9488" stroke-width="3" stroke-linecap="round"/>
          <circle cx="238" cy="216" r="9" fill="none" stroke="#0d9488" stroke-width="3"/>
          <circle cx="182" cy="141" r="3.5" fill="#115e59"/>
          <circle cx="238" cy="141" r="3.5" fill="#115e59"/>
          <circle cx="292" cy="150" r="24" fill="#e2e8f0"/>
          <path d="M292 176c-26 0-42 15-42 40v30h84v-30c0-25-16-40-42-40z" fill="#134e4a"/>
          <rect x="268" y="196" width="48" height="34" rx="6" fill="#f8fafc"/>
          <path d="M276 205h32M276 214h32M276 223h20" stroke="#5eead4" stroke-width="3" stroke-linecap="round"/>
          <circle cx="336" cy="118" r="26" fill="#16a34a"/>
          <path d="M336 105v26M323 118h26" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </section>

  <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6" aria-label="Ringkasan layanan">
    <div id="stats" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"></div>
  </section>

  <section id="layanan" class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
      <div class="max-w-2xl">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Poliklinik &amp; layanan</h2>
        <p class="mt-2 text-sm text-slate-600">Layanan rawat jalan yang tersedia. Jadwal tiap dokter dapat dilihat setelah Anda masuk.</p>
      </div>
      <div id="poli" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"></div>
    </div>
  </section>

  <section id="dokter" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Dokter kami</h2>
        <p class="mt-2 text-sm text-slate-600">Dokter yang sudah terverifikasi administrasi rumah sakit.</p>
      </div>
      <a href="login.php" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Masuk untuk membuat janji</a>
    </div>
    <div id="dokter-list" class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3"></div>
  </section>

  <section id="alur" class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
      <h2 class="text-2xl font-bold tracking-tight text-slate-900">Alur berobat</h2>
      <p class="mt-2 max-w-2xl text-sm text-slate-600">Tiga langkah sederhana sebelum Anda datang ke rumah sakit.</p>
      <ol class="mt-8 grid gap-5 md:grid-cols-3">
        <li class="rounded-2xl border border-slate-200 p-6">
          <span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">1</span>
          <h3 class="mt-4 text-base font-semibold text-slate-900">Buat akun</h3>
          <p class="mt-2 text-sm text-slate-600">Daftar sebagai pasien dengan data diri Anda, lalu masuk ke akun.</p>
        </li>
        <li class="rounded-2xl border border-slate-200 p-6">
          <span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">2</span>
          <h3 class="mt-4 text-base font-semibold text-slate-900">Pilih dokter &amp; jadwal</h3>
          <p class="mt-2 text-sm text-slate-600">Lihat poliklinik, pilih dokter, lalu ambil slot jam yang masih kosong dan isi keluhan Anda.</p>
        </li>
        <li class="rounded-2xl border border-slate-200 p-6">
          <span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">3</span>
          <h3 class="mt-4 text-base font-semibold text-slate-900">Datang sesuai jadwal</h3>
          <p class="mt-2 text-sm text-slate-600">Tunggu persetujuan dokter, lalu datang 15 menit lebih awal dengan membawa identitas.</p>
        </li>
      </ol>
    </div>
  </section>

  <section id="kontak" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Informasi kunjungan</h2>
    <div class="mt-8 grid gap-5 md:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-lg">🕒</span>
        <h3 class="mt-4 text-base font-semibold text-slate-900">Jam pendaftaran</h3>
        <dl class="mt-3 space-y-1 text-sm text-slate-600">
          <div class="flex justify-between gap-4"><dt>Senin–Jumat</dt><dd class="font-medium text-slate-800">07.00–20.00</dd></div>
          <div class="flex justify-between gap-4"><dt>Sabtu–Minggu</dt><dd class="font-medium text-slate-800">08.00–14.00</dd></div>
          <div class="flex justify-between gap-4"><dt>IGD</dt><dd class="font-medium text-slate-800">24 jam</dd></div>
        </dl>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-lg">📞</span>
        <h3 class="mt-4 text-base font-semibold text-slate-900">Kontak</h3>
        <dl class="mt-3 space-y-1 text-sm text-slate-600">
          <div class="flex justify-between gap-4"><dt>Layanan pelanggan</dt><dd class="font-medium text-slate-800">1500-000</dd></div>
          <div class="flex justify-between gap-4"><dt>Gawat darurat</dt><dd class="font-medium text-slate-800">119</dd></div>
          <div class="flex justify-between gap-4"><dt>Email</dt><dd class="font-medium text-slate-800">info@sehatsentosa.id</dd></div>
        </dl>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-lg">📍</span>
        <h3 class="mt-4 text-base font-semibold text-slate-900">Lokasi</h3>
        <p class="mt-3 text-sm text-slate-600">Jl. Kesehatan No. 1, Kota Sehat<br>Parkir pasien tersedia di sisi timur gedung.</p>
      </div>
    </div>
  </section>
</main>

<footer class="border-t border-slate-200 bg-white">
  <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm sm:px-6 md:grid-cols-3">
    <div>
      <p class="text-base font-bold text-slate-900">RS Sehat<span class="text-brand-700">Sentosa</span></p>
      <p class="mt-2 text-slate-600">Layanan kesehatan terpadu untuk pasien, dokter, dan tenaga administrasi.</p>
    </div>
    <div>
      <p class="font-semibold text-slate-900">Tautan</p>
      <ul class="mt-2 space-y-1 text-slate-600">
        <li><a href="#layanan" class="hover:text-brand-700">Poliklinik</a></li>
        <li><a href="#dokter" class="hover:text-brand-700">Dokter</a></li>
        <li><a href="login.php" class="hover:text-brand-700">Masuk</a></li>
        <li><a href="register-pasien.php" class="hover:text-brand-700">Pendaftaran pasien</a></li>
      </ul>
    </div>
    <div>
      <p class="font-semibold text-slate-900">Kontak</p>
      <p class="mt-2 text-slate-600">Jl. Kesehatan No. 1<br>Layanan pelanggan 1500-000<br>Darurat 119</p>
    </div>
  </div>
  <div class="border-t border-slate-100 px-4 py-4 text-xs text-slate-500 sm:px-6">
    <p class="mx-auto max-w-6xl">© 2026 RS SehatSentosa · Jl. Kesehatan No. 1</p>
  </div>
</footer>

<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>

<script>

var s = App.Session.get();
if (s) {
  var dash = url(s.role + "/dashboard.php");
  var main = document.getElementById("cta-main");
  main.href = dash;
  main.textContent = "Buka dashboard";
  document.getElementById("nav-login").outerHTML =
    '<a href="' + dash + '" class="ml-auto rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 md:ml-0">Dashboard</a>';
  document.getElementById("cta-register-pasien").classList.add("hidden");
  document.getElementById("register-line").classList.add("hidden");
  var note = document.getElementById("session-note");
  note.textContent = "Anda sedang masuk sebagai " + s.name + " (" + s.role + ").";
  note.classList.remove("hidden");
}

var IKON_POLI = { "Penyakit Dalam": "🩺", "Jantung": "❤️", "Anak": "🧸", "Kulit & Kelamin": "🧴", "Saraf": "🧠", "Dokter Umum": "🩹" };

function stats(count) {
  var items = [
    { label: "Poliklinik", value: count.poli, hint: "Pilih sesuai keluhan Anda" },
    { label: "Dokter terverifikasi", value: count.dokter, hint: "Sudah lolos verifikasi admin" },
    { label: "Pendaftaran janji temu", value: "Daring", hint: "Ajukan kapan saja, 24 jam" },
    { label: "Jam layanan rawat jalan", value: "07–20", hint: "Senin–Jumat, 08–14 akhir pekan" }
  ];
  document.getElementById("stats").innerHTML = items.map(function (i) {
    return '<div class="rounded-2xl border border-slate-200 bg-white p-5">' +
      '<p class="text-2xl font-bold text-brand-800">' + App.esc(i.value) + '</p>' +
      '<p class="mt-1 text-sm font-medium text-slate-700">' + App.esc(i.label) + '</p>' +
      (i.hint ? '<p class="mt-1 text-xs text-slate-500">' + App.esc(i.hint) + '</p>' : '') + '</div>';
  }).join("");
}

Promise.all([App.api("/api/specializations"), App.api("/api/doctors")]).then(function (r) {
  var poli = r[0].data, dokter = r[1].data;

  stats({ poli: poli.length, dokter: dokter.length });

  var host = document.getElementById("poli");
  host.innerHTML = poli.length ? poli.map(function (p) {
    var n = dokter.filter(function (d) { return d.specialization_id === p.id; }).length;
    return '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">' +
      '<span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-lg ring-1 ring-slate-200">' + (IKON_POLI[p.name] || "🏥") + '</span>' +
      '<h3 class="mt-4 text-base font-semibold text-slate-900">' + App.esc(p.name) + '</h3>' +
      '<p class="mt-2 text-sm text-slate-600">' + App.esc(p.description || "Layanan rawat jalan.") + '</p>' +
      '<p class="mt-3 text-xs text-slate-500">' + n + ' dokter terverifikasi</p></article>';
  }).join("") : App.stateBox("empty", "Data poliklinik belum tersedia.");

  var dh = document.getElementById("dokter-list");
  dh.innerHTML = dokter.length ? dokter.map(function (d) {
    return '<article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">' +
      '<div class="flex items-start gap-3">' +
        '<span aria-hidden="true" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg">🩺</span>' +
        '<div class="min-w-0"><h3 class="text-base font-semibold text-slate-900">' + App.esc(d.name) + '</h3>' +
        '<p class="text-sm text-slate-600">' + App.esc(d.specialization) + '</p></div>' +
      '</div>' +
      '<dl class="mt-4 grid grid-cols-2 gap-3 text-sm">' +
        '<div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Pengalaman</dt>' +
        '<dd class="text-slate-800">' + App.esc(d.experience_years) + ' tahun</dd></div>' +
        '<div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Biaya konsultasi</dt>' +
        '<dd class="font-semibold text-slate-900">' + App.fmt.rupiah(d.fee) + '</dd></div>' +
      '</dl>' +
      '<a href="login.php" class="mt-5 rounded-lg border border-brand-600 px-4 py-2.5 text-center text-sm font-medium text-brand-700 hover:bg-brand-50">Masuk untuk memilih jadwal</a>' +
    '</article>';
  }).join("") : App.stateBox("empty", "Belum ada dokter terverifikasi.");
}).catch(function (err) {
  document.getElementById("stats").innerHTML = App.stateBox("error", "Gagal memuat data layanan.");
  document.getElementById("poli").innerHTML = "";
  document.getElementById("dokter-list").innerHTML = "";
  if (window.console && console.warn) console.warn("Gagal memuat data beranda:", err && err.message);
});
</script>
</body>
</html>
