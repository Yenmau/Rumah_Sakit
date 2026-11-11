<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Audit Log · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="admin" data-active="audit">

<div id="page">

<div id="head"></div>
<div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
  <div class="grid gap-3 sm:grid-cols-4">
    <div><label for="f-user" class="block text-xs font-medium text-slate-600">User</label>
      <input id="f-user" type="search" placeholder="Nama / email…" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
    <div><label for="f-action" class="block text-xs font-medium text-slate-600">Action</label>
      <select id="f-action" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua</option></select></div>
    <div><label for="f-result" class="block text-xs font-medium text-slate-600">Hasil</label>
      <select id="f-result" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option><option value="success">Sukses</option><option value="failure">Gagal</option></select></div>
    <div><label for="f-date" class="block text-xs font-medium text-slate-600">Tanggal</label>
      <input id="f-date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
  </div>
  <div class="mt-3 flex gap-2">
    <button type="button" id="btn-apply" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Terapkan filter</button>
    <button type="button" id="btn-clear" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Bersihkan</button>
    <button type="button" id="btn-csv" class="ml-auto rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Salin ringkasan</button>
  </div>
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
  title: 'Audit Log',
  subtitle: 'Filter berdasarkan user, action, tanggal, dan hasil. Data dibagi per halaman.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Audit Log' }]
});
var host = document.getElementById('table-host'), all = [], lastFiltered = [];
function buildQuery() {
  var p = [];
  if (document.getElementById('f-user').value) p.push('user=' + encodeURIComponent(document.getElementById('f-user').value));
  if (document.getElementById('f-action').value) p.push('action=' + encodeURIComponent(document.getElementById('f-action').value));
  if (document.getElementById('f-result').value) p.push('result=' + encodeURIComponent(document.getElementById('f-result').value));
  if (document.getElementById('f-date').value) p.push('date=' + encodeURIComponent(document.getElementById('f-date').value));
  return p.length ? '?' + p.join('&') : '';
}
function load() {
  host.innerHTML = App.stateBox('loading');
  var q = buildQuery();
  App.api('/api/admin/audit-logs' + q).then(function (res) {
    lastFiltered = res.data;
    host.innerHTML = '';
    Render.table(host, {
      caption: 'Audit log sistem',
      searchPlaceholder: 'Cari action, user, atau resource…',
      searchText: function (r) { return r.action + ' ' + r.user + ' ' + r.resource + ' ' + r.ip; },
      rows: res.data,
      pageSize: 10,
      defaultSort: 'at', defaultDir: 'desc',
      columns: [
        { key: 'at', label: 'Waktu', render: function (r) { return App.fmt.tanggalJam(r.at) + '<br><span class="text-xs text-slate-400">' + App.fmt.timeAgo(r.at) + '</span>'; } },
        { key: 'user', label: 'User', render: function (r) { return App.esc(r.user) + '<br><span class="text-xs capitalize text-slate-500">' + App.esc(r.role) + '</span>'; } },
        { key: 'action', label: 'Action', render: function (r) { return '<span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">' + App.esc(r.action) + '</span>'; } },
        { key: 'resource', label: 'Resource' },
        { key: 'ip', label: 'IP' },
        { key: 'result', label: 'Hasil', render: function (r) { return Render.badge(r.result); } }
      ],
      emptyMessage: 'Tidak ada log yang cocok dengan filter tersebut.'
    });
  }).catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
App.api('/api/admin/audit-logs').then(function (res) {
  var actions = [];
  res.data.forEach(function (l) { if (actions.indexOf(l.action) === -1) actions.push(l.action); });
  document.getElementById('f-action').innerHTML = '<option value="">Semua</option>' + actions.sort().map(function (a) {
    return '<option value="' + App.esc(a) + '">' + App.esc(a) + '</option>';
  }).join('');
  load();
});
document.getElementById('btn-apply').addEventListener('click', load);
document.getElementById('btn-clear').addEventListener('click', function () {
  ['f-user','f-action','f-result','f-date'].forEach(function (id) { document.getElementById(id).value = ''; });
  load();
});
document.getElementById('btn-csv').addEventListener('click', function () {
  var txt = lastFiltered.map(function (l) { return [l.at, l.user, l.role, l.action, l.resource, l.result, l.ip].join('\t'); }).join('\n');
  navigator.clipboard && navigator.clipboard.writeText(txt).then(function () { App.toast('Ringkasan audit log disalin (' + lastFiltered.length + ' baris).', 'success'); },
    function () { App.toast('Gagal menyalin ke clipboard.', 'error'); });
});

</script>
</body>
</html>
