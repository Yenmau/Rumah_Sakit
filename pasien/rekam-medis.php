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
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-800" role="note">
  <strong>Catatan privasi:</strong> hanya rekam medis berstatus <em>final</em> yang ditampilkan. Draf dokter tidak dapat diakses pasien.
</div>
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
  title: 'Rekam Medis Saya',
  subtitle: 'Diagnosis, catatan dokter, dan resep dari kunjungan yang sudah difinalisasi.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis' }]
});
document.getElementById('table-host').innerHTML = App.stateBox('loading');
App.api('/api/pasien/records').then(function (res) {
  var host = document.getElementById('table-host');
  host.innerHTML = '';
  Render.table(host, {
    caption: 'Daftar rekam medis pasien',
    searchPlaceholder: 'Cari diagnosis atau dokter…',
    searchText: function (r) { return r.diagnosis + ' ' + r.doctor_name + ' ' + r.date; },
    rows: res.data,
    columns: [
      { key: 'date', label: 'Tanggal', render: function (r) { return App.fmt.tanggal(r.date); } },
      { key: 'doctor_name', label: 'Dokter' },
      { key: 'diagnosis', label: 'Diagnosis' },
      { key: 'prescriptions', label: 'Resep', sortable: false, render: function (r) { return '<span class="text-xs text-slate-600">' + (r.prescriptions || []).length + ' item obat</span>'; } },
      { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status); } },
      { key: 'act', label: 'Aksi', sortable: false, render: function (r) { return '<a href="' + url('pasien/rekam-medis-detail.php?id=' + r.id) + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Lihat</a>'; } }
    ],
    onRow: function (r) { location.href = url('pasien/rekam-medis-detail.php?id=' + r.id); }
  });
}).catch(function (err) { document.getElementById('table-host').innerHTML = App.stateBox('error', err.message); });

</script>
</body>
</html>
