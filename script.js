document.addEventListener("DOMContentLoaded", () => {
	class BlocksScanner {
		constructor() {
			this.tabWrapper = document.querySelectorAll(".nav-tab-wrapper a");
			this.tabContent = document.querySelectorAll(".tab-content");
			this.filterInputs = document.querySelectorAll('.tab-content .dhn-filter, .tab-content .block-checkbox');
			this.initialize();
		}

		toggleTab(tab) {
			event.preventDefault();
			const targetId = tab.getAttribute("href").substring(1);
			this.tabWrapper.forEach(t => t.classList.toggle("nav-tab-active", t === tab));
			this.tabContent.forEach(content => content.classList.toggle("active", content.id === targetId));
		}

		updateRowCountAndFilter() {
			this.tabContent.forEach(tabContent => {
				const filterInput = tabContent.querySelector('.dhn-filter');
				const blockCheckboxes = tabContent.querySelectorAll('.block-checkbox');
				const tableRows = tabContent.querySelectorAll('tbody tr');
				const rowCountElement = tabContent.querySelector('.row-count');

				const filterText = filterInput.value.trim().toLowerCase();
				const selectedBlocks = Array.from(blockCheckboxes)
					.filter(checkbox => checkbox.checked)
					.map(checkbox => checkbox.value.toLowerCase());

				let visibleRowCount = 0;

				tableRows.forEach(row => {
					const blockName = row.cells[1].textContent.trim().toLowerCase();
					const shouldDisplay = (selectedBlocks.length === 0 || selectedBlocks.includes(blockName)) && row.textContent.toLowerCase().includes(filterText);
					row.style.display = shouldDisplay ? "" : "none";
					if (shouldDisplay) visibleRowCount++;
				});

				rowCountElement.textContent = visibleRowCount;
			});
		}


		sortGrid(grid, colNum, type, sortOrder) {
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
						return valueA.localeCompare(valueB, undefined, {
							numeric: true
						});
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
		}

		initializeTableSorting() {
			this.tabContent.forEach(tabContent => {
				const tables = tabContent.querySelectorAll('table');
				tables.forEach(grid => {
					grid.onclick = (e) => {
						if (e.target.tagName !== 'TH') return;

						const th = e.target;
						const colIndex = th.cellIndex;
						let currentSortOrder = th.dataset.sortOrder || 'asc';

						currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
						th.dataset.sortOrder = currentSortOrder;

						this.sortGrid(grid, colIndex, th.dataset.type, currentSortOrder);
					};
				});
			});
		}

		initialize() {
			this.tabWrapper.forEach(tab => tab.addEventListener("click", () => this.toggleTab(tab)));
			this.tabWrapper[0].click();

			this.filterInputs.forEach(element => {
				element.addEventListener("input", () => this.updateRowCountAndFilter());
				element.addEventListener("change", () => this.updateRowCountAndFilter());
			});

			this.updateRowCountAndFilter();
			this.initializeTableSorting();
		}
	}

	const blocksScanner = new BlocksScanner();
});
