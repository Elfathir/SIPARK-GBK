/* assets/js/app.js */

document.addEventListener('DOMContentLoaded', function() {
    // 1. REALTIME CLOCK & DATE (INDONESIAN STYLE)
    const clockDisplay = document.getElementById('clock-display');
    const dateDisplay = document.getElementById('date-display');

    if (clockDisplay || dateDisplay) {
        function updateClock() {
            const now = new Date();
            
            // Format Clock: HH:MM:SS
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            if (clockDisplay) {
                clockDisplay.textContent = `${hours}:${minutes}:${seconds} WIB`;
            }

            // Format Date: Hari, DD Bulan YYYY
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[now.getDay()];
            const dateNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();

            if (dateDisplay) {
                dateDisplay.textContent = `${dayName}, ${dateNum} ${monthName} ${year}`;
            }
        }
        
        updateClock();
        setInterval(updateClock, 1000);
    }

        // 2. SIDEBAR RESPONSIVE

        const sidebar = document.getElementById("sidebar");
        const toggleSidebarBtn = document.getElementById("btn-toggle-sidebar");

        function updateSidebarMode(){

            if(window.innerWidth <= 991){

                sidebar.classList.remove("collapsed");

            }else{

                sidebar.classList.remove("show");

                const sidebarState =
                    localStorage.getItem("sidebar-collapsed");

                if(sidebarState === "true"){

                    sidebar.classList.add("collapsed");

                }else{

                    sidebar.classList.remove("collapsed");

                }

            }

        }

        updateSidebarMode();

        window.addEventListener("resize", updateSidebarMode);

        toggleSidebarBtn.addEventListener("click",function(){

            if(window.innerWidth <= 991){

                sidebar.classList.toggle("show");

            }else{

                sidebar.classList.toggle("collapsed");

                localStorage.setItem(
                    "sidebar-collapsed",
                    sidebar.classList.contains("collapsed")
                );

            }

        });

    document.addEventListener("click",function(e){

        if(window.innerWidth > 991) return;

        if(
            !sidebar.contains(e.target) &&
            !toggleSidebarBtn.contains(e.target)
        ){

            sidebar.classList.remove("show");

        }

    });

    // 3. SIDEBAR ACTIVE MENU HIGHLIGHTER
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
    
    menuItems.forEach(item => {
        const link = item.querySelector('a');
        if (link) {
            const href = link.getAttribute('href');
            if (href === currentPath || (currentPath === 'dashboard.php' && href === 'dashboard.php')) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        }
    });

    // 4. DROPDOWN PROFILE
    const profileTrigger = document.getElementById('profile-trigger');
    const dropdownMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && dropdownMenu) {
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
            }
        });
    }

    // 5. CLIENT-SIDE SEARCH TABLE
    const searchInputs = document.querySelectorAll('.table-actions-bar .search-box input, .topbar-search input');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector('table.table');
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let found = false;
                const cells = row.querySelectorAll('td');
                // Loop through all cells except action column (last one)
                for (let i = 0; i < cells.length - 1; i++) {
                    if (cells[i].textContent.toLowerCase().includes(filter)) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 6. TOAST NOTIFICATION UTILITY
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    window.showToast = function(title, desc, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let icon = 'fa-check-circle';
        if (type === 'danger') icon = 'fa-times-circle';
        if (type === 'warning') icon = 'fa-exclamation-triangle';
        if (type === 'info') icon = 'fa-info-circle';

        toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${icon}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-desc">${desc}</div>
            </div>
            <div class="toast-close"><i class="fas fa-times"></i></div>
        `;

        container.appendChild(toast);

        // Slide in
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Auto Close timer
        const timer = setTimeout(() => {
            closeToast(toast);
        }, 4000);

        // Close on click button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            clearTimeout(timer);
            closeToast(toast);
        });
    };

    function closeToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }

    // 7. DYNAMIC MODAL TOGGLER
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    };

    // Close modal when clicking on background overlay
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });

    // 8. CUSTOM CONFIRMATION BEFORE DELETE
    // Creates a premium confirm dialog overlay on the fly
    window.showConfirmModal = function(title, text, confirmCallback) {
        // Check if there is already a confirm modal
        let confirmModal = document.getElementById('confirm-delete-modal');
        if (!confirmModal) {
            confirmModal = document.createElement('div');
            confirmModal.id = 'confirm-delete-modal';
            confirmModal.className = 'modal-overlay';
            document.body.appendChild(confirmModal);
        }

        confirmModal.innerHTML = `
            <div class="modal-card" style="max-width: 400px;">
                <div class="modal-body" style="padding: 30px;">
                    <div class="confirm-icon-box">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="confirm-title">${title}</div>
                    <div class="confirm-text">${text}</div>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button class="btn btn-secondary" id="btn-confirm-cancel">Batal</button>
                        <button class="btn btn-danger" id="btn-confirm-yes">Hapus</button>
                    </div>
                </div>
            </div>
        `;

        confirmModal.classList.add('show');

        // Handlers
        const cancelBtn = confirmModal.querySelector('#btn-confirm-cancel');
        const yesBtn = confirmModal.querySelector('#btn-confirm-yes');

        cancelBtn.onclick = function() {
            confirmModal.classList.remove('show');
        };

        yesBtn.onclick = function() {
            confirmModal.classList.remove('show');
            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
        };
    };

    // Intercept delete clicks dynamically
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-icon.delete');
        if (deleteBtn) {
            e.preventDefault();
            const deleteUrl = deleteBtn.getAttribute('href');
            if (deleteUrl) {
                showConfirmModal(
                    'Konfirmasi Hapus',
                    'Apakah Anda yakin ingin menghapus data ini? Data yang dihapus tidak dapat dipulihkan.',
                    function() {
                        window.location.href = deleteUrl;
                    }
                );
            }
        }
    });
});