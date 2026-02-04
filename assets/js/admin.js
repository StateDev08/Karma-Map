// Admin Panel JavaScript

// Toast-Benachrichtigungen
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Formular-Validierung
function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Confirm Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-save Indikator
let autoSaveTimeout;
function indicateAutoSave() {
    clearTimeout(autoSaveTimeout);
    const indicator = document.createElement('div');
    indicator.className = 'autosave-indicator';
    indicator.textContent = 'Wird gespeichert...';
    document.body.appendChild(indicator);
    
    autoSaveTimeout = setTimeout(() => {
        indicator.remove();
    }, 2000);
}

// Datatable Sortierung
function initDataTableSort() {
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(table, index);
            });
        });
    });
}

function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[column].textContent.trim();
        const bValue = b.cells[column].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return parseFloat(aValue) - parseFloat(bValue);
        }
        
        return aValue.localeCompare(bValue);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Datei-Upload Preview
function setupFilePreview() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    showImagePreview(e.target.result, input);
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

function showImagePreview(src, inputElement) {
    let preview = inputElement.nextElementSibling;
    if (!preview || !preview.classList.contains('image-preview')) {
        preview = document.createElement('div');
        preview.className = 'image-preview';
        inputElement.parentNode.insertBefore(preview, inputElement.nextSibling);
    }
    
    preview.innerHTML = `<img src="${src}" alt="Preview">`;
}

// Init
document.addEventListener('DOMContentLoaded', function() {
    initDataTableSort();
    setupFilePreview();
});
