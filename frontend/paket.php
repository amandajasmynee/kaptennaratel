<?php
ob_start();
?>

<h4 class="fw-bold py-3 mb-4 d-flex justify-content-between align-items-center">
  Data Paket
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paketModal" id="btn-tambah">
    + Tambah Paket
  </button>
</h4>

<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Nama Paket</th>
          <th>Harga</th>
          <th>DPP</th>
          <th>PPN</th>
          <th>Margin</th>
          <th>Kecepatan</th>
          <th>Keterangan</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="paket-body">
        <tr>
          <td colspan="10" class="text-center">Memuat data...</td>
        </tr>
      </tbody>
    </table>
    <nav>
      <ul class="pagination justify-content-center mt-3" id="pagination"></ul>
      <div class="text-center small text-muted mt-2" id="pagination-info"></div>
    </nav>
  </div>
</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="paketModal" tabindex="-1" aria-labelledby="paketModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="paket-form">
      <div class="modal-header">
        <h5 class="modal-title" id="paketModalLabel">Tambah Paket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="paket-id">
        <input type="hidden" id="ref_gol" value="G3">
        <input type="hidden" id="create_log" value="<?= date('Y-m-d H:i:s') ?>">

        <div class="mb-3">
          <label class="form-label">Nama Paket</label>
          <input type="text" class="form-control" id="alias_paket" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Harga</label>
          <input type="number" class="form-control" id="total_amount" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Kecepatan</label>
          <input type="text" class="form-control" id="paket">
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <input type="text" class="form-control" id="jenis_paket">
        </div>
        <div class="mb-3">
          <label class="form-label">DPP</label>
          <input type="number" class="form-control" id="dpp">
        </div>
        <div class="mb-3">
          <label class="form-label">PPN</label>
          <input type="number" class="form-control" id="ppn">
        </div>
        <div class="mb-3">
          <label class="form-label">Margin</label>
          <input type="number" class="form-control" id="margin">
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-control" id="status">
            <option value="enable">enable</option>
            <option value="disable">disable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<script type="module">
  import {
    getHargaPaket,
    createHargaPaket,
    updateHargaPaket,
    deleteHargaPaket,
  } from './api.js';

  // ===== DOM =====
  const tableBody   = document.getElementById('paket-body');
  const btnTambah   = document.getElementById('btn-tambah');
  const modal       = new bootstrap.Modal(document.getElementById('paketModal'));
  const form        = document.getElementById('paket-form');
  const modalLabel  = document.getElementById('paketModalLabel');

  // form fields
  const aliasInput     = document.getElementById('alias_paket');
  const hargaInput     = document.getElementById('total_amount');
  const paketInput     = document.getElementById('paket');
  const ketInput       = document.getElementById('jenis_paket');
  const dppInput       = document.getElementById('dpp');
  const ppnInput       = document.getElementById('ppn');
  const marginInput    = document.getElementById('margin');
  const statusInput    = document.getElementById('status');
  const idInput        = document.getElementById('paket-id');
  const refGolInput    = document.getElementById('ref_gol');
  const createLogInput = document.getElementById('create_log');

  // ===== State =====
  let editMode    = false;
  let currentPage = 1;
  let lastPage    = 1;
  const perPage   = 10;

  // ===== Page memory (simpel) =====
  const PAGE_KEY = 'paket.currentPage';
  const getStoredPage = () => parseInt(sessionStorage.getItem(PAGE_KEY) || '1', 10);
  const setStoredPage = (p) => sessionStorage.setItem(PAGE_KEY, String(p));

  // optional: baca ?page=
  (function readInitialPageFromUrl(){
    const qs = new URLSearchParams(location.search);
    const qp = parseInt(qs.get('page') || '0', 10);
    if (qp > 0) setStoredPage(qp);
  })();

  // Deteksi apakah datang dari halaman selain paket
  function cameFromDifferentSection() {
    try {
      if (!document.referrer) return true; // tidak ada referrer → anggap beda section
      const ref = new URL(document.referrer, location.origin);
      // ganti keyword 'paket' sesuai routing/filename halaman ini jika perlu
      return !ref.pathname.includes('paket');
    } catch {
      return true;
    }
  }

  // ===== Pagination Renderer (grup 3) =====
  function renderPagination(current, last) {
    current = Math.max(1, Math.min(current, last || 1));
    last    = Math.max(1, last || 1);

    const groupSize    = 3;
    const currentGroup = Math.floor((current - 1) / groupSize);
    const startPage    = currentGroup * groupSize + 1;
    const endPage      = Math.min(startPage + groupSize - 1, last);

    let html = '';
    if (startPage > 1) {
      html += `<li class="page-item"><a class="page-link rounded-0 border" href="#" data-page="${startPage - 1}">&laquo;</a></li>`;
    }
    for (let i = startPage; i <= endPage; i++) {
      html += `<li class="page-item ${i===current?'active':''}">
                 <a class="page-link rounded-0 border" href="#" data-page="${i}">${i}</a>
               </li>`;
    }
    if (endPage < last) {
      html += `<li class="page-item"><a class="page-link rounded-0 border" href="#" data-page="${endPage + 1}">&raquo;</a></li>`;
    }

    document.getElementById('pagination').innerHTML = html;
    document.getElementById('pagination-info').textContent = `Page ${current} of ${last}`;
    lastPage = last;
  }

  // Delegasi klik pagination
  document.getElementById('pagination').addEventListener('click', (e) => {
    const a = e.target.closest('a.page-link');
    if (!a) return;
    e.preventDefault();
    const next = parseInt(a.dataset.page || '0', 10);
    if (!next || next === currentPage) return;
    loadHargaPaket(next);
  });

  // ===== Fetch & render =====
  async function loadHargaPaket(pageArg = null, { highlightId = null } = {}) {
    const page = pageArg ?? getStoredPage();

    currentPage = Math.max(1, page);
    setStoredPage(currentPage);

    tableBody.innerHTML = '<tr><td colspan="10" class="text-center">Memuat data...</td></tr>';

    try {
      const result = await getHargaPaket(currentPage, perPage);

      const last = Number(result.last_page ?? currentPage) || currentPage;
      if (currentPage > last && last >= 1) {
        currentPage = last;
        setStoredPage(last);
        return loadHargaPaket(last, { highlightId });
      }

      const rows = result.data ?? [];
      if (!Array.isArray(rows) || rows.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center">Tidak ada data ditemukan</td></tr>';
        renderPagination(currentPage, last);
        return;
      }

      tableBody.innerHTML = '';
      rows.forEach((item, index) => {
        const row = `
          <tr data-row-id="${item.log_key}">
            <td>${(currentPage - 1) * perPage + index + 1}</td>
            <td>${item.alias_paket}</td>
            <td>Rp ${parseFloat(item.total_amount ?? 0).toLocaleString('id-ID')}</td>
            <td>Rp ${parseFloat(item.dpp ?? 0).toLocaleString('id-ID')}</td>
            <td>Rp ${parseFloat(item.ppn ?? 0).toLocaleString('id-ID')}</td>
            <td>Rp ${parseFloat(item.margin ?? 0).toLocaleString('id-ID')}</td>
            <td>${(item.paket ?? '').toString().trim() || '-'}</td>
            <td>${item.jenis_paket || '-'}</td>
            <td><span class="badge bg-${item.status === 'enable' ? 'success' : 'danger'}">${item.status}</span></td>
            <td>
              <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="dropdown-item text-warning btn-edit" data-id="${item.log_key}">Edit</a></li>
                  <li><a href="#" class="dropdown-item text-danger btn-delete" data-id="${item.log_key}">Hapus</a></li>
                </ul>
              </div>
            </td>
          </tr>
        `;
        tableBody.insertAdjacentHTML('beforeend', row);
      });

      renderPagination(result.current_page || currentPage, last);

      // edit
      tableBody.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const id = btn.dataset.id;
          const item = rows.find(d => String(d.log_key) === String(id));
          if (!item) return;

          idInput.value     = item.log_key;
          aliasInput.value  = item.alias_paket ?? '';
          hargaInput.value  = item.total_amount ?? '';
          paketInput.value  = (item.paket ?? '').toString().trim();
          ketInput.value    = item.jenis_paket ?? '';
          dppInput.value    = item.dpp ?? '';
          ppnInput.value    = item.ppn ?? '';
          marginInput.value = item.margin ?? '';
          statusInput.value = item.status ?? 'enable';

          editMode = true;
          modalLabel.textContent = 'Edit Paket';
          modal.show();
        });
      });

      // delete
      tableBody.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async (e) => {
          e.preventDefault();
          const id = btn.dataset.id;
          if (!confirm('Yakin ingin menghapus data ini?')) return;
          await deleteHargaPaket(id);
          await loadHargaPaket(currentPage);
        });
      });

      // highlight row kalau ada
      if (highlightId) {
        const row = tableBody.querySelector(`[data-row-id="${highlightId}"]`);
        if (row) {
          row.classList.add('table-success');
          setTimeout(() => row.classList.remove('table-success'), 1200);
        }
      }
    } catch (err) {
      console.error('Gagal memuat data:', err);
      tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Gagal memuat data</td></tr>';
      document.getElementById('pagination').innerHTML = '';
      document.getElementById('pagination-info').textContent = '';
    }
  }

  // ===== Submit form: stay di halaman yang sama =====
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const pageToReload = currentPage;

    const payload = {
      alias_paket:  aliasInput.value,
      total_amount: hargaInput.value,
      paket:        paketInput.value,
      jenis_paket:  ketInput.value,
      dpp:          dppInput.value,
      ppn:          ppnInput.value,
      margin:       marginInput.value,
      status:       statusInput.value,
      ref_gol:      refGolInput.value,
      create_log:   createLogInput.value,
    };

    try {
      let editedId = null;

      if (editMode) {
        editedId = idInput.value;
        await updateHargaPaket(editedId, payload);
      } else {
        const res = await createHargaPaket(payload);
        editedId = res?.data?.log_key ?? null;
      }

      modal.hide();
      form.reset();
      editMode = false;

      await loadHargaPaket(pageToReload, { highlightId: editedId });

      document.activeElement?.blur?.();
      btnTambah.focus();
    } catch (err) {
      alert('Terjadi kesalahan: ' + (err?.message || err));
    }
  });

  // ===== Mode tambah =====
  btnTambah.addEventListener('click', () => {
    form.reset();
    idInput.value = '';
    editMode = false;
    modalLabel.textContent = 'Tambah Paket';
  });

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', () => {
    // Kalau datang dari halaman lain → reset page ke 1
    if (cameFromDifferentSection()) {
      setStoredPage(1);
    }
    loadHargaPaket(getStoredPage());
  });
</script>

<?php
$content = ob_get_clean();
$title = "Data Paket";
include 'layouts/template.php';
?>
