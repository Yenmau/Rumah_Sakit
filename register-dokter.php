<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Daftar Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<main class="mx-auto max-w-2xl px-4 py-10">
  <div class="mb-5">
    <h1 class="text-xl font-bold text-slate-900">Registrasi Dokter</h1>
    <p class="mt-1 text-sm text-slate-500">Akun dibuat dengan status verifikasi <strong>pending</strong> sampai administrator menyetujui.</p>
  </div>
  <div id="badge-host" class="mb-4"></div>
  <div id="form-host" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"></div>
  <p class="mt-4 text-sm text-slate-600">Sudah punya akun? <a class="font-medium text-brand-700 hover:underline" href="login.php">Masuk di sini</a></p>
</main>
<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>

document.getElementById('badge-host').innerHTML = '<div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">' +
  '<span aria-hidden="true">⏳</span><p>Status awal akun dokter: <strong>Menunggu verifikasi</strong> — ' +
  'Anda belum tampil di daftar dokter sampai admin menyetujui.</p></div>';

var f = Render.form('#form-host', {
  action: 'actions/doRegisterDoctor.php',
  submitLabel: 'Kirim pendaftaran',
  successMessage: 'Pendaftaran terkirim, menunggu verifikasi admin.',
  intro: '<p class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Nomor SIP (Surat Izin Praktik) diverifikasi manual oleh admin. Pastikan format sesuai berkas fisik.</p>',
  fields: [
    { name: 'name', label: 'Nama lengkap (dengan gelar)', required: true, placeholder: 'dr. Nama Lengkap, Sp.XX', rules: [App.validate.required, App.validate.minLength(5)] },
    { name: 'email', label: 'Email institusi', type: 'email', required: true, rules: [App.validate.email] },
    [
      { name: 'license', label: 'Nomor SIP / lisensi', required: true, placeholder: 'SIP-2026-00001', rules: [App.validate.required, App.validate.minLength(6)], help: 'Diperiksa admin saat verifikasi.' },
      { name: 'experience_years', label: 'Pengalaman (tahun)', type: 'number', required: true, min: 0, max: 60, rules: [App.validate.required] }
    ],
    [
      { name: 'specialization_id', id: 'spec', label: 'Spesialisasi', type: 'select', placeholder: 'Memuat…', required: true, rules: [App.validate.required] },
      { name: 'fee', label: 'Biaya konsultasi (Rp)', type: 'number', required: true, min: 0, step: 50000, rules: [App.validate.number], help: 'Ditampilkan ke pasien saat memilih dokter.' }
    ],
    { name: 'password', label: 'Password', type: 'password', required: true, rules: [App.validate.password], help: 'Minimal 12 karakter, kombinasi huruf dan angka.' },
    { name: 'password_confirm', label: 'Ulangi password', type: 'password', required: true, rules: [function (v, all) { return v === all.password || 'Password tidak sama.'; }] },
    { name: 'agree', label: 'Saya menyatakan data lisensi yang diisi benar dan dapat diverifikasi', type: 'checkbox', required: true, rules: [function (v) { return v === true || 'Pernyataan wajib dicentang.'; }] }
  ],
  onSubmit: function (v) {
    return App.api('/api/register/dokter', { method: 'POST', body: v }).then(function () {
      setTimeout(function () { location.href = url('login.php'); }, 1000);
    });
  }
});
App.api('/api/specializations').then(function (res) {
  var sel = document.querySelector('[name="specialization_id"]');
  sel.innerHTML = '<option value="">Pilih spesialisasi…</option>' + res.data.map(function (s) {
    return '<option value="' + App.esc(s.id) + '">' + App.esc(s.name) + '</option>';
  }).join('');
});

</script>
</body>
</html>
