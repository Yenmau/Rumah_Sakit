<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Kelola Janji Temu · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="janji">

<div id="page">

<div id="head"></div>
<div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Filter cepat">
  <button type="button" data-quick="" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">Semua</button>
  <button type="button" data-quick="pending" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">Menunggu</button>
  <button type="button" data-quick="confirmed" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">Diterima</button>
  <button type="button" data-quick="today" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">Hari ini</button>
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
  title: 'Kelola Janji Temu',
  subtitle: 'Terima, tolak (alasan wajib), atau tandai selesai. Setiap aksi masuk audit log.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Janji Temu' }]
});
var host = document.getElementById('table-host'), tbl = null, rowsData = [];
function render() {
  host.innerHTML = '';
  tbl = Render.table(host, {
    caption: 'Daftar janji temu dokter',
    searchPlaceholder: 'Cari pasien atau keluhan…',
    searchText: function (r) { return r.patient_name + ' ' + r.reason + ' ' + r.code; },
    rows: rowsData,
    filters: [{ key: 'status', label: 'Status', options: [
      { value: 'pending', label: 'Menunggu' }, { value: 'confirmed', label: 'Diterima' },
      { value: 'rejected', label: 'Ditolak' }, { value: 'cancelled', label: 'Dibatalkan' },
      { value: 'completed', label: 'Selesai' }
    ] }],
    columns: [
      { key: 'date', label: 'Jadwal', render: function (r) { return App.fmt.tanggal(r.date) + '<br><span class="text-xs text-slate-500">' + App.fmt.jam(r.start_time) + '–' + App.fmt.jam(r.end_time) + '</span>'; } },
      { key: 'patient_name', label: 'Pasien', render: function (r) { return '<span class="font-medium text-slate-900">' + App.esc(r.patient_name) + '</span><br><span class="text-xs text-slate-500">' + App.esc(r.code) + '</span>'; } },
      { key: 'reason', label: 'Keluhan' },
      { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status) + Render.rescheduledChip(r); } },
      { key: 'act', label: 'Aksi', sortable: false, render: function (r) {
        var b = [];
        if (r.status === 'pending') {
          b.push('<button type="button" data-act="accept" data-id="' + r.id + '" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Terima</button>');
          b.push('<button type="button" data-act="reject" data-id="' + r.id + '" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">Tolak</button>');
        }
        if (r.status === 'confirmed') {
          b.push('<button type="button" data-act="complete" data-id="' + r.id + '" class="rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-800">Tandai selesai</button>');
          b.push('<a href="' + url('dokter/rekam-medis-baru.php?appointment_id=' + r.id) + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Rekam medis</a>');
        }
        if (r.status === 'completed') b.push('<a href="' + url('dokter/rekam-medis.php') + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Lihat rekam medis</a>');
        return '<div class="flex flex-wrap gap-2">' + b.join('') + '</div>';
      } }
    ],
    onRow: function (r) { location.href = url('pasien/janji-detail.php?id=' + r.id); }
  });
}
function load(query) {
  host.innerHTML = App.stateBox('loading');
  App.api('/api/dokter/appointments' + (query || '')).then(function (res) { rowsData = res.data; render(); })
    .catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
load();

document.querySelectorAll('[data-quick]').forEach(function (b) {
  b.addEventListener('click', function () {
    document.querySelectorAll('[data-quick]').forEach(function (x) { x.className = 'rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium'; x.setAttribute('aria-pressed', 'false'); });
    this.className = 'rounded-lg border border-brand-600 bg-brand-50 px-3 py-2 text-sm font-medium text-brand-800';
    this.setAttribute('aria-pressed', 'true');
    var q = this.getAttribute('data-quick');
    if (q === 'today') load('?date=' + new Date().toISOString().slice(0, 10));
    else if (q === 'pending' || q === 'confirmed') load('?status=' + q);
    else load();
  });
});

host.addEventListener('click', function (e) {
  var b = e.target.closest('[data-act]');
  if (!b) return;
  e.preventDefault(); e.stopPropagation();
  var id = b.getAttribute('data-id'), act = b.getAttribute('data-act');
  function after(msg) { App.toast(msg, 'success'); load(); }
  if (act === 'accept') {
    App.modal({ title: 'Terima janji temu?', message: 'Pasien akan melihat status diterima beserta catatan Anda.', confirmLabel: 'Ya, terima' })
      .then(function (r) { if (r.ok) App.api('/api/dokter/appointments/' + id + '/accept', { method: 'POST' }).then(function () { after('Janji diterima.'); }).catch(App.handleError); });
  } else if (act === 'reject') {
    App.modal({ title: 'Tolak janji temu', message: 'Alasan penolakan wajib diisi dan akan ditampilkan ke pasien.', reasonRequired: true, confirmLabel: 'Tolak janji', danger: true })
      .then(function (r) { if (r.ok) App.api('/api/dokter/appointments/' + id + '/reject', { method: 'POST', body: { reason: r.reason } }).then(function () { after('Janji ditolak.'); }).catch(App.handleError); });
  } else if (act === 'complete') {
    App.modal({ title: 'Tandai janji selesai?', message: 'Pastikan rekam medis sudah difinalisasi sebelum menutup kunjungan.', confirmLabel: 'Ya, selesai' })
      .then(function (r) { if (r.ok) App.api('/api/dokter/appointments/' + id + '/complete', { method: 'POST' }).then(function () { after('Janji ditandai selesai.'); }).catch(App.handleError); });
  }
}, true);

</script>
</body>
</html>
