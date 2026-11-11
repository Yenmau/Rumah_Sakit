<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Daftar Pasien · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<main class="mx-auto max-w-2xl px-4 py-10">
  <div class="mb-5">
    <h1 class="text-xl font-bold text-slate-900">Registrasi Pasien</h1>
    <p class="mt-1 text-sm text-slate-500">Akun langsung aktif. Data diri dipakai untuk janji temu dan rekam medis.</p>
  </div>
  <div id="form-host" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"></div>
  <p class="mt-4 text-sm text-slate-600">Sudah punya akun? <a class="font-medium text-brand-700 hover:underline" href="login.php">Masuk di sini</a></p>
</main>
<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>

Render.form('#form-host', {
  action: 'actions/doRegister.php',
  submitLabel: 'Daftar sekarang',
  successMessage: 'Registrasi berhasil. Silakan masuk.',
  secondary: '<a href="login.php" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Kembali ke login</a>',
  intro: '<p class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Semua input divalidasi di sisi klien untuk UX; validasi akhir tetap dilakukan di server (PHP).</p>',
  fields: [
    { name: 'name', label: 'Nama lengkap', required: true, rules: [App.validate.required, App.validate.minLength(3)], autocomplete: 'name' },
    { name: 'email', label: 'Email', type: 'email', required: true, rules: [App.validate.email], help: 'Dipakai sebagai username.', autocomplete: 'email' },
    { name: 'phone', label: 'Nomor telepon', required: true, rules: [App.validate.phone], placeholder: '08xxxxxxxxxx' },
    [
      { name: 'birth_date', label: 'Tanggal lahir', type: 'date', required: true, rules: [App.validate.required] },
      { name: 'gender', label: 'Jenis kelamin', type: 'select', placeholder: 'Pilih…', required: true, options: [
        { value: 'L', label: 'Laki-laki' }, { value: 'P', label: 'Perempuan' }
      ], rules: [App.validate.required] }
    ],
    { name: 'address', label: 'Alamat', type: 'textarea', rows: 2, required: true, rules: [App.validate.required] },
    { name: 'password', label: 'Password', type: 'password', required: true, rules: [App.validate.password], help: 'Minimal 12 karakter, kombinasi huruf dan angka.', autocomplete: 'new-password' },
    { name: 'password_confirm', label: 'Ulangi password', type: 'password', required: true, rules: [function (v, all) { return v === all.password || 'Password tidak sama.'; }], autocomplete: 'new-password' },
    { name: 'agree', label: 'Saya menyetujui pemrosesan data pribadi sesuai kebijakan privasi rumah sakit', type: 'checkbox', required: true, rules: [function (v) { return v === true || 'Persetujuan wajib dicentang.'; }] }
  ],
  onSubmit: function (v) {
    return App.api('/api/register/pasien', { method: 'POST', body: v }).then(function () {
      setTimeout(function () { location.href = url('login.php'); }, 900);
    });
  }
});

</script>
</body>
</html>
