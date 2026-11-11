<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Booking Janji Temu · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="cari-dokter">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2 space-y-5">
    <div id="doctor"></div>
    <div id="slot-card"></div>
  </div>
  <div class="space-y-5">
    <div id="form-card"></div>
  </div>
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
  title: 'Buat Janji Temu',
  subtitle: 'Pilih dokter, tanggal, dan slot kosong. Permintaan masuk sebagai status menunggu konfirmasi dokter.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Cari Dokter', href: url('pasien/cari-dokter.php') }, { label: 'Booking' }]
});
var doctorId = qs('doctor_id');
var host = document.getElementById('doctor');
if (!doctorId) {
  host.innerHTML = Render.card({ body: App.stateBox('error', 'Dokter belum dipilih. Mulai dari halaman <a class="text-brand-700 underline" href="' + url('pasien/cari-dokter.php') + '">Cari Dokter</a>.') });
} else {
  host.innerHTML = Render.card({ body: App.stateBox('loading') });
  document.getElementById('slot-card').innerHTML = Render.card({ body: App.stateBox('loading') });
  var picked = null;
  App.api('/api/doctors/' + doctorId).then(function (res) {
    var d = res.data;
    host.innerHTML = Render.card({
      title: d.name,
      subtitle: d.specialization,
      actions: '<a href="' + url('pasien/dokter-detail.php?id=' + d.id) + '" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50">Lihat profil</a>',
      body: '<div class="flex flex-wrap gap-4 text-sm text-slate-700">' +
        '<p>Pengalaman <strong>' + App.esc(d.experience_years) + ' tahun</strong></p>' +
        '<p>Biaya konsultasi <strong>' + App.fmt.rupiah(d.fee) + '</strong></p>' +
        '<p>Verifikasi ' + Render.badge(d.verification) + '</p></div>'
    });
    document.getElementById('slot-card').innerHTML = Render.card({
      title: 'Pilih slot kosong',
      subtitle: 'Slot penuh atau terhalang cuti tidak dapat dipilih.',
      body: '<div id="slots"></div>'
    });
    App.api('/api/doctors/' + d.id + '/slots?days=14').then(function (s) {
      var pk = Render.slotPicker('#slots', { dates: s.data, onPick: function (c) { picked = c; } });
      var pre = qs('date'), preStart = qs('start');
      if (pre) {
        var tab = document.querySelector('[data-date="' + pre + '"]');
        if (tab) tab.click();
        if (preStart) { var b = document.querySelector('[data-slot="' + preStart + '"]'); if (b) b.click(); }
      }
    });
  }).catch(function (err) { host.innerHTML = Render.card({ body: App.stateBox('error', err.message) }); });

  Render.form('#form-card', {
    action: 'actions/doBooking.php',
    submitLabel: 'Kirim permintaan',
    successMessage: 'Permintaan janji temu terkirim. Menunggu konfirmasi dokter.',
    intro: '<p class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Janji baru berstatus <strong>menunggu</strong>. Dokter akan menerima atau menolak dengan alasan.</p>',
    fields: [
      { name: 'reason', label: 'Alasan / keluhan', type: 'textarea', rows: 4, required: true,
        rules: [App.validate.required, App.validate.minLength(10)],
        help: 'Ceritakan singkat keluhan agar dokter dapat menyiapkan pemeriksaan.' },
      { name: 'patient_name', label: 'Nama pasien', required: true, rules: [App.validate.required], help: 'Terisi otomatis dari profil.' }
    ],
    validate: function (v) {
      if (!picked) return 'Silakan pilih slot tanggal dan jam terlebih dahulu.';
      return true;
    },
    onSubmit: function (v) {
      return App.api('/api/pasien/appointments', { method: 'POST', body: {
        doctor_id: doctorId, date: picked.date, start_time: picked.start, reason: v.reason
      } }).then(function (r) {
        setTimeout(function () { location.href = url('pasien/janji-detail.php?id=' + r.id); }, 900);
      });
    }
  });
  var s = App.Session.get();
  setTimeout(function () { var i = document.querySelector('[name="patient_name"]'); if (i && s) i.value = s.name; }, 50);
}

</script>
</body>
</html>
