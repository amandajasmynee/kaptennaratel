// =====================================================
// Base URLs
// =====================================================
export const API_UNITS_BASE_URL = "http://localhost:8001/api/units";
export const API_PAKET_BASE_URL = "http://localhost:8002/api/harga-paket";
export const API_PELANGGAN_BASE_URL = "http://localhost:8003/api/pelanggan";

// Root 8003 untuk endpoint /api/dashboard
export const API_ROOT_8003 = (() => {
  try {
    return API_PELANGGAN_BASE_URL.replace(/\/pelanggan\/?$/, "");
  } catch {
    return "http://localhost:8003/api";
  }
})();

// =====================================================
// Helpers: anti-cache + timeout fetch
// =====================================================
const TIMEOUT_MS = 15000;

function withTs(url) {
  const sep = url.includes("?") ? "&" : "?";
  return `${url}${sep}_ts=${Date.now()}`;
}

async function fetchJson(url, opts = {}) {
  const controller = new AbortController();
  const to = setTimeout(() => controller.abort(), opts.timeout ?? TIMEOUT_MS);

  const isGet = !opts.method || opts.method.toUpperCase() === "GET";
  const headers = new Headers(opts.headers || {});
  headers.set("Accept", "application/json");
  if (!isGet && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  try {
    const res = await fetch(withTs(url), {
      cache: "no-store",
      ...opts,
      headers,
      signal: controller.signal,
    });
    if (!res.ok) {
      const text = await res.text().catch(() => "");
      throw new Error(
        `[${res.status}] ${res.statusText} ${text ? "- " + text : ""}`
      );
    }
    return await res.json();
  } finally {
    clearTimeout(to);
  }
}

// =====================================================
// UNITS
// =====================================================
export async function getUnits(page = 1, perPage = 10) {
  const url = `${API_UNITS_BASE_URL}?page=${page}&per_page=${perPage}`;
  const result = await fetchJson(url);
  return {
    data: result.data || [],
    meta: result.meta || {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: perPage,
      from: 1,
      to: 1,
    },
  };
}

export async function getAllUnits() {
  const url = `${API_UNITS_BASE_URL}/all`;
  const result = await fetchJson(url);
  return { data: result.data || [] };
}

export async function deleteUnit(id) {
  const url = `${API_UNITS_BASE_URL}/${id}`;
  const result = await fetchJson(url, { method: "DELETE" });
  return result.message;
}

export async function createUnit(data) {
  const url = API_UNITS_BASE_URL;
  return await fetchJson(url, { method: "POST", body: JSON.stringify(data) });
}

export async function updateUnit(id, data) {
  const url = `${API_UNITS_BASE_URL}/${id}`;
  return await fetchJson(url, { method: "PUT", body: JSON.stringify(data) });
}

// =====================================================
// HARGA PAKET
// =====================================================
export async function getHargaPaket(page = 1, perPage = 10) {
  const url = `${API_PAKET_BASE_URL}?page=${page}&per_page=${perPage}`;
  try {
    const result = await fetchJson(url);
    return {
      data: result.data || [],
      current_page: result.current_page || 1,
      last_page: result.last_page || 1,
      total: result.total || 0,
    };
  } catch (error) {
    console.error("Kesalahan jaringan/server:", error);
    return { data: [], current_page: 1, last_page: 1, total: 0 };
  }
}

export async function getAllHargaPaket() {
  const url = `${API_PAKET_BASE_URL}/all`;
  const result = await fetchJson(url);
  return { data: result.data || [] };
}

export async function getSingleHargaPaket(id) {
  const url = `${API_PAKET_BASE_URL}/${id}`;
  const result = await fetchJson(url);
  return result.data || result;
}

export async function createHargaPaket(data) {
  const url = API_PAKET_BASE_URL;
  return await fetchJson(url, { method: "POST", body: JSON.stringify(data) });
}

export async function updateHargaPaket(id, data) {
  const url = `${API_PAKET_BASE_URL}/${id}`;
  return await fetchJson(url, { method: "PUT", body: JSON.stringify(data) });
}

export async function deleteHargaPaket(id) {
  const url = `${API_PAKET_BASE_URL}/${id}`;
  const result = await fetchJson(url, { method: "DELETE" });
  return result.message;
}

// =====================================================
// PELANGGAN
// =====================================================
export async function getPelanggan(page = 1, perPage = 10) {
  const url = `${API_PELANGGAN_BASE_URL}?page=${page}&per_page=${perPage}`;
  const result = await fetchJson(url);
  return {
    data: result.data ?? [],
    current_page: result.current_page ?? 1,
    last_page: result.last_page ?? 1,
    total: result.total ?? 0,
  };
}

export async function getSinglePelanggan(id) {
  const url = `${API_PELANGGAN_BASE_URL}/${id}`;
  const result = await fetchJson(url);
  return result.data;
}

export async function createPelanggan(data) {
  const url = API_PELANGGAN_BASE_URL;
  return await fetchJson(url, { method: "POST", body: JSON.stringify(data) });
}

export async function updatePelanggan(id, data) {
  const url = `${API_PELANGGAN_BASE_URL}/${id}`;
  return await fetchJson(url, { method: "PUT", body: JSON.stringify(data) });
}

export async function deletePelanggan(id) {
  const url = `${API_PELANGGAN_BASE_URL}/${id}`;
  const result = await fetchJson(url, { method: "DELETE" });
  return result.message;
}

// =====================================================
// DASHBOARD
// =====================================================
/** GET /api/dashboard */
export async function getDashboard(
  weeks = 12,
  unitLimit = 5,
  includeOthers = true
) {
  const url = `${API_ROOT_8003}/dashboard?weeks=${weeks}&unit_limit=${unitLimit}&include_others=${includeOthers}`;
  return await fetchJson(url);
}

/** GET /api/dashboard/week — detail harian untuk satu minggu (Sen–Min) */
export async function getDashboardWeek(
  weekStart,
  unitLimit = 5,
  includeOthers = true
) {
  const url = `${API_ROOT_8003}/dashboard/week?week_start=${encodeURIComponent(
    weekStart
  )}&unit_limit=${unitLimit}&include_others=${includeOthers}`;
  return await fetchJson(url);
}

// GET /api/dashboard/aggregate — harian/mingguan/bulanan (support start/end date)
export async function getDashboardAggregate(
  group = "week",        // 'day' | 'week' | 'month'
  periods = 12,
  unitLimit = 5,
  includeOthers = true,
  basis = "log_aktivasi",// atau 'created_at'
  startDate = null,      // "YYYY-MM-DD" (opsional)
  endDate = null         // "YYYY-MM-DD" (opsional)
) {
  const qs = new URLSearchParams({
    group,
    periods,
    unit_limit: String(unitLimit),
    include_others: String(!!includeOthers),
    basis
  });
  if (startDate) qs.set("start_date", startDate);
  if (endDate)   qs.set("end_date",   endDate);
  const url = `${API_ROOT_8003}/dashboard/aggregate?${qs.toString()}`;
  return await fetchJson(url);
}
