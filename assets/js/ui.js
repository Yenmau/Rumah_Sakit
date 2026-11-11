
(function (global) {
  var App = global.App, CFG = global.APP_CONFIG;
  var esc = App.esc;

  var STATUS = {
    pending: { label: "Menunggu", cls: "bg-amber-100 text-amber-800 ring-amber-200" },
    confirmed: { label: "Diterima", cls: "bg-brand-100 text-brand-800 ring-brand-200" },
    accepted: { label: "Diterima", cls: "bg-brand-100 text-brand-800 ring-brand-200" },
    rejected: { label: "Ditolak", cls: "bg-rose-100 text-rose-800 ring-rose-200" },
    cancelled: { label: "Dibatalkan", cls: "bg-slate-200 text-slate-700 ring-slate-300" },
    moved: { label: "Dijadwalkan ulang", cls: "bg-slate-100 text-slate-700 ring-slate-300" },
    leave: { label: "Cuti penuh", cls: "bg-slate-200 text-slate-700 ring-slate-300" },
    custom_hours: { label: "Jam khusus", cls: "bg-amber-100 text-amber-800 ring-amber-200" },
    completed: { label: "Selesai", cls: "bg-emerald-100 text-emerald-800 ring-emerald-200" },
    draft: { label: "Draf", cls: "bg-slate-200 text-slate-700 ring-slate-300" },
    finalized: { label: "Final", cls: "bg-emerald-100 text-emerald-800 ring-emerald-200" },
    revised: { label: "Direvisi", cls: "bg-amber-100 text-amber-800 ring-amber-200" },
    active: { label: "Aktif", cls: "bg-emerald-100 text-emerald-800 ring-emerald-200" },
    inactive: { label: "Nonaktif", cls: "bg-slate-200 text-slate-700 ring-slate-300" },
    suspended: { label: "Ditangguhkan", cls: "bg-rose-100 text-rose-800 ring-rose-200" },
    verified: { label: "Terverifikasi", cls: "bg-emerald-100 text-emerald-800 ring-emerald-200" },
    locked: { label: "Terkunci", cls: "bg-rose-100 text-rose-800 ring-rose-200" },
    success: { label: "Sukses", cls: "bg-emerald-100 text-emerald-800 ring-emerald-200" },
    failure: { label: "Gagal", cls: "bg-rose-100 text-rose-800 ring-rose-200" }
  };
  function badge(status, customLabel) {
    var s = STATUS[String(status || "").toLowerCase()] || { label: status || "-", cls: "bg-slate-100 text-slate-700 ring-slate-200" };
    var label = customLabel || s.label;
    return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' + s.cls + '">' +
      '<span aria-hidden="true">•</span>' + esc(label) + '</span>';
  }

  function rescheduledChip(row) {
    return (row && row.rescheduled_from_id) ? ' <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-300" title="Menggantikan janji #' + esc(row.rescheduled_from_id) + '">↻ dijadwalkan ulang</span>' : "";
  }

  function card(opts) {
    return '<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">' +
      (opts.title ? '<header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">' +
        '<div><h2 class="text-base font-semibold text-slate-900">' + esc(opts.title) + '</h2>' +
        (opts.subtitle ? '<p class="mt-0.5 text-sm text-slate-500">' + esc(opts.subtitle) + '</p>' : "") + '</div>' +
        (opts.actions || "") + '</header>' : "") +
      '<div class="' + (opts.padded === false ? "" : "p-5") + '">' + (opts.body || "") + '</div>' +
      (opts.footer ? '<footer class="border-t border-slate-100 px-5 py-3">' + opts.footer + '</footer>' : "") +
      '</section>';
  }

  function pageHeader(opts) {
    var bc = (opts.breadcrumb || []).map(function (b, i, a) {
      return (i === a.length - 1)
        ? '<span class="text-slate-700" aria-current="page">' + esc(b.label) + '</span>'
        : '<a class="hover:text-brand-700" href="' + esc(b.href || "#") + '">' + esc(b.label) + '</a><span aria-hidden="true" class="text-slate-400">/</span>';
    }).join(" ");
    return '<div class="mb-5 flex flex-wrap items-end justify-between gap-3">' +
      '<div><h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">' + esc(opts.title) + '</h1>' +
      (opts.subtitle ? '<p class="mt-1 max-w-2xl text-sm text-slate-600">' + esc(opts.subtitle) + '</p>' : "") +
      (bc ? '<nav class="mt-2 flex items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">' + bc + '</nav>' : "") +
      '</div>' + (opts.actions ? '<div class="flex flex-wrap gap-2">' + opts.actions + '</div>' : "") +
      '</div>';
  }

  function statCards(items) {
    return '<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">' + items.map(function (i) {
      return '<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">' +
        '<p class="text-xs font-medium uppercase tracking-wide text-slate-500">' + esc(i.label) + '</p>' +
        '<p class="mt-2 text-2xl font-bold text-slate-900">' + esc(i.value) + '</p>' +
        (i.hint ? '<p class="mt-1 text-xs text-slate-500">' + esc(i.hint) + '</p>' : "") + '</div>';
    }).join("") + '</div>';
  }

  function describe(items, cols) {
    return '<dl class="grid gap-x-6 gap-y-4 ' + (cols || "sm:grid-cols-2") + '">' + items.map(function (i) {
      return '<div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">' + esc(i.label) + '</dt>' +
        '<dd class="mt-1 text-sm text-slate-800">' + (i.html ? i.html : esc(i.value === null || i.value === undefined ? "-" : i.value)) + '</dd></div>';
    }).join("") + '</dl>';
  }

  function timeline(items) {
    if (!items || !items.length) return App.stateBox("empty", "Belum ada riwayat.");
    return '<ol class="relative space-y-5 border-l border-slate-200 pl-5">' + items.map(function (i) {
      return '<li><span class="absolute -left-[7px] mt-1.5 flex h-3 w-3 rounded-full ring-4 ring-white ' + (i.tone === "danger" ? "bg-rose-500" : i.tone === "success" ? "bg-emerald-500" : "bg-slate-400") + '"></span>' +
        '<p class="text-sm font-semibold text-slate-800">' + esc(i.title) + '</p>' +
        '<p class="text-xs text-slate-500">' + esc(i.meta || "") + '</p>' +
        (i.body ? '<p class="mt-1 text-sm text-slate-600">' + esc(i.body) + '</p>' : "") + '</li>';
    }).join("") + '</ol>';
  }

  function tabs(items, activeKey) {
    return '<div role="tablist" aria-label="Navigasi bagian" class="mb-4 flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1">' +
      items.map(function (t) {
        var on = t.key === activeKey;
        return '<a role="tab" aria-selected="' + on + '" href="' + esc(t.href || "#") + '" class="rounded-lg px-3 py-1.5 text-sm font-medium ' +
          (on ? "bg-white text-slate-900 shadow-sm" : "text-slate-600 hover:text-slate-900") + '">' + esc(t.label) + '</a>';
      }).join("") + '</div>';
  }

  function table(container, opts) {
    var host = typeof container === "string" ? document.querySelector(container) : container;
    if (!host) return;
    var pageSize = opts.pageSize || CFG.PAGE_SIZE;
    var st = { sort: opts.defaultSort || null, dir: opts.defaultDir || "asc", q: "", page: 1, filters: {} };
    var preview = CFG.STATE_PREVIEW ? global.qs("state") : null;
    var pid = App.uid("tbl");
    host.innerHTML =
      '<div class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">' +
        '<div class="grow sm:max-w-xs"><label for="' + pid + '-q" class="block text-xs font-medium text-slate-600">Cari</label>' +
        '<input id="' + pid + '-q" type="search" placeholder="' + esc(opts.searchPlaceholder || "Cari…") + '" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></div>' +
        (opts.filters || []).map(function (f) {
          return '<div><label for="' + pid + "-f-" + esc(f.key) + '" class="block text-xs font-medium text-slate-600">' + esc(f.label) + '</label>' +
            '<select id="' + pid + "-f-" + esc(f.key) + '" data-filter="' + esc(f.key) + '" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">' +
            '<option value="">Semua</option>' + (f.options || []).map(function (o) {
              return '<option value="' + esc(o.value) + '">' + esc(o.label) + '</option>';
            }).join("") + '</select></div>';
        }).join("") +
        (opts.actions ? '<div class="ml-auto flex gap-2">' + opts.actions + '</div>' : "") +
      '</div><div data-body></div><div data-foot class="border-t border-slate-100 px-4 py-3"></div>';

    var qEl = host.querySelector("#" + pid + "-q");
    qEl.addEventListener("input", function () { st.q = this.value.toLowerCase(); st.page = 1; paint(); });
    host.querySelectorAll("[data-filter]").forEach(function (sel) {
      sel.addEventListener("change", function () { st.filters[this.getAttribute("data-filter")] = this.value; st.page = 1; paint(); });
    });

    function data() {
      var rows = (opts.rows || []).slice();
      if (st.q) rows = rows.filter(function (r) { return (opts.searchText ? opts.searchText(r) : Object.keys(r).map(function (k) { return r[k]; }).join(" ")).toLowerCase().indexOf(st.q) > -1; });
      Object.keys(st.filters).forEach(function (k) {
        if (st.filters[k]) rows = rows.filter(function (r) { return String(r[k]) === st.filters[k]; });
      });
      if (st.sort) {
        rows.sort(function (a, b) {
          var x = a[st.sort], y = b[st.sort];
          if (!isNaN(Number(x)) && !isNaN(Number(y)) && x !== "" && y !== "") { x = Number(x); y = Number(y); }
          return (x > y ? 1 : x < y ? -1 : 0) * (st.dir === "asc" ? 1 : -1);
        });
      }
      return rows;
    }

    function paint() {
      var body = host.querySelector("[data-body]"), foot = host.querySelector("[data-foot]");
      if (preview === "loading") { body.innerHTML = App.stateBox("loading"); foot.innerHTML = ""; return; }
      if (preview === "error") { body.innerHTML = App.stateBox("error", "Koneksi ke server gagal. Coba lagi."); foot.innerHTML = ""; return; }
      if (preview === "denied") { body.innerHTML = App.stateBox("denied", "Anda tidak berhak melihat data ini."); foot.innerHTML = ""; return; }
      var rows = data();
      if (!rows.length) {
        body.innerHTML = App.stateBox("empty", opts.emptyMessage || "Belum ada data yang cocok dengan filter.");
        foot.innerHTML = "";
        return;
      }
      var total = Math.ceil(rows.length / pageSize);
      if (st.page > total) st.page = total;
      var slice = rows.slice((st.page - 1) * pageSize, st.page * pageSize);
      body.innerHTML = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">' +
        '<caption class="sr-only">' + esc(opts.caption || (opts.title || "Daftar data")) + '</caption><thead class="bg-slate-50"><tr>' +
        (opts.columns || []).map(function (c) {
          var sortable = c.sortable !== false;
          var aria = st.sort === c.key ? (st.dir === "asc" ? "ascending" : "descending") : "none";
          return '<th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">' +
            (sortable ? '<button type="button" data-sort="' + esc(c.key) + '" aria-sort="' + aria + '" class="inline-flex items-center gap-1 hover:text-slate-900">' + esc(c.label) +
              '<span aria-hidden="true" class="text-slate-400">' + (st.sort === c.key ? (st.dir === "asc" ? "▲" : "▼") : "↕") + '</span></button>'
              : esc(c.label)) + '</th>';
        }).join("") + '</tr></thead><tbody class="divide-y divide-slate-100 bg-white">' +
        slice.map(function (r, i) {
          return '<tr class="' + (opts.onRow ? "cursor-pointer hover:bg-brand-50/60" : "hover:bg-slate-50") + '"' +
            (opts.onRow ? ' tabindex="0" role="button" data-row="' + i + '"' : "") + '>' +
            (opts.columns || []).map(function (c) {
              var v = c.render ? c.render(r) : esc(r[c.key] === null || r[c.key] === undefined ? "-" : r[c.key]);
              return '<td class="px-4 py-3 align-top text-slate-700">' + v + '</td>';
            }).join("") + '</tr>';
        }).join("") + '</tbody></table></div>';
      foot.innerHTML = '<div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">' +
        '<p>Menampilkan <strong>' + ((st.page - 1) * pageSize + 1) + '–' + Math.min(st.page * pageSize, rows.length) + '</strong> dari <strong>' + rows.length + '</strong> data</p>' +
        '<nav class="flex items-center gap-1" aria-label="Pagination">' +
          '<button type="button" data-page="prev" ' + (st.page === 1 ? "disabled" : "") + ' class="rounded-lg border border-slate-300 px-3 py-1.5 font-medium disabled:opacity-40">Sebelumnya</button>' +
          '<span class="px-2">Hal. ' + st.page + ' / ' + total + '</span>' +
          '<button type="button" data-page="next" ' + (st.page === total ? "disabled" : "") + ' class="rounded-lg border border-slate-300 px-3 py-1.5 font-medium disabled:opacity-40">Berikutnya</button>' +
        '</nav></div>';
      body.querySelectorAll("[data-sort]").forEach(function (b) {
        b.addEventListener("click", function () {
          var k = this.getAttribute("data-sort");
          if (st.sort === k) st.dir = st.dir === "asc" ? "desc" : "asc"; else { st.sort = k; st.dir = "asc"; }
          paint();
        });
      });
      body.querySelectorAll("[data-row]").forEach(function (tr) {
        var run = function () { opts.onRow(slice[Number(tr.getAttribute("data-row"))]); };
        tr.addEventListener("click", run);
        tr.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); run(); } });
      });
      foot.querySelector('[data-page="prev"]').addEventListener("click", function () { st.page--; paint(); });
      foot.querySelector('[data-page="next"]').addEventListener("click", function () { st.page++; paint(); });
    }
    paint();
    return { reload: function (rows) { opts.rows = rows; paint(); } };
  }

  function form(container, opts) {
    var host = typeof container === "string" ? document.querySelector(container) : container;
    if (!host) return;
    var fid = App.uid("form");
    var fields = opts.fields || [];
    function fieldHTML(f) {
      var id = fid + "-" + f.name, helpId = id + "-help", errId = id + "-err";
      var common = 'id="' + esc(id) + '" name="' + esc(f.name) + '"' +
        (f.required ? " required" : "") + ' aria-describedby="' + helpId + '"' +
        (f.maxlength ? ' maxlength="' + f.maxlength + '"' : "") +
        ' class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 aria-[invalid=true]:border-rose-400"';
      var input;
      if (f.type === "textarea") input = '<textarea rows="' + (f.rows || 3) + '" ' + common + ' placeholder="' + esc(f.placeholder || "") + '">' + esc(f.value || "") + '</textarea>';
      else if (f.type === "select") input = '<select ' + common + '>' + (f.placeholder ? '<option value="">' + esc(f.placeholder) + '</option>' : "") +
        (f.options || []).map(function (o) { return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(f.value) ? " selected" : "") + '>' + esc(o.label) + '</option>'; }).join("") + '</select>';
      else input = '<input type="' + esc(f.type || "text") + '" ' + common + ' placeholder="' + esc(f.placeholder || "") + '" value="' + esc(f.value || "") + '"' +
        (f.min !== undefined ? ' min="' + esc(f.min) + '"' : "") + (f.max !== undefined ? ' max="' + esc(f.max) + '"' : "") +
        (f.autocomplete ? ' autocomplete="' + esc(f.autocomplete) + '"' : ' autocomplete="off"') + '>';
      return '<div class="' + (f.colSpan || "") + '">' +
        '<label for="' + esc(id) + '" class="block text-sm font-medium text-slate-700">' + esc(f.label) +
        (f.required ? ' <span class="text-rose-600" aria-hidden="true">*</span>' : "") + (f.optional ? ' <span class="text-xs font-normal text-slate-400">(opsional)</span>' : "") + '</label>' +
        input +
        '<p id="' + helpId + '" class="mt-1 text-xs text-slate-500">' + esc(f.help || "") + '</p>' +
        '<p id="' + errId + '" data-err="' + esc(f.name) + '" class="mt-1 hidden text-xs font-medium text-rose-600"></p></div>';
    }
    var rowsHTML = fields.map(function (scope) {
      return Array.isArray(scope) ? '<div class="grid gap-5 sm:grid-cols-2">' + scope.map(fieldHTML).join("") + '</div>' : fieldHTML(scope);
    }).join("");
    host.innerHTML = '<form id="' + fid + '" method="post" action="' + esc(opts.action || "#") + '" novalidate class="space-y-5">' +
      '<input type="hidden" name="csrf_token" value="' + esc(App.csrfToken()) + '">' +
      (opts.intro || "") + rowsHTML +
      '<p data-formmsg role="alert" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>' +
      '<div class="flex flex-wrap gap-2 pt-1">' +
        '<button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">' + esc(opts.submitLabel || "Simpan") + '</button>' +
        (opts.secondary || "") +
      '</div></form>';
    var f = host.querySelector("form");
    function showErr(name, msg) {
      var e = f.querySelector('[data-err="' + name + '"]'), i = f.querySelector('[name="' + name + '"]');
      if (!e || !i) return;
      e.textContent = msg; e.classList.remove("hidden");
      i.setAttribute("aria-invalid", "true");
    }
    function clearErr(name) {
      var e = f.querySelector('[data-err="' + name + '"]'), i = f.querySelector('[name="' + name + '"]');
      if (e) { e.textContent = ""; e.classList.add("hidden"); }
      if (i) i.removeAttribute("aria-invalid");
    }
    f.querySelectorAll("[name]").forEach(function (i) { i.addEventListener("input", function () { clearErr(this.name); }); });
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var msg = f.querySelector("[data-formmsg]");
      msg.classList.add("hidden");
      var values = {}, firstBad = null;
      fields.forEach(function (scope) {
        (Array.isArray(scope) ? scope : [scope]).forEach(function (fd) {
          var i = f.querySelector('[name="' + fd.name + '"]');
          if (!i) return;
          var v = i.type === "checkbox" ? i.checked : i.value;
          values[fd.name] = v;
          var res = true, rules = fd.rules || (fd.validate ? [fd.validate] : []);
          rules.forEach(function (r) { if (res === true && typeof r === "function") res = r(v, values); });
          if (!res || res === true) { if (res !== true) { showErr(fd.name, res); if (!firstBad) firstBad = i; } }
        });
      });
      var extra = opts.validate ? opts.validate(values, f) : true;
      if (extra !== true) { if (!firstBad) firstBad = f.querySelector("button[type=submit]"); msg.textContent = extra; msg.classList.remove("hidden"); }
      if (firstBad) { firstBad.focus(); return; }
      var btn = f.querySelector("button[type=submit]");
      btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = "Menyimpan…";
      Promise.resolve(opts.onSubmit ? opts.onSubmit(values, f) : null).then(function (r) {
        btn.disabled = false; btn.textContent = btn.dataset.label;
        if (r !== false) App.toast((opts.successMessage || "Data berhasil disimpan."), "success");
      }).catch(function (err) {
        btn.disabled = false; btn.textContent = btn.dataset.label;
        if (err && err.status === 419) { msg.textContent = "Sesi keamanan tidak valid. Muat ulang halaman."; msg.classList.remove("hidden"); return; }
        App.handleError(err);
      });
    });
    return { form: f, values: function () { var o = {}; fields.forEach(function (sc) { (Array.isArray(sc) ? sc : [sc]).forEach(function (fd) { var i = f.querySelector('[name="' + fd.name + '"]'); if (i) o[fd.name] = i.value; }); }); return o; } };
  }

  function slotPicker(container, opts) {
    var host = typeof container === "string" ? document.querySelector(container) : container;
    if (!host) return;
    host.innerHTML = App.stateBox("loading");
    var dates = opts.dates || [];

    var startIdx = 0;
    for (var si = 0; si < dates.length; si++) {
      if ((dates[si].slots || []).some(function (x) { return x.available !== false; })) { startIdx = si; break; }
    }
    host.innerHTML = '<div class="space-y-3">' +
      '<div class="flex flex-wrap gap-2" role="tablist" aria-label="Pilih tanggal">' +
      dates.map(function (d, i) {
        return '<button type="button" role="tab" aria-selected="' + (i === startIdx) + '" data-date="' + esc(d.date) + '" class="rounded-lg border px-3 py-2 text-sm font-medium ' +
          (i === startIdx ? "border-brand-600 bg-brand-50 text-brand-800" : "border-slate-300 text-slate-700 hover:bg-slate-50") + '">' +
          esc(App.fmt.hari(d.date)) + '<br><span class="text-xs font-normal">' + esc(App.fmt.tanggal(d.date)) + '</span></button>';
      }).join("") + '</div>' +
      '<div data-slots class="grid grid-cols-2 gap-2 sm:grid-cols-4"></div>' +
      '<p data-selected class="text-sm text-slate-600" role="status" aria-live="polite">Belum ada slot dipilih.</p></div>';
    var chosen = null;
    function paintSlots(dateStr) {
      var day = dates.filter(function (d) { return d.date === dateStr; })[0] || { slots: [] };
      var box = host.querySelector("[data-slots]");
      if (!day.slots || !day.slots.length) { box.innerHTML = '<p class="col-span-full">' + App.stateBox("empty", "Tidak ada slot tersedia pada tanggal ini.") + '</p>'; return; }
      box.innerHTML = day.slots.map(function (s) {
        var off = s.available === false;
        return '<button type="button" ' + (off ? "disabled" : "") + ' data-slot="' + esc(s.start) + '" class="rounded-lg border px-3 py-2 text-sm font-medium ' +
          (off ? "cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 line-through" : "border-slate-300 text-slate-700 hover:border-brand-500 hover:bg-brand-50") + '">' +
          esc(App.fmt.jam(s.start)) + '–' + esc(App.fmt.jam(s.end)) + '<span class="sr-only">' + (off ? " (penuh)" : "") + '</span></button>';
      }).join("");
      box.querySelectorAll("[data-slot]").forEach(function (b) {
        b.addEventListener("click", function () {
          box.querySelectorAll("[data-slot]").forEach(function (x) { x.className = "rounded-lg border px-3 py-2 text-sm font-medium border-slate-300 text-slate-700 hover:border-brand-500 hover:bg-brand-50"; });
          this.className = "rounded-lg border px-3 py-2 text-sm font-medium border-brand-600 bg-brand-700 text-white";
          chosen = { date: dateStr, start: this.getAttribute("data-slot") };
          var lbl = host.querySelector("[data-selected]");
          lbl.textContent = "Dipilih: " + App.fmt.hari(chosen.date) + ", " + App.fmt.tanggal(chosen.date) + " pukul " + App.fmt.jam(chosen.start);
          if (opts.onPick) opts.onPick(chosen);
        });
      });
    }
    host.querySelectorAll("[data-date]").forEach(function (t) {
      t.addEventListener("click", function () {
        host.querySelectorAll("[data-date]").forEach(function (x) {
          x.setAttribute("aria-selected", "false");
          x.className = "rounded-lg border px-3 py-2 text-sm font-medium border-slate-300 text-slate-700 hover:bg-slate-50";
        });
        this.setAttribute("aria-selected", "true");
        this.className = "rounded-lg border px-3 py-2 text-sm font-medium border-brand-600 bg-brand-50 text-brand-800";
        chosen = null;
        host.querySelector("[data-selected]").textContent = "Belum ada slot dipilih.";
        paintSlots(this.getAttribute("data-date"));
      });
    });
    paintSlots(dates[startIdx] && dates[startIdx].date);
    return { value: function () { return chosen; } };
  }

  global.Render = { badge: badge, rescheduledChip: rescheduledChip, card: card, pageHeader: pageHeader, statCards: statCards, describe: describe, timeline: timeline, tabs: tabs, table: table, form: form, slotPicker: slotPicker };
})(window);
