
window.APP_CONFIG = {
  API_URL: "http://localhost:8080",
  MOCK: true,
  LOCALE: "id-ID",
  MOCK_LATENCY_MS: 220,
  PAGE_SIZE: 8,
  SESSION_ABSOLUTE_MINUTES: 120,
  STATE_PREVIEW: true
};

window.APP_BASE = window.APP_BASE || "";

window.url = function (p) { return (window.APP_BASE ? window.APP_BASE + "/" : "") + String(p).replace(/^\//, ""); };
window.asset = function (p) { return (window.APP_BASE ? window.APP_BASE + "/" : "") + "assets/" + String(p).replace(/^\//, ""); };
window.qs = function (k, d) {
  var v = new URLSearchParams(location.search).get(k);
  return (v === null || v === "") ? (d === undefined ? null : d) : v;
};
