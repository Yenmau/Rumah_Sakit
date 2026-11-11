<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Detail Janji Temu · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="pasien" data-active="janji">

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
  title: 'Detail Janji Temu',
  subtitle: 'Termasuk alasan penolakan/pembatalan dan riwayat reschedule.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Janji Temu', href: url('pasien/janji.php') }, { label: 'Detail' }]
});
var id = qs('id'), host = document.getElementById('body');
function load() {
  host.innerHTML = Render.card({ body: App.stateBox('loading') });
  App.api('/api/pasien/appointments/' + id).then(function (res) {
    var a = res.data;
    var cancelable = ['pending','confirmed'].indexOf(a.status) > -1;
    host.innerHTML =
      Render.card({
        title: a.code,
        subtitle: 'Dibuat ' + App.fmt.tanggalJam(a.created_at),
        actions: Render.badge(a.status) + Render.rescheduledChip(a),
        body: Render.describe([
          { label: 'Dokter', value: a.doctor_name },
          { label: 'Spesialisasi', value: a.specialization },
          { label: 'Tanggal', value: App.fmt.hari(a.date) + ', ' + App.fmt.tanggal(a.date) },
          { label: 'Jam', value: App.fmt.jam(a.start_time) + '–' + App.fmt.jam(a.end_time) },
          { label: 'Keluhan', value: a.reason, html: '<p class="text-sm text-slate-800">' + App.esc(a.reason) + '</p>' }
        ]),
        footer: (cancelable ?
          '<div class="flex flex-wrap gap-2">' +
          '<button type="button" id="btn-resched" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Jadwalkan ulang</button>' +
          '<button type="button" id="btn-cancel" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Batalkan janji</button>' +
          '</div>' : '<p class="text-xs text-slate-500">Janji ini sudah tidak dapat diubah karena statusnya <strong>' + App.esc(a.status) + '</strong>.</p>')
      }) +
      ((a.rejection_reason || a.cancellation_reason) ? '<div class="mt-5">' + Render.card({
        title: a.rejection_reason ? 'Alasan penolakan' : 'Alasan pembatalan',
        body: '<p class="rounded-lg bg-rose-50 p-3 text-sm text-rose-800">' + App.esc(a.rejection_reason || a.cancellation_reason) + '</p>'
      }) + '</div>' : '') +
      '<div class="mt-5">' + Render.card({ title: 'Riwayat janji temu', subtitle: 'Semua perubahan status dan reschedule dicatat.', body: Render.timeline(a.history.map(function (h) {
        return { title: h.action, meta: App.fmt.tanggalJam(h.at) + ' · oleh ' + h.by, body: h.note || '', tone: h.action === 'Ditolak' || h.action === 'Dibatalkan' ? 'danger' : h.action === 'Selesai' ? 'success' : '' };
      })) }) + '</div>';

    var cb = document.getElementById('btn-cancel');
    if (cb) cb.addEventListener('click', function () {
      App.modal({ title: 'Batalkan janji temu?', message: 'Alasan wajib diisi dan akan tercatat pada riwayat janji.', reasonRequired: true, confirmLabel: 'Ya, batalkan', danger: true })
        .then(function (r) {
          if (!r.ok) return;
          App.api('/api/pasien/appointments/' + id + '/cancel', { method: 'POST', body: { reason: r.reason } })
            .then(function () { App.toast('Janji dibatalkan.', 'success'); load(); })
            .catch(App.handleError);
        });
    });
    var rb = document.getElementById('btn-resched');
    if (rb) rb.addEventListener('click', function () {
      App.modal({ title: 'Jadwalkan ulang', message: 'Masukkan tanggal (YYYY-MM-DD) dan jam (HH:MM) baru, serta alasan perubahan.', reasonRequired: true, confirmLabel: 'Simpan jadwal baru' })
        .then(function (r) {
          if (!r.ok) return;
          var date = prompt('Tanggal baru (YYYY-MM-DD):', a.date);
          if (!date) return;
          var start = prompt('Jam baru (HH:MM):', a.start_time);
          if (!start) return;
          App.api('/api/pasien/appointments/' + id + '/reschedule', { method: 'POST', body: { date: date, start_time: start, reason: r.reason } })
            .then(function () { App.toast('Jadwal diperbarui.', 'success'); load(); })
            .catch(App.handleError);
        });
    });
  }).catch(function (err) {
    host.innerHTML = Render.card({ body: App.stateBox('error', err.message) });
    if (err.status === 404) setTimeout(function () { location.replace(url('404.php')); }, 1200);
  });
}
if (!id) { host.innerHTML = Render.card({ body: App.stateBox('error', 'Parameter id janji temu tidak ditemukan.') }); } else load();

</script>
</body>
</html>
