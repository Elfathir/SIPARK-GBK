// assets/js/area_parkir.js

const getBaseUrl = () => {
    const pathArray = window.location.pathname.split('/');
    const projectIndex = pathArray.findIndex(p => p.toLowerCase().startsWith('sipark-gbk'));
    if (projectIndex !== -1) {
        return '/' + pathArray.slice(1, projectIndex + 1).join('/') + '/';
    }
    return '/SIPARK-GBK/';
};
const BASE_URL = getBaseUrl();

let _areaCurrentPage = 1;
const _areaLimit = 10;
let _areaSearchTimeout = null;

document.addEventListener("DOMContentLoaded", () => {
    loadArea();

    // Live search
    const searchInput = document.getElementById("searchArea");
    if (searchInput) {
        searchInput.addEventListener("input", () => {
            clearTimeout(_areaSearchTimeout);
            _areaSearchTimeout = setTimeout(() => {
                _areaCurrentPage = 1;
                loadArea();
            }, 300);
        });
    }

    // Tutup modal saat klik overlay
    const overlay = document.getElementById("modalTambah");
    if (overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === this) {
                closeAreaModal();
            }
        });
    }
});

function loadArea() {
    const search = document.getElementById("searchArea") ? document.getElementById("searchArea").value : '';
    fetch(`${BASE_URL}dashboard/area_parkir/area_data.php?search=${encodeURIComponent(search)}&page=${_areaCurrentPage}&limit=${_areaLimit}`)
    .then(response => response.json())
    .then(res => {
        const tbody = document.getElementById("tableArea");
        if (!tbody) return;
        tbody.innerHTML = "";

        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data area parkir.</td></tr>`;
            const pg = document.getElementById("paginationArea");
            if (pg) pg.innerHTML = '';
            return;
        }

        let no = (_areaCurrentPage - 1) * _areaLimit + 1;
        res.data.forEach(area => {
            tbody.innerHTML += `
            <tr>
                <td>${no++}</td>
                <td style="font-weight: 600; color: var(--primary);">${escapeHtmlArea(area.nama_area)}</td>
                <td>${escapeHtmlArea(area.lokasi)}</td>
                <td>${escapeHtmlArea(area.kapasitas_mobil)}</td>
                <td>${escapeHtmlArea(area.kapasitas_motor)}</td>
                <td>
                    ${area.status === "Aktif"
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-danger">Tidak Aktif</span>'
                    }
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon edit" title="Edit" onclick="editArea(${area.id_area})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete" title="Hapus" onclick="hapusArea(${area.id_area})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });

        renderAreaPagination(res.pagination);
    })
    .catch(error => {
        console.error("Gagal memuat area:", error);
        showAreaToast("danger", "Error", "Gagal memuat data area parkir.");
    });
}

function renderAreaPagination(p) {
    const wrapper = document.getElementById("paginationArea");
    if (!wrapper) return;
    wrapper.innerHTML = '';

    if (!p || p.pages <= 1) return;

    const start = (p.page - 1) * p.limit + 1;
    const end   = Math.min(p.page * p.limit, p.total);

    let html = `<div class="pagination-info">Menampilkan ${start}–${end} dari ${p.total} data</div>`;
    html += `<div class="pagination-list">`;

    // Prev
    if (p.page > 1) {
        html += `<span class="page-link" onclick="changeAreaPage(${p.page - 1})"><i class="fas fa-chevron-left"></i></span>`;
    } else {
        html += `<span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    // Nomor halaman (tampilkan maks 5 di sekitar halaman aktif)
    const maxVisible = 5;
    let startPage = Math.max(1, p.page - Math.floor(maxVisible / 2));
    let endPage   = Math.min(p.pages, startPage + maxVisible - 1);
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        html += `<span class="page-link" onclick="changeAreaPage(1)">1</span>`;
        if (startPage > 2) html += `<span class="page-link disabled">&hellip;</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<span class="page-link${i === p.page ? ' active' : ''}" onclick="changeAreaPage(${i})">${i}</span>`;
    }

    if (endPage < p.pages) {
        if (endPage < p.pages - 1) html += `<span class="page-link disabled">&hellip;</span>`;
        html += `<span class="page-link" onclick="changeAreaPage(${p.pages})">${p.pages}</span>`;
    }

    // Next
    if (p.page < p.pages) {
        html += `<span class="page-link" onclick="changeAreaPage(${p.page + 1})"><i class="fas fa-chevron-right"></i></span>`;
    } else {
        html += `<span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    html += `</div>`;
    wrapper.innerHTML = html;
}

function changeAreaPage(page) {
    _areaCurrentPage = page;
    loadArea();
}

async function editArea(id) {
    try {
        const response = await fetch(`${BASE_URL}dashboard/area_parkir/get_area.php?id=${id}`);
        const area = await response.json();

        if (!area || !area.id_area) {
            showAreaToast("danger", "Gagal", "Data area tidak ditemukan.");
            return;
        }

        document.getElementById("modalTitle").innerHTML = "Edit Area Parkir";
        document.getElementById("id_area").value = area.id_area;
        document.getElementById("nama_area").value = area.nama_area;
        document.getElementById("lokasi").value = area.lokasi;
        document.getElementById("kapasitas_mobil").value = area.kapasitas_mobil;
        document.getElementById("kapasitas_motor").value = area.kapasitas_motor;
        document.getElementById("status_area").value = area.status;

        document.getElementById("modalTambah").classList.add("show");
        // Kunci scroll saat modal terbuka
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error("Gagal mengambil data area:", error);
        showAreaToast("danger", "Error", "Gagal mengambil data area.");
    }
}

function openAreaModal() {
    const form = document.getElementById("formTambahArea");
    if (form) form.reset();
    document.getElementById("id_area").value = "";
    document.getElementById("modalTitle").innerHTML = "Tambah Area Parkir";
    document.getElementById("modalTambah").classList.add("show");
    // Kunci scroll saat modal terbuka
    document.body.style.overflow = 'hidden';
}

function closeAreaModal() {
    const form = document.getElementById("formTambahArea");
    if (form) form.reset();
    document.getElementById("id_area").value = "";
    document.getElementById("modalTambah").classList.remove("show");
    // Kembalikan scroll
    document.body.style.overflow = '';
}

async function submitArea() {
    const form = document.getElementById("formTambahArea");
    const idArea = document.getElementById("id_area").value;

    const nama_area = document.getElementById("nama_area").value.trim();
    const lokasi = document.getElementById("lokasi").value.trim();
    const kapasitas_mobil = parseInt(document.getElementById("kapasitas_mobil").value) || 0;
    const kapasitas_motor = parseInt(document.getElementById("kapasitas_motor").value) || 0;
    const status = document.getElementById("status_area").value;

    // Validasi
    if (!nama_area) {
        showAreaToast("warning", "Peringatan", "Nama area wajib diisi.");
        return;
    }
    if (!lokasi) {
        showAreaToast("warning", "Peringatan", "Lokasi wajib diisi.");
        return;
    }
    if (kapasitas_mobil < 0 || kapasitas_motor < 0) {
        showAreaToast("warning", "Peringatan", "Kapasitas tidak boleh negatif.");
        return;
    }

    const data = {
        id_area: idArea ? parseInt(idArea) : null,
        nama_area: nama_area,
        lokasi: lokasi,
        kapasitas_mobil: kapasitas_mobil,
        kapasitas_motor: kapasitas_motor,
        status: status
    };

    const url = idArea
        ? `${BASE_URL}dashboard/area_parkir/update_area.php`
        : `${BASE_URL}dashboard/area_parkir/simpan.php`;

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            closeAreaModal();
            loadArea();
            showAreaToast("success", "Berhasil", result.message || "Data berhasil disimpan.");
        } else {
            showAreaToast("danger", "Gagal", result.message || "Gagal menyimpan data.");
        }
    } catch (error) {
        console.error("Error submit area:", error);
        showAreaToast("danger", "Error", "Terjadi kesalahan server.");
    }
}

function hapusArea(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus area parkir ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`${BASE_URL}dashboard/area_parkir/delete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id_area: id })
                });
                const res = await response.json();
                if (res.success) {
                    loadArea();
                    Swal.fire('Terhapus!', res.message || 'Area parkir berhasil dihapus.', 'success');
                } else {
                    Swal.fire('Gagal!', res.message || 'Gagal menghapus area parkir.', 'error');
                }
            } catch (error) {
                console.error("Error delete area:", error);
                Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
            }
        }
    });
}

function escapeHtmlArea(text) {
    if (text === null || text === undefined) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showAreaToast(type, title, message) {
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    let icon = "fa-circle-info";
    switch (type) {
        case "success": icon = "fa-circle-check"; break;
        case "danger":  icon = "fa-circle-xmark"; break;
        case "warning": icon = "fa-triangle-exclamation"; break;
        case "info":    icon = "fa-circle-info"; break;
    }

    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-desc">${message}</div>
        </div>
        <div class="toast-close">
            <i class="fas fa-times"></i>
        </div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add("show");
    }, 100);

    toast.querySelector(".toast-close").onclick = () => {
        toast.remove();
    };

    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4000);
}
