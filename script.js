document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".nav-tab-wrapper a").forEach(tab => tab.addEventListener("click", (event) => {
        event.preventDefault();
        const targetId = tab.getAttribute("href").substring(1);
        document.querySelectorAll(".nav-tab-wrapper a").forEach(t => t.classList.toggle("nav-tab-active", t === tab));
        document.querySelectorAll(".tab-content").forEach(content => content.classList.toggle("active", content.id === targetId));
    }));

    document.querySelector(".nav-tab-wrapper a").click();

    const updateRowCountAndFilter = () => {
        document.querySelectorAll('.tab-content').forEach(tabContent => {
            const filterInput = tabContent.querySelector('.dhn-filter');
            const blockDropdown = tabContent.querySelector('#block-dropdown');
            const tableRows = tabContent.querySelectorAll('tbody tr');
            const rowCountElement = tabContent.querySelector('.row-count');

            const filterText = filterInput.value.trim().toLowerCase();
            const selectedBlock = blockDropdown.value;
            let visibleRowCount = 0;

            tableRows.forEach(row => {
                const blockName = row.cells[1].textContent.trim().toLowerCase();
                const shouldDisplay = (selectedBlock === "all" || row.textContent.toLowerCase().includes(selectedBlock)) && row.textContent.toLowerCase().includes(filterText);
                row.style.display = shouldDisplay ? "" : "none";
                if (shouldDisplay) {
                    visibleRowCount++;
                }
            });

            rowCountElement.textContent = visibleRowCount;
        });
    };

    document.querySelectorAll('.tab-content .dhn-filter').forEach(filterInput => {
        filterInput.addEventListener("input", updateRowCountAndFilter);
    });

    document.querySelectorAll('.tab-content #block-dropdown').forEach(blockDropdown => {
        blockDropdown.addEventListener("change", updateRowCountAndFilter);
    });

    updateRowCountAndFilter();
});