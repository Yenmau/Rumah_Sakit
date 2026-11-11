<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Dashboard Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="dashboard">

<div id="page">

<div id="head"></div>
<div id="stats" class="mb-5"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2" id="today"></div>
  <div class="space-y-5"><div id="pending"></div><div id="verify"></div></div>
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
  title: 'Dashboard Dokter',
  subtitle: 'Janji temu hari ini dan permintaan yang menunggu keputusan Anda.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Dashboard Dokter' }],
  actions: '<a href="' + url('dokter/janji.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Kelola janji temu</a>'
});
document.getElementById('stats').innerHTML = App.stateBox('loading');
Promise.all([App.api('/api/dokter/appointments'), App.api('/api/dokter/profile')]).then(function (r) {
  var all = r[0].data, me = r[1].data;
  var today = new Date().toISOString().slice(0, 10);
  var hariIni = all.filter(function (a) { return a.date === today && ['pending','confirmed'].indexOf(a.status) > -1; });
  var pending = all.filter(function (a) { return a.status === 'pending'; });
  var done = all.filter(function (a) { return a.status === 'completed'; });
  document.getElementById('stats').innerHTML = Render.statCards([
    { label: 'Janji hari ini', value: hariIni.length, hint: App.fmt.tanggal(today) },
    { label: 'Menunggu keputusan', value: pending.length, hint: 'Butuh terima / tolak' },
    { label: 'Total selesai', value: done.length },
    { label: 'Biaya konsultasi', value: App.fmt.rupiah(me.fee) }
  ]);

  document.getElementById('today').innerHTML = Render.card({
    title: 'Jadwal hari ini',
    subtitle: hariIni.length ? 'Termasuk pasien yang belum dikonfirmasi.' : 'Tidak ada janji aktif hari ini.',
    padded: false,
    body: hariIni.length ? '<ul class="divide-y divide-slate-100">' + hariIni.map(function (a) {
      return '<li class="flex flex-wrap items-center gap-3 p-4"><div class="w-16 shrink-0"><p class="text-sm font-semibold text-slate-900">' + App.fmt.jam(a.start_time) + '</p><p class="text-xs text-slate-500">' + App.fmt.jam(a.end_time) + '</p></div>' +
        '<div class="min-w-0 grow"><p class="text-sm font-medium text-slate-900">' + App.esc(a.patient_name) + '</p><p class="line-clamp-1 text-xs text-slate-500">' + App.esc(a.reason) + '</p></div>' +
        Render.badge(a.status) +
        '<a href="' + url('dokter/rekam-medis-baru.php?appointment_id=' + a.id) + '" class="rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-800">Rekam medis</a></li>';
    }).join('') + '</ul>' : App.stateBox('empty', 'Tidak ada janji temu hari ini.')
  });

  document.getElementById('pending').innerHTML = Render.card({
    title: 'Permintaan menunggu',
    subtitle: pending.length + ' permintaan',
    body: pending.length ? '<ul class="divide-y divide-slate-100 text-sm">' + pending.slice(0, 4).map(function (a) {
      return '<li class="py-2"><p class="font-medium text-slate-800">' + App.esc(a.patient_name) + '</p>' +
        '<p class="text-xs text-slate-500">' + App.fmt.tanggal(a.date) + ' · ' + App.fmt.jam(a.start_time) + '</p></li>';
    }).join('') + '</ul>' : App.stateBox('empty', 'Semua permintaan sudah diproses.'),
    footer: '<a href="' + url('dokter/janji.php') + '" class="text-sm font-medium text-brand-700 hover:underline">Buka daftar janji temu →</a>'
  });

  document.getElementById('verify').innerHTML = Render.card({
    title: 'Status verifikasi akun',
    body: '<div class="space-y-3">' + Render.badge(me.verification) +
      '<p class="text-sm text-slate-600">' + App.esc(me.verification === 'verified' ? 'Akun Anda tampil di daftar dokter dan dapat menerima janji temu.' : me.verification === 'pending' ? 'Menunggu peninjauan admin. Profil belum tampil ke pasien.' : 'Verifikasi ditolak. Perbaiki data lisensi lalu hubungi admin.') + '</p>' +
      '<a href="' + url('dokter/profil.php') + '" class="inline-block rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Lihat profil</a></div>'
  });
}).catch(function (err) { document.getElementById('stats').innerHTML = App.stateBox('error', err.message); });

</script>
</body>
</html>
