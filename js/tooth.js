document.addEventListener('DOMContentLoaded', function () {
    // Cache DOM Elements
    const elements = {
        chartContainer: document.querySelector('.chart-container'),
        teeth: document.querySelectorAll('#Spots polygon, #Spots path'),
        toothInfo: document.getElementById('toothInfo'),
        historyList: document.getElementById('historyList'),
        tooltip: document.getElementById('tooltip'),
        form: document.getElementById('treatmentForm'),
        table: document.getElementById('treatmentTable')?.getElementsByTagName('tbody')[0],
        treatmentSelect: document.getElementById('treatment'),
        otherTreatmentGroup: document.getElementById('other-treatment-group'),
        otherTreatmentInput: document.getElementById('other-treatment'),
        toothNumberInput: document.getElementById('toothNumber')
    };

    // Get patient_id from URL
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');

    // Use Map for tooth data storage
    const toothDataMap = new Map();

    // Event Delegation for tooth interactions
    document.getElementById('Spots')?.addEventListener('click', handleToothClick);
    document.getElementById('Spots')?.addEventListener('mouseover', handleToothHover);
    document.getElementById('Spots')?.addEventListener('mousemove', handleToothMove);
    document.getElementById('Spots')?.addEventListener('mouseout', handleToothOut);

    function handleToothClick(e) {
        const tooth = e.target.closest('polygon, path');
        if (!tooth) return;

        // Remove 'clicked' class from all teeth
        elements.teeth.forEach(t => t.classList.remove('clicked'));
        tooth.classList.add('clicked');

        const toothNumber = tooth.getAttribute('data-key');
        if (elements.toothNumberInput) {
            elements.toothNumberInput.value = toothNumber;
        }
        loadToothHistory(toothNumber);
    }

    function handleToothHover(e) {
        const tooth = e.target.closest('polygon, path');
        if (!tooth) return;

        const toothNumber = tooth.getAttribute('data-key');
        const data = toothDataMap.get(toothNumber) || { status: 'No Data', treatment: [] };

        const tooltipContent = document.createElement('div');
        tooltipContent.innerHTML = `
            <div class="tooltip-title">Tooth ${toothNumber}</div>
            <div class="tooltip-content">Status: ${data.status}</div>
            <div class="tooltip-treatment">
                Recent: ${data.treatment.length > 0 ? data.treatment.join(', ') : 'None'}
            </div>
        `;

        elements.tooltip.replaceChildren(tooltipContent);
        elements.tooltip.classList.add('show');
        positionTooltip(e);
    }

    function handleToothMove(e) {
        if (elements.tooltip.classList.contains('show')) {
            positionTooltip(e);
        }
    }

    function handleToothOut() {
        elements.tooltip.classList.remove('show');
    }

    function positionTooltip(e) {
        if (!elements.tooltip) return;

        const tooltipRect = elements.tooltip.getBoundingClientRect();
        const chartRect = elements.chartContainer.getBoundingClientRect();

        const left = Math.min(
            e.clientX - chartRect.left + 15,
            chartRect.width - tooltipRect.width - 15
        );
        const top = Math.min(
            e.clientY - chartRect.top + 15,
            chartRect.height - tooltipRect.height - 15
        );

        elements.tooltip.style.left = `${left}px`;
        elements.tooltip.style.top = `${top}px`;
    }

    function showOtherInput() {
        const isOther = elements.treatmentSelect.value === "Other";
        elements.otherTreatmentGroup.style.display = isOther ? "block" : "none";
        if (!isOther) {
            elements.otherTreatmentInput.value = "";
        }
    }

    async function loadToothHistory(toothNumber) {
        try {
            if (!elements.toothInfo || !elements.historyList) {
                throw new Error('Required elements not found');
            }

            const data = toothDataMap.get(toothNumber) || { 
                status: 'No Data', 
                treatment: [], 
                last_checked: 'Not available' 
            };

            // Create and append tooth info elements
            const infoContainer = document.createElement('div');
            infoContainer.innerHTML = `
                <h3>Tooth ${toothNumber}</h3>
                <p>Status: <span class="tooth-status ${data.status.toLowerCase().replace(/\s+/g, '-')}">${data.status}</span></p>
                <p>Last Checked: <span class="last_checked">${data.last_checked || 'Not available'}</span></p>
            `;
            elements.toothInfo.replaceChildren(infoContainer);

            // Update history list
            const uniqueTreatments = [...new Set(data.treatment.map(t => t.trim().toLowerCase()))];
            const fragment = document.createDocumentFragment();

            if (uniqueTreatments.length) {
                uniqueTreatments.forEach(treatment => {
                    const li = document.createElement('li');
                    li.textContent = treatment;
                    fragment.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = 'No treatment history';
                fragment.appendChild(li);
            }

            elements.historyList.replaceChildren(fragment);
        } catch (error) {
            console.error('Error in loadToothHistory:', error);
        }
    }

    async function initializeToothData() {
        try {
            const response = await fetch(`/php/get_tooth_data.php?patient_id=${encodeURIComponent(patientId)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const rawResponse = await response.text();
            const data = parseServerResponse(rawResponse);

            if (data?.tooth_data) {
                Object.entries(data.tooth_data).forEach(([key, value]) => {
                    toothDataMap.set(key, value);
                });
                updateToothColors();
            }
        } catch (error) {
            console.error('Error loading tooth data:', error);
        }
    }

    function parseServerResponse(rawResponse) {
        try {
            return JSON.parse(rawResponse);
        } catch (error) {
            const jsonMatch = rawResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return JSON.parse(jsonMatch[0]);
            }
            throw new Error('Invalid server response format');
        }
    }

    function updateToothColors() {
        elements.teeth.forEach(tooth => {
            const toothNumber = tooth.getAttribute('data-key');
            const toothData = toothDataMap.get(toothNumber) || { status: 'Healthy', treatment: [] };

            // Remove all condition classes
            tooth.classList.remove('healthy', 'cavity', 'filled', 'crown', 'missing');

            // Add new status class
            const statusClass = toothData.status.toLowerCase().replace(/\s+/g, '-');
            tooth.classList.add(statusClass);

            // Toggle treatment class
            tooth.classList.toggle('has-treatment', toothData.treatment?.length > 0);
        });
    }

    async function loadTreatments() {
        if (!elements.table) return;

        try {
            const response = await fetch(`/php/get_treatment.php?patient_id=${encodeURIComponent(patientId)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();
            const fragment = document.createDocumentFragment();

            // Update toothDataMap and create table rows
            data.forEach(treatment => {
                updateToothDataFromTreatment(treatment);
                const row = createTreatmentRow(treatment);
                fragment.appendChild(row);
            });

            elements.table.replaceChildren(fragment);
            updateToothColors();
        } catch (error) {
            console.error('Error loading treatments:', error);
        }
    }

    function updateToothDataFromTreatment(treatment) {
        if (!toothDataMap.has(treatment.tooth_number)) {
            toothDataMap.set(treatment.tooth_number, {
                status: treatment.status.toLowerCase().replace(/\s+/g, '-'),
                treatment: []
            });
        }

        const toothData = toothDataMap.get(treatment.tooth_number);
        if (!toothData.treatment.includes(treatment.treatment)) {
            toothData.treatment.push(treatment.treatment);
        }
    }

    function createTreatmentRow(treatment) {
        const row = document.createElement('tr');
        const safeNotes = treatment.notes?.replace(/[<>]/g, '') || '';

        row.innerHTML = `
            <td>${treatment.date}</td>
            <td>${treatment.treatment}</td>
            <td>${treatment.tooth_number}</td>
            <td>${treatment.status}</td>
            <td>${safeNotes}</td>
            <td>${treatment.progress}</td>
            <td>
                <button class="delete-btn" data-treatment-id="${treatment.treatment_id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
            <td>
                <button class="edit-btn" 
                    data-treatment-id="${treatment.treatment_id}"
                    data-tooth="${treatment.tooth_number}"
                    data-treatment="${treatment.treatment}"
                    data-date="${treatment.date}"
                    data-status="${treatment.status}"
                    data-progress="${treatment.progress}"
                    data-notes="${safeNotes}">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;

        // Add event listeners
        row.querySelector('.delete-btn').addEventListener('click', () => deleteTreatment(treatment.treatment_id));
        row.querySelector('.edit-btn').addEventListener('click', (e) => {
            const btn = e.currentTarget;
            editTreatment(
                btn.dataset.treatmentId,
                btn.dataset.tooth,
                btn.dataset.treatment,
                btn.dataset.date,
                btn.dataset.status,
                btn.dataset.progress,
                btn.dataset.notes
            );
        });

        return row;
    }

    async function handleFormSubmission(e) {
        e.preventDefault();

        try {
            const formData = new FormData(elements.form);
            formData.set('patient_id', patientId);

            // Handle 'Other' treatment
            if (formData.get('treatment') === 'Other') {
                const otherTreatment = formData.get('other_treatment');
                if (!otherTreatment) {
                    throw new Error('Please specify the other treatment');
                }
                formData.set('treatment', otherTreatment);
            }
            formData.delete('other_treatment');

            const isEditing = elements.form.hasAttribute('data-treatment-id');
            const endpoint = isEditing ? '/php/update_treatment.php' : '/php/add_treatment.php';

            if (isEditing) {
                formData.set('treatment_id', elements.form.getAttribute('data-treatment-id'));
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData).toString()
            });

            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Operation failed');

            showSuccessToast(data.message);
            await Promise.all([loadTreatments(), initializeToothData()]);
            resetForm();

        } catch (error) {
            console.error('Form submission error:', error);
        }
    }

    function resetForm() {
        elements.form.reset();
        elements.form.removeAttribute('data-treatment-id');
        elements.form.querySelector('button[type="submit"]').textContent = 'Add Treatment';
        elements.otherTreatmentGroup.style.display = 'none';
    }

    async function deleteTreatment(treatmentId) {
        try {
          const confirmDeletion = await confirmDeletionDialog();
          if (!confirmDeletion) return;
      
          const response = await sendDeleteRequest(treatmentId);
          const data = await response.json();
          if (!data.success) throw new Error(data.message);
      
          await loadTreatments();
        } catch (error) {
          console.error('Error deleting treatment:', error);
        }
      }
      
      async function confirmDeletionDialog() {
        const result = await Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'No, cancel!'
        });
        return result.isConfirmed;
      }
      
      async function sendDeleteRequest(treatmentId) {
        const url = '/php/delete_treatment.php';
        const params = new URLSearchParams({ treatment_id: treatmentId });
        const response = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params.toString()
        });
        return response;
      }

    function editTreatment(treatmentId, toothNumber, treatment, date, status, progress, notes) {
        elements.toothNumberInput.value = toothNumber;

        // Handle treatment selection
        const treatmentExists = Array.from(elements.treatmentSelect.options)
            .some(option => option.value === treatment && option.value !== 'Other');

        if (treatmentExists) {
            elements.treatmentSelect.value = treatment;
            elements.otherTreatmentGroup.style.display = 'none';
        } else {
            elements.treatmentSelect.value = 'Other';
            elements.otherTreatmentGroup.style.display = 'block';
            elements.otherTreatmentInput.value = treatment;
        }

        // Set other form values
        document.getElementById('date').value = date;
        document.getElementById('status').value = status;
        document.getElementById('progress').value = progress;
        document.getElementById('notes').value = notes || '';

        // Update form state
        elements.form.setAttribute('data-treatment-id', treatmentId);
        elements.form.querySelector('button[type="submit"]').textContent = 'Update Treatment';

        // Smooth scroll to form
        elements.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showSuccessToast(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 3000
        });
    }

    function showErrorToast(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: `Error: ${message}`,
            showConfirmButton: false,
            timer: 3000
        });
    }

    // Event Listeners
    elements.treatmentSelect.addEventListener('change', showOtherInput);
    elements.form.addEventListener('submit', handleFormSubmission);

    // Initialize
    if (patientId) {
        Promise.all([
            initializeToothData(),
            loadTreatments()
        ]).catch(error => {
            console.error('Initialization error:', error);
        });
    } else {
        console.error('No patient ID found in URL');
    }
});