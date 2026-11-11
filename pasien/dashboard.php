<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Dashboard Pasien · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="dashboard">

<div id="page">

<div id="head"></div>
<div id="stats" class="mb-5"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2">
    <div id="upcoming"></div>
  </div>
  <div class="space-y-5">
    <div id="latest"></div>
  </div>
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
  title: 'Dashboard Pasien',
  subtitle: 'Ringkasan janji temu mendatang dan status terbaru layanan Anda.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Dashboard Pasien' }],
  actions: '<a href="' + url('pasien/cari-dokter.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">+ Buat janji temu</a>'
});
document.getElementById('stats').innerHTML = App.stateBox('loading');

Promise.all([App.api('/api/pasien/appointments'), App.api('/api/pasien/records')]).then(function (r) {
  var all = r[0].data, recs = r[1].data;
  var upcoming = all.filter(function (a) { return ['pending','confirmed'].indexOf(a.status) > -1 && a.date >= new Date().toISOString().slice(0,10); })
                    .sort(function (a, b) { return (a.date + a.start_time).localeCompare(b.date + b.start_time); });
  document.getElementById('stats').innerHTML = Render.statCards([
    { label: 'Janji mendatang', value: upcoming.length, hint: upcoming[0] ? 'Terdekat: ' + App.fmt.tanggal(upcoming[0].date) : 'Belum ada' },
    { label: 'Menunggu konfirmasi', value: all.filter(function (a) { return a.status === 'pending'; }).length },
    { label: 'Selesai', value: all.filter(function (a) { return a.status === 'completed'; }).length },
    { label: 'Rekam medis final', value: recs.length, hint: 'Hanya yang difinalisasi dokter' }
  ]);

  document.getElementById('upcoming').innerHTML = Render.card({
    title: 'Janji temu mendatang',
    subtitle: 'Diurutkan dari yang paling dekat.',
    padded: false,
    body: upcoming.length ? '<ul class="divide-y divide-slate-100">' + upcoming.map(function (a) {
      return '<li class="flex flex-wrap items-center gap-3 p-4"><div class="min-w-0 grow">' +
        '<p class="text-sm font-semibold text-slate-900">' + App.esc(a.doctor_name) + '</p>' +
        '<p class="text-xs text-slate-500">' + App.esc(a.specialization) + ' · ' + App.fmt.hari(a.date) + ', ' + App.fmt.tanggal(a.date) + ' · ' + App.fmt.jam(a.start_time) + '–' + App.fmt.jam(a.end_time) + '</p>' +
        '<p class="mt-1 line-clamp-2 text-sm text-slate-600">' + App.esc(a.reason) + '</p></div>' +
        '<div class="flex items-center gap-2">' + Render.badge(a.status) + Render.rescheduledChip(a) +
        '<a href="' + url('pasien/janji-detail.php?id=' + a.id) + '" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Detail</a></div></li>';
    }).join('') + '</ul>' : App.stateBox('empty', 'Belum ada janji temu mendatang.', '<a class="mt-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white" href="' + url('pasien/cari-dokter.php') + '">Cari dokter</a>')
  });

  var latest = all.slice().sort(function (a, b) { return (b.date + b.start_time).localeCompare(a.date + a.start_time); })[0];
  document.getElementById('latest').innerHTML = Render.card({
    title: 'Status terbaru',
    body: latest ? '<div class="space-y-3"><div>' + Render.badge(latest.status) + '</div>' +
      '<p class="text-sm font-medium text-slate-800">' + App.esc(latest.code) + ' · ' + App.esc(latest.doctor_name) + '</p>' +
      '<p class="text-xs text-slate-500">' + App.fmt.tanggalJam(latest.created_at) + '</p>' +
      ((latest.cancellation_reason || latest.rejection_reason) ? '<p class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800">' + App.esc(latest.cancellation_reason || latest.rejection_reason) + '</p>' : '') +
      '<a href="' + url('pasien/janji-detail.php?id=' + latest.id) + '" class="inline-block rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Lihat detail &amp; riwayat</a></div>'
      : App.stateBox('empty', 'Belum ada aktivitas.')
  });
}).catch(function (err) {
  document.getElementById('stats').innerHTML = '';
  document.getElementById('upcoming').innerHTML = Render.card({ title: 'Janji temu mendatang', body: App.stateBox('error', err.message) });
  App.handleError(err);
});

</script>
</body>
</html>
