<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Profil Dokter · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="cari-dokter">

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
  title: 'Profil Dokter',
  subtitle: 'Biaya, pengalaman, spesialisasi, jadwal praktik, dan slot kosong.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Cari Dokter', href: url('pasien/cari-dokter.php') }, { label: 'Profil Dokter' }]
});
var id = qs('id');
var host = document.getElementById('body');
if (!id) {
  host.innerHTML = Render.card({ body: App.stateBox('error', 'Parameter dokter tidak ada. Buka halaman ini dari daftar dokter.') });
} else {
  host.innerHTML = Render.card({ body: App.stateBox('loading') });
  App.api('/api/doctors/' + id).then(function (res) {
    var d = res.data, DAYS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    host.innerHTML =
      Render.pageHeader({ title: d.name, subtitle: d.specialization }) +
      '<div class="grid gap-5 lg:grid-cols-3">' +
        '<div class="lg:col-span-2 space-y-5">' +
          Render.card({ title: 'Data praktik', body:
            Render.describe([
              { label: 'Spesialisasi', value: d.specialization },
              { label: 'Nomor SIP', value: d.license },
              { label: 'Pengalaman', value: d.experience_years + ' tahun' },
              { label: 'Biaya konsultasi', value: App.fmt.rupiah(d.fee) },
              { label: 'Status verifikasi', html: Render.badge(d.verification) }
            ]) }) +
          Render.card({ title: 'Jadwal praktik mingguan', subtitle: 'Jadwal nonaktif tidak ditampilkan ke pasien.', body:
            (res.schedules.filter(function (s) { return s.active; }).length ? '<ul class="divide-y divide-slate-100 text-sm">' + res.schedules.filter(function (s) { return s.active; }).map(function (s) {
              return '<li class="flex items-center justify-between py-2"><span>' + DAYS[s.day] + '</span><span class="text-slate-600">' + App.fmt.jam(s.start) + '–' + App.fmt.jam(s.end) + '</span></li>';
            }).join('') + '</ul>' : App.stateBox('empty', 'Belum ada jadwal aktif.')) }) +
           Render.card({ title: 'Cuti / jam khusus terdekat', body:
            (res.leaves.length ? '<ul class="divide-y divide-slate-100 text-sm">' + res.leaves.map(function (l) {
              return '<li class="py-2"><p class="font-medium text-slate-800">' + (l.type === 'cuti' ? 'Cuti penuh' : 'Jam khusus ' + App.fmt.jam(l.start) + '–' + App.fmt.jam(l.end)) + '</p>' +
                '<p class="text-xs text-slate-500">' + App.fmt.tanggal(l.date) + (l.note ? ' · ' + App.esc(l.note) : '') + '</p></li>';
            }).join('') + '</ul>' : App.stateBox('empty', 'Tidak ada cuti dalam waktu dekat.')) }) +
        '</div>' +
        '<div class="space-y-5">' +
          Render.card({ title: 'Slot kosong 7 hari ke depan', subtitle: 'Gabungan jadwal mingguan dan pengecualian cuti.', body: '<div id="slots"></div>',
            footer: '<a href="' + url('pasien/booking.php?doctor_id=' + d.id) + '" class="block rounded-lg bg-brand-700 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">Booking janji temu</a>' }) +
        '</div>' +
      '</div>';
    App.api('/api/doctors/' + d.id + '/slots?days=7').then(function (s) {
      Render.slotPicker('#slots', { dates: s.data, onPick: function (c) {
        location.href = url('pasien/booking.php?doctor_id=' + d.id + '&date=' + c.date + '&start=' + c.start);
      } });
    });
  }).catch(function (err) {
    host.innerHTML = Render.card({ body: App.stateBox('error', err.message) });
    if (err.status === 404) setTimeout(function () { location.replace(url('404.php')); }, 1200);
  });
}

</script>
</body>
</html>
