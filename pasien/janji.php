<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Janji Temu Saya · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="janji">

<div id="page">

<div id="head"></div>
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div id="table-host"></div>
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
  title: 'Janji Temu Saya',
  subtitle: 'Filter berdasarkan status. Klik baris untuk melihat detail, riwayat, dan aksi.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Janji Temu' }],
  actions: '<a href="' + url('pasien/cari-dokter.php') + '" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">+ Janji baru</a>'
});
document.getElementById('table-host').innerHTML = App.stateBox('loading');
App.api('/api/pasien/appointments').then(function (res) {
  var host = document.getElementById('table-host');
  host.innerHTML = '';
  Render.table(host, {
    caption: 'Daftar janji temu pasien',
    searchPlaceholder: 'Cari kode, dokter, atau keluhan…',
    searchText: function (r) { return r.code + ' ' + r.doctor_name + ' ' + r.reason + ' ' + r.specialization; },
    rows: res.data,
    filters: [{ key: 'status', label: 'Status', options: [
      { value: 'pending', label: 'Menunggu' }, { value: 'confirmed', label: 'Diterima' },
      { value: 'rejected', label: 'Ditolak' }, { value: 'cancelled', label: 'Dibatalkan' },
      { value: 'completed', label: 'Selesai' }
    ] }],
    columns: [
      { key: 'code', label: 'Kode' },
      { key: 'doctor_name', label: 'Dokter', render: function (r) { return '<span class="font-medium text-slate-900">' + App.esc(r.doctor_name) + '</span><br><span class="text-xs text-slate-500">' + App.esc(r.specialization) + '</span>'; } },
      { key: 'date', label: 'Jadwal', render: function (r) { return App.fmt.tanggal(r.date) + '<br><span class="text-xs text-slate-500">' + App.fmt.jam(r.start_time) + '–' + App.fmt.jam(r.end_time) + '</span>'; } },
      { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status) + Render.rescheduledChip(r); } },
      { key: 'reason', label: 'Keluhan', render: function (r) { return '<span class="line-clamp-2">' + App.esc(r.reason) + '</span>'; } },
      { key: 'act', label: 'Aksi', sortable: false, render: function (r) {
          var cancelable = ['pending','confirmed'].indexOf(r.status) > -1;
          return '<div class="flex gap-2"><a href="' + url('pasien/janji-detail.php?id=' + r.id) + '" data-act="detail" data-id="' + r.id + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Detail</a>' +
            (cancelable ? '<button type="button" data-act="cancel" data-id="' + r.id + '" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">Batalkan</button>' : '') + '</div>';
        } }
    ],
    onRow: function (r) { location.href = url('pasien/janji-detail.php?id=' + r.id); }
  });

  host.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (!b) return;
    if (b.getAttribute('data-act') === 'detail') return;
    e.preventDefault(); e.stopPropagation();
    App.modal({ title: 'Batalkan janji temu?', message: 'Tindakan ini tidak dapat dibatalkan. Alasan akan tercatat dan tidak boleh kosong.', reasonRequired: true, confirmLabel: 'Ya, batalkan', danger: true })
      .then(function (r) {
        if (!r.ok) return;
        App.api('/api/pasien/appointments/' + b.getAttribute('data-id') + '/cancel', { method: 'POST', body: { reason: r.reason } })
          .then(function () { App.toast('Janji temu dibatalkan.', 'success'); location.reload(); })
          .catch(function (err) { App.handleError(err); });
      });
  }, true);
}).catch(function (err) {
  document.getElementById('table-host').innerHTML = App.stateBox('error', err.message);
  App.handleError(err);
});

</script>
</body>
</html>
