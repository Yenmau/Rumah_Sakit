<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Profil Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="profil">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2"><div id="form-host"></div></div>
  <div class="space-y-5"><div id="verify"></div><div id="sip"></div></div>
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
  title: 'Profil Praktik',
  subtitle: 'Spesialisasi, biaya konsultasi, pengalaman, dan status verifikasi akun Anda.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Profil' }]
});
document.getElementById('form-host').innerHTML = Render.card({ body: App.stateBox('loading') });
Promise.all([App.api('/api/dokter/profile'), App.api('/api/specializations')]).then(function (r) {
  var me = r[0].data, v = r[0].verification, specs = r[1].data;
  document.getElementById('form-host').innerHTML = '';
  Render.form('#form-host', {
    action: 'actions/doUpdateDoctorProfile.php',
    submitLabel: 'Simpan profil',
    successMessage: 'Profil diperbarui.',
    fields: [
      { name: 'name', label: 'Nama & gelar', value: me.name, help: 'Perubahan gelar diverifikasi ulang oleh admin.', rules: [App.validate.required] },
      { name: 'specialization_id', label: 'Spesialisasi', type: 'select', required: true, value: me.specialization_id,
        options: specs.map(function (s) { return { value: s.id, label: s.name }; }), rules: [App.validate.required] },
      [
        { name: 'fee', label: 'Biaya konsultasi (Rp)', type: 'number', required: true, value: me.fee, min: 0, step: 50000, rules: [App.validate.number] },
        { name: 'experience_years', label: 'Pengalaman (tahun)', type: 'number', required: true, value: me.experience_years, min: 0, rules: [App.validate.required] }
      ],
    ],
    onSubmit: function (val) { return App.api('/api/dokter/profile', { method: 'PUT', body: val }); }
  });
  document.getElementById('verify').innerHTML = Render.card({
    title: 'Status verifikasi',
    body: '<div class="space-y-3">' + Render.badge(me.verification) + '<p class="text-sm text-slate-600">' + App.esc(v.note) + '</p>' +
      (me.verification === 'pending' ? '<p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Selama menunggu, profil tidak tampil di daftar dokter sehingga pasien belum bisa memesan.</p>' : '') + '</div>'
  });
  document.getElementById('sip').innerHTML = Render.card({
    title: 'Data lisensi',
    body: Render.describe([{ label: 'Nomor SIP / lisensi', value: me.license }], 'grid-cols-1') +
      '<p class="mt-3 text-xs text-slate-500">Nomor SIP hanya dapat diubah melalui admin (audit log wajib).</p>'
  });
}).catch(function (err) { document.getElementById('form-host').innerHTML = Render.card({ body: App.stateBox('error', err.message) }); });

</script>
</body>
</html>
