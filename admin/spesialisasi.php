<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Spesialisasi · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="admin" data-active="spesialisasi">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2"><div class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div id="table-host"></div></div></div>
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
  title: 'Kelola Spesialisasi',
  subtitle: 'Tambah atau ubah spesialisasi. Nama spesialisasi harus unik.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Spesialisasi' }]
});
var host = document.getElementById('table-host'), editing = null;
function load() {
  host.innerHTML = App.stateBox('loading');
  Promise.all([App.api('/api/admin/specializations'), App.api('/api/admin/doctors')]).then(function (r) {
    var spes = r[0].data, dok = r[1].data;

    spes.forEach(function (x) { x.jumlah_dokter = dok.filter(function (d) { return d.specialization_id === x.id; }).length; });
    host.innerHTML = '';
    Render.table(host, {
      caption: 'Daftar spesialisasi',
      searchPlaceholder: 'Cari nama atau deskripsi…',
      searchText: function (r) { return r.name + ' ' + r.description; },
      rows: spes,
      pageSize: 6,
      columns: [
        { key: 'name', label: 'Nama spesialisasi' },
        { key: 'description', label: 'Deskripsi' },
        { key: 'jumlah_dokter', label: 'Dokter', render: function (r) { return '<span class="text-slate-700">' + App.esc(r.jumlah_dokter) + ' dokter</span>'; } },
        { key: 'act', label: 'Aksi', sortable: false, render: function (r) {
            return '<button type="button" data-edit="' + r.id + '" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Ubah</button>';
          } }
      ]
    });
  }).catch(function (err) { host.innerHTML = App.stateBox('error', err.message); });
}
load();
function paintForm(data) {
  editing = data;
  document.getElementById('form-host').innerHTML = '';
  Render.form('#form-host', {
    action: 'actions/doSaveSpecialization.php',
    submitLabel: data ? 'Simpan perubahan' : 'Tambah spesialisasi',
    successMessage: data ? 'Spesialisasi diperbarui.' : 'Spesialisasi ditambahkan.',
    secondary: data ? '<button type="button" id="btn-cancel" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>' : '',
    fields: [
      { name: 'name', label: 'Nama spesialisasi', required: true, value: data ? data.name : '', rules: [App.validate.required] },
      { name: 'description', label: 'Deskripsi', type: 'textarea', rows: 3, optional: true, value: data ? data.description : '' }
    ],
    onSubmit: function (v) {
      return data
        ? App.api('/api/admin/specializations/' + data.id, { method: 'PUT', body: v }).then(function () { paintForm(null); load(); })
        : App.api('/api/admin/specializations', { method: 'POST', body: v }).then(function () { paintForm(null); load(); });
    }
  });
  var c = document.getElementById('btn-cancel');
  if (c) c.addEventListener('click', function () { paintForm(null); });
}
paintForm(null);
host.addEventListener('click', function (e) {
  var ed = e.target.closest('[data-edit]');
  if (ed) {
    App.api('/api/admin/specializations').then(function (res) {
      var s = res.data.filter(function (x) { return String(x.id) === ed.getAttribute('data-edit'); })[0];
      paintForm(s);
    });
  }
});

</script>
</body>
</html>
