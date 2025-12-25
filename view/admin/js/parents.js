/**
 * PARENTS MANAGEMENT - JavaScript
 * Handles CRUD operations for parent information
 */

// ========================================
// GLOBAL VARIABLES & STATE
// ========================================
let currentPage = 1;
let recordsPerPage = 20;
let totalRecords = 0;
let selectedParents = [];
let allParents = [];
let currentFilters = {};
let currentMode = 'view'; // 'view', 'edit', 'add'
let currentParentId = null;
let allStudents = []; // For student selector

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    loadParents();
    loadStudentsForSelector();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Enter key on filter inputs
    const filterInputs = document.querySelectorAll('.filter-section input');
    filterInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchParents();
            }
        });
    });
}

// ========================================
// FILTER FUNCTIONS
// ========================================
function searchParents() {
    currentFilters = {
        maph: document.getElementById('filter-maph').value,
        hoten: document.getElementById('filter-hoten').value,
        sdt: document.getElementById('filter-sdt').value,
        hotenhs: document.getElementById('filter-hoten-hs').value
    };
    
    currentPage = 1;
    loadParents();
}

function clearFilters() {
    document.getElementById('filter-maph').value = '';
    document.getElementById('filter-hoten').value = '';
    document.getElementById('filter-sdt').value = '';
    document.getElementById('filter-hoten-hs').value = '';
    
    currentFilters = {};
    currentPage = 1;
    loadParents();
}

// ========================================
// DATA LOADING
// ========================================
function loadParents() {
    const tbody = document.getElementById('parents-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>';
    
    // Build query string
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: recordsPerPage,
        ...currentFilters
    });
    
    fetch(`../../controller/cParentManagement.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allParents = data.parents || [];
                totalRecords = data.total || 0;
                renderParentsTable();
                updatePagination();
            } else {
                showToast(data.message || 'Lỗi khi tải dữ liệu', 'error');
                tbody.innerHTML = '<tr><td colspan="7" class="empty-row">Không thể tải dữ liệu</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading parents:', error);
            showToast('Lỗi kết nối: ' + error.message, 'error');
            tbody.innerHTML = '<tr><td colspan="7" class="empty-row">Lỗi kết nối máy chủ</td></tr>';
        });
}

function loadStudentsForSelector() {
    fetch('../../controller/cParentManagement.php?action=getAllStudents')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allStudents = data.students || [];
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
        });
}

function renderParentsTable() {
    const tbody = document.getElementById('parents-tbody');
    
    if (allParents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-row">Không tìm thấy phụ huynh nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = allParents.map(parent => `
        <tr>
            <td>
                <input type="checkbox" 
                       class="parent-checkbox" 
                       value="${parent.maPhuHuynh}"
                       onchange="updateSelectedCount()">
            </td>
            <td>${escapeHtml(parent.maPhuHuynh)}</td>
            <td>${escapeHtml(parent.hoTen)}</td>
            <td>${escapeHtml(parent.soDienThoai || 'N/A')}</td>
            <td>${escapeHtml(parent.email || 'N/A')}</td>
            <td>${parent.soHocSinh || 0}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-view" onclick="viewParent('${parent.maPhuHuynh}')">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                    <button class="btn-icon btn-edit" onclick="editParent('${parent.maPhuHuynh}')">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteParent('${parent.maPhuHuynh}')">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// ========================================
// PAGINATION
// ========================================
function updatePagination() {
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    
    // Update info
    const showingFrom = totalRecords === 0 ? 0 : ((currentPage - 1) * recordsPerPage + 1);
    const showingTo = Math.min(currentPage * recordsPerPage, totalRecords);
    
    document.getElementById('showing-from').textContent = showingFrom;
    document.getElementById('showing-to').textContent = showingTo;
    document.getElementById('total-records').textContent = totalRecords;
    
    // Build pagination buttons
    const paginationDiv = document.getElementById('pagination');
    let paginationHTML = '';
    
    // Previous button
    paginationHTML += `
        <button onclick="goToPage(${currentPage - 1})" 
                ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Page numbers
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<button disabled>...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="goToPage(${i})" 
                    class="${i === currentPage ? 'active' : ''}">
                ${i}
            </button>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<button disabled>...</button>`;
        }
        paginationHTML += `<button onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="goToPage(${currentPage + 1})" 
                ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    paginationDiv.innerHTML = paginationHTML;
}

function goToPage(page) {
    if (page < 1 || page > Math.ceil(totalRecords / recordsPerPage)) return;
    currentPage = page;
    loadParents();
}

function changeRecordsPerPage() {
    recordsPerPage = parseInt(document.getElementById('records-per-page').value);
    currentPage = 1;
    loadParents();
}

// ========================================
// SELECTION MANAGEMENT
// ========================================
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.parent-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.parent-checkbox:checked');
    selectedParents = Array.from(checkboxes).map(cb => cb.value);
    
    document.getElementById('selected-count').textContent = selectedParents.length;
    document.getElementById('delete-selected-btn').disabled = selectedParents.length === 0;
    
    // Update select-all checkbox state
    const selectAllCheckbox = document.getElementById('select-all');
    const allCheckboxes = document.querySelectorAll('.parent-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && selectedParents.length === allCheckboxes.length;
}

// ========================================
// MODAL FUNCTIONS
// ========================================
function openAddModal() {
    currentMode = 'add';
    currentParentId = null;
    
    document.getElementById('modal-title').textContent = 'Thêm phụ huynh mới';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-plus"></i> Thêm phụ huynh';
    
    // Reset form
    document.getElementById('parent-form').reset();
    document.getElementById('parent-id').value = '';
    
    // Reset student selector
    const container = document.getElementById('students-selector');
    container.innerHTML = `
        <div class="student-link-item">
            <select class="form-control student-select" name="linkedStudents[]">
                <option value="">-- Chọn học sinh --</option>
                ${allStudents.map(s => `<option value="${s.maHocSinh}">${s.hoTen} (${s.maHocSinh})</option>`).join('')}
            </select>
            <button type="button" class="btn btn-success btn-sm" onclick="addStudentLink()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;
    
    // Switch to info tab
    switchTab('info');
    
    // Enable all inputs
    const inputs = document.querySelectorAll('#parent-form input, #parent-form select');
    inputs.forEach(input => input.disabled = false);
    
    document.getElementById('parent-modal').classList.add('show');
}

function viewParent(maPhuHuynh) {
    currentMode = 'view';
    currentParentId = maPhuHuynh;
    
    document.getElementById('modal-title').textContent = 'Xem thông tin phụ huynh';
    document.getElementById('save-btn').style.display = 'none';
    
    loadParentDetail(maPhuHuynh, true);
    document.getElementById('parent-modal').classList.add('show');
}

function editParent(maPhuHuynh) {
    currentMode = 'edit';
    currentParentId = maPhuHuynh;
    
    document.getElementById('modal-title').textContent = 'Chỉnh sửa thông tin phụ huynh';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
    document.getElementById('save-btn').style.display = 'block';
    
    loadParentDetail(maPhuHuynh, false);
    document.getElementById('parent-modal').classList.add('show');
}

function loadParentDetail(maPhuHuynh, readonly) {
    fetch(`../../controller/cParentManagement.php?action=getDetail&maPhuHuynh=${maPhuHuynh}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.parent) {
                populateForm(data.parent, data.linkedStudents || [], readonly);
            } else {
                showToast(data.message || 'Không tìm thấy thông tin phụ huynh', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading parent detail:', error);
            showToast('Lỗi khi tải thông tin: ' + error.message, 'error');
        });
}

function populateForm(parent, linkedStudents, readonly) {
    document.getElementById('parent-id').value = parent.maPhuHuynh || parent.maPH || '';
    document.getElementById('hoten').value = parent.hoTen || '';
    document.getElementById('sdt').value = parent.soDienThoai || '';
    document.getElementById('email').value = parent.email || '';
    document.getElementById('diachi').value = parent.diaChi || '';
    
    // Populate linked students in form selector
    const container = document.getElementById('students-selector');
    if (linkedStudents.length > 0) {
        container.innerHTML = linkedStudents.map((ls, index) => `
            <div class="student-link-item">
                <select class="form-control student-select" name="linkedStudents[]">
                    <option value="">-- Chọn học sinh --</option>
                    ${allStudents.map(s => 
                        `<option value="${s.maHocSinh}" ${String(s.maHocSinh) === String(ls.maHocSinh) ? 'selected' : ''}>
                            ${s.hoTen} (${s.maHocSinh})
                        </option>`
                    ).join('')}
                </select>
                ${index === 0 ? 
                    '<button type="button" class="btn btn-success btn-sm" onclick="addStudentLink()"><i class="fas fa-plus"></i></button>' :
                    '<button type="button" class="btn btn-remove btn-sm" onclick="removeStudentLink(this)"><i class="fas fa-minus"></i></button>'
                }
            </div>
        `).join('');
    } else {
        // Reset to default empty selector
        container.innerHTML = `
            <div class="student-link-item">
                <select class="form-control student-select" name="linkedStudents[]">
                    <option value="">-- Chọn học sinh --</option>
                    ${allStudents.map(s => 
                        `<option value="${s.maHocSinh}">${s.hoTen} (${s.maHocSinh})</option>`
                    ).join('')}
                </select>
                <button type="button" class="btn btn-success btn-sm" onclick="addStudentLink()"><i class="fas fa-plus"></i></button>
            </div>
        `;
    }
    
    // Populate linked students table in view tab
    const tbody = document.getElementById('students-tbody');
    if (linkedStudents.length > 0) {
        tbody.innerHTML = linkedStudents.map(ls => `
            <tr>
                <td>${escapeHtml(ls.maHocSinh)}</td>
                <td>${escapeHtml(ls.hoTen)}</td>
                <td>${escapeHtml(ls.tenLop || 'N/A')}</td>
                <td>${escapeHtml(ls.khoi || 'N/A')}</td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Chưa có học sinh liên kết</td></tr>';
    }
    
    // Disable all inputs if readonly
    if (readonly) {
        const inputs = document.querySelectorAll('#parent-form input, #parent-form select, #parent-form button');
        inputs.forEach(input => input.disabled = true);
    } else {
        const inputs = document.querySelectorAll('#parent-form input, #parent-form select');
        inputs.forEach(input => input.disabled = false);
    }
}

function addStudentLink() {
    const container = document.getElementById('students-selector');
    const newItem = document.createElement('div');
    newItem.className = 'student-link-item';
    newItem.innerHTML = `
        <select class="form-control student-select" name="linkedStudents[]">
            <option value="">-- Chọn học sinh --</option>
            ${allStudents.map(s => `<option value="${s.maHocSinh}">${s.hoTen} (${s.maHocSinh})</option>`).join('')}
        </select>
        <button type="button" class="btn btn-remove btn-sm" onclick="removeStudentLink(this)">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(newItem);
}

function removeStudentLink(button) {
    button.parentElement.remove();
}

function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

function closeModal() {
    document.getElementById('parent-modal').classList.remove('show');
    
    // Re-enable all inputs
    const inputs = document.querySelectorAll('#parent-form input, #parent-form select');
    inputs.forEach(input => input.disabled = false);
}

// ========================================
// CRUD OPERATIONS
// ========================================
function saveParent() {
    const form = document.getElementById('parent-form');
    
    // Validation
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Check if at least one student is selected
    const studentSelects = document.querySelectorAll('.student-select');
    const hasStudent = Array.from(studentSelects).some(select => select.value !== '');
    
    if (!hasStudent) {
        showToast('Vui lòng chọn ít nhất một học sinh liên kết', 'error');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', currentMode === 'add' ? 'create' : 'update');
    
    fetch('../../controller/cParentManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Lưu thành công!', 'success');
            closeModal();
            loadParents();
        } else {
            showToast(data.message || 'Lỗi khi lưu dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving parent:', error);
        showToast('Lỗi kết nối: ' + error.message, 'error');
    });
}

function deleteParent(maPhuHuynh) {
    currentParentId = maPhuHuynh;
    document.getElementById('delete-message').textContent = 
        `Bạn có chắc chắn muốn xóa phụ huynh "${maPhuHuynh}" không?`;
    
    document.getElementById('confirm-delete-btn').onclick = function() {
        confirmDelete([maPhuHuynh]);
    };
    
    document.getElementById('delete-modal').classList.add('show');
}

function deleteSelected() {
    if (selectedParents.length === 0) return;
    
    document.getElementById('delete-message').textContent = 
        `Bạn có chắc chắn muốn xóa ${selectedParents.length} phụ huynh đã chọn không?`;
    
    document.getElementById('confirm-delete-btn').onclick = function() {
        confirmDelete(selectedParents);
    };
    
    document.getElementById('delete-modal').classList.add('show');
}

function confirmDelete(parentIds) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('parentIds', JSON.stringify(parentIds));
    
    fetch('../../controller/cParentManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Xóa thành công!', 'success');
            closeDeleteModal();
            selectedParents = [];
            document.getElementById('select-all').checked = false;
            loadParents();
        } else {
            showToast(data.message || 'Lỗi khi xóa dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting parents:', error);
        showToast('Lỗi kết nối: ' + error.message, 'error');
    });
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('show');
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 
                     'fa-info-circle';
    
    toast.innerHTML = `
        <i class="fas ${iconClass}"></i>
        <span class="toast-message">${escapeHtml(message)}</span>
    `;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

function showNotifications() {
    showToast('Bạn có 3 thông báo mới', 'info');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
