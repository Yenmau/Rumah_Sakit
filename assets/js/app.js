
(function (global) {
  var CFG = global.APP_CONFIG;

  function esc(v) {
    return String(v === null || v === undefined ? "" : v)
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
  }

  function setText(el, v) { if (el) el.textContent = v === null || v === undefined ? "" : String(v); }
  function el(sel, root) { return (root || document).querySelector(sel); }
  function uid(p) { return (p || "id") + "-" + Math.random().toString(36).slice(2, 9); }

  var fmt = {
    rupiah: function (n) {
      try { return new Intl.NumberFormat(CFG.LOCALE, { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(n) || 0); }
      catch (e) { return "Rp " + (Number(n) || 0); }
    },
    tanggal: function (d) { var dt = new Date(d); return isNaN(dt) ? "-" : dt.toLocaleDateString(CFG.LOCALE, { day: "2-digit", month: "short", year: "numeric" }); },
    hari: function (d) { var dt = new Date(d); return isNaN(dt) ? "-" : dt.toLocaleDateString(CFG.LOCALE, { weekday: "long" }); },
    jam: function (t) { return t ? String(t).slice(0, 5) : "-"; },
    tanggalJam: function (d) {
      var dt = new Date(d); if (isNaN(dt)) return "-";
      return dt.toLocaleString(CFG.LOCALE, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
    },
    timeAgo: function (d) {
      var s = (Date.now() - new Date(d).getTime()) / 1000;
      if (isNaN(s)) return "-";
      if (s < 60) return "baru saja";
      if (s < 3600) return Math.floor(s / 60) + " menit lalu";
      if (s < 86400) return Math.floor(s / 3600) + " jam lalu";
      return Math.floor(s / 86400) + " hari lalu";
    }
  };

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute("content")) return meta.getAttribute("content");
    var s = Session.get();
    if (s && s.csrf_token) return s.csrf_token;
    if (!global.__CSRF) global.__CSRF = "mock-" + Math.random().toString(36).slice(2, 12);
    return global.__CSRF;
  }

  var Session = {
    KEY: "rs_demo_session",
    get: function () {
      if (global.__SESSION) return global.__SESSION;
      try {
        var raw = sessionStorage.getItem(this.KEY);
        if (!raw) return null;
        var s = JSON.parse(raw);
        if (s && s.expires_at && Date.now() > s.expires_at) { this.clear(); return null; }
        global.__SESSION = s; return s;
      } catch (e) { return null; }
    },
    set: function (s) {
      global.__SESSION = s;
      try { sessionStorage.setItem(this.KEY, JSON.stringify(s)); } catch (e) {}
    },
    touch: function () {
      var s = this.get(); if (!s) return;
      s.last_seen = Date.now();
      this.set(s);
    },
    clear: function () {
      global.__SESSION = null;
      try { sessionStorage.removeItem(this.KEY); } catch (e) {}
    }
  };

  function ApiError(status, message, detail) { this.name = "ApiError"; this.status = status; this.message = message || ""; this.detail = detail || null; }
  ApiError.prototype = Object.create(Error.prototype);

  function api(path, options) {
    options = options || {};
    var method = (options.method || "GET").toUpperCase();
    if (global.App && global.App.__blocked) return new Promise(function () {});
    if (CFG.MOCK && global.MockAPI) return global.MockAPI.request(method, path, options.body);
    var ctrl = new AbortController();
    if (options.timeout) setTimeout(function () { ctrl.abort(); }, options.timeout);
    return fetch(String(CFG.API_URL).replace(/\/$/, "") + path, {
      method: method,
      credentials: "include",
      signal: ctrl.signal,
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken()
      },
      body: options.body ? JSON.stringify(options.body) : undefined
    }).then(function (res) {
      if (res.status === 204) return null;
      return res.json().catch(function () { return null; }).then(function (data) {
        if (res.ok) return data;
        if (res.status === 401) { Session.clear(); location.replace(global.url("login.php") + "?expired=1"); }
        throw new ApiError(res.status, (data && (data.message || data.error)) || "Terjadi kesalahan.", data && data.detail);
      });
    }).catch(function (err) {
      if (err instanceof ApiError) throw err;
      throw new ApiError(0, "Tidak dapat menghubungi server. Periksa koneksi Anda.");
    });
  }

  function guard(role) {
    var s = Session.get();
    if (!s) {
      global.App.__blocked = true;

      var next = location.pathname.replace(/^\//, "") + location.search;
      location.replace(global.url("login.php") + "?next=" + encodeURIComponent(next));
      return null;
    }
    if (role && s.role !== role) { global.App.__blocked = true; location.replace(global.url("403.php")); return null; }
    return s;
  }

  function toastHost() {
    var host = el("#toast-host");
    if (!host) {
      host = document.createElement("div");
      host.id = "toast-host";
      host.className = "fixed bottom-4 right-4 z-[60] flex w-[min(92vw,22rem)] flex-col gap-2";
      host.setAttribute("aria-live", "polite");
      host.setAttribute("aria-atomic", "false");
      document.body.appendChild(host);
    }
    return host;
  }
  function toast(message, type, ms) {
    type = type || "info";
    var styles = {
      success: "border-emerald-200 bg-emerald-50 text-emerald-900",
      error: "border-rose-200 bg-rose-50 text-rose-900",
      warning: "border-amber-200 bg-amber-50 text-amber-900",
      info: "border-slate-200 bg-white text-slate-800"
    }[type] || "border-slate-200 bg-white text-slate-800";
    var icons = { success: "✓", error: "!", warning: "!", info: "i" };
    var t = document.createElement("div");
    t.className = "flex items-start gap-3 rounded-xl border px-4 py-3 shadow-sm " + styles;
    t.setAttribute("role", type === "error" ? "alert" : "status");
    t.innerHTML = '<span aria-hidden="true" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-black/5 text-xs font-bold">' + icons[type] + '</span>' +
      '<p class="text-sm">' + esc(message) + '</p>';
    toastHost().appendChild(t);
    setTimeout(function () { t.style.transition = "opacity .25s"; t.style.opacity = "0"; setTimeout(function () { t.remove(); }, 250); }, ms || 4200);
  }

  function modal(opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var prev = document.activeElement;
      var wrap = document.createElement("div");
      wrap.className = "fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center";
      var needReason = !!opts.reasonRequired;
      wrap.innerHTML =
        '<div class="absolute inset-0 bg-slate-900/60" data-close="1"></div>' +
        '<div role="dialog" aria-modal="true" aria-labelledby="mdl-title" aria-describedby="mdl-desc" class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">' +
          '<form class="p-5 sm:p-6" novalidate>' +
            '<h2 id="mdl-title" class="text-lg font-semibold text-slate-900">' + esc(opts.title || "Konfirmasi") + '</h2>' +
            (opts.message ? '<p id="mdl-desc" class="mt-2 text-sm text-slate-600">' + esc(opts.message) + '</p>' : '<p id="mdl-desc" class="sr-only">Konfirmasi tindakan</p>') +
            (needReason ?
              '<div class="mt-4">' +
                '<label for="mdl-reason" class="block text-sm font-medium text-slate-700">Alasan <span class="text-rose-600" aria-hidden="true">*</span></label>' +
                '<textarea id="mdl-reason" name="reason" rows="3" minlength="5" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" aria-describedby="mdl-help"></textarea>' +
                '<p id="mdl-help" class="mt-1 text-xs text-slate-500">Wajib diisi, minimal 5 karakter. Alasan ikut tercatat di riwayat.</p>' +
                '<p data-err class="mt-1 hidden text-xs font-medium text-rose-600">Alasan wajib diisi (minimal 5 karakter).</p>' +
              '</div>' : '') +
            '<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">' +
              '<button type="button" data-close="1" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">Batal</button>' +
              '<button type="submit" data-confirm class="rounded-lg px-4 py-2.5 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ' + (opts.danger ? "bg-rose-600 hover:bg-rose-700 focus-visible:outline-rose-500" : "bg-brand-700 hover:bg-brand-800 focus-visible:outline-brand-500") + '">' + esc(opts.confirmLabel || "Ya, lanjutkan") + '</button>' +
            '</div>' +
          '</form>' +
        '</div>';
      document.body.appendChild(wrap);
      var form = wrap.querySelector("form"), ta = wrap.querySelector("#mdl-reason"), err = wrap.querySelector("[data-err]");
      function close(res) { wrap.remove(); if (prev && prev.focus) prev.focus(); resolve(res); }
      wrap.addEventListener("click", function (e) { if (e.target.getAttribute("data-close")) close({ ok: false }); });
      wrap.addEventListener("keydown", function (e) { if (e.key === "Escape") close({ ok: false }); });
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var reason = ta ? ta.value.trim() : "";
        if (needReason && reason.length < 5) {
          if (err) err.classList.remove("hidden");
          if (ta) { ta.setAttribute("aria-invalid", "true"); ta.focus(); }
          return;
        }
        close({ ok: true, reason: reason });
      });
      (ta || wrap.querySelector("[data-confirm]")).focus();
    });
  }

  var validate = {
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v || "").trim()) || "Format email tidak valid."; },
    password: function (v) {
      v = String(v || "");
      if (v.length < 12) return "Password minimal 12 karakter.";
      if (!/[A-Za-z]/.test(v) || !/\d/.test(v)) return "Password harus memuat huruf dan angka.";
      return true;
    },
    required: function (v) { return (String(v || "").trim().length > 0) || "Wajib diisi."; },
    minLength: function (n) { return function (v) { return String(v || "").trim().length >= n || ("Minimal " + n + " karakter."); }; },
    phone: function (v) { return /^[0-9+\-\s()]{8,20}$/.test(String(v || "").trim()) || "Nomor telepon tidak valid."; },
    timeRange: function (start, end) { return String(end) > String(start) || "Jam selesai harus lebih besar dari jam mulai."; },
    futureDate: function (v) { return new Date(v) >= new Date(new Date().toDateString()) || "Tanggal tidak boleh di masa lalu."; },
    number: function (v) { return (!isNaN(Number(v)) && Number(v) > 0) || "Harus berupa angka lebih dari 0."; }
  };

  function stateBox(kind, message, actionHTML) {
    var map = {
      loading: { icon: "⏳", title: "Memuat data…", cls: "text-slate-500" },
      empty: { icon: "📭", title: "Belum ada data", cls: "text-slate-500" },
      error: { icon: "⚠️", title: "Gagal memuat data", cls: "text-rose-600" },
      denied: { icon: "🔒", title: "Akses ditolak", cls: "text-rose-600" }
    }[kind] || { icon: "•", title: "", cls: "text-slate-500" };
    if (kind === "loading") {
      return '<div class="space-y-3 p-6" role="status" aria-live="polite"><span class="sr-only">Memuat data</span>' +
        '<div class="h-4 w-1/3 animate-pulse rounded bg-slate-200"></div>' +
        '<div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>' +
        '<div class="h-4 w-5/6 animate-pulse rounded bg-slate-200"></div>' +
        '<div class="h-4 w-2/3 animate-pulse rounded bg-slate-200"></div></div>';
    }
    return '<div class="flex flex-col items-center gap-2 px-6 py-12 text-center" role="' + (kind === "error" ? "alert" : "status") + '">' +
      '<span class="text-2xl" aria-hidden="true">' + map.icon + '</span>' +
      '<p class="text-sm font-medium ' + map.cls + '">' + esc(message || map.title) + '</p>' +
      (actionHTML || "") + '</div>';
  }

  function handleError(err) {
    if (err && err.status === 401) { Session.clear(); location.replace(global.url("login.php") + "?expired=1"); return; }
    if (err && err.status === 403) { location.replace(global.url("403.php")); return; }
    if (err && err.status === 419) { toast("Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.", "error"); return; }
    toast((err && err.message) || "Terjadi kesalahan.", "error");
  }

  function logout() {
    return api("/api/logout", { method: "POST" }).catch(function () {}).then(function () {
      Session.clear();
      location.replace(global.url("login.php"));
    });
  }

  global.App = {
    esc: esc, setText: setText, el: el, uid: uid, fmt: fmt, validate: validate,
    csrfToken: csrfToken, api: api, guard: guard, Session: Session, toast: toast,
    modal: modal, stateBox: stateBox, handleError: handleError, logout: logout,
    ApiError: ApiError
  };
})(window);
