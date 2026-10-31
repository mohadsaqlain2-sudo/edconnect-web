/**
 * Client-side search and sorting for the data tables (Phase 3B).
 *
 * Server-side pagination is untouched: this only filters and sorts the rows of
 * the current page. Without JavaScript every row is visible.
 */

function cellValue(row, index) {
    const cell = row.children[index];
    if (!cell) {
        return '';
    }

    return cell.dataset.sortValue ?? cell.textContent.trim();
}

function compare(a, b) {
    const numberA = Number(a);
    const numberB = Number(b);

    if (a !== '' && b !== '' && !Number.isNaN(numberA) && !Number.isNaN(numberB)) {
        return numberA - numberB;
    }

    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
}

function bootTable(table) {
    const body = table.querySelector('[data-table-body]');
    const search = table.querySelector('[data-table-search]');
    const counter = table.querySelector('[data-table-count]');
    const empty = table.querySelector('[data-table-empty]');
    const rows = Array.from(table.querySelectorAll('tr[data-table-row]'));

    if (!body || rows.length === 0) {
        return;
    }

    let sortIndex = null;
    let sortDirection = 'ascending';

    function applySearch() {
        const term = (search?.value ?? '').trim().toLowerCase();
        let visible = 0;

        rows.forEach((row) => {
            const haystack = (row.dataset.search ?? row.textContent).toLowerCase();
            const show = term === '' || haystack.includes(term);
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (empty) {
            empty.hidden = visible !== 0;
        }

        if (counter) {
            counter.textContent = table.dataset.countLabel
                ? table.dataset.countLabel.replace(':count', String(visible))
                : `${visible}`;
        }
    }

    function applySort() {
        if (sortIndex === null) {
            return;
        }

        const direction = sortDirection === 'ascending' ? 1 : -1;
        const sorted = [...rows].sort((a, b) => direction * compare(cellValue(a, sortIndex), cellValue(b, sortIndex)));

        sorted.forEach((row) => body.appendChild(row));
    }

    if (search) {
        search.addEventListener('input', applySearch);
    }

    table.querySelectorAll('th[data-sort]').forEach((header) => {
        header.classList.add('edu-sortable');
        header.setAttribute('tabindex', '0');
        header.setAttribute('role', 'button');
        header.setAttribute('aria-sort', 'none');

        const activate = () => {
            const index = Array.from(header.parentElement.children).indexOf(header);

            if (sortIndex === index) {
                sortDirection = sortDirection === 'ascending' ? 'descending' : 'ascending';
            } else {
                sortIndex = index;
                sortDirection = 'ascending';
            }

            table.querySelectorAll('th[data-sort]').forEach((other) => {
                other.setAttribute('aria-sort', 'none');
            });
            header.setAttribute('aria-sort', sortDirection);

            applySort();
        };

        header.addEventListener('click', activate);
        header.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                activate();
            }
        });
    });

    applySearch();
}

function bootTables() {
    document.querySelectorAll('[data-data-table]').forEach(bootTable);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTables);
} else {
    bootTables();
}
