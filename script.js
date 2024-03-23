document.addEventListener("DOMContentLoaded", () => {
    const toggleTab = (tab) => {
        event.preventDefault();
        const targetId = tab.getAttribute("href").substring(1);
        document.querySelectorAll(".nav-tab-wrapper a").forEach(t => t.classList.toggle("nav-tab-active", t === tab));
        document.querySelectorAll(".tab-content").forEach(content => content.classList.toggle("active", content.id === targetId));
    };

    document.querySelectorAll(".nav-tab-wrapper a").forEach(tab => tab.addEventListener("click", () => toggleTab(tab)));
    document.querySelector(".nav-tab-wrapper a").click();

    const updateRowCountAndFilter = () => {
        document.querySelectorAll('.tab-content').forEach(tabContent => {
            const filterInput = tabContent.querySelector('.dhn-filter');
            const blockDropdown = document.getElementById('block-dropdown');
            const tableRows = tabContent.querySelectorAll('tbody tr');
            const rowCountElement = tabContent.querySelector('.row-count');

            const filterText = filterInput.value.trim().toLowerCase();
            const selectedBlock = blockDropdown.value.toLowerCase();

            let visibleRowCount = 0;

            tableRows.forEach(row => {
                const blockName = row.cells[1].textContent.trim().toLowerCase();
                const shouldDisplay = (selectedBlock === "all" || blockName.includes(selectedBlock)) && row.textContent.toLowerCase().includes(filterText);
                row.style.display = shouldDisplay ? "" : "none";
                if (shouldDisplay) visibleRowCount++;
            });

            rowCountElement.textContent = visibleRowCount;
        });
    };

    document.querySelectorAll('.tab-content .dhn-filter, .tab-content #block-dropdown').forEach(element => {
        element.addEventListener("input", updateRowCountAndFilter);
        element.addEventListener("change", updateRowCountAndFilter);
    });

    updateRowCountAndFilter();

    const initializeTableSorting = () => {
        document.querySelectorAll('table').forEach(grid => {
            grid.onclick = (e) => {
                if (e.target.tagName !== 'TH') return;

                const th = e.target;
                const colIndex = th.cellIndex;
                let currentSortOrder = th.dataset.sortOrder || 'asc';

                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                th.dataset.sortOrder = currentSortOrder;

                sortGrid(colIndex, th.dataset.type, currentSortOrder);
            };

            const sortGrid = (colNum, type, sortOrder) => {
                const tbody = grid.querySelector('tbody');
                const rowsArray = Array.from(tbody.rows);
                let compare;

                switch (type) {
                    case 'number':
                        compare = (rowA, rowB) => Number(rowA.cells[colNum].innerHTML) - Number(rowB.cells[colNum].innerHTML);
                        break;
                    case 'string':
                        compare = (rowA, rowB) => {
                            const valueA = rowA.cells[colNum].textContent.trim().toLowerCase();
                            const valueB = rowB.cells[colNum].textContent.trim().toLowerCase();
                            return valueA.localeCompare(valueB, undefined, {numeric: true});
                        };
                        break;
                    case 'date':
                        compare = (rowA, rowB) => new Date(rowA.cells[colNum].innerHTML) - new Date(rowB.cells[colNum].innerHTML);
                        break;
                }

                if (sortOrder === 'asc') {
                    rowsArray.sort(compare);
                } else {
                    rowsArray.sort((a, b) => compare(b, a)); 
                }

                tbody.innerHTML = ''; 

                rowsArray.forEach(row => tbody.appendChild(row));
            };
        });
    };

    initializeTableSorting();
});
