<?php
ob_start();
?>

<h4 class="fw-bold py-3 mb-4 d-flex justify-content-between align-items-center">
  Data Pelanggan
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-pelanggan" id="btn-tambah">
    + Tambah Pelanggan
  </button>
</h4>

<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>No WA</th>
          <th>Alamat</th>
          <th>Unit</th>
          <th>Status</th>
          <th>Paket</th>
          <th>Tipe Paket</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="pelanggan-body">
        <tr>
          <td colspan="9" class="text-center text-muted">Memuat data...</td>
        </tr>
      </tbody>
    </table>

    <div class="d-flex flex-column align-items-center p-3">
      <nav>
        <ul class="pagination justify-content-center mt-3" id="pagination"></ul>
        <div class="text-center small text-muted mt-2" id="pagination-info"></div>
      </nav>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modal-pelanggan" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" id="form-pelanggan">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Tambah Pelanggan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" id="pelanggan-id">
        <input type="hidden" id="page-lock">

        <div class="col-md-6">
          <label for="nama-pelanggan" class="form-label">Nama</label>
          <input type="text" class="form-control" id="nama-pelanggan" required>
        </div>
        <div class="col-md-6">
          <label for="telp-user" class="form-label">No WA</label>
          <input type="text" class="form-control" id="telp-user" required>
        </div>

        <div class="col-md-12">
          <label for="alamat-pelanggan" class="form-label">Alamat</label>
          <textarea class="form-control" id="alamat-pelanggan" rows="2"></textarea>
        </div>

        <div class="col-md-3">
          <label for="rt" class="form-label">RT</label>
          <input type="text" class="form-control" id="rt">
        </div>
        <div class="col-md-3">
          <label for="rw" class="form-label">RW</label>
          <input type="text" class="form-control" id="rw">
        </div>

        <div class="col-md-6">
          <label for="kelurahan-id" class="form-label">Kelurahan ID</label>
          <input type="text" class="form-control" id="kelurahan-id">
        </div>
        <div class="col-md-6">
          <label for="kecamatan" class="form-label">Kecamatan</label>
          <input type="text" class="form-control" id="kecamatan">
        </div>

        <div class="col-md-6">
          <label for="unit-id" class="form-label">Unit</label>
          <select class="form-select" id="unit-id" required></select>
        </div>
        <div class="col-md-6">
          <label for="harga-paket-id" class="form-label">Harga Paket</label>
          <select class="form-select" id="harga-paket-id" required></select>
        </div>

        <div class="col-md-6">
          <label for="status-log" class="form-label">Status</label>
          <select class="form-select" id="status-log">
            <option value="enable">enable</option>
            <option value="disable">disable</option>
          </select>
        </div>
        <div class="col-md-6">
          <label for="status-followup" class="form-label">Status Follow-up</label>
          <input type="text" class="form-control" id="status-followup">
        </div>

        <div class="col-md-6">
          <label for="stts-send-survei" class="form-label">Status Survei</label>
          <input type="text" class="form-control" id="stts-send-survei">
        </div>
        <div class="col-md-6">
          <label for="log-aktivasi" class="form-label">Log Aktivasi</label>
          <input type="datetime-local" class="form-control" id="log-aktivasi">
        </div>

        <div class="col-md-6">
          <label for="va-bri" class="form-label">VA BRI</label>
          <input type="text" class="form-control" id="va-bri">
        </div>
        <div class="col-md-6">
          <label for="va-bca" class="form-label">VA BCA</label>
          <input type="text" class="form-control" id="va-bca">
        </div>

        <div class="col-md-6">
          <label for="no-combo" class="form-label">No Combo</label>
          <input type="text" class="form-control" id="no-combo">
        </div>
        <div class="col-md-6">
          <label for="log-username-dcp" class="form-label">Username DCP</label>
          <input type="text" class="form-control" id="log-username-dcp">
        </div>

        <div class="col-md-6">
          <label for="pendaftaran-id" class="form-label">ID Pendaftaran</label>
          <input type="text" class="form-control" id="pendaftaran-id">
        </div>
        <div class="col-md-6">
          <label for="id-telegram" class="form-label">ID Telegram</label>
          <input type="text" class="form-control" id="id-telegram">
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
    getPelanggan,
    createPelanggan,
    updatePelanggan,
    deletePelanggan,
    getSinglePelanggan,
    getAllUnits,
    getAllHargaPaket
  } from './api.js';

  // ===== Helpers: Toast & Confirm (fallback kalau belum ada di template) =====
  const notify = (msg, variant = 'info') => {
    if (window.showToast) return window.showToast(msg, variant);
    // fallback
    console.log(`[${variant}] ${msg}`);
  };
  const confirmUI = async (opts = {}) => {
    if (window.uiConfirm) return await window.uiConfirm(opts);
    // fallback
    return confirm(opts?.message || 'Apakah Anda yakin?');
  };

  const perPage = 10;
  const PAGE_KEY = 'pelanggan.currentPage';
  const removeEmptyFields = (o) => Object.fromEntries(Object.entries(o).filter(([_, v]) => v !== null && v !== ''));
  const isEnabled = (p) => {
    const v = p?.status ?? p?.is_active ?? p?.active ?? p?.enabled ?? p?.status_paket ?? p?.isEnable;
    if (typeof v === 'string') return ['enable','enabled','aktif','active','1','true'].includes(v.toLowerCase());
    return !!v;
  };

  // ===== DOM =====
  const pelangganBody   = document.getElementById('pelanggan-body');
  const paginationEl    = document.getElementById('pagination');
  const paginationInfo  = document.getElementById('pagination-info');

  const modalEl         = document.getElementById('modal-pelanggan');
  const modalPelanggan  = new bootstrap.Modal(modalEl);
  const formPelanggan   = document.getElementById('form-pelanggan');
  const btnTambah       = document.getElementById('btn-tambah');
  const modalLabel      = document.getElementById('modalLabel');

  const pelangganId     = document.getElementById('pelanggan-id');
  const pageLock        = document.getElementById('page-lock');

  const namaPelanggan   = document.getElementById('nama-pelanggan');
  const telpUser        = document.getElementById('telp-user');
  const alamatPelanggan = document.getElementById('alamat-pelanggan');
  const rt              = document.getElementById('rt');
  const rw              = document.getElementById('rw');
  const kelurahanId     = document.getElementById('kelurahan-id');
  const kecamatan       = document.getElementById('kecamatan');
  const unitId          = document.getElementById('unit-id');
  const hargaPaketId    = document.getElementById('harga-paket-id');
  const statusLog       = document.getElementById('status-log');
  const statusFollowup  = document.getElementById('status-followup');
  const sttsSendSurvei  = document.getElementById('stts-send-survei');
  const logAktivasi     = document.getElementById('log-aktivasi');
  const vaBri           = document.getElementById('va-bri');
  const vaBca           = document.getElementById('va-bca');
  const noCombo         = document.getElementById('no-combo');
  const logUsernameDcp  = document.getElementById('log-username-dcp');
  const pendaftaranId   = document.getElementById('pendaftaran-id');
  const idTelegram      = document.getElementById('id-telegram');

  // ===== Page state + URL sync =====
  const getCurrentPage = () => parseInt(sessionStorage.getItem(PAGE_KEY) || '1', 10);
  const setCurrentPage = (p) => sessionStorage.setItem(PAGE_KEY, String(p));
  const syncUrlPage = (p) => {
    const u = new URL(location.href);
    u.searchParams.set('page', String(p));
    history.replaceState(null, '', u.toString());
  };

  // baca ?page= dari URL jika ada (initial)
  {
    const qs = new URLSearchParams(location.search);
    const qp = parseInt(qs.get('page') || '0', 10);
    if (qp > 0) setCurrentPage(qp);
  }

  let currentPage = getCurrentPage();
  let lastPage = 1;
  let lockedPage  = null;
  let isSaving    = false;

  let units = [];
  let hargaPaket = [];
  let hargaPaketEnabled = [];

  // ===== Dropdown =====
  function populateSelect(selectElement, data, valueKey, textKey, additionalTextKey = null) {
    selectElement.innerHTML = '<option value="">Pilih...</option>';
    (data || []).forEach(item => {
      if (!item) return;
      const option = document.createElement('option');
      option.value = item[valueKey];
      let text = item[textKey];
      if (additionalTextKey && item[additionalTextKey] != null) {
        let add = item[additionalTextKey];
        if (!isNaN(+add)) add = new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 }).format(+add);
        text += ` - ${add}`;
      }
      option.textContent = text;
      selectElement.appendChild(option);
    });
  }

  // ===== Fetch & render =====
  async function fetchAndDisplayAllData(page = getCurrentPage(), _retry = false) {
    setCurrentPage(page);
    currentPage = page;
    syncUrlPage(page);

    pelangganBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Memuat data...</td></tr>';

    try {
      const [u, h] = await Promise.all([ getAllUnits(), getAllHargaPaket() ]);
      units = u.data || [];
      hargaPaket = h.data || [];
      hargaPaketEnabled = (hargaPaket || []).filter(isEnabled);

      const unitsForSelect = units.map(x => ({ id:x.id, label:`[${x.kode_unit}] - ${x.nama_unit}` }));
      populateSelect(unitId, unitsForSelect, 'id', 'label');
      populateSelect(hargaPaketId, hargaPaketEnabled, 'log_key', 'alias_paket', 'total_amount');

      const result = await getPelanggan(page, perPage);

      const last = Number(result.last_page ?? page) || page;

      if (!_retry && page > last && last >= 1) {
        setCurrentPage(last); currentPage = last; syncUrlPage(last);
        return fetchAndDisplayAllData(last, true);
      }

      renderPelangganTable(Array.isArray(result.data) ? result.data : []);
      renderPagination(page, last);
    } catch (e) {
      console.error('Gagal memuat data:', e);
      pelangganBody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Gagal memuat data.</td></tr>';
      paginationEl.innerHTML = '';
      paginationInfo.textContent = '';
      notify('Gagal memuat data pelanggan.', 'danger');
    }
  }

  function renderPelangganTable(data) {
    if (!Array.isArray(data) || data.length === 0) {
      pelangganBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Tidak ada data pelanggan.</td></tr>';
      return;
    }

    pelangganBody.innerHTML = '';
    const offset = (currentPage - 1) * perPage;

    data.forEach((pelanggan, index) => {
      const unit = units.find(u => u.id == (pelanggan.unit_id || pelanggan.unit?.id));
      const unitName = unit ? `[${unit.kode_unit}] - ${unit.nama_unit}` : '-';

      const paket = hargaPaket.find(p => p.log_key == (pelanggan.harga_paket_id || pelanggan.harga_paket?.id));
      const paketTampil = paket && isEnabled(paket) ? paket : null;

      const packageName = paketTampil ? paketTampil.alias_paket : '-';
      const packageType = paketTampil ? (paketTampil.jenis_paket ?? '-') : '-';

      const isEnable = pelanggan.status_log === 'enable';
      const statusBadgeClass = isEnable ? 'bg-success' : 'bg-danger';
      const statusLabel = isEnable ? 'enable' : 'disable';

      const row = `
        <tr data-row-id="${pelanggan.id}">
          <td>${offset + index + 1}</td>
          <td>${pelanggan.nama_pelanggan || '-'}</td>
          <td>${pelanggan.telp_user || pelanggan.telp || '-'}</td>
          <td>${pelanggan.alamat_pelanggan || pelanggan.alamat || '-'}</td>
          <td>${unitName}</td>
          <td><span class="badge ${statusBadgeClass}">${statusLabel}</span></td>
          <td>${packageName}</td>
          <td>${packageType}</td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Action
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="dropdown-item text-warning btn-edit" data-id="${pelanggan.id}">Edit</a></li>
                <li><a href="#" class="dropdown-item text-danger btn-delete" data-id="${pelanggan.id}">Hapus</a></li>
              </ul>
            </div>
          </td>
        </tr>
      `;
      pelangganBody.insertAdjacentHTML('beforeend', row);
    });

    addRowButtonHandlers();
  }

  // ===== Pagination (grup 3 + « » antar-grup) =====
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

    paginationEl.innerHTML = html;
    paginationInfo.textContent = `Page ${current} of ${last}`;
    lastPage = last;
  }

  // Delegasi klik pagination
  paginationEl.addEventListener('click', (e) => {
    const a = e.target.closest('a.page-link');
    if (!a) return;
    e.preventDefault();
    const next = parseInt(a.dataset.page || '0', 10);
    if (!next || next === currentPage) return;
    setCurrentPage(next);
    currentPage = next;
    syncUrlPage(next);
    fetchAndDisplayAllData(next);
  });

  // ===== Row action handlers =====
  function addRowButtonHandlers() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.onclick = async (e) => {
        e.preventDefault();
        lockedPage   = getCurrentPage();
        pageLock.value = String(lockedPage);

        try {
          const p = await getSinglePelanggan(btn.dataset.id);
          if (!p) {
            notify('Data pelanggan tidak ditemukan.', 'warning');
            return;
          }

          modalLabel.textContent = 'Edit Pelanggan';
          pelangganId.value = p.id;
          namaPelanggan.value = p.nama_pelanggan || '';
          telpUser.value = p.telp_user || p.telp || '';
          alamatPelanggan.value = p.alamat_pelanggan || p.alamat || '';
          rt.value = p.rt || '';
          rw.value = p.rw || '';
          kelurahanId.value = p.kelurahan_id || '';
          kecamatan.value = p.kecamatan || '';

          const unitsForSelect = (units || []).map(u => ({ id:u.id, label:`[${u.kode_unit}] - ${u.nama_unit}` }));
          populateSelect(unitId, unitsForSelect, 'id', 'label');
          populateSelect(hargaPaketId, hargaPaketEnabled, 'log_key', 'alias_paket', 'total_amount');

          unitId.value = p.unit?.id || '';

          const selectedPaketId = p.harga_paket?.id || '';
          if (selectedPaketId && hargaPaketEnabled.some(x => String(x.log_key) === String(selectedPaketId))) {
            hargaPaketId.value = selectedPaketId;
          } else {
            hargaPaketId.value = '';
          }

          statusLog.value = p.status_log || '';
          statusFollowup.value = p.status_followup || '';
          sttsSendSurvei.value = p.stts_send_survei || '';
          logAktivasi.value = p.log_aktivasi ? new Date(p.log_aktivasi).toISOString().slice(0,16) : '';
          vaBri.value = p.va_bri || '';
          vaBca.value = p.va_bca || '';
          noCombo.value = p.no_combo || '';
          logUsernameDcp.value = p.log_username_dcp || '';
          pendaftaranId.value = p.pendaftaran_id || '';
          idTelegram.value = p.id_telegram || '';

          setTimeout(() => modalPelanggan.show(), 10);
        } catch (err) {
          console.error(err);
          notify('Gagal memuat data pelanggan.', 'danger');
        }
      };
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.onclick = async (e) => {
        e.preventDefault();

        const ok = await confirmUI({
          title: 'Hapus Pelanggan',
          message: 'Apakah Anda yakin ingin menghapus pelanggan ini?',
          okText: 'Hapus',
          cancelText: 'Batal',
          okVariant: 'danger'
        });
        if (!ok) return;

        try {
          await deletePelanggan(btn.dataset.id);
          notify('Pelanggan berhasil dihapus!', 'success');
          await fetchAndDisplayAllData(getCurrentPage());
        } catch (err) {
          console.error(err);
          notify('Gagal menghapus pelanggan.', 'danger');
        }
      };
    });
  }

  // Bersihkan lock saat modal ditutup tanpa save
  modalEl.addEventListener('hidden.bs.modal', () => {
    if (!isSaving && !pelangganId.value) lockedPage = null;
    pelangganId.value = '';
    pageLock.value = '';
  });

  // ===== Submit form (Create/Update) — pakai Toast =====
  formPelanggan.addEventListener('submit', async (e) => {
    e.preventDefault();
    isSaving = true;

    const fromHidden   = parseInt(pageLock.value || '0', 10);
    const pageToReload = fromHidden > 0 ? fromHidden : (lockedPage ?? getCurrentPage());
    const editedId     = pelangganId.value || null;

    let payload = removeEmptyFields({
      nama_pelanggan: namaPelanggan.value,
      telp_user: telpUser.value,
      alamat_pelanggan: alamatPelanggan.value,
      rt: rt.value, rw: rw.value,
      kelurahan_id: kelurahanId.value, kecamatan: kecamatan.value,
      unit_id: unitId.value,
      harga_paket_id: hargaPaketId.value,
      status_log: statusLog.value,
      status_followup: statusFollowup.value,
      stts_send_survei: sttsSendSurvei.value,
      log_aktivasi: logAktivasi.value ? new Date(logAktivasi.value).toISOString() : null,
      va_bri: vaBri.value, va_bca: vaBca.value,
      no_combo: noCombo.value,
      log_username_dcp: logUsernameDcp.value,
      pendaftaran_id: pendaftaranId.value,
      id_telegram: idTelegram.value,
    });

    try {
      if (pelangganId.value) {
        await updatePelanggan(pelangganId.value, payload);
        notify('Data pelanggan berhasil diperbarui!', 'success');
      } else {
        await createPelanggan(payload);
        notify('Pelanggan berhasil ditambahkan!', 'success');
      }

      modalPelanggan.hide();
      formPelanggan.reset();

      await fetchAndDisplayAllData(pageToReload);
      lockedPage = null;

      if (editedId) {
        const row = document.querySelector(`[data-row-id="${editedId}"]`);
        if (row) {
          row.classList.add('table-success');
          setTimeout(() => row.classList.remove('table-success'), 1200);
        }
      }
    } catch (err) {
      console.error(err);
      notify('Gagal menyimpan data pelanggan.', 'danger');
    } finally {
      isSaving = false;
      pageLock.value = '';
    }
  });

  // Mode tambah
  btnTambah.addEventListener('click', () => {
    modalLabel.textContent = 'Tambah Pelanggan';
    formPelanggan.reset();
    const unitsForSelect = (units || []).map(u => ({ id:u.id, label:`[${u.kode_unit}] - ${u.nama_unit}` }));
    populateSelect(unitId, unitsForSelect, 'id', 'label');
    populateSelect(hargaPaketId, hargaPaketEnabled, 'log_key', 'alias_paket', 'total_amount');
    lockedPage = null;
    pageLock.value = '';
  });

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    fetchAndDisplayAllData(getCurrentPage());
  });
</script>

<?php
$content = ob_get_clean();
$title = "Data Pelanggan";
include 'layouts/template.php';
?>
