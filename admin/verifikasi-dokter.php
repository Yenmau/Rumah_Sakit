<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Verifikasi Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="admin" data-active="verifikasi">

<div id="page">

<div id="head"></div>
<div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-800" role="note">
  Periksa kecocokan nomor SIP dengan berkas fisik sebelum menyetujui. Setiap keputusan tercatat di audit log.
</div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2"><div class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div id="table-host"></div></div></div>
  <div><div id="detail"></div></div>
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
  title: 'Verifikasi Dokter',
  subtitle: 'Setujui atau tolak pengajuan dokter baru. Penolakan wajib memakai alasan.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Verifikasi Dokter' }]
});
var current = null;
function renderDetail(d) {
  if (!d) { document.getElementById('detail').innerHTML = Render.card({ title: 'Detail pengajuan', body: App.stateBox('empty', 'Pilih satu baris pada tabel untuk meninjau pengajuan.') }); return; }
  document.getElementById('detail').innerHTML = Render.card({
    title: d.name,
    subtitle: d.email,
    actions: Render.badge(d.verification),
    body: Render.describe([
      { label: 'Spesialisasi', value: d.specialization },
      { label: 'Nomor SIP', value: d.license },
      { label: 'Pengalaman', value: d.experience_years + ' tahun' },
      { label: 'Biaya konsultasi', value: App.fmt.rupiah(d.fee) },
    ], 'grid-cols-1'),
    footer: d.verification === 'pending' ?
      '<div class="flex flex-wrap gap-2">' +
      '<button type="button" id="btn-approve" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Setujui</button>' +
      '<button type="button" id="btn-reject" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Tolak</button></div>'
      : '<p class="text-xs text-slate-500">Pengajuan ini sudah diputuskan (' + App.esc(d.verification) + ').</p>'
  });
  var ap = document.getElementById('btn-approve'), rj = document.getElementById('btn-reject');
  if (ap) ap.addEventListener('click', function () {
    App.modal({ title: 'Setujui dokter ini?', message: 'Setelah disetujui, profil dokter langsung tampil ke pasien dan dapat menerima janji temu.', confirmLabel: 'Ya, setujui' })
      .then(function (r) { if (r.ok) decide('verified', null); });
  });
  if (rj) rj.addEventListener('click', function () {
    App.modal({ title: 'Tolak pengajuan dokter', message: 'Alasan penolakan wajib diisi dan akan dikirim ke email dokter.', reasonRequired: true, confirmLabel: 'Tolak pengajuan', danger: true })
      .then(function (r) { if (r.ok) decide('rejected', r.reason); });
  });
}
function decide(decision, note) {
  App.api('/api/admin/doctors/' + current.id + '/verify', { method: 'POST', body: { decision: decision, note: note } })
    .then(function () { App.toast(decision === 'verified' ? 'Dokter diverifikasi.' : 'Pengajuan ditolak.', 'success'); load(); })
    .catch(App.handleError);
}
function load() {
  var host = document.getElementById('table-host');
  host.innerHTML = App.stateBox('loading');
  App.api('/api/admin/doctors/pending').then(function (res) {
    host.innerHTML = '';
    Render.table(host, {
      caption: 'Daftar dokter menunggu verifikasi',
      searchPlaceholder: 'Cari nama, spesialisasi, atau SIP…',
      searchText: function (r) { return r.name + ' ' + r.specialization + ' ' + r.license; },
      rows: res.data,
      pageSize: 5,
      columns: [
        { key: 'name', label: 'Dokter' },
        { key: 'specialization', label: 'Spesialisasi' },
        { key: 'license', label: 'Nomor SIP' },
        { key: 'experience_years', label: 'Pengalaman', render: function (r) { return r.experience_years + ' th' } },
        { key: 'fee', label: 'Biaya', render: function (r) { return App.fmt.rupiah(r.fee); } }
      ],
      onRow: function (r) { current = r; renderDetail(r); },
      emptyMessage: 'Tidak ada pengajuan yang menunggu verifikasi.'
    });
    renderDetail(null);
  }).catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
load();

</script>
</body>
</html>
