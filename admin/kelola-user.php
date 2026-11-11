<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Kelola User · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="admin" data-active="users">

<div id="page">

<div id="head"></div>
<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="note">
  Perubahan status dan pembukaan kunci akun tercatat di audit log beserta IP pelaku.
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
  title: 'Kelola User',
  subtitle: 'Lihat akun, ubah status (active/inactive/suspended), dan buka kunci akun yang terkunci.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Kelola User' }]
});
var host = document.getElementById('table-host');
function load() {
  host.innerHTML = App.stateBox('loading');
  App.api('/api/admin/users').then(function (res) {
    host.innerHTML = '';
    Render.table(host, {
      caption: 'Daftar seluruh user',
      searchPlaceholder: 'Cari nama atau email…',
      searchText: function (r) { return r.name + ' ' + r.email + ' ' + r.role; },
      rows: res.data,
      filters: [
        { key: 'role', label: 'Peran', options: [{ value: 'pasien', label: 'Pasien' }, { value: 'dokter', label: 'Dokter' }, { value: 'admin', label: 'Admin' }] },
        { key: 'status', label: 'Status', options: [{ value: 'active', label: 'Aktif' }, { value: 'inactive', label: 'Nonaktif' }, { value: 'suspended', label: 'Ditangguhkan' }] }
      ],
      columns: [
        { key: 'name', label: 'Nama', render: function (r) { return '<span class="font-medium text-slate-900">' + App.esc(r.name) + '</span><br><span class="text-xs text-slate-500">' + App.esc(r.email) + '</span>'; } },
        { key: 'role', label: 'Peran', render: function (r) { return '<span class="capitalize">' + App.esc(r.role) + '</span>'; } },
        { key: 'status', label: 'Status', render: function (r) { return Render.badge(r.status); } },
        { key: 'locked_until', label: 'Kunci', render: function (r) {
            var locked = r.locked_until && new Date(r.locked_until).getTime() > Date.now();
            return locked ? Render.badge('locked', 'Terkunci s/d ' + App.fmt.jam(new Date(r.locked_until).toTimeString().slice(0, 5))) : '<span class="text-xs text-slate-400">Tidak terkunci</span>';
          } },
        { key: 'last_login', label: 'Login terakhir', render: function (r) { return r.last_login ? App.fmt.tanggalJam(r.last_login) : '-'; } },
        { key: 'act', label: 'Aksi', sortable: false, render: function (r) {
            var locked = r.locked_until && new Date(r.locked_until).getTime() > Date.now();
            return '<div class="flex flex-wrap gap-2">' +
              '<select data-status="' + r.id + '" aria-label="Ubah status ' + App.esc(r.name) + '" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs">' +
              ['active','inactive','suspended'].map(function (s) { return '<option value="' + s + '"' + (r.status === s ? ' selected' : '') + '>' + ({ active: 'Aktif', inactive: 'Nonaktif', suspended: 'Ditangguhkan' })[s] + '</option>'; }).join('') + '</select>' +
              (locked ? '<button type="button" data-unlock="' + r.id + '" class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Buka kunci</button>' : '') +
              '</div>';
          } }
      ]
    });
  }).catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
load();
host.addEventListener('change', function (e) {
  var sel = e.target.closest('[data-status]');
  if (!sel) return;
  var id = sel.getAttribute('data-status'), status = sel.value;
  App.modal({ title: 'Ubah status akun?', message: 'Status baru: ' + status + '. User yang ditangguhkan tidak dapat masuk meski password benar.', confirmLabel: 'Ya, ubah' })
    .then(function (r) {
      if (!r.ok) { load(); return; }
      App.api('/api/admin/users/' + id + '/status', { method: 'PUT', body: { status: status } })
        .then(function () { App.toast('Status akun diperbarui.', 'success'); load(); })
        .catch(function (err) { App.handleError(err); load(); });
    });
});
host.addEventListener('click', function (e) {
  var b = e.target.closest('[data-unlock]');
  if (!b) return;
  App.api('/api/admin/users/' + b.getAttribute('data-unlock') + '/unlock', { method: 'POST' })
    .then(function () { App.toast('Kunci akun dibuka.', 'success'); load(); }).catch(App.handleError);
});

</script>
</body>
</html>
