<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Cari Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="cari-dokter">

<div id="page">

<div id="head"></div>
<div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4">
  <div class="grid gap-3 sm:grid-cols-3">
    <div class="sm:col-span-2">
      <label for="q" class="block text-xs font-medium text-slate-600">Kata kunci</label>
      <input id="q" type="search" placeholder="Nama dokter atau spesialisasi…" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
    </div>
    <div>
      <label for="spec" class="block text-xs font-medium text-slate-600">Spesialisasi</label>
      <select id="spec" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><option value="">Semua spesialisasi</option></select>
    </div>
  </div>
</div>
<div id="results"></div>
</div>

<script>window.APP_BASE = "..";</script>
<script src="../assets/js/config.js"></script>
<script src="../assets/js/mock.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/ui.js"></script>
<script src="../assets/js/layout.js"></script>

<script>

document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Cari Dokter',
  subtitle: 'Hanya dokter terverifikasi dan berstatus aktif yang ditampilkan.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Cari Dokter' }]
});

var all = [];
function paint() {
  var q = document.getElementById('q').value.toLowerCase();
  var sp = document.getElementById('spec').value;
  var rows = all.filter(function (d) {
    var okQ = !q || (d.name + ' ' + d.specialization).toLowerCase().indexOf(q) > -1;
    var okS = !sp || String(d.specialization_id) === sp;
    return okQ && okS;
  });
  var host = document.getElementById('results');
  if (!rows.length) { host.innerHTML = Render.card({ body: App.stateBox('empty', 'Tidak ada dokter yang cocok. Ubah kata kunci atau spesialisasi.') }); return; }
  host.innerHTML = '<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">' + rows.map(function (d) {
    return '<article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">' +
      '<div class="flex items-start gap-3"><span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-lg">🩺</span>' +
      '<div class="min-w-0"><h3 class="text-sm font-semibold text-slate-900">' + App.esc(d.name) + '</h3>' +
      '<p class="text-xs text-slate-500">' + App.esc(d.specialization) + '</p>' +
      '<div class="mt-1">' + Render.badge('verified') + '</div></div></div>' +
      '<dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">' +
      '<div><dt class="font-medium text-slate-500">Pengalaman</dt><dd>' + App.esc(d.experience_years) + ' tahun</dd></div>' +
      '<div><dt class="font-medium text-slate-500">Biaya konsultasi</dt><dd class="font-semibold text-slate-800">' + App.fmt.rupiah(d.fee) + '</dd></div></dl>' +
      '<div class="mt-4 flex gap-2">' +
      '<a href="' + url('pasien/dokter-detail.php?id=' + d.id) + '" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Profil</a>' +
      '<a href="' + url('pasien/booking.php?doctor_id=' + d.id) + '" class="rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-800">Pilih jadwal</a>' +
      '</div></article>';
  }).join('') + '</div>';
}
Render.table;
document.getElementById('q').addEventListener('input', paint);
document.getElementById('spec').addEventListener('change', paint);
document.getElementById('results').innerHTML = Render.card({ body: App.stateBox('loading') });
Promise.all([App.api('/api/doctors'), App.api('/api/specializations')]).then(function (r) {
  all = r[0].data;
  document.getElementById('spec').innerHTML = '<option value="">Semua spesialisasi</option>' + r[1].data.map(function (s) {
    return '<option value="' + App.esc(s.id) + '">' + App.esc(s.name) + '</option>';
  }).join('');
  paint();
}).catch(function (err) { document.getElementById('results').innerHTML = Render.card({ body: App.stateBox('error', err.message) }); });

</script>
</body>
</html>
