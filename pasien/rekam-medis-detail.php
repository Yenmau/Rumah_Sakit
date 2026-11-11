<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Detail Rekam Medis · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div id="body"></div>
</div>

<script>window.APP_BASE = "..";</script>
<script src="../assets/js/config.js"></script>
<script src="../assets/js/mock.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/ui.js"></script>
<script src="../assets/js/layout.js"></script>

<script>

document.getElementById('head').innerHTML = Render.pageHeader({
  title: 'Detail Rekam Medis',
  subtitle: 'Diagnosis, catatan, dan resep dari kunjungan final.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis', href: url('pasien/rekam-medis.php') }, { label: 'Detail' }]
});
var id = qs('id'), host = document.getElementById('body');
if (!id) { host.innerHTML = Render.card({ body: App.stateBox('error', 'Parameter id rekam medis tidak ada.') }); }
else {
  host.innerHTML = Render.card({ body: App.stateBox('loading') });
  App.api('/api/pasien/records/' + id).then(function (res) {
    var r = res.data;
    host.innerHTML = Render.card({
      title: 'Rekam medis ' + App.fmt.tanggal(r.date),
      subtitle: r.doctor_name,
      actions: Render.badge(r.status),
      body: Render.describe([
        { label: 'Diagnosis', html: '<p class="font-medium text-slate-900">' + App.esc(r.diagnosis) + '</p>' },
        { label: 'Dokter pemeriksa', value: r.doctor_name },
        { label: 'Tanggal kunjungan', value: App.fmt.tanggal(r.date) },
        { label: 'Difinalisasi', value: r.finalized_at ? App.fmt.tanggalJam(r.finalized_at) : '-' }
      ]) +
      '<div class="mt-5"><h3 class="text-sm font-semibold text-slate-800">Catatan dokter</h3>' +
      '<p class="mt-1 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-700">' + App.esc(r.notes) + '</p></div>'
    }) + '<div class="mt-5">' + Render.card({
      title: 'Resep / obat',
      subtitle: (r.prescriptions || []).length + ' item',
      padded: false,
      body: (r.prescriptions || []).length ? '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">' +
        '<thead class="bg-slate-50"><tr>' + ['Nama obat','Dosis','Frekuensi','Durasi','Jumlah','Instruksi'].map(function (h) {
          return '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">' + h + '</th>'; }).join('') + '</tr></thead><tbody class="divide-y divide-slate-100">' +
        r.prescriptions.map(function (p) {
          return '<tr>' + [p.name, p.dose, p.frequency, p.duration, p.qty, p.instructions].map(function (c) {
            return '<td class="px-4 py-3 text-slate-700">' + App.esc(c) + '</td>'; }).join('') + '</tr>';
        }).join('') + '</tbody></table></div>' : App.stateBox('empty', 'Tidak ada resep pada kunjungan ini.')
    }) + '</div>';
  }).catch(function (err) {
    host.innerHTML = Render.card({ body: App.stateBox('error', err.message) });
    if (err.status === 404) setTimeout(function () { location.replace(url('404.php')); }, 1200);
  });
}

</script>
</body>
</html>
