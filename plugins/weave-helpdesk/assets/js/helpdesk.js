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

    // 2. Live-Suche in der Aufgabenliste (inkl. Gruppen-Überschriften ausblenden, falls Gruppe leer)
    const searchInput = document.getElementById('hdSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            const ticketRows = document.querySelectorAll('.hd-table tbody tr[data-href]');
            const visibleByGroup = {};

            ticketRows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                const matches = text.includes(term);
                row.style.display = matches ? '' : 'none';
                const gid = row.getAttribute('data-group-id');
                if (gid && matches) {
                    visibleByGroup[gid] = (visibleByGroup[gid] || 0) + 1;
                }
            });

            const groupRows = document.querySelectorAll('.hd-table tbody tr.hd-group-row');
            groupRows.forEach(function (gRow) {
                const gid = gRow.getAttribute('data-group-id');
                gRow.style.display = (gid && visibleByGroup[gid] > 0) ? '' : 'none';
            });
        });
    }

    // 3. Interne Notiz deaktiviert automatisch die E-Mail-Benachrichtigung an den Einsender
    const internalCheckbox = document.getElementById('hdIsInternalCheckbox');
    const notifyCheckbox = document.getElementById('hdNotifyCustomerCheckbox');
    if (internalCheckbox && notifyCheckbox) {
        const syncInternalNotify = function () {
            if (internalCheckbox.checked) {
                notifyCheckbox.checked = false;
                notifyCheckbox.disabled = true;
            } else {
                notifyCheckbox.disabled = false;
            }
        };
        internalCheckbox.addEventListener('change', syncInternalNotify);
        syncInternalNotify();
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

