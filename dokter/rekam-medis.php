<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Rekam Medis · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div id="table-host"></div></div>
</div>

<script>window.APP_BASE = "..";</script>
<script src="../assets/js/config.js"></script>
<script src="../assets/js/mock.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/ui.js"></script>
<script src="../assets/js/layout.js"></script>

<script>

document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Rekam Medis Pasien',
  subtitle: 'Draf dapat disunting bebas. Rekam medis final hanya boleh diubah dengan alasan revisi.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis' }],
  actions: '<a href="' + url('dokter/rekam-medis-baru.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">+ Rekam medis baru</a>'
});
document.getElementById('table-host').innerHTML = App.stateBox('loading');
App.api('/api/dokter/records').then(function (res) {
  var host = document.getElementById('table-host');
  host.innerHTML = '';
  Render.table(host, {
    caption: 'Daftar rekam medis dokter',
    searchPlaceholder: 'Cari pasien atau diagnosis…',
    searchText: function (r) { return r.patient_name + ' ' + r.diagnosis + ' ' + r.date; },
    rows: res.data,
    filters: [{ key: 'status', label: 'Status', options: [{ value: 'draft', label: 'Draf' }, { value: 'finalized', label: 'Final' }, { value: 'revised', label: 'Direvisi' }] }],
    columns: [
      { key: 'date', label: 'Tanggal', render: function (r) { return App.fmt.tanggal(r.date); } },
      { key: 'patient_name', label: 'Pasien' },
      { key: 'diagnosis', label: 'Diagnosis', render: function (r) { return r.diagnosis ? App.esc(r.diagnosis) : '<em class="text-slate-400">Belum diisi</em>'; } },
      { key: 'prescriptions', label: 'Resep', sortable: false, render: function (r) { return '<span class="text-xs text-slate-600">' + (r.prescriptions || []).length + ' item</span>'; } },
      { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status); } },
      { key: 'act', label: 'Aksi', sortable: false, render: function (r) {
        return '<div class="flex flex-wrap gap-2">' +
          '<a href="' + url('dokter/rekam-medis-edit.php?id=' + r.id) + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">' + (r.status === 'draft' ? 'Lanjutkan' : 'Edit (revisi)') + '</a>' +
          (r.revisions && r.revisions.length ? '<a href="' + url('dokter/riwayat-revisi.php?id=' + r.id) + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Riwayat (' + r.revisions.length + ')</a>' : '') +
          '</div>';
      } }
    ],
    onRow: function (r) { location.href = url('dokter/rekam-medis-edit.php?id=' + r.id); }
  });
}).catch(function (err) { document.getElementById('table-host').innerHTML = App.stateBox('error', err.message); });

</script>
</body>
</html>
