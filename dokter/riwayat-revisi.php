<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Riwayat Revisi · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div id="body"></div>
</div>

<script>window.APP_BASE = "..";</script>
<script src="../assets/js/config.js"></script>
<script src="../assets/js/mock.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/ui.js"></script>
<script src="../assets/js/layout.js"></script>

<script>

document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Riwayat Revisi Rekam Medis',
  subtitle: 'Setiap revisi menyimpan alasan, waktu, pelaku, serta diagnosis sebelum dan sesudah.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis', href: url('dokter/rekam-medis.php') }, { label: 'Riwayat Revisi' }]
});
var id = qs('id'), host = document.getElementById('body');
if (!id) host.innerHTML = Render.card({ body: App.stateBox('error', 'Parameter id rekam medis tidak ada.') });
else {
  host.innerHTML = Render.card({ body: App.stateBox('loading') });
  App.api('/api/dokter/records/' + id + '/revisions').then(function (res) {
    var r = res.record, revs = res.data || [];
    host.innerHTML = Render.card({
      title: 'Rekam medis ' + (r ? App.fmt.tanggal(r.date) : ''),
      subtitle: r ? r.patient_name + ' · ' + r.doctor_name : '',
      actions: '<a href="' + url('dokter/rekam-medis-edit.php?id=' + id) + '" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Kembali ke editor</a>',
      body: Render.describe([
        { label: 'Diagnosis saat ini', html: '<p class="font-medium text-slate-900">' + App.esc(r ? r.diagnosis : '-') + '</p>' },
        { label: 'Status', html: Render.badge(r ? r.status : '-') },
        { label: 'Jumlah revisi', value: (revs || []).length }
      ])
    }) + '<div class="mt-5">' + Render.card({
      title: 'Linimasa revisi',
      padded: true,
      body: revs.length ? '<ol class="space-y-4 border-l border-slate-200 pl-5">' + revs.map(function (v) {
        return '<li><p class="text-sm font-semibold text-slate-800">' + App.esc(v.reason) + '</p>' +
          '<p class="text-xs text-slate-500">' + App.fmt.tanggalJam(v.at) + ' · ' + App.esc(v.by) + '</p>' +
          '<dl class="mt-2 grid gap-2 sm:grid-cols-2">' +
          '<div class="rounded-lg bg-rose-50 p-2"><dt class="text-xs font-medium text-rose-700">Sebelum</dt><dd class="text-sm text-rose-900">' + App.esc(v.before) + '</dd></div>' +
          '<div class="rounded-lg bg-emerald-50 p-2"><dt class="text-xs font-medium text-emerald-700">Sesudah</dt><dd class="text-sm text-emerald-900">' + App.esc(v.after) + '</dd></div>' +
          '</dl></li>';
      }).join('') + '</ol>' : App.stateBox('empty', 'Belum ada revisi pada rekam medis ini.')
    }) + '</div>';
  }).catch(function (err) { host.innerHTML = Render.card({ body: App.stateBox('error', err.message) }); });
}

</script>
</body>
</html>
