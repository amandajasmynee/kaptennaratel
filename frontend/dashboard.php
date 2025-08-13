<?php
ob_start();

/** Cache-busting versi file api.js & Chart.js */
$apiJsPath = __DIR__ . '/api.js';
$verApi = @filemtime($apiJsPath) ?: time();
$verCdn = date('YmdHi');
?>

<h4 class="fw-bold py-1 mb-0">Dashboard Analitik</h4>
<p class="text-muted mb-3">Data aktivasi secara real-time</p>

<div class="d-flex align-items-center justify-content-end mb-3">
  <!-- Dropdown Filter -->
  <div class="dropdown">
    <button
      class="btn btn-outline-secondary dropdown-toggle"
      type="button"
      id="btnFilterLabel"
      data-bs-toggle="dropdown"
      data-bs-auto-close="false"
      aria-expanded="false">
      Semua Data
    </button>

    <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
      <div class="mb-2 small fw-semibold text-muted">Grouping</div>
      <div class="btn-group w-100 mb-3" role="group" aria-label="Grouping">
        <input type="radio" class="btn-check" name="grouping" id="grpDay"   value="day">
        <label class="btn btn-outline-primary" for="grpDay">Harian</label>

        <input type="radio" class="btn-check" name="grouping" id="grpWeek"  value="week" checked>
        <label class="btn btn-outline-primary" for="grpWeek">Mingguan</label>

        <input type="radio" class="btn-check" name="grouping" id="grpMonth" value="month">
        <label class="btn btn-outline-primary" for="grpMonth">Bulanan</label>
      </div>

      <div class="mb-2 small fw-semibold text-muted">Basis Tanggal</div>
      <select id="selBasis" class="form-select mb-3">
        <option value="log_aktivasi" selected>log_aktivasi (default)</option>
        <option value="created_at">created_at</option>
      </select>

      <div class="row g-2">
        <div class="col-6">
          <label class="form-label small mb-1">Top-N Unit</label>
          <select id="selUnitLimit" class="form-select">
            <option value="3">3</option>
            <option value="5" selected>5</option>
            <option value="10">10</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label small mb-1">Jumlah Periode</label>
          <input id="inpPeriods" type="number" class="form-control" min="1" value="12">
          <div class="form-text" id="hintPeriods">Dipakai saat tidak memilih rentang tanggal.</div>
        </div>
      </div>

      <!-- Rentang tanggal JELAS -->
      <div class="row g-2 mt-3">
        <div class="col-6">
          <label class="form-label small mb-1">Dari Tanggal</label>
          <input id="inpStartDate" type="date" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label small mb-1">Sampai Tanggal</label>
          <input id="inpEndDate" type="date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-12">
          <div class="form-text">Jika “Dari” & “Sampai” diisi, sistem pakai rentang ini (Jumlah Periode diabaikan).</div>
        </div>
      </div>

      <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" id="chkOthers" checked>
        <label class="form-check-label" for="chkOthers">Gabung “Others”</label>
      </div>

      <div class="d-grid mt-3">
        <button id="btnApply" class="btn btn-primary">Terapkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Kartu metrik -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-3">
    <div class="card soft-card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Total Aktivasi</div>
          <div id="cardTotal" class="fs-4 fw-bold">–</div>
        </div>
        <div class="icon-badge bg-warning-subtle text-warning"><i class="bi bi-person"></i></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="card soft-card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small" id="cardTodayCap">Aktivasi Hari Ini</div>
          <div id="cardToday" class="fs-4 fw-bold">–</div>
        </div>
        <div class="icon-badge bg-info-subtle text-info"><i class="bi bi-calendar-day"></i></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="card soft-card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small" id="cardWeekCap">Aktivasi Minggu Ini</div>
          <div id="cardWeek" class="fs-4 fw-bold">–</div>
        </div>
        <div class="icon-badge bg-success-subtle text-success"><i class="bi bi-calendar-week"></i></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="card soft-card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small" id="cardMonthCap">Aktivasi Bulan Ini</div>
          <div id="cardMonth" class="fs-4 fw-bold">–</div>
        </div>
        <div class="icon-badge bg-warning-subtle text-warning"><i class="bi bi-calendar-month"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Tren -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0">Tren Aktivasi</h6>
      <div class="btn-group">
        <button id="btnExport" class="btn btn-sm btn-outline-secondary" title="Unduh PNG"><i class="bi bi-download"></i></button>
        <button id="btnRefresh" class="btn btn-sm btn-outline-secondary" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
      </div>
    </div>
    <canvas id="chartTotal" height="120" role="img" aria-label="Grafik total"></canvas>
  </div>
</div>

<!-- Komposisi Unit -->
<div class="card">
  <div class="card-body">
    <h6 class="mb-3">Komposisi per Unit (Stacked)</h6>
    <canvas id="chartByUnit" height="160" role="img" aria-label="Grafik unit"></canvas>
  </div>
</div>

<!-- Style ringan -->
<style>
  .soft-card { border: 0; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
  .icon-badge { width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; font-size: 1.1rem; }
  .bg-warning-subtle{ background: rgba(250, 176, 5, .12)!important; }
  .bg-info-subtle{ background: rgba(13, 202, 240, .12)!important; }
  .bg-success-subtle{ background: rgba(25, 135, 84, .12)!important; }
  .is-disabled { opacity: .6; pointer-events: none; }
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<script
  src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js?v=<?= $verCdn ?>"
  crossorigin
  onerror="(function(){var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js?v=<?= $verCdn ?>';document.head.appendChild(s);}())">
</script>
<script>window.__waitChart=new Promise(r=>{(function c(){if(window.Chart)return r();setTimeout(c,50)}())});</script>

<script type="module">
  import { getDashboardAggregate } from './api.js?v=<?= $verApi ?>';
  await window.__waitChart;

  // ====== THEME UTILS ======
  const css = (name, fb='') => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fb;
  const rgbVar = (key) => css(`--bs-${key}-rgb`, '');
  const colVar = (key) => css(`--bs-${key}`, '');
  const hexToRgba = (hex, a=1) => {
    let c = hex.replace('#',''); if (c.length===3) c = [...c].map(x=>x+x).join('');
    const n = parseInt(c,16); const r=(n>>16)&255, g=(n>>8)&255, b=n&255;
    return `rgba(${r}, ${g}, ${b}, ${a})`;
  };
  const rgba = (key, a=1) => {
    const rgb = rgbVar(key);
    if (rgb) return `rgba(${rgb}, ${a})`;
    const hex = colVar(key) || '#6c757d';
    return a===1 ? hex : hexToRgba(hex, a);
  };

  function getTheme() {
    return {
      text: css('--bs-body-color', '#495057'),
      grid: css('--bs-border-color', 'rgba(0,0,0,.1)'),
      line: {
        border: rgba('primary', 1),
        fillTop: rgba('primary', .25),
        fillBottom: rgba('primary', 0),
        pointBg: rgba('primary', 1),
      },
      bars: ['primary','success','info','warning','danger','secondary','teal','indigo']
    };
  }
  let THEME = getTheme();
  Chart.defaults.color = THEME.text;
  Chart.defaults.borderColor = THEME.grid;

  // ====== State + Elemen ======
  const $btnApply     = document.getElementById('btnApply');
  const $btnRefresh   = document.getElementById('btnRefresh');
  const $btnExport    = document.getElementById('btnExport');
  const $labelBtn     = document.getElementById('btnFilterLabel');

  const $selBasis     = document.getElementById('selBasis');
  const $selUnitLimit = document.getElementById('selUnitLimit');
  const $inpPeriods   = document.getElementById('inpPeriods');
  const $chkOthers    = document.getElementById('chkOthers');

  const $inpStartDate = document.getElementById('inpStartDate');
  const $inpEndDate   = document.getElementById('inpEndDate');
  const $hintPeriods  = document.getElementById('hintPeriods');

  const $cardTotal = document.getElementById('cardTotal');
  const $cardToday = document.getElementById('cardToday');
  const $cardWeek  = document.getElementById('cardWeek');
  const $cardMonth = document.getElementById('cardMonth');

  const $cardTodayCap = document.getElementById('cardTodayCap');
  const $cardWeekCap  = document.getElementById('cardWeekCap');
  const $cardMonthCap = document.getElementById('cardMonthCap');

  const grpRadios = [...document.querySelectorAll('input[name="grouping"]')];

  let chartTotal = null;
  let chartByUnit = null;

  // ====== Helpers tanggal/label ======
  const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate()+n);
  const parseISO = iso => new Date(iso + 'T00:00:00');
  const fmt = (d, opt) => new Intl.DateTimeFormat('id-ID', opt).format(d);
  const fmtFull = (d) => fmt(d, { day:'2-digit', month:'short', year:'numeric' });
  const fmtShort = (d) => fmt(d, { day:'2-digit', month:'short' });

  function fmtLabel(group, iso) {
    const s = parseISO(iso);
    if (group === 'day')   return fmtShort(s);
    if (group === 'month') return fmt(s, { month:'short', year:'numeric' });
    const e = addDays(s, 6);
    return `${fmtShort(s)}–${fmtShort(e)}`;
  }

  function weekRangeCap(date) {
    const start = new Date(date);
    const end = addDays(start, 6);
    return `Minggu ${fmtShort(start)}–${fmtShort(end)}`;
  }

  function currentGroup() { return grpRadios.find(r => r.checked)?.value || 'week'; }

  function updateFilterLabel() {
    const g = currentGroup();
    const gtxt = g==='day'?'Harian':g==='month'?'Bulanan':'Mingguan';
    const s = $inpStartDate.value, e = $inpEndDate.value;
    if (s && e) {
      const sD = parseISO(s), eD = parseISO(e);
      $labelBtn.textContent = `${gtxt} · ${fmtFull(sD)} – ${fmtFull(eD)}`;
    } else {
      $labelBtn.textContent = `Semua Data · ${gtxt}`;
    }
  }

  // Disable/enable "Jumlah Periode" saat rentang tanggal dipakai
  function togglePeriodsState() {
    const useRange = !!($inpStartDate.value && $inpEndDate.value);
    $inpPeriods.disabled = useRange;
    $hintPeriods.classList.toggle('text-muted', useRange);
    $hintPeriods.classList.toggle('fw-semibold', !useRange);
    $inpPeriods.parentElement.classList.toggle('is-disabled', useRange);
    updateFilterLabel();
  }
  $inpStartDate.addEventListener('change', togglePeriodsState);
  $inpEndDate.addEventListener('change', togglePeriodsState);

  // ====== Recolor charts jika tema berubah ======
  function recolorCharts() {
    THEME = getTheme();
    Chart.defaults.color       = THEME.text;
    Chart.defaults.borderColor = THEME.grid;

    if (chartTotal) {
      const ctx = chartTotal.ctx;
      const grad = ctx.createLinearGradient(0,0,0,200);
      grad.addColorStop(0, THEME.line.fillTop);
      grad.addColorStop(1, THEME.line.fillBottom);

      const ds = chartTotal.data.datasets[0];
      ds.borderColor = THEME.line.border;
      ds.pointBackgroundColor = THEME.line.pointBg;
      ds.pointBorderColor = THEME.line.border;
      ds.backgroundColor = grad;

      chartTotal.options.scales.x.grid.color = THEME.grid;
      chartTotal.options.scales.y.grid.color = THEME.grid;
      chartTotal.update('none');
    }

    if (chartByUnit) {
      chartByUnit.data.datasets.forEach((ds,i)=>{
        const key = THEME.bars[i % THEME.bars.length];
        ds.backgroundColor = rgba(key, .75);
        ds.borderColor     = rgba(key, 1);
      });
      chartByUnit.options.scales.x.grid.color = THEME.grid;
      chartByUnit.options.scales.y.grid.color = THEME.grid;
      chartByUnit.update('none');
    }
  }
  new MutationObserver(recolorCharts)
    .observe(document.documentElement, { attributes:true, attributeFilter:['data-bs-theme'] });

  // ====== Load data + render ======
  async function load() {
    try {
      $btnApply.disabled = true; $btnRefresh.disabled = true;

      const group        = currentGroup();
      const periods      = Math.max(1, parseInt($inpPeriods.value||12,10));
      const unitLimit    = parseInt($selUnitLimit.value,10);
      const includeOthers= $chkOthers.checked;
      const basis        = $selBasis.value;

      const startDate    = $inpStartDate.value || null;
      const endDate      = $inpEndDate.value   || null;

      if (startDate && endDate && startDate > endDate) {
        alert('Tanggal "Dari" tidak boleh lebih besar dari "Sampai".');
        return;
      }

      const { data } = await getDashboardAggregate(
        group, periods, unitLimit, includeOthers, basis, startDate, endDate
      );

      // Kartu — caption ikut anchor (pakai endDate jika ada)
      const anchor = endDate ? parseISO(endDate) : new Date();
      $cardTodayCap.textContent = `Aktivasi Tanggal ${fmtFull(anchor)}`;
      const monday = new Date(anchor); monday.setDate(anchor.getDate() - ((monday.getDay()+6)%7));
      $cardWeekCap.textContent  = `Aktivasi ${weekRangeCap(monday)}`;
      $cardMonthCap.textContent = `Aktivasi Bulan ${fmt(anchor,{month:'long',year:'numeric'})}`;

      $cardTotal.textContent = (data.cards?.total ?? 0).toLocaleString('id-ID');
      $cardToday.textContent = (data.cards?.today ?? 0).toLocaleString('id-ID');
      $cardWeek.textContent  = (data.cards?.this_week ?? 0).toLocaleString('id-ID');
      $cardMonth.textContent = (data.cards?.this_month ?? 0).toLocaleString('id-ID');

      // Labels grafik
      const labels = (data.labels||[]).map(iso => fmtLabel(group, iso));

      // Chart 1 (area/line)
      const ctx1 = document.getElementById('chartTotal').getContext('2d');
      const grad = ctx1.createLinearGradient(0,0,0,200);
      grad.addColorStop(0, THEME.line.fillTop);
      grad.addColorStop(1, THEME.line.fillBottom);

      if (chartTotal) chartTotal.destroy();
      chartTotal = new Chart(ctx1, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Aktivasi',
            data: data.series?.total || [],
            tension: .35,
            fill: true,
            backgroundColor: grad,
            borderColor: THEME.line.border,
            pointRadius: 3,
            pointBackgroundColor: THEME.line.pointBg,
            pointBorderColor: THEME.line.border,
          }]
        },
        options: {
          responsive: true,
          interaction: { mode:'index', intersect:false },
          scales: {
            x: { grid: { color: THEME.grid } },
            y: { beginAtZero:true, ticks:{ precision:0 }, grid: { color: THEME.grid } }
          },
          plugins: { legend: { display: false } }
        }
      });

      // Chart 2 (stacked unit)
      const ctx2 = document.getElementById('chartByUnit').getContext('2d');
      const datasets = (data.by_unit?.series || []).map((s, i) => {
        const key = THEME.bars[i % THEME.bars.length];
        return {
          label: s.label,
          data: s.data,
          stack: 'stack1',
          backgroundColor: rgba(key, .75),
          borderColor: rgba(key, 1),
          borderWidth: 1
        };
      });

      if (chartByUnit) chartByUnit.destroy();
      chartByUnit = new Chart(ctx2, {
        type: 'bar',
        data: { labels, datasets },
        options: {
          responsive: true,
          scales: {
            x: { stacked: true, grid: { color: THEME.grid } },
            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: THEME.grid } }
          },
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
      });

      updateFilterLabel();
    } catch (e) {
      console.error(e);
      alert('Gagal memuat dashboard. Cek service 8003 & endpoint /api/dashboard/aggregate');
    } finally {
      $btnApply.disabled = false; $btnRefresh.disabled = false;
    }
  }

  // ====== Event ======
  $btnApply.addEventListener('click', async () => {
    await load();
    // Tutup dropdown hanya saat "Terapkan"
    const t = document.getElementById('btnFilterLabel');
    if (window.bootstrap?.Dropdown) {
      window.bootstrap.Dropdown.getOrCreateInstance(t).hide();
    } else {
      document.querySelector('#btnFilterLabel + .dropdown-menu')?.classList.remove('show');
    }
  });
  $btnRefresh.addEventListener('click', load);
  grpRadios.forEach(r => r.addEventListener('change', updateFilterLabel));

  // Export PNG
  document.getElementById('btnExport').addEventListener('click', () => {
    if (!chartTotal) return;
    const a = document.createElement('a');
    a.href = chartTotal.toBase64Image('image/png', 1);
    a.download = `tren-aktivasi-${Date.now()}.png`;
    a.click();
  });

  // Init
  togglePeriodsState();
  updateFilterLabel();
  load();

  // Recolor juga saat preferensi OS dark/light berubah
  matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', recolorCharts);
</script>

<?php
$content = ob_get_clean();
$title = "Dashboard";
include 'layouts/template.php';
?>
