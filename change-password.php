<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Ganti Password · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<main class="mx-auto max-w-xl px-4 py-10">
  <div id="who" class="mb-4"></div>
  <h1 class="text-xl font-bold text-slate-900">Ganti Password</h1>
  <p class="mt-1 text-sm text-slate-500">Setelah password diubah, sesi lain akan diminta masuk kembali.</p>
  <div id="form-host" class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"></div>
  <p class="mt-4 text-sm"><a id="back" class="font-medium text-brand-700 hover:underline" href="#">← Kembali ke dashboard</a></p>
</main>
<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>

var s = App.guard(null);
if (s) {
  document.getElementById('who').innerHTML = '<p class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">Masuk sebagai <strong>' + App.esc(s.name) + '</strong> (' + App.esc(s.role) + ')</p>';
  document.getElementById('back').href = url(s.role + '/dashboard.php');
  Render.form('#form-host', {
    action: 'actions/doChangePassword.php',
    submitLabel: 'Simpan password baru',
    successMessage: 'Password berhasil diubah. Sesi lain otomatis berakhir.',
    fields: [
      { name: 'current_password', label: 'Password saat ini', type: 'password', required: true, rules: [App.validate.required], autocomplete: 'current-password' },
      { name: 'new_password', label: 'Password baru', type: 'password', required: true, rules: [App.validate.password], help: 'Minimal 12 karakter, kombinasi huruf dan angka.', autocomplete: 'new-password' },
      { name: 'confirm_password', label: 'Ulangi password baru', type: 'password', required: true, rules: [function (v, all) { return v === all.new_password || 'Konfirmasi tidak sama.'; }], autocomplete: 'new-password' }
    ],
    validate: function (v) { return v.new_password !== v.current_password || 'Password baru tidak boleh sama dengan password lama.'; },
    onSubmit: function (v) { return App.api('/api/change-password', { method: 'POST', body: v }); }
  });
}

</script>
</body>
</html>
