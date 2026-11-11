<?php
http_response_code(404);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<base href="<?php echo htmlspecialchars($base, ENT_QUOTES); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="">
<meta name="description" content="Front-end sistem rumah sakit (COMP6695 Secure Programming).">
<title>Tidak Ditemukan · RS SehatSentosa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>

  tailwind.config = { theme: { extend: { colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } };
</script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<main class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-10">
  <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <span aria-hidden="true" class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">🧭</span>
    <h1 class="mt-4 text-xl font-bold text-slate-900">404 — Halaman Tidak Ditemukan</h1>
    <p class="mt-2 text-sm text-slate-600">Alamat yang Anda buka tidak ada, atau data sudah dipindahkan. Endpoint API yang tidak dikenal juga membalas <code>404</code> dengan pesan generik.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-2">
      <a id="home" href="index.php" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Kembali ke beranda</a>
      <a href="pasien/cari-dokter.php" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cari dokter</a>
    </div>
  </div>
</main>
<script>window.APP_BASE = "";</script>
<script src="assets/js/config.js"></script>
<script src="assets/js/mock.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>

<script>

var s = App.Session.get();
if (s) document.getElementById('home').href = url(s.role + '/dashboard.php');

</script>
</body>
</html>
