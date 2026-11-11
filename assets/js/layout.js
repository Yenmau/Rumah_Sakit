
(function (global) {
  var App = global.App, CFG = global.APP_CONFIG;

  var NAV = {
    pasien: [
      { key: "dashboard", label: "Dashboard", href: "pasien/dashboard.php", icon: "home" },
      { key: "cari-dokter", label: "Cari Dokter", href: "pasien/cari-dokter.php", icon: "search" },
      { key: "janji", label: "Janji Temu", href: "pasien/janji.php", icon: "calendar" },
      { key: "rekam-medis", label: "Rekam Medis", href: "pasien/rekam-medis.php", icon: "folder" },
      { key: "profil", label: "Profil", href: "pasien/profil.php", icon: "user" }
    ],
    dokter: [
      { key: "dashboard", label: "Dashboard", href: "dokter/dashboard.php", icon: "home" },
      { key: "janji", label: "Janji Temu", href: "dokter/janji.php", icon: "calendar" },
      { key: "jadwal", label: "Jadwal Mingguan", href: "dokter/jadwal.php", icon: "clock" },
      { key: "cuti", label: "Cuti & Jam Khusus", href: "dokter/cuti.php", icon: "sun" },
      { key: "rekam-medis", label: "Rekam Medis", href: "dokter/rekam-medis.php", icon: "folder" },
      { key: "profil", label: "Profil", href: "dokter/profil.php", icon: "user" }
    ],
    admin: [
      { key: "dashboard", label: "Dashboard", href: "admin/dashboard.php", icon: "home" },
      { key: "verifikasi", label: "Verifikasi Dokter", href: "admin/verifikasi-dokter.php", icon: "shield" },
      { key: "users", label: "Kelola User", href: "admin/kelola-user.php", icon: "users" },
      { key: "spesialisasi", label: "Spesialisasi", href: "admin/spesialisasi.php", icon: "tag" },
      { key: "audit", label: "Audit Log", href: "admin/audit-log.php", icon: "clipboard" }
    ]
  };
  var ROLE_LABEL = { pasien: "Pasien", dokter: "Dokter", admin: "Administrator" };

  var ICONS = {
    home: '<path d="M3 10.8 12 3.5l9 7.3"/><path d="M5.6 9.6v10.9h12.8V9.6"/><path d="M10 20.5v-5h4v5"/>',
    search: '<circle cx="10.8" cy="10.8" r="6.3"/><path d="M15.6 15.6 20.5 20.5"/>',
    calendar: '<rect x="3.5" y="5.5" width="17" height="15" rx="2.5"/><path d="M3.5 10.2h17M8.2 3.5v3.6M15.8 3.5v3.6"/>',
    clock: '<circle cx="12" cy="12" r="8.4"/><path d="M12 7.4V12l3.4 2"/>',
    folder: '<path d="M4 7.4A2.4 2.4 0 0 1 6.4 5h3.1l1.9 2.4h6.2A2.4 2.4 0 0 1 20 9.8v6.8a2.4 2.4 0 0 1-2.4 2.4H6.4A2.4 2.4 0 0 1 4 16.6z"/><path d="M8 13.4h8M8 16.2h5"/>',
    user: '<circle cx="12" cy="8.4" r="3.7"/><path d="M4.9 20.3c1.4-3.5 3.9-5.2 7.1-5.2s5.7 1.7 7.1 5.2"/>',
    shield: '<path d="M12 3.4l7 2.7v5.5c0 4.3-2.9 7.7-7 8.9-4.1-1.2-7-4.6-7-8.9V6.1z"/><path d="M9.3 12.2l1.9 1.9 3.6-3.8"/>',
    users: '<circle cx="9.6" cy="8.6" r="3.3"/><path d="M3.6 19.6c1.1-3.1 3.3-4.7 6-4.7s4.9 1.6 6 4.7"/><path d="M16.6 6.3a3 3 0 0 1 0 5.6M17.9 19.4c-.4-1.6-1-2.8-1.8-3.8"/>',
    tag: '<path d="M4.2 10.4V5.7A1.5 1.5 0 0 1 5.7 4.2h4.7l9.4 9.4-6.2 6.2z"/><circle cx="8" cy="8" r="1.3"/>',
    clipboard: '<rect x="5" y="4.6" width="14" height="15.9" rx="2.4"/><path d="M9.2 4.6V3.9A1.7 1.7 0 0 1 10.9 2.2h2.2A1.7 1.7 0 0 1 14.8 3.9v.7"/><path d="M8.6 10h6.8M8.6 13.4h6.8M8.6 16.8h4"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2.6v2.2M12 19.2v2.2M2.6 12h2.2M19.2 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M18.7 5.3l-1.6 1.6M6.9 17.1l-1.6 1.6"/>'
  };
  function svgIcon(name) {
    return '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || ICONS.home) + '</svg>';
  }

  function sidebarHTML(role, active) {
    return '<div class="flex items-center gap-2 px-3 pt-4 pb-1">' +
        '<button type="button" id="sidebar-toggle" aria-expanded="true" aria-controls="sidebar"' +
          ' aria-label="Perkecil menu" data-tip="Perkecil menu  \u00b7  Ctrl+B"' +
          ' class="tip-host hidden shrink-0 rounded-lg border border-white/20 p-2 text-brand-100 transition hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400 lg:inline-flex">' +
          '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
            '<path d="M4 7h16M4 12h16M4 17h16"></path></svg>' +
          '<span class="sr-only">Buka/tutup menu samping</span></button>' +
        '<a href="' + global.url("index.php") + '" class="nav-brand min-w-0 px-1">' +
          '<span class="nav-brand-text block truncate text-lg font-bold tracking-tight text-white">RS Sehat<span class="text-brand-300">Sentosa</span></span>' +
          '<span class="nav-brand-desc block truncate text-xs text-brand-200">Sistem layanan rumah sakit</span>' +
        '</a>' +
      '</div>' +
      '<nav aria-label="Menu utama" class="nav-menu mt-2 flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto p-3 pt-0">' +
      (NAV[role] || []).map(function (n) {
        var on = n.key === active;
        return '<a href="' + global.url(n.href) + '" ' + (on ? 'aria-current=\"page\"' : "") +
          ' title="' + n.label + '" class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ' +
          (on ? "bg-brand-400 font-semibold text-brand-900 shadow-sm" : "text-brand-100 hover:bg-white/10 hover:text-white") + '">' +
          '<span aria-hidden="true" class="nav-icon">' + svgIcon(n.icon) + '</span>' +
          '<span class="nav-label">' + n.label + '</span></a>';
      }).join("") +
      '</nav>';
  }

  function shell(role, active, session) {
    var wrap = document.createElement("div");
    wrap.className = "min-h-screen bg-slate-100";

    var page = document.getElementById("page");
    var nodes = page
      ? [].slice.call(page.children)
      : [].slice.call(document.body.children).filter(function (el) {
          return el.tagName !== "SCRIPT" && el.tagName !== "TEMPLATE";
        });
    nodes.forEach(function (n) { if (n.parentNode) n.parentNode.removeChild(n); });
    if (page && page.parentNode) page.parentNode.removeChild(page);
    wrap.innerHTML =
      '<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">Lewati ke konten utama</a>' +
      '<div class="flex min-h-screen">' +
        '<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-black/30 bg-brand-900 transition-transform lg:static lg:translate-x-0" aria-label="Navigasi samping">' +
          sidebarHTML(role, active) +
        '</aside>' +
        '<div id="scrim" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>' +
        '<div class="flex min-w-0 grow flex-col">' +
          '<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">' +
            '<div class="flex items-center gap-3 px-4 py-3 sm:px-6">' +
              '<button type="button" id="nav-toggle" class="rounded-lg border border-slate-300 p-2 text-slate-700 hover:bg-slate-100 lg:hidden" aria-expanded="false" aria-controls="sidebar" aria-label="Buka menu">' +
                '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
                  '<path d="M4 7h16M4 12h16M4 17h16"></path></svg></button>' +

              '<div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">' + App.esc(session.name) + '</p>' +
              '<p class="text-xs text-slate-500">' + App.esc(ROLE_LABEL[session.role] || session.role) + (session.subtitle ? " · " + App.esc(session.subtitle) : "") + '</p></div>' +
              '<div class="ml-auto flex items-center gap-2">' +
                '<a href="' + global.url("change-password.php") + '" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Ganti password</a>' +
                '<button type="button" id="logout-btn" class="rounded-lg bg-brand-800 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-900">Keluar</button>' +
              '</div></div></header>' +
          '<main id="main" tabindex="-1" class="grow p-4 sm:p-6"></main>' +
          '<footer class="border-t border-slate-200 bg-white px-6 py-3 text-xs text-slate-500">' +
            '© 2026 RS SehatSentosa' +
          '</footer>' +
        '</div></div>';
    document.body.appendChild(wrap);
    var main = document.getElementById("main");
    nodes.forEach(function (n) { main.appendChild(n); });
    var aside = document.getElementById("sidebar"), scrim = document.getElementById("scrim"), tog = document.getElementById("nav-toggle");
    function setNav(open) {
      aside.classList.toggle("-translate-x-full", !open);
      scrim.classList.toggle("hidden", !open);
      tog.setAttribute("aria-expanded", String(open));
    }
    tog.addEventListener("click", function () { setNav(aside.classList.contains("-translate-x-full")); });
    scrim.addEventListener("click", function () { setNav(false); });
    document.addEventListener("keydown", function (e) { if (e.key === "Escape") setNav(false); });
    document.getElementById("logout-btn").addEventListener("click", function () {
      App.modal({ title: "Keluar dari akun?", message: "Sesi Anda akan diakhiri dan audit log mencatat tindakan ini.", confirmLabel: "Ya, keluar" }).then(function (r) {
        if (r.ok) App.logout();
      });
    });

    var sbToggle = document.getElementById("sidebar-toggle");
    function setCollapsed(collapsed) {
      aside.classList.toggle("is-collapsed", collapsed);
      sbToggle.setAttribute("aria-expanded", String(!collapsed));
      var label = collapsed ? "Perbesar menu" : "Perkecil menu";
      sbToggle.setAttribute("aria-label", label);
      sbToggle.setAttribute("data-tip", label + "  \u00b7  Ctrl+B");
      try { sessionStorage.setItem("rs_sidebar_collapsed", collapsed ? "1" : "0"); } catch (e) {}
    }
    var savedCollapsed = null;
    try { savedCollapsed = sessionStorage.getItem("rs_sidebar_collapsed"); } catch (e) {}
    setCollapsed(savedCollapsed === "1");
    sbToggle.addEventListener("click", function () {
      setCollapsed(!aside.classList.contains("is-collapsed"));
    });

    document.addEventListener("keydown", function (e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === "b" || e.key === "B")) {
        e.preventDefault();
        setCollapsed(!aside.classList.contains("is-collapsed"));
      }
    });
  }

  var ROLE_ATTR = document.body.getAttribute("data-role");

  var SESSION = ROLE_ATTR ? App.guard(ROLE_ATTR) : null;

  document.addEventListener("DOMContentLoaded", function () {
    if (ROLE_ATTR && SESSION) {
      shell(ROLE_ATTR, document.body.getAttribute("data-active") || "", SESSION);
    }
  });
})(window);
