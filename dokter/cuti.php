<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Cuti & Jam Khusus · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="cuti">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2 space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div id="list"></div></div>
  </div>
  <div><div id="form-host"></div></div>
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
  title: 'Cuti & Jam Khusus',
  subtitle: 'Pengecualian per tanggal: cuti penuh atau jam khusus. Boleh lebih dari satu entri pada tanggal yang sama.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Cuti & Jam Khusus' }]
});
function load() {
  var host = document.getElementById('list');
  host.innerHTML = App.stateBox('loading');
  App.api('/api/dokter/leaves').then(function (res) {
    host.innerHTML = '';
    Render.table(host, {
      caption: 'Daftar cuti dan jam khusus',
      searchPlaceholder: 'Cari tanggal atau catatan…',
      searchText: function (r) { return r.date + ' ' + r.type + ' ' + (r.note || ''); },
      rows: res.data,
      filters: [{ key: 'type', label: 'Jenis', options: [{ value: 'cuti', label: 'Cuti penuh' }, { value: 'jam_khusus', label: 'Jam khusus' }] }],
      columns: [
        { key: 'date', label: 'Tanggal', render: function (r) { return App.fmt.hari(r.date) + ', ' + App.fmt.tanggal(r.date); } },
        { key: 'type', label: 'Jenis', render: function (r) { return r.type === 'cuti' ? Render.badge('leave') : Render.badge('custom_hours'); } },
        { key: 'start', label: 'Jam', render: function (r) { return r.type === 'cuti' ? '—' : App.fmt.jam(r.start) + '–' + App.fmt.jam(r.end); } },
        { key: 'note', label: 'Catatan' },
        { key: 'act', label: 'Aksi', sortable: false, render: function (r) { return '<button type="button" data-del="' + r.id + '" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">Hapus</button>'; } }
      ],
      emptyMessage: 'Belum ada pengecualian jadwal.'
    });
  }).catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
load();
document.getElementById('list').addEventListener('click', function (e) {
  var b = e.target.closest('[data-del]');
  if (!b) return;
  App.modal({ title: 'Hapus pengecualian ini?', message: 'Slot pada tanggal tersebut akan tersedia kembali bila jadwal mingguan aktif.', confirmLabel: 'Hapus', danger: true })
    .then(function (r) {
      if (!r.ok) return;
      App.api('/api/dokter/leaves/' + b.getAttribute('data-del'), { method: 'DELETE' })
        .then(function () { App.toast('Pengecualian dihapus.', 'success'); load(); }).catch(App.handleError);
    });
});
var type = 'cuti';
Render.form('#form-host', {
  action: 'actions/doCreateLeave.php',
  submitLabel: 'Tambah pengecualian',
  successMessage: 'Pengecualian jadwal ditambahkan.',
  fields: [
    { name: 'date', label: 'Tanggal', type: 'date', required: true, rules: [App.validate.required, App.validate.futureDate] },
    { name: 'type', label: 'Jenis', type: 'select', required: true, value: 'cuti', options: [{ value: 'cuti', label: 'Cuti penuh (sehari)' }, { value: 'jam_khusus', label: 'Jam khusus (sebagian hari)' }] },
    [
      { name: 'start', label: 'Jam mulai', type: 'time', value: '13:00', help: 'Hanya untuk jam khusus.' },
      { name: 'end', label: 'Jam selesai', type: 'time', value: '15:00', help: 'Hanya untuk jam khusus.' }
    ],
    { name: 'note', label: 'Catatan', optional: true, placeholder: 'Contoh: operasi terjadwal' }
  ],
  validate: function (v) {
    if (v.type === 'jam_khusus') {
      if (!v.start || !v.end) return 'Jam mulai dan selesai wajib diisi untuk jam khusus.';
      if (v.end <= v.start) return 'Jam selesai harus lebih besar dari jam mulai.';
    }
    return true;
  },
  onSubmit: function (v) {
    return App.api('/api/dokter/leaves', { method: 'POST', body: v }).then(function () { load(); });
  }
});
document.querySelector('[name="type"]').addEventListener('change', function () {
  var on = this.value === 'cuti';
  ['start','end'].forEach(function (n) {
    var i = document.querySelector('[name="' + n + '"]');
    i.disabled = on; i.classList.toggle('bg-slate-100', on);
  });
});

</script>
</body>
</html>
