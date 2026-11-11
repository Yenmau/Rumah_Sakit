<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Edit Rekam Medis · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800" data-role="dokter" data-active="rekam-medis">

<div id="page">

<div id="head"></div>
<div id="banner"></div>
<div class="grid gap-5 lg:grid-cols-3">
  <div class="lg:col-span-2 space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <form id="rm-form" method="post" action="actions/doUpdateRecord.php" novalidate class="space-y-4">
        <input type="hidden" name="csrf_token" id="csrf-field" value="">
        <div>
          <label for="diagnosis" class="block text-sm font-medium text-slate-700">Diagnosis <span class="text-rose-600" aria-hidden="true">*</span></label>
          <input id="diagnosis" name="diagnosis" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
        </div>
        <div>
          <label for="notes" class="block text-sm font-medium text-slate-700">Catatan pemeriksaan</label>
          <textarea id="notes" name="notes" rows="6" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
        </div>
      </form>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-base font-semibold text-slate-900">Resep</h2>
        <button type="button" id="rx-add" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">+ Tambah obat</button>
      </div>
      <div id="rx-list" class="mt-4 space-y-4"></div>
    </div>
  </div>
  <div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Aksi</h2>
      <div class="mt-4 flex flex-col gap-2">
        <button type="button" id="btn-save" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan perubahan</button>
        <a id="btn-history" href="#" class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Riwayat revisi</a>
      </div>
      <p id="msg" class="mt-3 hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert"></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Info rekam medis</h2>
      <div id="meta" class="mt-3 text-sm text-slate-600">Memuat…</div>
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
  title: 'Edit Rekam Medis',
  subtitle: 'Rekam medis final hanya dapat diubah dengan alasan revisi yang wajib diisi.',
  breadcrumb: [{ label: 'Beranda', href: url('index.php') }, { label: 'Rekam Medis', href: url('dokter/rekam-medis.php') }, { label: 'Edit' }]
});
var _csrf = document.getElementById('csrf-field'); if (_csrf) _csrf.value = App.csrfToken();
var id = qs('id'), rec = null;

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

function load() {
  App.api('/api/dokter/records/' + id).then(function (res) {
    rec = res.data;
    document.getElementById('diagnosis').value = rec.diagnosis || '';
    document.getElementById('notes').value = rec.notes || '';
    rows = (rec.prescriptions || []).slice();
    paintRx();
    document.getElementById('meta').innerHTML = Render.describe([
      { label: 'Pasien', value: rec.patient_name },
      { label: 'Tanggal', value: App.fmt.tanggal(rec.date) },
      { label: 'Status', html: Render.badge(rec.status) },
      { label: 'Difinalisasi', value: rec.finalized_at ? App.fmt.tanggalJam(rec.finalized_at) : 'Belum' },
      { label: 'Jumlah revisi', value: (rec.revisions || []).length }
    ], 'grid-cols-1');
    document.getElementById('btn-history').href = url('dokter/riwayat-revisi.php?id=' + rec.id);
    var locked = rec.status !== 'draft';
    document.getElementById('banner').innerHTML = locked
      ? '<div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="note">' +
        '<strong>Rekam medis terkunci.</strong> Menyimpan perubahan diagnosis/catatan akan meminta <em>alasan revisi</em> (minimal 5 karakter) dan riwayat sebelum/sesudah dicatat. <strong>Resep tidak dapat diubah</strong> setelah rekam medis difinalisasi.</div>'
      : '<div class="mb-5 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800" role="note">Status masih <strong>draf</strong> — bebas disunting tanpa alasan.</div>';

    if (locked) {
      [].slice.call(document.querySelectorAll('#rx-list input, #rx-list button')).forEach(function (el) {
        el.disabled = true; el.classList.add('bg-slate-100'); el.classList.remove('hover:bg-rose-50');
      });
      var add = document.getElementById('rx-add');
      add.disabled = true; add.classList.add('opacity-50', 'cursor-not-allowed');
      var rxNote = document.createElement('p');
      rxNote.className = 'mt-1 text-xs text-slate-500';
      rxNote.textContent = 'Resep terkunci karena rekam medis sudah difinalisasi.';
      add.parentNode.parentNode.insertBefore(rxNote, add.parentNode.nextSibling);
    }
  }).catch(function (err) {
    document.getElementById('banner').innerHTML = App.stateBox('error', err.message);
    if (err.status === 404) setTimeout(function () { location.replace(url('404.php')); }, 1200);
  });
}
if (!id) { document.getElementById('banner').innerHTML = App.stateBox('error', 'Parameter id rekam medis tidak ada.'); }
else { document.getElementById('banner').innerHTML = App.stateBox('loading'); load(); }

document.getElementById('btn-save').addEventListener('click', function () {
  var msg = document.getElementById('msg');
  msg.classList.add('hidden');
  var payload = { diagnosis: document.getElementById('diagnosis').value.trim(), notes: document.getElementById('notes').value.trim(), prescriptions: collectRx() };
  if (!payload.diagnosis) { msg.textContent = 'Diagnosis wajib diisi.'; msg.classList.remove('hidden'); return; }
  var locked = rec && rec.status !== 'draft';
  var go = function (reason) {
    if (reason) payload.reason = reason;
    var btn = document.getElementById('btn-save');
    btn.disabled = true; btn.textContent = 'Menyimpan…';
    App.api('/api/dokter/records/' + id, { method: 'PUT', body: payload }).then(function () {
      App.toast('Rekam medis diperbarui.', 'success');
      btn.disabled = false; btn.textContent = 'Simpan perubahan';
      load();
    }).catch(function (err) { App.handleError(err); btn.disabled = false; btn.textContent = 'Simpan perubahan'; });
  };
  if (locked) {
    App.modal({ title: 'Alasan revisi wajib diisi', message: 'Revisi rekam medis final akan tercatat lengkap dengan nilai sebelum dan sesudah.', reasonRequired: true, confirmLabel: 'Simpan revisi' })
      .then(function (r) { if (r.ok) go(r.reason); });
  } else go(null);
});

</script>
</body>
</html>
