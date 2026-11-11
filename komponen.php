<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Komponen Bersama · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<main class="mx-auto max-w-5xl px-4 py-10">
  <div id="head"></div>

  <section class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">StatusBadge</h2>
      <p class="mt-1 text-sm text-slate-500">Pemetaan status janji temu, verifikasi, rekam medis, dan user.</p>
      <div id="badges" class="mt-4 flex flex-wrap gap-2"></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">ConfirmModal (dengan alasan wajib)</h2>
      <p class="mt-1 text-sm text-slate-500">Dialog ini dipakai untuk tolak janji, batalkan janji, revisi rekam medis, dan verifikasi.</p>
      <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" data-modal="plain" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Konfirmasi biasa</button>
        <button type="button" data-modal="reason" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Konfirmasi + alasan wajib</button>
      </div>
      <p id="modal-out" class="mt-3 text-sm text-slate-600" role="status" aria-live="polite"></p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Toast</h2>
      <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" data-toast="success" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Sukses</button>
        <button type="button" data-toast="error" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Error</button>
        <button type="button" data-toast="warning" class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50">Peringatan</button>
        <button type="button" data-toast="info" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Info</button>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Tabel: sorting, filter, pagination</h2>
      <p class="mt-1 text-sm text-slate-500">Klik judul kolom untuk mengurutkan, gunakan kotak cari dan filter.</p>
      <div id="tbl" class="mt-4 overflow-hidden rounded-xl border border-slate-200"></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Slot picker</h2>
      <p class="mt-1 text-sm text-slate-500">Gabungan jadwal mingguan + pengecualian; slot terisi atau terhalang cuti tidak bisa dipilih.</p>
      <div id="slots" class="mt-4"></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">State loading / empty / error</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 p-3"><p class="mb-2 text-xs font-medium text-slate-500">loading</p><div id="s-load" class="rounded-lg bg-slate-50"></div></div>
        <div class="rounded-xl border border-slate-200 p-3"><p class="mb-2 text-xs font-medium text-slate-500">empty</p><div id="s-empty" class="rounded-lg bg-slate-50"></div></div>
        <div class="rounded-xl border border-slate-200 p-3"><p class="mb-2 text-xs font-medium text-slate-500">error</p><div id="s-error" class="rounded-lg bg-slate-50"></div></div>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Form &amp; validasi klien</h2>
      <p class="mt-1 text-sm text-slate-500">Coba isi password singkat atau jam selesai lebih kecil dari jam mulai.</p>
      <div id="form-host" class="mt-4"></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Catatan aksesibilitas &amp; keamanan tampilan</h2>
      <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
        <li>Setiap input punya <code>&lt;label for&gt;</code>; pesan bantuan terhubung via <code>aria-describedby</code>, pesan error diumumkan lewat <code>role="alert"</code>.</li>
        <li>Modal memakai <code>role="dialog"</code>, <code>aria-modal="true"</code>, fokus dipindah ke dalam dialog, dan bisa ditutup dengan tombol Esc.</li>
        <li>Tabel memakai <code>&lt;caption&gt;</code> (sr-only) dan <code>aria-sort</code> pada kolom terurut; baris dapat dibuka dengan Enter/Space.</li>
        <li>Semua data pengguna dirender lewat <code>textContent</code>/escape — input seperti <code>&lt;script&gt;alert(1)&lt;/script&gt;</code> tampil sebagai teks.</li>
      </ul>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Data demo</h2>
      <p class="mt-1 text-sm text-slate-500">Perubahan yang dibuat saat mode mock (booking, verifikasi, ubah status) tersimpan sementara di tab ini. Muat ulang cukup dengan menutup tab, atau tekan tombol di bawah.</p>
      <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="button" id="reset-demo" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset data demo</button>
        <span id="reset-note" class="text-xs text-slate-500" role="status" aria-live="polite"></span>
      </div>
    </div>
  </section>
</main>
<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>

var s = App.Session.get();
document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Komponen Bersama',
  subtitle: 'Katalog komponen yang dipakai seluruh modul (Modul 5) — dapat dibuka tanpa login.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Komponen' }],
  actions: (s ? '<a href="' + url(s.role + '/dashboard.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Dashboard saya</a>' : '<a href="' + url('login.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Masuk</a>')
});

document.getElementById('badges').innerHTML = ['pending','confirmed','rejected','cancelled','completed','moved','leave','custom_hours','draft','finalized','revised','active','inactive','suspended','verified','locked','success','failure']
  .map(function (s) { return Render.badge(s); }).join('');

document.querySelectorAll('[data-modal]').forEach(function (b) {
  b.addEventListener('click', function () {
    var needReason = this.getAttribute('data-modal') === 'reason';
    App.modal({
      title: needReason ? 'Tolak pengajuan ini?' : 'Simpan perubahan?',
      message: needReason ? 'Alasan wajib diisi (minimal 5 karakter) dan akan ditampilkan ke pengguna terkait.' : 'Perubahan akan langsung tersimpan.',
      reasonRequired: needReason, danger: needReason, confirmLabel: needReason ? 'Ya, tolak' : 'Simpan'
    }).then(function (r) {
      App.setText(document.getElementById('modal-out'), r.ok ? ('Dikonfirmasi' + (r.reason ? ' dengan alasan: "' + r.reason + '"' : '') + '.') : 'Dialog dibatalkan.');
    });
  });
});
document.querySelectorAll('[data-toast]').forEach(function (b) {
  b.addEventListener('click', function () { App.toast('Contoh notifikasi ' + this.getAttribute('data-toast') + '.', this.getAttribute('data-toast')); });
});

Render.table('#tbl', {
  caption: 'Contoh tabel data dokter',
  searchPlaceholder: 'Cari nama dokter…',
  searchText: function (r) { return r.name + ' ' + r.spec; },
  pageSize: 4,
  rows: [
    { name: 'dr. Sari Wijaya, Sp.PD', spec: 'Penyakit Dalam', fee: 250000, status: 'verified', experience: 9 },
    { name: 'dr. Bagus Prakoso, Sp.JP', spec: 'Jantung', fee: 400000, status: 'verified', experience: 12 },
    { name: 'dr. Nadia Kusuma, Sp.A', spec: 'Anak', fee: 300000, status: 'suspended', experience: 6 },
    { name: 'dr. Fajar Ramadhan', spec: 'Dokter Umum', fee: 120000, status: 'pending', experience: 2 },
    { name: 'dr. Melati Anjani, Sp.KK', spec: 'Kulit & Kelamin', fee: 275000, status: 'pending', experience: 5 },
    { name: 'dr. Hendra Surya, Sp.S', spec: 'Saraf', fee: 350000, status: 'rejected', experience: 15 }
  ],
  filters: [{ key: 'status', label: 'Status', options: [{ value: 'verified', label: 'Terverifikasi' }, { value: 'pending', label: 'Menunggu' }, { value: 'rejected', label: 'Ditolak' }, { value: 'suspended', label: 'Ditangguhkan' }] }],
  columns: [
    { key: 'name', label: 'Dokter' },
    { key: 'spec', label: 'Spesialisasi' },
    { key: 'experience', label: 'Pengalaman' },
    { key: 'fee', label: 'Biaya', render: function (r) { return App.fmt.rupiah(r.fee); } },
    { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status); } }
  ]
});

var demoDates = [];
for (var i = 0; i < 4; i++) {
  var d = new Date(); d.setDate(d.getDate() + i);
  var ds = d.toISOString().slice(0, 10);
  var slots = [];
  for (var h = 9; h < 12; h++) slots.push({ start: h + ':00', end: h + ':30', available: !(i === 1 && h === 9) });
  demoDates.push({ date: ds, slots: slots });
}
Render.slotPicker('#slots', { dates: demoDates, onPick: function (c) { App.toast('Slot dipilih: ' + c.date + ' ' + c.start, 'info'); } });

document.getElementById('s-load').innerHTML = App.stateBox('loading');
document.getElementById('s-empty').innerHTML = App.stateBox('empty', 'Belum ada data.');
document.getElementById('s-error').innerHTML = App.stateBox('error', 'Gagal memuat data dari server.');

Render.form('#form-host', {
  submitLabel: 'Kirim contoh',
  successMessage: 'Validasi klien lolos — di produksi data dikirim ke endpoint PHP.',
  fields: [
    { name: 'email', label: 'Email', type: 'email', required: true, rules: [App.validate.email] },
    { name: 'password', label: 'Password', type: 'password', required: true, rules: [App.validate.password] },
    [
      { name: 'start_time', label: 'Jam mulai', type: 'time', value: '09:00', required: true, rules: [App.validate.required] },
      { name: 'end_time', label: 'Jam selesai', type: 'time', value: '08:00', required: true, rules: [App.validate.required] }
    ],
    { name: 'reason', label: 'Alasan', type: 'textarea', rows: 2, required: true, rules: [App.validate.minLength(5)] }
  ],
  validate: function (v) { return App.validate.timeRange(v.start_time, v.end_time); },
  onSubmit: function () { return null; }
});

document.getElementById('reset-demo').addEventListener('click', function () {
  try { sessionStorage.removeItem('rs_demo_db'); } catch (e) {}
  App.setText(document.getElementById('reset-note'), 'Data demo dikembalikan ke kondisi awal. Memuat ulang…');
  setTimeout(function () { location.reload(); }, 900);
});

</script>
</body>
</html>
