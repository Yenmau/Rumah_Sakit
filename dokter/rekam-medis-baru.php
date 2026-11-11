<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Rekam Medis Baru · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2 space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Isi rekam medis</h2>
      <form id="rm-form" method="post" action="actions/doCreateRecord.php" novalidate class="mt-4 space-y-4">
        <input type="hidden" name="csrf_token" id="csrf-field" value="">
        <div>
          <label for="appointment_id" class="block text-sm font-medium text-slate-700">Janji temu</label>
          <select id="appointment_id" name="appointment_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><option value="">Memuat…</option></select>
          <p class="mt-1 text-xs text-slate-500">Hanya janji bertatus diterima/selesai yang muncul. Pasien ikut terisi otomatis.</p>
        </div>
        <div>
          <label for="diagnosis" class="block text-sm font-medium text-slate-700">Diagnosis <span class="text-rose-600" aria-hidden="true">*</span></label>
          <input id="diagnosis" name="diagnosis" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100" placeholder="Contoh: Hipertensi primer (I10)">
          <p data-err="diagnosis" class="mt-1 hidden text-xs font-medium text-rose-600">Diagnosis wajib diisi.</p>
        </div>
        <div>
          <label for="notes" class="block text-sm font-medium text-slate-700">Catatan pemeriksaan</label>
          <textarea id="notes" name="notes" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100" placeholder="Tanda vital, hasil lab, edukasi, rencana kontrol…"></textarea>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div><h2 class="text-base font-semibold text-slate-900">Resep</h2><p class="text-sm text-slate-500">Tambah atau hapus item obat secara bebas.</p></div>
        <button type="button" id="rx-add" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">+ Tambah obat</button>
      </div>
      <div id="rx-list" class="mt-4 space-y-4"></div>
    </div>
  </div>

  <div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Simpan</h2>
      <p class="mt-1 text-sm text-slate-500">Draf bisa diubah kapan saja. Finalisasi mengunci rekam medis dan mewajibkan alasan bila nanti direvisi.</p>
      <div class="mt-4 flex flex-col gap-2">
        <button type="button" id="btn-draft" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Simpan sebagai draf</button>
        <button type="button" id="btn-final" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan &amp; finalisasi</button>
      </div>
      <p id="msg" class="mt-3 hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert"></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Konteks janji</h2>
      <div id="ctx" class="mt-3 text-sm text-slate-600">Pilih janji temu untuk melihat keluhan pasien.</div>
    </div>
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
  title: 'Rekam Medis Baru',
  subtitle: 'Form diagnosis, catatan, dan resep dinamis.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis', href: url('dokter/rekam-medis.php') }, { label: 'Baru' }]
});
var _csrf = document.getElementById('csrf-field'); if (_csrf) _csrf.value = App.csrfToken();

var rows = [];
function rxRow(item, i) {
  item = item || { name: '', dose: '', frequency: '', duration: '', qty: '', instructions: '' };
  return '<fieldset class="rounded-xl border border-slate-200 p-4" data-rx="' + i + '">' +
    '<legend class="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Obat #' + (i + 1) + '</legend>' +
    '<div class="grid gap-3 sm:grid-cols-2">' +
    ['name:Nama obat:text', 'dose:Dosis:text', 'frequency:Frekuensi:text', 'duration:Durasi:text', 'qty:Jumlah:number', 'instructions:Instruksi:text'].map(function (f) {
      var p = f.split(':');
      var id = 'rx' + i + '-' + p[0];
      return '<div><label for="' + id + '" class="block text-xs font-medium text-slate-600">' + p[1] + '</label>' +
        '<input id="' + id + '" data-f="' + p[0] + '" type="' + p[2] + '" value="' + App.esc(item[p[0]]) + '" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>';
    }).join('') + '</div>' +
    '<div class="mt-3 flex justify-end"><button type="button" data-del-rx="' + i + '" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">Hapus item</button></div></fieldset>';
}
function paintRx() {
  var host = document.getElementById('rx-list');
  if (!rows.length) { host.innerHTML = App.stateBox('empty', 'Belum ada item resep. Tekan "Tambah obat" bila diperlukan.'); return; }
  host.innerHTML = rows.map(function (r, i) { return rxRow(r, i); }).join('');
}
function collectRx() {
  return [].slice.call(document.querySelectorAll('[data-rx]')).map(function (fs) {
    var o = {};
    fs.querySelectorAll('[data-f]').forEach(function (i) { o[i.getAttribute('data-f')] = i.value; });
    return o;
  }).filter(function (o) { return o.name; });
}
function readForm() {
  return {
    patient_name: document.querySelector('[name="appointment_id"]').selectedOptions[0] ? document.querySelector('[name="appointment_id"]').selectedOptions[0].dataset.patient : '',
    diagnosis: document.querySelector('[name="diagnosis"]').value.trim(),
    notes: document.querySelector('[name="notes"]').value.trim(),
    prescriptions: collectRx()
  };
}
document.getElementById('rx-add').addEventListener('click', function () { rows.push({}); paintRx(); });
document.getElementById('rx-list').addEventListener('click', function (e) {
  var b = e.target.closest('[data-del-rx]');
  if (!b) return;
  var idx = Number(b.getAttribute('data-del-rx'));
  var cur = collectRx();
  rows.splice(idx, 1); paintRx();
});

paintRx();
App.api('/api/dokter/appointments').then(function (res) {
  var pre = qs('appointment_id');
  var opts = res.data.filter(function (a) { 
    return ['confirmed','completed'].indexOf(a.status) > -1 || (pre && String(a.id) === String(pre)); 
  });
  var sel = document.getElementById('appointment_id');
  sel.innerHTML = '<option value="">Pilih janji temu…</option>' + opts.map(function (a) {
    return '<option value="' + a.id + '" data-patient="' + App.esc(a.patient_name) + '"' + (String(a.id) === String(pre) ? ' selected' : '') + '>' +
      App.esc(a.code) + ' · ' + App.esc(a.patient_name) + ' · ' + App.fmt.tanggal(a.date) + ' ' + App.fmt.jam(a.start_time) + '</option>';
  }).join('');
  sel.addEventListener('change', function () {
    var a = opts.filter(function (x) { return String(x.id) === this.value; }.bind(this))[0];
    document.getElementById('ctx').innerHTML = a ? '<p class="font-medium text-slate-800">' + App.esc(a.patient_name) + '</p><p class="text-xs text-slate-500">' + App.fmt.tanggal(a.date) + ' · ' + App.fmt.jam(a.start_time) + '</p><p class="mt-2 rounded-lg bg-slate-50 p-2 text-xs">' + App.esc(a.reason) + '</p>' : 'Pilih janji temu untuk melihat keluhan pasien.';
  });
  if (pre) sel.dispatchEvent(new Event('change'));
});

function submit(finalize) {
  var d = readForm();
  var msg = document.getElementById('msg');
  msg.classList.add('hidden');
  if (!d.diagnosis) {
    msg.textContent = 'Diagnosis wajib diisi sebelum menyimpan.';
    msg.classList.remove('hidden');
    document.getElementById('diagnosis').focus();
    return;
  }
  var btn = document.getElementById(finalize ? 'btn-final' : 'btn-draft');
  var label = btn.textContent;
  btn.disabled = true; btn.textContent = 'Menyimpan…';
  App.api('/api/dokter/records', { method: 'POST', body: {
    appointment_id: document.querySelector('[name="appointment_id"]').value,
    diagnosis: d.diagnosis, notes: d.notes, prescriptions: d.prescriptions, finalize: finalize
  } }).then(function (r) {
    App.toast(finalize ? 'Rekam medis difinalisasi.' : 'Draf rekam medis disimpan.', 'success');
    setTimeout(function () { location.href = url('dokter/rekam-medis-edit.php?id=' + r.id); }, 800);
  }).catch(function (err) { App.handleError(err); btn.disabled = false; btn.textContent = label; });
}
document.getElementById('btn-draft').addEventListener('click', function () { submit(false); });
document.getElementById('btn-final').addEventListener('click', function () {
  App.modal({ title: 'Finalisasi rekam medis?', message: 'Setelah final, perubahan hanya bisa dilakukan dengan alasan revisi yang tercatat.', confirmLabel: 'Ya, finalisasi' })
    .then(function (r) { if (r.ok) submit(true); });
});

rows.push({}); paintRx();

</script>
</body>
</html>
