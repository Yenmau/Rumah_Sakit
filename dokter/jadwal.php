<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Jadwal Mingguan · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="jadwal">

<div id="page">

<div id="head"></div>
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
  <div id="rows"></div>
  <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
    <button type="button" id="save" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan jadwal</button>
    <button type="button" id="reset" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Muat ulang</button>
  </div>
  <p class="mt-3 text-xs text-slate-500">Validasi <code>end_time &gt; start_time</code> dijalankan di klien dan diulang di server.</p>
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
  title: 'Jadwal Praktik Mingguan',
  subtitle: 'Atur hari, jam mulai, jam selesai, dan status aktif. Jadwal ini menjadi sumber slot yang dilihat pasien.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Jadwal Mingguan' }]
});
var DAYS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
function paint(rows) {
  document.getElementById('rows').innerHTML = '<table class="min-w-full divide-y divide-slate-200 text-sm">' +
    '<caption class="sr-only">Pengaturan jadwal mingguan</caption><thead class="bg-slate-50"><tr>' +
    ['Hari','Jam mulai','Jam selesai','Aktif'].map(function (h) { return '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">' + h + '</th>'; }).join('') +
    '</tr></thead><tbody class="divide-y divide-slate-100">' + DAYS.map(function (d, i) {
      var r = rows.filter(function (x) { return Number(x.day) === i; })[0] || { start: '08:00', end: '12:00', active: false };
      return '<tr><th scope="row" class="px-4 py-3 text-left font-medium text-slate-800">' + d + '</th>' +
        '<td class="px-4 py-3"><input type="time" data-day="' + i + '" data-field="start" value="' + App.esc(r.start) + '" aria-label="Jam mulai ' + d + '" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></td>' +
        '<td class="px-4 py-3"><input type="time" data-day="' + i + '" data-field="end" value="' + App.esc(r.end) + '" aria-label="Jam selesai ' + d + '" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></td>' +
        '<td class="px-4 py-3"><label class="inline-flex items-center gap-2"><input type="checkbox" data-day="' + i + '" data-field="active" ' + (r.active ? 'checked' : '') + ' class="h-4 w-4 rounded border-slate-300" aria-label="Aktif pada hari ' + d + '"><span class="text-xs text-slate-600">' + (r.active ? 'Aktif' : 'Nonaktif') + '</span></label>' +
        '<p data-err-day="' + i + '" class="mt-1 hidden text-xs font-medium text-rose-600"></p></td></tr>';
    }).join('') + '</tbody></table>';
  document.querySelectorAll('[data-field="active"]').forEach(function (c) {
    c.addEventListener('change', function () { this.parentNode.querySelector('span').textContent = this.checked ? 'Aktif' : 'Nonaktif'; });
  });
}
document.getElementById('reset').addEventListener('click', function () { load(); });
function load() {
  document.getElementById('rows').innerHTML = App.stateBox('loading');
  App.api('/api/dokter/schedule').then(function (res) { paint(res.data); }).catch(function (err) { document.getElementById('rows').innerHTML = App.stateBox('error', err.message); });
}
load();
document.getElementById('save').addEventListener('click', function () {
  var slots = DAYS.map(function (_, i) {
    return {
      day: i,
      start: document.querySelector('[data-day="' + i + '"][data-field="start"]').value,
      end: document.querySelector('[data-day="' + i + '"][data-field="end"]').value,
      active: document.querySelector('[data-day="' + i + '"][data-field="active"]').checked
    };
  });
  document.querySelectorAll('[data-err-day]').forEach(function (e) { e.classList.add('hidden'); e.textContent = ''; });
  var bad = false;
  slots.forEach(function (s) {
    if (s.active && (!s.start || !s.end || s.end <= s.start)) {
      var e = document.querySelector('[data-err-day="' + s.day + '"]');
      if (e) { e.textContent = 'Jam selesai harus lebih besar dari jam mulai.'; e.classList.remove('hidden'); }
      bad = true;
    }
  });
  if (bad) { App.toast('Periksa jam pada hari yang ditandai.', 'error'); return; }
  var btn = this; btn.disabled = true; btn.textContent = 'Menyimpan…';
  App.api('/api/dokter/schedule', { method: 'PUT', body: { slots: slots } }).then(function () {
    App.toast('Jadwal mingguan disimpan.', 'success'); load();
  }).catch(function (err) { App.handleError(err); }).then(function () { btn.disabled = false; btn.textContent = 'Simpan jadwal'; });
});

</script>
</body>
</html>
