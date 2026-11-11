
(function (global) {
  var CFG = global.APP_CONFIG;
  var LAT = CFG.MOCK_LATENCY_MS || 0;

  function iso(off) { var d = new Date(); d.setDate(d.getDate() + (off || 0)); return d.toISOString().slice(0, 10); }
  function now(offH) { var d = new Date(Date.now() + (offH || 0) * 3600000); return d.toISOString(); }
  function addMin(t, m) { var p = String(t).split(":"); var tot = Number(p[0]) * 60 + Number(p[1]) + m; return String(Math.floor(tot / 60)).padStart(2, "0") + ":" + String(tot % 60).padStart(2, "0"); }
  function nextId(arr) { return arr.reduce(function (m, x) { return Math.max(m, Number(x.id) || 0); }, 0) + 1; }

  var specializations = [
    { id: 1, name: "Dokter Umum", description: "Pemeriksaan dan konsultasi kesehatan umum" },
    { id: 2, name: "Penyakit Dalam", description: "Diagnosis dan terapi penyakit organ dalam" },
    { id: 3, name: "Anak", description: "Kesehatan bayi, anak, dan remaja" },
    { id: 4, name: "Jantung", description: "Kardiologi dan pembuluh darah" },
    { id: 5, name: "Kulit & Kelamin", description: "Dermatologi dan venereologi" },
    { id: 6, name: "Saraf", description: "Neurologi" }
  ];

  var users = [
    { id: 1, name: "Admin Rumah Sakit", email: "admin@rs.demo", status: "active", role: "admin", locked_until: null, last_login: now(-2), phone: "0811000001", created_at: iso(-120) },
    { id: 2, name: "dr. Sari Wijaya", email: "sari@rs.demo", status: "active", role: "dokter", locked_until: null, last_login: now(-5), phone: "0811000002", created_at: iso(-100) },
    { id: 3, name: "dr. Bagus Prakoso", email: "bagus@rs.demo", status: "active", role: "dokter", locked_until: null, last_login: now(-30), phone: "0811000003", created_at: iso(-90) },
    { id: 4, name: "dr. Nadia Kusuma", email: "nadia@rs.demo", status: "suspended", role: "dokter", locked_until: now(6), last_login: now(-48), phone: "0811000004", created_at: iso(-60) },
    { id: 5, name: "Vincent Tiono", email: "vincent@mail.demo", status: "active", role: "pasien", locked_until: null, last_login: now(-1), phone: "0812000001", created_at: iso(-40) },
    { id: 6, name: "Ayu Lestari", email: "ayu@mail.demo", status: "active", role: "pasien", locked_until: now(0.5), last_login: now(-3), phone: "0812000002", created_at: iso(-30) },
    { id: 7, name: "Rudi Hartono", email: "rudi@mail.demo", status: "inactive", role: "pasien", locked_until: null, last_login: now(-300), phone: "0812000003", created_at: iso(-20) }
  ];

  var doctors = [
    { id: 1, user_id: 2, name: "dr. Sari Wijaya, Sp.PD", email: "sari@rs.demo", specialization_id: 2, specialization: "Penyakit Dalam", license: "SIP-2021-00123", experience_years: 9, fee: 250000, verification: "verified" },
    { id: 2, user_id: 3, name: "dr. Bagus Prakoso, Sp.JP", email: "bagus@rs.demo", specialization_id: 4, specialization: "Jantung", license: "SIP-2020-00871", experience_years: 12, fee: 400000, verification: "verified" },
    { id: 3, user_id: 4, name: "dr. Nadia Kusuma, Sp.A", email: "nadia@rs.demo", specialization_id: 3, specialization: "Anak", license: "SIP-2022-00455", experience_years: 6, fee: 300000, verification: "verified" },
    { id: 4, user_id: null, name: "dr. Fajar Ramadhan", email: "fajar@rs.demo", specialization_id: 1, specialization: "Dokter Umum", license: "SIP-2024-01190", experience_years: 2, fee: 120000, verification: "pending" },
    { id: 5, user_id: null, name: "dr. Melati Anjani, Sp.KK", email: "melati@rs.demo", specialization_id: 5, specialization: "Kulit & Kelamin", license: "SIP-2023-00777", experience_years: 5, fee: 275000, verification: "pending" },
    { id: 6, user_id: null, name: "dr. Hendra Surya, Sp.S", email: "hendra@rs.demo", specialization_id: 6, specialization: "Saraf", license: "SIP-2019-00031", experience_years: 15, fee: 350000, verification: "rejected" }
  ];

  var patients = [
    { id: 1, user_id: 5, name: "Vincent Tiono", email: "vincent@mail.demo", phone: "0812000001", birth_date: "2004-05-11", address: "Jl. Kemuning No. 12, Banjarbaru", gender: "L", created_at: iso(-40) },
    { id: 2, user_id: 6, name: "Ayu Lestari", email: "ayu@mail.demo", phone: "0812000002", birth_date: "1999-02-20", address: "Jl. Ahmad Yani No. 88", gender: "P", created_at: iso(-30) },
    { id: 3, user_id: 7, name: "Rudi Hartono", email: "rudi@mail.demo", phone: "0812000003", birth_date: "1988-11-02", address: "Jl. Guntung Manggis No. 5", gender: "L", created_at: iso(-20) }
  ];

  var appointments = [
    { id: 1, code: "APT-1001", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(1), start_time: "09:00", end_time: "09:30", status: "pending", reason: "Kontrol gula darah, sering lemas setelah makan.", rejection_reason: null, created_at: now(-20), history: [{ at: now(-20), action: "Dibuat", by: "Vincent Tiono", note: "Pengajuan janji temu" }] },
    { id: 2, code: "APT-1002", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 2, doctor_name: "dr. Bagus Prakoso, Sp.JP", specialization: "Jantung", date: iso(3), start_time: "10:30", end_time: "11:00", status: "confirmed", reason: "Nyeri dada saat olahraga.", rejection_reason: null, created_at: now(-70), history: [{ at: now(-70), action: "Dibuat", by: "Vincent Tiono", note: "Pengajuan janji temu" }, { at: now(-60), action: "Diterima", by: "dr. Bagus Prakoso, Sp.JP", note: "Silakan datang 15 menit lebih awal." }] },
    { id: 3, code: "APT-1003", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(-6), start_time: "08:30", end_time: "09:00", status: "cancelled", reason: "Kontrol rutin bulanan.", rejection_reason: null, cancellation_reason: "Dijadwalkan ulang ke jadwal baru", created_at: now(-200), history: [{ at: now(-200), action: "Dibuat", by: "Vincent Tiono", note: "" }, { at: now(-180), action: "Dijadwalkan ulang", by: "dr. Sari Wijaya, Sp.PD", note: "Dari " + iso(-10) + " 08:30 ke " + iso(-6) + " 08:30 karena jadwal operasi." }] },
    { id: 13, code: "APT-1013", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(2), start_time: "10:00", end_time: "10:30", status: "pending", reason: "Kontrol rutin bulanan.", rejection_reason: null, cancellation_reason: null, rescheduled_from_id: 3, rescheduled_by: "Vincent Tiono", created_at: now(-170), history: [{ at: now(-170), action: "Dibuat (hasil penjadwalan ulang)", by: "Vincent Tiono", note: "Menggantikan jadwal " + iso(-6) + " 08:30" }] },
    { id: 4, code: "APT-1004", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 3, doctor_name: "dr. Nadia Kusuma, Sp.A", specialization: "Anak", date: iso(-14), start_time: "13:00", end_time: "13:30", status: "cancelled", reason: "Konsultasi imunisasi.", rejection_reason: "Dibatalkan pasien: demam saat hari H.", created_at: now(-400), history: [{ at: now(-400), action: "Dibuat", by: "Vincent Tiono", note: "" }, { at: now(-330), action: "Dibatalkan", by: "Vincent Tiono", note: "Demam saat hari H." }] },
    { id: 5, code: "APT-1005", patient_id: 2, patient_name: "Ayu Lestari", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(0), start_time: "09:00", end_time: "09:30", status: "confirmed", reason: "Hasil lab darah perlu ditinjau.", rejection_reason: null, created_at: now(-26), history: [{ at: now(-26), action: "Dibuat", by: "Ayu Lestari", note: "" }] },
    { id: 6, code: "APT-1006", patient_id: 2, patient_name: "Ayu Lestari", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(0), start_time: "10:00", end_time: "10:30", status: "pending", reason: "Pusing berulang sejak seminggu.", rejection_reason: null, created_at: now(-8), history: [{ at: now(-8), action: "Dibuat", by: "Ayu Lestari", note: "" }] },
    { id: 7, code: "APT-1007", patient_id: 3, patient_name: "Rudi Hartono", doctor_id: 2, doctor_name: "dr. Bagus Prakoso, Sp.JP", specialization: "Jantung", date: iso(0), start_time: "11:00", end_time: "11:30", status: "confirmed", reason: "Rekam EKG lanjutan.", rejection_reason: null, created_at: now(-50), history: [{ at: now(-50), action: "Dibuat", by: "Rudi Hartono", note: "" }] },
    { id: 8, code: "APT-1008", patient_id: 3, patient_name: "Rudi Hartono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(-2), start_time: "14:00", end_time: "14:30", status: "completed", reason: "Kontrol kolesterol.", rejection_reason: null, created_at: now(-150), history: [{ at: now(-150), action: "Dibuat", by: "Rudi Hartono", note: "" }, { at: now(-140), action: "Diterima", by: "dr. Sari Wijaya, Sp.PD", note: "" }, { at: now(-50), action: "Selesai", by: "dr. Sari Wijaya, Sp.PD", note: "Rekam medis difinalisasi." }] },
    { id: 9, code: "APT-1009", patient_id: 2, patient_name: "Ayu Lestari", doctor_id: 3, doctor_name: "dr. Nadia Kusuma, Sp.A", specialization: "Anak", date: iso(2), start_time: "15:00", end_time: "15:30", status: "rejected", reason: "Konsultasi tumbuh kembang.", rejection_reason: "Dokter sedang cuti pada tanggal tersebut.", created_at: now(-90), history: [{ at: now(-90), action: "Dibuat", by: "Ayu Lestari", note: "" }, { at: now(-80), action: "Ditolak", by: "dr. Nadia Kusuma, Sp.A", note: "Dokter sedang cuti pada tanggal tersebut." }] },
    { id: 10, code: "APT-1010", patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(5), start_time: "09:30", end_time: "10:00", status: "pending", reason: "Minta rujukan fisioterapi.", rejection_reason: null, created_at: now(-4), history: [{ at: now(-4), action: "Dibuat", by: "Vincent Tiono", note: "" }] },
    { id: 11, code: "APT-1011", patient_id: 3, patient_name: "Rudi Hartono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", specialization: "Penyakit Dalam", date: iso(7), start_time: "16:00", end_time: "16:30", status: "pending", reason: "Sesak napas ringan.", rejection_reason: null, created_at: now(-2), history: [{ at: now(-2), action: "Dibuat", by: "Rudi Hartono", note: "" }] },
    { id: 12, code: "APT-1012", patient_id: 2, patient_name: "Ayu Lestari", doctor_id: 2, doctor_name: "dr. Bagus Prakoso, Sp.JP", specialization: "Jantung", date: iso(4), start_time: "08:00", end_time: "08:30", status: "confirmed", reason: "Ekokardiografi.", rejection_reason: null, created_at: now(-12), history: [{ at: now(-12), action: "Dibuat", by: "Ayu Lestari", note: "" }] }
  ];

  var schedules = [
    { doctor_id: 1, day: 1, start: "08:00", end: "12:00", active: true },
    { doctor_id: 1, day: 3, start: "08:00", end: "12:00", active: true },
    { doctor_id: 1, day: 5, start: "13:00", end: "16:00", active: true },
    { doctor_id: 2, day: 2, start: "09:00", end: "13:00", active: true },
    { doctor_id: 2, day: 4, start: "09:00", end: "12:00", active: false },
    { doctor_id: 2, day: 6, start: "09:00", end: "12:00", active: true }
  ];

  var leaves = [
    { id: 1, doctor_id: 1, date: iso(2), type: "cuti", start: null, end: null, note: "Cuti tahunan" },
    { id: 2, doctor_id: 2, date: iso(1), type: "jam_khusus", start: "13:00", end: "15:00", note: "Operasi terjadwal" },
    { id: 3, doctor_id: 1, date: iso(5), type: "jam_khusus", start: "08:00", end: "09:00", note: "Rapat komite medis" }
  ];

  var records = [
    {
      id: 1, patient_id: 3, patient_name: "Rudi Hartono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", appointment_id: 8,
      date: iso(-2), diagnosis: "Hiperkolesterolemia ringan (E78.0)", notes: "LDL 168 mg/dL. Disarankan diet rendah lemak dan aktivitas aerobik 30 menit, 5x/minggu. Kontrol 1 bulan.",
      status: "finalized", finalized_at: now(-49),
      prescriptions: [
        { name: "Simvastatin", dose: "20 mg", frequency: "1x sehari", duration: "30 hari", qty: 30, instructions: "Diminum malam sebelum tidur" },
        { name: "Vitamin B Complex", dose: "1 tablet", frequency: "1x sehari", duration: "14 hari", qty: 14, instructions: "Setelah makan pagi" }
      ],
      revisions: []
    },
    {
      id: 2, patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", appointment_id: 3,
      date: iso(-6), diagnosis: "Gula darah puasa borderline (R73.0)", notes: "GDP 118 mg/dL. Edukasi pola makan, olahraga, dan pemantauan gula darah mandiri.",
      status: "finalized", finalized_at: now(-140),
      prescriptions: [{ name: "Metformin", dose: "500 mg", frequency: "2x sehari", duration: "30 hari", qty: 60, instructions: "Setelah makan, pagi dan malam" }],
      revisions: [{ at: now(-130), by: "dr. Sari Wijaya, Sp.PD", reason: "Salah ketik dosis: tertulis 1000 mg, seharusnya 500 mg.", before: "Metformin 1000 mg 2x sehari", after: "Metformin 500 mg 2x sehari" }]
    },
    {
      id: 3, patient_id: 1, patient_name: "Vincent Tiono", doctor_id: 1, doctor_name: "dr. Sari Wijaya, Sp.PD", appointment_id: null,
      date: iso(0), diagnosis: "", notes: "Anamnesis awal: sering lemas setelah makan. Menunggu hasil lab.", status: "draft", finalized_at: null, prescriptions: [], revisions: []
    }
  ];

  var auditLogs = [
    { id: 1, at: now(-1), user: "Admin Rumah Sakit", role: "admin", action: "LOGIN", resource: "auth", result: "success", ip: "10.0.2.15" },
    { id: 2, at: now(-2), user: "dr. Sari Wijaya, Sp.PD", role: "dokter", action: "FINALIZE_RECORD", resource: "record#1", result: "success", ip: "10.0.2.31" },
    { id: 3, at: now(-5), user: "dr. Nadia Kusuma, Sp.A", role: "dokter", action: "LOGIN", resource: "auth", result: "success", ip: "10.0.2.44" },
    { id: 4, at: now(-6), user: "dr. Bagus Prakoso, Sp.JP", role: "dokter", action: "ACCESS_DENIED", resource: "admin/users", result: "failure", ip: "10.0.2.52" },
    { id: 5, at: now(-9), user: "vincent@mail.demo", role: "pasien", action: "LOGIN_FAILED", resource: "auth", result: "failure", ip: "10.0.2.15" },
    { id: 6, at: now(-20), user: "Vincent Tiono", role: "pasien", action: "CREATE_APPOINTMENT", resource: "appointment#1", result: "success", ip: "10.0.2.15" },
    { id: 7, at: now(-26), user: "Ayu Lestari", role: "pasien", action: "CREATE_APPOINTMENT", resource: "appointment#5", result: "success", ip: "10.0.2.77" },
    { id: 8, at: now(-30), user: "Admin Rumah Sakit", role: "admin", action: "UPDATE_SPECIALIZATION", resource: "specialization#6", result: "success", ip: "10.0.2.15" },
    { id: 9, at: now(-48), user: "dr. Nadia Kusuma, Sp.A", role: "dokter", action: "LOGIN_FAILED", resource: "auth", result: "failure", ip: "10.0.2.44" },
    { id: 10, at: now(-70), user: "Vincent Tiono", role: "pasien", action: "CREATE_APPOINTMENT", resource: "appointment#2", result: "success", ip: "10.0.2.15" }
  ];

  var failCount = {};
  var lockUntil = {};
  var SESSIONS = {
    admin: { user_id: 1, name: "Admin Rumah Sakit", email: "admin@rs.demo", role: "admin", subtitle: "Kendali penuh sistem", authorized: true },
    dokter: { user_id: 2, name: "dr. Sari Wijaya, Sp.PD", email: "sari@rs.demo", role: "dokter", subtitle: "Penyakit Dalam · Terverifikasi", doctor_id: 1, authorized: true },
    pasien: { user_id: 5, name: "Vincent Tiono", email: "vincent@mail.demo", role: "pasien", subtitle: "Pasien terdaftar", patient_id: 1, authorized: true }
  };

  function sessionFor(role) {
    var s = JSON.parse(JSON.stringify(SESSIONS[role] || SESSIONS.pasien));
    s.last_seen = Date.now();
    s.expires_at = Date.now() + (CFG.SESSION_ABSOLUTE_MINUTES || 120) * 60000;
    return s;
  }

  function audit(user, role, action, resource, result) {
    auditLogs.unshift({ id: nextId(auditLogs), at: new Date().toISOString(), user: user, role: role, action: action, resource: resource, result: result, ip: "10.0.2.15" });
  }

  function slotsForDoctor(doctorId, days) {
    var out = [], DAYS = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    for (var i = 0; i < days; i++) {
      var d = new Date(); d.setDate(d.getDate() + i);
      var dateStr = d.toISOString().slice(0, 10);
      var dow = d.getDay();
      var slots = [];
      var lv = leaves.filter(function (l) { return l.doctor_id === doctorId && l.date === dateStr; });
      var cuti = lv.some(function (l) { return l.type === "cuti"; });
      if (!cuti) {
        schedules.filter(function (s) { return s.doctor_id === doctorId && s.day === dow && s.active; }).forEach(function (s) {
          var t = s.start;
          while (t < s.end) {
            var end = addMin(t, 30);
            if (end > s.end) break;
            var blockedByLeave = lv.some(function (l) { return l.type === "jam_khusus" && l.start && t >= l.start && t < l.end; });
            var taken = appointments.some(function (a) {
              return a.doctor_id === doctorId && a.date === dateStr && a.start_time === t && ["pending", "confirmed"].indexOf(a.status) > -1;
            });
            slots.push({ start: t, end: end, available: !blockedByLeave && !taken });
            t = end;
          }
        });
      }
      out.push({ date: dateStr, day: DAYS[dow], slots: slots });
    }
    return out;
  }

  var DB_KEY = "rs_demo_db";
  var DB_VERSION = 5;
  function snapshot() {
    try {
      sessionStorage.setItem(DB_KEY, JSON.stringify({
        v: DB_VERSION, users: users, doctors: doctors, patients: patients, appointments: appointments,
        records: records, schedules: schedules, leaves: leaves,
        specializations: specializations, auditLogs: auditLogs.slice(0, 200)
      }));
    } catch (e) {}
  }
  function rehydrate() {
    try {
      var raw = sessionStorage.getItem(DB_KEY);
      if (!raw) return;
      var d = JSON.parse(raw);
      if (d.v !== DB_VERSION) { sessionStorage.removeItem(DB_KEY); return; }
      if (d.users) users = d.users;
      if (d.doctors) doctors = d.doctors;
      if (d.patients) patients = d.patients;
      if (d.appointments) appointments = d.appointments;
      if (d.records) records = d.records;
      if (d.schedules) schedules = d.schedules;
      if (d.leaves) leaves = d.leaves;
      if (d.specializations) specializations = d.specializations;
      if (d.auditLogs) auditLogs = d.auditLogs;
    } catch (e) {}
  }
  rehydrate();

  function json(data) { return new Promise(function (res) { setTimeout(function () { res(data); }, LAT); }); }
  function fail(status, message) { var e = new global.App.ApiError(status, message); return new Promise(function (_, rej) { setTimeout(function () { rej(e); }, LAT); }); }

  var ROUTES = [];
  function on(method, pattern, handler) {
    var keys = [];
    var re = new RegExp("^" + pattern.replace(/:(\w+)/g, function (_, k) { keys.push(k); return "([^/]+)"; }) + "$");
    ROUTES.push({ method: method, re: re, keys: keys, handler: handler });
  }

  function match(method, path) {
    var clean = path.split("?")[0];
    var query = {};
    if (path.indexOf("?") > -1) path.split("?")[1].split("&").forEach(function (kv) { var p = kv.split("="); query[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ""); });
    for (var i = 0; i < ROUTES.length; i++) {
      var r = ROUTES[i];
      if (r.method !== method) continue;
      var m = clean.match(r.re);
      if (m) { var params = {}; r.keys.forEach(function (k, idx) { params[k] = m[idx + 1]; }); return { handler: r.handler, params: params, query: query }; }
    }
    return null;
  }

  on("POST", "/api/login", function (body) {
    var email = String((body && body.email) || "").trim().toLowerCase();
    var u = users.filter(function (x) { return x.email.toLowerCase() === email; })[0];
    if (lockUntil[email] && lockUntil[email] > Date.now()) {
      var mins = Math.ceil((lockUntil[email] - Date.now()) / 60000);
      return fail(423, "Akun terkunci sementara. Coba lagi dalam " + mins + " menit.");
    }
    var roleHint = (body && (body.role || body.role_hint)) || null;
    if (!u && !roleHint) { audit(email, "-", "LOGIN_FAILED", "auth", "failure"); return fail(401, "Email atau password salah."); }
    if ((body && body.password) === "wrong") {
      failCount[email] = (failCount[email] || 0) + 1;
      audit(email, u ? u.role : "-", "LOGIN_FAILED", "auth", "failure");
      if (failCount[email] >= 5) { lockUntil[email] = Date.now() + 15 * 60000; failCount[email] = 0; return fail(423, "Terlalu banyak percobaan gagal. Akun terkunci 15 menit."); }
      return fail(401, "Email atau password salah.");
    }

    var role = roleHint || (u ? u.role : "pasien");
    if (u && u.status === "suspended") return fail(403, "Akun Anda ditangguhkan. Hubungi administrator.");
    var s = sessionFor(role);
    if (u && (!roleHint || roleHint === u.role)) { s.name = u.name; s.email = u.email; s.user_id = u.id; u.last_login = new Date().toISOString(); }
    audit(s.name, s.role, "LOGIN", "auth", "success");
    return json({ session: s });
  });

  on("POST", "/api/logout", function () {
    var s = global.App.Session.get();
    audit(s ? s.name : "-", s ? s.role : "-", "LOGOUT", "auth", "success");
    return json({ ok: true });
  });

  on("POST", "/api/register/pasien", function (b) {
    var id = nextId(patients);
    patients.push({ id: id, user_id: null, name: b.name, email: b.email, phone: b.phone, date_of_birth: b.birth_date, gender: b.gender, address: b.address, created_at: iso(0) });
    users.push({ id: nextId(users), name: b.name, email: b.email, role: "pasien", locked_until: null, last_login: null, phone: b.phone, created_at: iso(0) });
    audit(b.email, "pasien", "REGISTER_PATIENT", "patient#" + id, "success");
    return json({ ok: true, id: id });
  });

  on("POST", "/api/register/dokter", function (b) {
    var id = nextId(doctors);
    doctors.push({ id: id, user_id: null, name: b.name, email: b.email, specialization_id: Number(b.specialization_id), specialization: (specializations.filter(function (s) { return String(s.id) === String(b.specialization_id); })[0] || {}).name || "-", license: b.license, experience_years: Number(b.experience_years), fee: Number(b.fee), verification: "pending"});
    audit(b.email, "dokter", "REGISTER_DOCTOR", "doctor#" + id, "success");
    return json({ ok: true, id: id, verification: "pending" });
  });

  on("POST", "/api/change-password", function () {
    var s = global.App.Session.get();
    audit(s ? s.name : "-", s ? s.role : "-", "CHANGE_PASSWORD", "auth", "success");
    return json({ ok: true });
  });

  on("GET", "/api/me", function (_, _p, _q, ctx) { return json({ session: ctx.session() }); });

  on("GET", "/api/specializations", function () { return json({ data: specializations }); });
  on("GET", "/api/doctors", function (_, _p, q) {
    var rows = doctors.filter(function (d) {
      if (d.verification !== "verified") return false;
      var u = users.filter(function (x) { return x.id === d.user_id; })[0];
      return !u || u.status === "active";
    });
    if (q.specialization) rows = rows.filter(function (d) { return String(d.specialization_id) === String(q.specialization); });
    if (q.q) rows = rows.filter(function (d) { return (d.name + " " + d.specialization).toLowerCase().indexOf(String(q.q).toLowerCase()) > -1; });
    return json({ data: rows });
  });
  on("GET", "/api/doctors/:id", function (_, p) {
    var d = doctors.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!d) return fail(404, "Dokter tidak ditemukan.");
    return json({ data: d, schedules: schedules.filter(function (s) { return s.doctor_id === d.id; }), leaves: leaves.filter(function (l) { return l.doctor_id === d.id; }) });
  });
  on("GET", "/api/doctors/:id/slots", function (_, p, q) { return json({ data: slotsForDoctor(Number(p.id), Number(q.days || 7)) }); });

  function currentPatient() {
    var s = global.App.Session.get();
    if (!s) return fail(401, "Sesi berakhir.");
    if (s.role !== "pasien") return fail(403, "Hanya pasien yang dapat mengakses data ini.");
    return patients.filter(function (p) { return p.id === s.patient_id; })[0] || patients[0];
  }
  on("GET", "/api/pasien/appointments", function () {
    var pt = currentPatient();
    if (pt && pt.then) return pt;
    var rows = appointments.filter(function (a) { return a.patient_id === pt.id; }).sort(function (a, b) { return (b.date + b.start_time).localeCompare(a.date + a.start_time); });
    return json({ data: rows });
  });
  on("GET", "/api/pasien/appointments/upcoming", function () {
    var pt = patients[0];
    var today = iso(0);
    var rows = appointments.filter(function (a) { return a.patient_id === pt.id && a.date >= today && ["pending", "confirmed"].indexOf(a.status) > -1; });
    return json({ data: rows.sort(function (a, b) { return (a.date + a.start_time).localeCompare(b.date + b.start_time); }) });
  });
  on("GET", "/api/pasien/appointments/:id", function (_, p) {
    var a = appointments.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!a) return fail(404, "Janji temu tidak ditemukan.");
    return json({ data: a });
  });
  on("POST", "/api/pasien/appointments", function (b) {
    var s = global.App.Session.get();
    if (!b || !b.doctor_id || !b.date || !b.start_time) return fail(422, "Data janji temu belum lengkap.");
    var d = doctors.filter(function (x) { return String(x.id) === String(b.doctor_id); })[0] || {};
    var pt = patients[0];
    var id = nextId(appointments);
    appointments.push({
      id: id, code: "APT-" + (1000 + id), patient_id: pt.id, patient_name: pt.name, doctor_id: Number(b.doctor_id), doctor_name: d.name, specialization: d.specialization,
      date: b.date, start_time: b.start_time, end_time: addMin(b.start_time, 30), status: "pending", reason: b.reason, rejection_reason: null, created_at: new Date().toISOString(),
      history: [{ at: new Date().toISOString(), action: "Dibuat", by: pt.name, note: "Pengajuan janji temu" }]
    });
    audit(pt.name, "pasien", "CREATE_APPOINTMENT", "appointment#" + id, "success");
    return json({ ok: true, id: id, code: "APT-" + (1000 + id) });
  });
  on("POST", "/api/pasien/appointments/:id/cancel", function (b, p) {
    var a = appointments.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!a) return fail(404, "Janji temu tidak ditemukan.");
    if (!b || !b.reason || b.reason.trim().length < 5) return fail(422, "Alasan pembatalan wajib diisi (minimal 5 karakter).");
    a.status = "cancelled"; a.cancellation_reason = b.reason; a.cancelled_by = a.patient_name; a.cancelled_at = new Date().toISOString();
    a.history.push({ at: new Date().toISOString(), action: "Dibatalkan", by: a.patient_name, note: b.reason });
    audit(a.patient_name, "pasien", "CANCEL_APPOINTMENT", "appointment#" + a.id, "success");
    return json({ ok: true, data: a });
  });
  on("POST", "/api/pasien/appointments/:id/reschedule", function (b, p) {
    var a = appointments.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!a) return fail(404, "Janji temu tidak ditemukan.");
    if (!b || !b.date || !b.start_time) return fail(422, "Tanggal dan jam baru wajib dipilih.");
    var from = a.date + " " + a.start_time;

    var newId = nextId(appointments);
    var moved = {
      id: newId, code: "APT-" + (1000 + newId), patient_id: a.patient_id, patient_name: a.patient_name,
      doctor_id: a.doctor_id, doctor_name: a.doctor_name, specialization: a.specialization,
      date: b.date, start_time: b.start_time, end_time: addMin(b.start_time, 30), status: "pending",
      reason: a.reason, rejection_reason: null, cancellation_reason: null,
      rescheduled_from_id: a.id, rescheduled_by: a.patient_name, created_at: new Date().toISOString(),
      history: [{ at: new Date().toISOString(), action: "Dibuat (hasil penjadwalan ulang)", by: a.patient_name, note: "Menggantikan jadwal " + from + (b.reason ? " — " + b.reason : "") }]
    };
    a.status = "cancelled";
    a.cancellation_reason = "Dijadwalkan ulang ke " + b.date + " " + b.start_time;
    a.cancelled_by = a.patient_name;
    a.cancelled_at = new Date().toISOString();
    a.history.push({ at: new Date().toISOString(), action: "Dijadwalkan ulang", by: a.patient_name, note: "Dari " + from + " ke " + b.date + " " + b.start_time + (b.reason ? " — " + b.reason : "") });
    appointments.push(moved);
    audit(a.patient_name, "pasien", "RESCHEDULE_APPOINTMENT", "appointment#" + newId, "success");
    return json({ ok: true, id: newId, data: moved });
  });
  on("GET", "/api/pasien/records", function () {
    var pt = patients[0];
    return json({ data: records.filter(function (r) { return r.patient_id === pt.id && r.status === "finalized"; }) });
  });
  on("GET", "/api/pasien/records/:id", function (_, p) {
    var r = records.filter(function (x) { return String(x.id) === String(p.id) && x.status === "finalized"; })[0];
    if (!r) return fail(404, "Rekam medis tidak ditemukan atau belum difinalisasi.");
    return json({ data: r });
  });
  on("GET", "/api/pasien/profile", function () { return json({ data: patients[0] }); });
  on("PUT", "/api/pasien/profile", function (b) {
    var pt = patients[0];
    ["name", "phone", "address", "gender", "birth_date"].forEach(function (k) { if (b[k] !== undefined) pt[k] = b[k]; });
    audit(pt.name, "pasien", "UPDATE_PROFILE", "patient#" + pt.id, "success");
    return json({ ok: true, data: pt });
  });

  function myDoctor() { var s = global.App.Session.get(); return doctors.filter(function (d) { return s && d.id === s.doctor_id; })[0] || doctors[0]; }
  on("GET", "/api/dokter/appointments", function (_, _p, q) {
    var me = myDoctor();
    var rows = appointments.filter(function (a) { return a.doctor_id === me.id; });
    if (q.date) rows = rows.filter(function (a) { return a.date === q.date; });
    if (q.status) rows = rows.filter(function (a) { return a.status === q.status; });
    return json({ data: rows.sort(function (a, b) { return (b.date + b.start_time).localeCompare(a.date + a.start_time); }) });
  });
  function apptAction(id, status, reason) {
    var me = myDoctor();
    var a = appointments.filter(function (x) { return String(x.id) === String(id) && x.doctor_id === me.id; })[0];
    if (!a) return fail(404, "Janji temu tidak ditemukan untuk dokter ini.");
    if (status === "rejected" && (!reason || reason.trim().length < 5)) return fail(422, "Alasan penolakan wajib diisi (minimal 5 karakter).");
    var labels = { confirmed: "Diterima", rejected: "Ditolak", completed: "Selesai" };
    a.status = status;
    if (status === "rejected") a.rejection_reason = reason;
    a.history.push({ at: new Date().toISOString(), action: labels[status], by: me.name, note: reason || "" });
    audit(me.name, "dokter", status.toUpperCase() + "_APPOINTMENT", "appointment#" + a.id, "success");
    return json({ ok: true, data: a });
  }
  on("POST", "/api/dokter/appointments/:id/accept", function (_, p) { return apptAction(p.id, "confirmed"); });
  on("POST", "/api/dokter/appointments/:id/reject", function (b, p) { return apptAction(p.id, "rejected", b && b.reason); });
  on("POST", "/api/dokter/appointments/:id/complete", function (_, p) { return apptAction(p.id, "completed"); });
  on("GET", "/api/dokter/schedule", function () {
    var me = myDoctor();
    return json({ data: schedules.filter(function (s) { return s.doctor_id === me.id; }), days: ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"] });
  });
  on("PUT", "/api/dokter/schedule", function (b) {
    var me = myDoctor();
    if (!b || !Array.isArray(b.slots)) return fail(422, "Format jadwal tidak valid.");
    for (var i = 0; i < b.slots.length; i++) {
      var s = b.slots[i];
      if (s.active && (!s.start || !s.end || String(s.end) <= String(s.start))) return fail(422, "Jam selesai harus lebih besar dari jam mulai (hari ke-" + (Number(s.day) + 1) + ").");
    }
    schedules = schedules.filter(function (s) { return s.doctor_id !== me.id; });
    b.slots.forEach(function (s) { schedules.push({ doctor_id: me.id, day: Number(s.day), start: s.start, end: s.end, active: !!s.active }); });
    audit(me.name, "dokter", "UPDATE_SCHEDULE", "doctor#" + me.id, "success");
    return json({ ok: true });
  });
  on("GET", "/api/dokter/leaves", function () {
    var me = myDoctor();
    return json({ data: leaves.filter(function (l) { return l.doctor_id === me.id; }) });
  });
  on("POST", "/api/dokter/leaves", function (b) {
    var me = myDoctor();
    if (!b || !b.date) return fail(422, "Tanggal wajib diisi.");
    if (b.type === "jam_khusus" && (!b.start || !b.end || String(b.end) <= String(b.start))) return fail(422, "Jam selesai harus lebih besar dari jam mulai.");
    var id = nextId(leaves);
    leaves.push({ id: id, doctor_id: me.id, date: b.date, type: b.type || "cuti", start: b.start || null, end: b.end || null, note: b.note || "" });
    audit(me.name, "dokter", "CREATE_LEAVE", "leave#" + id, "success");
    return json({ ok: true, id: id });
  });
  on("DELETE", "/api/dokter/leaves/:id", function (_, p) {
    var me = myDoctor();
    leaves = leaves.filter(function (l) { return !(String(l.id) === String(p.id) && l.doctor_id === me.id); });
    audit(me.name, "dokter", "DELETE_LEAVE", "leave#" + p.id, "success");
    return json({ ok: true });
  });
  on("GET", "/api/dokter/records", function () {
    var me = myDoctor();
    return json({ data: records.filter(function (r) { return r.doctor_id === me.id; }).sort(function (a, b) { return b.date.localeCompare(a.date); }) });
  });
  on("GET", "/api/dokter/records/:id", function (_, p) {
    var r = records.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!r) return fail(404, "Rekam medis tidak ditemukan.");
    return json({ data: r });
  });
  on("POST", "/api/dokter/records", function (b) {
    var me = myDoctor();
    if (!b || !b.diagnosis) return fail(422, "Diagnosis wajib diisi.");
    var id = nextId(records);
    var a = appointments.filter(function (x) { return String(x.id) === String(b.appointment_id); })[0];
    records.unshift({
      id: id, patient_id: a ? a.patient_id : 1, patient_name: a ? a.patient_name : "Tanpa nama", doctor_id: me.id, doctor_name: me.name,
      appointment_id: a ? a.id : null, date: iso(0), diagnosis: b.diagnosis, notes: b.notes || "",
      status: b.finalize ? "finalized" : "draft", finalized_at: b.finalize ? new Date().toISOString() : null,
      prescriptions: b.prescriptions || [], revisions: []
    });
    audit(me.name, "dokter", b.finalize ? "FINALIZE_RECORD" : "SAVE_DRAFT_RECORD", "record#" + id, "success");
    return json({ ok: true, id: id, status: b.finalize ? "finalized" : "draft" });
  });
  on("PUT", "/api/dokter/records/:id", function (b, p) {
    var me = myDoctor();
    var r = records.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!r) return fail(404, "Rekam medis tidak ditemukan.");
    if (r.status === "finalized" && (!b.reason || b.reason.trim().length < 5)) return fail(422, "Alasan revisi wajib diisi untuk rekam medis yang sudah final.");
    if (r.status === "finalized") {
      r.revisions.push({ at: new Date().toISOString(), by: me.name, reason: b.reason, before: r.diagnosis, after: b.diagnosis });
      r.status = "revised";
    }
    if (b.diagnosis !== undefined) r.diagnosis = b.diagnosis;
    if (b.notes !== undefined) r.notes = b.notes;
    if (b.prescriptions !== undefined) r.prescriptions = b.prescriptions;
    audit(me.name, "dokter", "REVISE_RECORD", "record#" + r.id, "success");
    return json({ ok: true, data: r });
  });
  on("POST", "/api/dokter/records/:id/finalize", function (_, p) {
    var r = records.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!r) return fail(404, "Rekam medis tidak ditemukan.");
    r.status = "finalized"; r.finalized_at = new Date().toISOString();
    audit(myDoctor().name, "dokter", "FINALIZE_RECORD", "record#" + r.id, "success");
    return json({ ok: true, data: r });
  });
  on("GET", "/api/dokter/records/:id/revisions", function (_, p) {
    var r = records.filter(function (x) { return String(x.id) === String(p.id); })[0];
    return json({ data: r ? r.revisions : [], record: r || null });
  });
  on("GET", "/api/dokter/profile", function () {
    var me = myDoctor();
    return json({ data: me, verification: { status: me.verification, note: me.verification === "verified" ? "Terverifikasi oleh admin pada " + iso(-30) : me.verification === "pending" ? "Menunggu peninjauan admin." : "Ditolak: nomor SIP tidak sesuai berkas." } });
  });
  on("PUT", "/api/dokter/profile", function (b) {
    var me = myDoctor();
    ["specialization_id", "fee", "experience_years"].forEach(function (k) { if (b[k] !== undefined) me[k] = b[k]; });
    audit(me.name, "dokter", "UPDATE_PROFILE", "doctor#" + me.id, "success");
    return json({ ok: true, data: me });
  });

  on("GET", "/api/admin/summary", function () {
    var pending = doctors.filter(function (d) { return d.verification === "pending"; }).length;
    var active = users.filter(function (u) { return u.status === "active"; }).length;
    var locked = users.filter(function (u) { return u.locked_until && new Date(u.locked_until) > new Date(); }).length;
    var today = appointments.filter(function (a) { return a.date === iso(0); }).length;
    return json({ data: { pending_doctors: pending, active_users: active, locked_users: locked, appointments_today: today, total_doctors: doctors.length, total_patients: patients.length, failed_logins_24h: auditLogs.filter(function (l) { return l.action === "LOGIN_FAILED"; }).length } });
  });
  on("GET", "/api/admin/doctors/pending", function () {
    return json({ data: doctors.filter(function (d) { return d.verification === "pending"; }) });
  });
  on("GET", "/api/admin/doctors", function () { return json({ data: doctors }); });
  on("POST", "/api/admin/doctors/:id/verify", function (b, p) {
    var d = doctors.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!d) return fail(404, "Dokter tidak ditemukan.");
    if (!b || ["verified", "rejected"].indexOf(b.decision) === -1) return fail(422, "Keputusan verifikasi tidak valid.");
    if (b.decision === "rejected" && (!b.note || b.note.trim().length < 5)) return fail(422, "Alasan penolakan wajib diisi (minimal 5 karakter).");
    d.verification = b.decision;

    audit(global.App.Session.get().name, "admin", b.decision === "verified" ? "VERIFY_DOCTOR" : "REJECT_DOCTOR", "doctor#" + d.id, "success");
    return json({ ok: true, data: d });
  });
  on("GET", "/api/admin/users", function () { return json({ data: users }); });
  on("PUT", "/api/admin/users/:id/status", function (b, p) {
    var u = users.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!u) return fail(404, "User tidak ditemukan.");
    if (!b || ["active", "inactive", "suspended"].indexOf(b.status) === -1) return fail(422, "Status tidak valid.");
    if (u.role === "admin" && b.status !== "active") return fail(403, "Akun administrator tidak dapat dinonaktifkan dari halaman ini.");
    var before = u.status; u.status = b.status;
    audit(global.App.Session.get().name, "admin", "UPDATE_USER_STATUS", "user#" + u.id, "success");
    return json({ ok: true, data: u, before: before });
  });
  on("POST", "/api/admin/users/:id/unlock", function (_, p) {
    var u = users.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!u) return fail(404, "User tidak ditemukan.");
    u.locked_until = null; delete lockUntil[u.email.toLowerCase()]; failCount[u.email.toLowerCase()] = 0;
    audit(global.App.Session.get().name, "admin", "UNLOCK_USER", "user#" + u.id, "success");
    return json({ ok: true, data: u });
  });
  on("GET", "/api/admin/specializations", function () { return json({ data: specializations }); });
  on("POST", "/api/admin/specializations", function (b) {
    if (!b || !b.name) return fail(422, "Nama spesialisasi wajib diisi.");
    if (specializations.some(function (s) { return s.name.toLowerCase() === String(b.name).toLowerCase(); })) return fail(409, "Nama spesialisasi sudah ada.");
    var id = nextId(specializations);
    specializations.push({ id: id, name: b.name, description: b.description || "" });
    audit(global.App.Session.get().name, "admin", "CREATE_SPECIALIZATION", "specialization#" + id, "success");
    return json({ ok: true, id: id });
  });
  on("PUT", "/api/admin/specializations/:id", function (b, p) {
    var s = specializations.filter(function (x) { return String(x.id) === String(p.id); })[0];
    if (!s) return fail(404, "Spesialisasi tidak ditemukan.");
    ["name", "description"].forEach(function (k) { if (b[k] !== undefined) s[k] = b[k]; });
    audit(global.App.Session.get().name, "admin", "UPDATE_SPECIALIZATION", "specialization#" + s.id, "success");
    return json({ ok: true, data: s });
  });
  on("GET", "/api/admin/audit-logs", function (_, _p, q) {
    var rows = auditLogs.slice();
    if (q.user) rows = rows.filter(function (l) { return l.user.toLowerCase().indexOf(String(q.user).toLowerCase()) > -1; });
    if (q.action) rows = rows.filter(function (l) { return l.action === q.action; });
    if (q.result) rows = rows.filter(function (l) { return l.result === q.result; });
    if (q.date) rows = rows.filter(function (l) { return String(l.at).slice(0, 10) === q.date; });
    return json({ data: rows });
  });

  global.MockAPI = {

    request: function (method, path, body) {
      var out = this.handle(method, path, body);
      if (["POST", "PUT", "PATCH", "DELETE"].indexOf(method) > -1) {
        out = out.then(function (r) { snapshot(); return r; }, function (e) { snapshot(); throw e; });
      }
      return out;
    },
    handle: function (method, path, body) {

      if (["POST", "PUT", "PATCH", "DELETE"].indexOf(method) > -1 && !global.App.csrfToken()) {
        return fail(419, "CSRF token tidak valid atau kedaluwarsa.");
      }
      var ctx = {
        session: function () { return global.App.Session.get(); }
      };
      var m = match(method, path);
      if (!m) return fail(404, "Endpoint " + method + " " + path + " tidak ditemukan.");
      try { return m.handler(body, m.params, m.query, ctx); }
      catch (e) { return fail(500, e.message); }
    },
    raw: { doctors: doctors, patients: patients, appointments: appointments, records: records, users: users, specializations: specializations, auditLogs: auditLogs, leaves: leaves, schedules: schedules },
    sessionFor: sessionFor
  };
})(window);
