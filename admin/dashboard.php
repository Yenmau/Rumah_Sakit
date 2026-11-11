<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Dashboard Admin · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="admin" data-active="dashboard">

<div id="page">

<div id="head"></div>
<div id="stats" class="mb-5"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2"><div id="pending"></div></div>
  <div class="space-y-5"><div id="audit"></div></div>
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
  title: 'Dashboard Administrator',
  subtitle: 'Ringkasan dokter menunggu verifikasi, user aktif, dan aktivitas terbaru.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Dashboard Admin' }],
  actions: '<a href="' + url('admin/verifikasi-dokter.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Verifikasi dokter</a>'
});
document.getElementById('stats').innerHTML = App.stateBox('loading');
Promise.all([App.api('/api/admin/summary'), App.api('/api/admin/doctors/pending'), App.api('/api/admin/audit-logs')]).then(function (r) {
  var s = r[0].data, pend = r[1].data, logs = r[2].data;
  document.getElementById('stats').innerHTML = Render.statCards([
    { label: 'Dokter menunggu', value: s.pending_doctors, hint: 'Perlu keputusan admin' },
    { label: 'User aktif', value: s.active_users, hint: s.locked_users + ' akun terkunci' },
    { label: 'Janji temu hari ini', value: s.appointments_today },
    { label: 'Login gagal', value: s.failed_logins_24h, hint: 'Indikasi percobaan brute force' }
  ]);
  document.getElementById('pending').innerHTML = Render.card({
    title: 'Dokter menunggu verifikasi',
    subtitle: pend.length + ' pengajuan',
    padded: false,
    body: pend.length ? '<ul class="divide-y divide-slate-100">' + pend.map(function (d) {
      return '<li class="flex flex-wrap items-center gap-3 p-4"><div class="min-w-0 grow">' +
        '<p class="text-sm font-semibold text-slate-900">' + App.esc(d.name) + '</p>' +
        '<p class="text-xs text-slate-500">' + App.esc(d.specialization) + ' · SIP ' + App.esc(d.license) + ' · ' + App.esc(d.experience_years) + ' tahun</p></div>' +
        '<span class="text-sm text-slate-700">' + App.fmt.rupiah(d.fee) + '</span>' +
        '<a href="' + url('admin/verifikasi-dokter.php') + '" class="rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-800">Tinjau</a></li>';
    }).join('') + '</ul>' : App.stateBox('empty', 'Tidak ada pengajuan menunggu verifikasi.')
  });
  document.getElementById('audit').innerHTML = Render.card({
    title: 'Aktivitas terbaru',
    subtitle: '10 entri terakhir',
    padded: false,
    body: '<ul class="divide-y divide-slate-100 text-sm">' + logs.slice(0, 10).map(function (l) {
      return '<li class="flex items-start gap-3 p-3"><span class="mt-0.5">' + (l.result === 'success' ? '✅' : '⚠️') + '</span>' +
        '<div class="min-w-0"><p class="truncate font-medium text-slate-800">' + App.esc(l.action) + '</p>' +
        '<p class="truncate text-xs text-slate-500">' + App.esc(l.user) + ' · ' + App.fmt.timeAgo(l.at) + '</p></div></li>';
    }).join('') + '</ul>',
    footer: '<a href="' + url('admin/audit-log.php') + '" class="text-sm font-medium text-brand-700 hover:underline">Buka audit log lengkap →</a>'
  });
}).catch(function (err) { document.getElementById('stats').innerHTML = App.stateBox('error', err.message); });

</script>
</body>
</html>
