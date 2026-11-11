<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Profil Pasien · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="profil">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2"><div id="form-host"></div></div>
  <div id="side"></div>
</div>
</div>

<script>window.APP_BASE = "..";</script>
<script src="../assets/js/config.js"></script>
<script src="../assets/js/mock.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/ui.js"></script>
<script src="../assets/js/layout.js"></script>

<script>

document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Profil Saya',
  subtitle: 'Perbarui data diri. Perubahan tercatat di audit log.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Profil' }]
});
document.getElementById('form-host').innerHTML = Render.card({ body: App.stateBox('loading') });
App.api('/api/pasien/profile').then(function (res) {
  var p = res.data;
  document.getElementById('form-host').innerHTML = '';
  Render.form('#form-host', {
    action: 'actions/doUpdateProfile.php',
    submitLabel: 'Simpan perubahan',
    successMessage: 'Profil diperbarui.',
    fields: [
      { name: 'name', label: 'Nama lengkap', required: true, value: p.name, rules: [App.validate.required] },
      [
        { name: 'phone', label: 'Telepon', required: true, value: p.phone, rules: [App.validate.phone] },
        { name: 'gender', label: 'Jenis kelamin', type: 'select', value: p.gender, required: true, options: [{ value: 'L', label: 'Laki-laki' }, { value: 'P', label: 'Perempuan' }] }
      ],
      { name: 'birth_date', label: 'Tanggal lahir', type: 'date', value: p.birth_date, required: true, rules: [App.validate.required] },
      { name: 'address', label: 'Alamat', type: 'textarea', rows: 2, value: p.address, required: true, rules: [App.validate.required] },
      { name: 'email', label: 'Email (tidak dapat diubah)', type: 'email', value: p.email, help: 'Hubungi admin untuk mengubah email.' }
    ],
    onSubmit: function (v) { return App.api('/api/pasien/profile', { method: 'PUT', body: v }); }
  });
  var f = document.querySelector('[name="email"]');
  if (f) { f.readOnly = true; f.classList.add('bg-slate-100'); }
  document.getElementById('side').innerHTML = Render.card({
    title: 'Data akun',
    body: Render.describe([
      { label: 'Terdaftar', value: App.fmt.tanggal(p.created_at) },
      { label: 'ID pasien', value: '#' + p.id }
    ], 'grid-cols-1') +
    '<p class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Ganti password secara terpisah di halaman <a class="text-brand-700 underline" href="' + url('change-password.php') + '">Ganti Password</a>.</p>'
  });
}).catch(function (err) { document.getElementById('form-host').innerHTML = Render.card({ body: App.stateBox('error', err.message) }); });

</script>
</body>
</html>
