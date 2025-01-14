<!-- Search and Filter Section -->
<div class="container mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form id="searchFilterForm" class="row g-3 align-items-end">
                <!-- Search Input -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="searchInput" 
                                   placeholder="Search paperworks..."
                                   aria-label="Search paperworks">
                            <button class="btn btn-outline-primary" type="button" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="submitted">Submitted</option>
                            <option value="hod_review">HOD Review</option>
                            <option value="dean_review">Dean Review</option>
                            <option value="approved">Approved</option>
                            <option value="returned">Returned</option>
                        </select>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="col-md-5">
                    <div class="form-group">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="startDate">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="endDate">
                            <button type="reset" class="btn btn-light" id="resetFilters">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const resetBtn = document.getElementById('resetFilters');
    const tableBody = document.querySelector('.table tbody');
    const originalRows = [...tableBody.querySelectorAll('tr')];

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value.toLowerCase();
        const start = startDate.value ? new Date(startDate.value) : null;
        const end = endDate.value ? new Date(endDate.value) : null;

        const filteredRows = originalRows.filter(row => {
            const cells = [...row.querySelectorAll('td')];
            const rowData = cells.map(cell => cell.textContent.toLowerCase());
            
            // Search filter
            const matchesSearch = searchTerm === '' || rowData.some(text => text.includes(searchTerm));
            
            // Status filter - Fix for status matching
            const statusButton = row.querySelector('.status-badge');
            const statusClass = statusButton ? statusButton.classList : [];
            const currentStatus = Array.from(statusClass).find(cls => cls.startsWith('status-'));
            const matchesStatus = status === '' || (currentStatus && currentStatus.replace('status-', '') === status);
            
            // Date filter
            const dateCell = row.querySelector('td:nth-child(4)').textContent; // Adjust column index if needed
            const rowDate = new Date(dateCell);
            const matchesDate = (!start || rowDate >= start) && (!end || rowDate <= end);
            
            return matchesSearch && matchesStatus && matchesDate;
        });

        showLoading();
        
        setTimeout(() => {
            // Clear table
            tableBody.innerHTML = '';
            
            if (filteredRows.length === 0) {
                // Show no results message
                const noResults = document.createElement('tr');
                noResults.innerHTML = `
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-search fa-2x mb-3"></i>
                            <p class="mb-0">No matching records found</p>
                        </div>
                    </td>
                `;
                tableBody.appendChild(noResults);
            } else {
                // Add filtered rows
                filteredRows.forEach(row => {
                    const newRow = row.cloneNode(true);
                    if (searchTerm) {
                        highlightSearchTerms(newRow, searchTerm);
                    }
                    tableBody.appendChild(newRow);
                });
            }
        }, 200);
    }

    function highlightSearchTerms(row, term) {
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            if (!cell.querySelector('.status-badge')) { // Skip status badges
                const text = cell.textContent;
                cell.innerHTML = highlightSearchTerm(text, term);
            }
        });
    }

    function highlightSearchTerm(text, term) {
        if (!term) return text;
        const regex = new RegExp(`(${term})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    }

    // Event listeners
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    startDate.addEventListener('change', filterTable);
    endDate.addEventListener('change', filterTable);
    resetBtn.addEventListener('click', () => {
        searchInput.value = '';
        statusFilter.value = '';
        startDate.value = '';
        endDate.value = '';
        tableBody.innerHTML = '';
        originalRows.forEach(row => tableBody.appendChild(row.cloneNode(true)));
    });
});

// Add to the existing JavaScript
function showLoading() {
    const loader = document.createElement('div');
    loader.className = 'text-center py-4';
    loader.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    tableBody.innerHTML = '';
    tableBody.appendChild(loader);
}

function highlightSearchTerm(text, term) {
    if (!term) return text;
    const regex = new RegExp(`(${term})`, 'gi');
    return text.replace(regex, '<span class="search-highlight">$1</span>');
}
</script>

<style>
.input-group {
    border-radius: 0.5rem;
    overflow: hidden;
}

.input-group .btn {
    border-top-right-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

.form-group {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .input-group {
        margin-bottom: 1rem;
    }
    
    .col-md-5 .input-group {
        margin-bottom: 0;
    }
}

/* Add to your existing styles */
.search-highlight {
    background-color: rgba(67, 97, 238, 0.1);
    padding: 0.1em 0.2em;
    border-radius: 2px;
}

.status-badge {
    transition: all 0.2s ease;
}

.status-badge:hover {
    transform: translateY(-1px);
}

/* Loading animation */
.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
    border-width: 0.15em;
}
</style>