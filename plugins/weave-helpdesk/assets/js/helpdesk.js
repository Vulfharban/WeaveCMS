/**
 * Weave Helpdesk – Client Interaction Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. User Dropdown Toggle
    const userToggle = document.getElementById('hdUserToggle');
    const userDropdown = document.getElementById('hdUserDropdown');
    if (userToggle && userDropdown) {
        userToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        document.addEventListener('click', function () {
            userDropdown.classList.remove('show');
        });
    }

    // 2. Filter: Geschlossene Aufgaben verbergen
    const hideClosedCheck = document.getElementById('hdHideClosedCheckbox');
    if (hideClosedCheck) {
        hideClosedCheck.addEventListener('change', function () {
            const hide = this.checked;
            const rows = document.querySelectorAll('.hd-table tbody tr');
            rows.forEach(function (row) {
                if (row.classList.contains('row-closed') || row.classList.contains('row-billed')) {
                    row.style.display = hide ? 'none' : '';
                }
            });
            // Status in LocalStorage merken
            localStorage.setItem('hd_hide_closed', hide ? '1' : '0');
        });

        // Gespeicherten Zustand wiederherstellen
        if (localStorage.getItem('hd_hide_closed') === '1') {
            hideClosedCheck.checked = true;
            hideClosedCheck.dispatchEvent(new Event('change'));
        }
    }

    // 3. Live-Suche in der Aufgabenliste
    const searchInput = document.getElementById('hdSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.hd-table tbody tr');
            rows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                const matches = text.includes(term);
                // Wenn geschlossen ausgeblendet sein soll, Zustand berücksichtigen
                const isClosed = row.classList.contains('row-closed') || row.classList.contains('row-billed');
                const hideClosed = hideClosedCheck ? hideClosedCheck.checked : false;

                if (!matches || (isClosed && hideClosed)) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
    }

    // 4. Modal: Vertragsauswertung
    const openContractModalBtn = document.getElementById('hdOpenContractModal');
    const contractModal = document.getElementById('hdContractModal');
    const closeContractModalBtn = document.getElementById('hdCloseContractModal');
    const closeContractModalX = document.getElementById('hdCloseContractModalX');

    if (openContractModalBtn && contractModal) {
        openContractModalBtn.addEventListener('click', function (e) {
            e.preventDefault();
            contractModal.classList.add('show');
        });
    }
    const closeModal = function () {
        if (contractModal) {
            contractModal.classList.remove('show');
        }
    };
    if (closeContractModalBtn) closeContractModalBtn.addEventListener('click', closeModal);
    if (closeContractModalX) closeContractModalX.addEventListener('click', closeModal);

    // 5. Modal: Tätigkeit hinzufügen
    const openActivityModalBtn = document.getElementById('hdOpenActivityModal');
    const activityModal = document.getElementById('hdActivityModal');
    const closeActivityModalBtn = document.getElementById('hdCloseActivityModal');
    const closeActivityModalX = document.getElementById('hdCloseActivityModalX');

    if (openActivityModalBtn && activityModal) {
        openActivityModalBtn.addEventListener('click', function (e) {
            e.preventDefault();
            activityModal.classList.add('show');
        });
    }
    const closeActModal = function () {
        if (activityModal) {
            activityModal.classList.remove('show');
        }
    };
    if (closeActivityModalBtn) closeActivityModalBtn.addEventListener('click', closeActModal);
    if (closeActivityModalX) closeActivityModalX.addEventListener('click', closeActModal);

    // 6. Klickbare Zeilen in der Ticket-Tabelle
    const ticketRows = document.querySelectorAll('.hd-table tbody tr[data-href]');
    ticketRows.forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            // Falls nicht auf einen Button/Link geklickt wurde
            if (!e.target.closest('a') && !e.target.closest('button')) {
                const href = this.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            }
        });
    });

    // 7. Druckfunktion im Modal
    const printBtn = document.getElementById('hdPrintContractBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    }
});

