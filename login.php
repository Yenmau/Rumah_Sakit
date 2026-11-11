<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Masuk · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">

<div class="grid min-h-screen lg:grid-cols-2">

  <section class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 to-brand-800 lg:flex lg:flex-col lg:items-center lg:justify-center lg:px-12" aria-label="Sambutan">
    <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
    <div class="absolute -bottom-28 -right-20 h-80 w-80 rounded-full bg-white/10"></div>

    <div class="relative w-full max-w-md text-center">
      <svg viewBox="0 0 420 320" class="mx-auto w-full max-w-sm" aria-hidden="true">
        <circle cx="210" cy="165" r="140" fill="#ffffff" fill-opacity="0.14"/>
        <circle cx="210" cy="165" r="118" fill="#ffffff" fill-opacity="0.12"/>
        <path d="M92 96h26M105 83v26" stroke="#99f6e4" stroke-width="4" stroke-linecap="round"/>
        <path d="M330 216h22M341 205v22" stroke="#99f6e4" stroke-width="4" stroke-linecap="round"/>

        <circle cx="128" cy="150" r="24" fill="#e2e8f0"/>
        <path d="M128 176c-26 0-42 15-42 40v30h84v-30c0-25-16-40-42-40z" fill="#0f766e"/>
        <rect x="110" y="140" width="36" height="14" rx="7" fill="#f8fafc"/>

        <circle cx="210" cy="134" r="28" fill="#f8fafc"/>
        <path d="M210 164c-30 0-48 17-48 45v37h96v-37c0-28-18-45-48-45z" fill="#f8fafc"/>
        <path d="M186 164l-14 30 14 12" fill="none" stroke="#0f766e" stroke-width="3" stroke-linecap="round"/>
        <path d="M234 164l14 30-14 12" fill="none" stroke="#0f766e" stroke-width="3" stroke-linecap="round"/>
        <circle cx="238" cy="216" r="9" fill="none" stroke="#0f766e" stroke-width="3"/>
        <circle cx="182" cy="141" r="3.5" fill="#115e59"/>
        <circle cx="238" cy="141" r="3.5" fill="#115e59"/>

        <circle cx="292" cy="150" r="24" fill="#e2e8f0"/>
        <path d="M292 176c-26 0-42 15-42 40v30h84v-30c0-25-16-40-42-40z" fill="#134e4a"/>
        <rect x="268" y="196" width="48" height="34" rx="6" fill="#f8fafc"/>
        <path d="M276 205h32M276 214h32M276 223h20" stroke="#5eead4" stroke-width="3" stroke-linecap="round"/>

        <circle cx="336" cy="118" r="26" fill="#22c55e"/>
        <path d="M336 105v26M323 118h26" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>
      </svg>

      <h2 class="mt-8 text-2xl font-bold text-white">Layanan kesehatan yang terhubung</h2>
      <p class="mt-3 text-sm leading-relaxed text-brand-100">Kelola janji temu, jadwal praktik, rekam medis, dan verifikasi dokter dalam satu sistem rumah sakit.</p>
      <ul class="mt-6 space-y-2 text-left text-sm text-brand-50">
        <li class="flex gap-3"><span aria-hidden="true">✓</span><span>Pasien: cari dokter, booking, lihat rekam medis</span></li>
        <li class="flex gap-3"><span aria-hidden="true">✓</span><span>Dokter: kelola janji, jadwal, resep, revisi</span></li>
        <li class="flex gap-3"><span aria-hidden="true">✓</span><span>Admin: verifikasi dokter, kelola user, audit log</span></li>
      </ul>
    </div>
  </section>

  <section class="flex items-center justify-center px-4 py-10 sm:px-8">
    <div class="w-full max-w-md">

      <div class="mb-6 flex items-center gap-3 lg:hidden">
        <span class="text-lg font-bold tracking-tight text-slate-900">RS Sehat<span class="text-brand-600">Sentosa</span></span>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6 hidden items-center gap-3 lg:flex">
          <span class="text-lg font-bold tracking-tight text-slate-900">RS Sehat<span class="text-brand-600">Sentosa</span></span>
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Selamat Datang Kembali</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk melanjutkan ke layanan rumah sakit.</p>

        <div id="alert" class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" aria-live="assertive"></div>

        <form id="login-form" method="post" action="actions/doLogin.php" novalidate class="mt-6 space-y-5">
          <input type="hidden" name="csrf_token" id="csrf-field" value="">

          <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="email" name="email" type="email" autocomplete="username" required
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                   aria-describedby="email-help">
            <p id="email-help" class="mt-1 text-xs text-slate-500">Alamat email yang terdaftar pada sistem.</p>
            <p data-err="email" class="mt-1 hidden text-xs font-medium text-rose-600"></p>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password <span class="text-rose-600" aria-hidden="true">*</span></label>
            <div class="relative mt-1">
              <input id="password" name="password" type="password" autocomplete="current-password" required
                     class="w-full rounded-lg border border-slate-300 px-3 py-2.5 pr-20 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
              <button type="button" id="toggle-pw" aria-pressed="false"
                      class="absolute inset-y-0 right-2 my-auto h-8 rounded-md px-2 text-xs font-medium text-slate-600 hover:bg-slate-100">Lihat</button>
            </div>
            <p data-err="password" class="mt-1 hidden text-xs font-medium text-rose-600"></p>
          </div>

          <fieldset>
            <legend class="block text-sm font-medium text-slate-700">Masuk sebagai <span class="text-rose-600" aria-hidden="true">*</span></legend>
            <div class="mt-1.5 grid grid-cols-3 gap-2">
              <div>
                <input type="radio" id="role-pasien" name="role" value="pasien" class="peer sr-only" required>
                <label for="role-pasien" class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-slate-300 px-2 py-3 text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-slate-50 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-checked:ring-1 peer-checked:ring-brand-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                  <span aria-hidden="true" class="text-lg">🧑</span>Pasien
                </label>
              </div>
              <div>
                <input type="radio" id="role-dokter" name="role" value="dokter" class="peer sr-only">
                <label for="role-dokter" class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-slate-300 px-2 py-3 text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-slate-50 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-checked:ring-1 peer-checked:ring-brand-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                  <span aria-hidden="true" class="text-lg">🩺</span>Dokter
                </label>
              </div>
              <div>
                <input type="radio" id="role-admin" name="role" value="admin" class="peer sr-only">
                <label for="role-admin" class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-slate-300 px-2 py-3 text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-slate-50 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-checked:ring-1 peer-checked:ring-brand-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                  <span aria-hidden="true" class="text-lg">🛡️</span>Administrator
                </label>
              </div>
            </div>
            <p class="mt-1.5 text-xs text-slate-500">Menu yang tampil menyesuaikan role yang dipilih.</p>
            <p data-err="role" class="mt-1 hidden text-xs font-medium text-rose-600"></p>
          </fieldset>

          <button type="submit"
                  class="w-full rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
            Masuk
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
          Belum punya akun?
          <a class="font-medium text-brand-700 hover:underline" href="register-pasien.php">Daftar pasien</a>
          <span class="text-slate-400" aria-hidden="true">·</span>
          <a class="font-medium text-brand-700 hover:underline" href="register-dokter.php">Daftar dokter</a>
        </p>
      </div>

      <p class="mt-4 text-center text-xs text-slate-500">Dengan masuk, Anda menyetujui kebijakan privasi dan ketentuan layanan rumah sakit.</p>
    </div>
  </section>
</div>

<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>
var _csrf = document.getElementById('csrf-field'); if (_csrf) _csrf.value = App.csrfToken();

if (qs('expired')) {
  var box = document.getElementById('alert');
  box.textContent = 'Sesi Anda telah berakhir. Silakan masuk kembali.';
  box.classList.remove('hidden');
}

document.querySelectorAll('input[name="role"]').forEach(function (r) {
  r.addEventListener('change', function () { clearErr(); });
});

document.getElementById('toggle-pw').addEventListener('click', function () {
  var i = document.getElementById('password');
  var on = i.type === 'password';
  i.type = on ? 'text' : 'password';
  this.textContent = on ? 'Sembunyikan' : 'Lihat';
  this.setAttribute('aria-pressed', String(on));
});

function showErr(name, msg) {
  var e = document.querySelector('[data-err="' + name + '"]');
  if (e) { e.textContent = msg; e.classList.remove('hidden'); }
  var i = document.querySelector('[name="' + name + '"]');
  if (i) i.setAttribute('aria-invalid', 'true');
}
function clearErr() {
  document.querySelectorAll('[data-err]').forEach(function (e) { e.classList.add('hidden'); e.textContent = ''; });
  document.querySelectorAll('[aria-invalid]').forEach(function (i) { i.removeAttribute('aria-invalid'); });
}

document.getElementById('login-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErr();
  var box = document.getElementById('alert');
  box.classList.add('hidden');

  var email = document.getElementById('email').value.trim();
  var pw = document.getElementById('password').value;
  var roleEl = document.querySelector('input[name="role"]:checked');
  var role = roleEl ? roleEl.value : '';

  var bad = false;
  if (App.validate.email(email) !== true) { showErr('email', App.validate.email(email)); bad = true; }
  if (!pw) { showErr('password', 'Password wajib diisi.'); bad = true; }
  if (!role) { showErr('role', 'Pilih role terlebih dahulu.'); bad = true; }
  if (bad) { document.querySelector('[aria-invalid]').focus(); return; }

  var btn = this.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Memproses…';
  App.api('/api/login', { method: 'POST', body: { email: email, password: pw, role: role } })
    .then(function (res) {
      App.Session.set(res.session);
      App.toast('Berhasil masuk. Mengalihkan…', 'success');
      var next = qs('next');
      location.replace(next ? url(next) : url(res.session.role + '/dashboard.php'));
    })
    .catch(function (err) {
      btn.disabled = false; btn.textContent = 'Masuk';
      box.textContent = err.message || 'Gagal masuk. Periksa email, password, dan role Anda.';
      box.classList.remove('hidden');
      document.getElementById('email').focus();
    });
});
</script>
</body>
</html>
