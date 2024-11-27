document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const filterSelect = document.getElementById('filter');
    const tableBody = document.querySelector('#documentsTable tbody');
    const noResults = document.getElementById('noResults');

    function renderTable(docs) {
        tableBody.innerHTML = '';
        if (docs.length === 0) {
            noResults.style.display = 'block';
            tableBody.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            tableBody.style.display = '';
            docs.forEach(doc => {
                const row = `
                    <tr>
                        <td>${doc.name}</td>
                        <td>${doc.type}</td>
                        <td>${doc.date}</td>
                        <td>
                            ${doc.source === 'xray' 
                                ? `<button class="action-btn" onclick="viewXray(${doc.id})">View</button>
                                   <button class="action-btn" onclick="downloadXray(${doc.id})">Download</button>`
                                : `<button class="action-btn" disabled>View</button>
                                   <button class="action-btn" disabled>Download</button>`
                            }
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
    }

    function filterDocuments() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterType = filterSelect.value;

        const filteredDocs = documents.filter(doc => 
            (filterType === 'all' || doc.type === filterType) &&
            (doc.name.toLowerCase().includes(searchTerm) || doc.date.includes(searchTerm))
        );

        renderTable(filteredDocs);
    }

    searchInput.addEventListener('input', filterDocuments);
    filterSelect.addEventListener('change', filterDocuments);

    // Initial render
    renderTable(documents);
});

function viewXray(id) {
    window.open(`../patients/docs/view_xray.php?id=${id}`, '_blank');
}

function downloadXray(id) {
    window.location.href = `../patients/docs/download_xray.php?id=${id}`;
}