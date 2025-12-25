/**
 * TEACHERS MANAGEMENT - JavaScript
 * Handles CRUD operations for teacher information
 */

// ========================================
// GLOBAL VARIABLES & STATE
// ========================================
let currentPage = 1;
let recordsPerPage = 20;
let totalRecords = 0;
let selectedTeachers = [];
let allTeachers = [];
let currentFilters = {};
let currentMode = 'view'; // 'view', 'edit', 'add'
let currentTeacherId = null;

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    loadTeachers();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Enter key on filter inputs
    const filterInputs = document.querySelectorAll('.filter-section input');
    filterInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTeachers();
            }
        });
    });
}

// ========================================
// FILTER FUNCTIONS
// ========================================
function searchTeachers() {
    currentFilters = {
        magv: document.getElementById('filter-magv').value,
        hoten: document.getElementById('filter-hoten').value,
        tobomon: document.getElementById('filter-tobomon').value,
        gioitinh: document.getElementById('filter-gioitinh').value
    };
    
    currentPage = 1;
    loadTeachers();
}

function clearFilters() {
    document.getElementById('filter-magv').value = '';
    document.getElementById('filter-hoten').value = '';
    document.getElementById('filter-tobomon').value = '';
    document.getElementById('filter-gioitinh').value = '';
    
    currentFilters = {};
    currentPage = 1;
    loadTeachers();
}

// ========================================
// DATA LOADING
// ========================================
function loadTeachers() {
    const tbody = document.getElementById('teachers-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>';
    
    // Build query string
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: recordsPerPage,
        ...currentFilters
    });
    
    fetch(`../../controller/cTeacherManagement.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allTeachers = data.teachers || [];
                totalRecords = data.total || 0;
                renderTeachersTable();
                updatePagination();
            } else {
                showToast(data.message || 'Lỗi khi tải dữ liệu', 'error');
                tbody.innerHTML = '<tr><td colspan="6" class="empty-row">Không thể tải dữ liệu</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading teachers:', error);
            showToast('Lỗi kết nối: ' + error.message, 'error');
            tbody.innerHTML = '<tr><td colspan="6" class="empty-row">Lỗi kết nối máy chủ</td></tr>';
        });
}

function renderTeachersTable() {
    const tbody = document.getElementById('teachers-tbody');
    
    if (allTeachers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-row">Không tìm thấy giáo viên nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = allTeachers.map(teacher => `
        <tr>
            <td>
                <input type="checkbox" 
                       class="teacher-checkbox" 
                       value="${teacher.maGiaoVien}"
                       onchange="updateSelectedCount()">
            </td>
            <td>${escapeHtml(teacher.maGiaoVien)}</td>
            <td>${escapeHtml(teacher.hoTen)}</td>
            <td>${escapeHtml(teacher.toBoMon || 'N/A')}</td>
            <td>${escapeHtml(teacher.gioiTinh)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-view" onclick="viewTeacher('${teacher.maGiaoVien}')">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                    <button class="btn-icon btn-edit" onclick="editTeacher('${teacher.maGiaoVien}')">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteTeacher('${teacher.maGiaoVien}')">
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
    loadTeachers();
}

function changeRecordsPerPage() {
    recordsPerPage = parseInt(document.getElementById('records-per-page').value);
    currentPage = 1;
    loadTeachers();
}

// ========================================
// SELECTION MANAGEMENT
// ========================================
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.teacher-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.teacher-checkbox:checked');
    selectedTeachers = Array.from(checkboxes).map(cb => cb.value);
    
    document.getElementById('selected-count').textContent = selectedTeachers.length;
    document.getElementById('delete-selected-btn').disabled = selectedTeachers.length === 0;
    
    // Update select-all checkbox state
    const selectAllCheckbox = document.getElementById('select-all');
    const allCheckboxes = document.querySelectorAll('.teacher-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && selectedTeachers.length === allCheckboxes.length;
}

// ========================================
// MODAL FUNCTIONS
// ========================================
function openAddModal() {
    currentMode = 'add';
    currentTeacherId = null;
    
    document.getElementById('modal-title').textContent = 'Thêm giáo viên mới';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-plus"></i> Thêm giáo viên';
    
    // Reset form
    document.getElementById('teacher-form').reset();
    document.getElementById('teacher-id').value = '';
    
    // Enable all inputs
    const inputs = document.querySelectorAll('#teacher-form input, #teacher-form select');
    inputs.forEach(input => input.disabled = false);
    
    document.getElementById('teacher-modal').classList.add('show');
}

function viewTeacher(maGiaoVien) {
    currentMode = 'view';
    currentTeacherId = maGiaoVien;
    
    document.getElementById('modal-title').textContent = 'Xem thông tin giáo viên';
    document.getElementById('save-btn').style.display = 'none';
    
    loadTeacherDetail(maGiaoVien, true);
    document.getElementById('teacher-modal').classList.add('show');
}

function editTeacher(maGiaoVien) {
    currentMode = 'edit';
    currentTeacherId = maGiaoVien;
    
    document.getElementById('modal-title').textContent = 'Chỉnh sửa thông tin giáo viên';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
    document.getElementById('save-btn').style.display = 'block';
    
    loadTeacherDetail(maGiaoVien, false);
    document.getElementById('teacher-modal').classList.add('show');
}

function loadTeacherDetail(maGiaoVien, readonly) {
    fetch(`../../controller/cTeacherManagement.php?action=getDetail&maGiaoVien=${maGiaoVien}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.teacher) {
                populateForm(data.teacher, readonly);
            } else {
                showToast(data.message || 'Không tìm thấy thông tin giáo viên', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading teacher detail:', error);
            showToast('Lỗi khi tải thông tin: ' + error.message, 'error');
        });
}

function populateForm(teacher, readonly) {
    document.getElementById('teacher-id').value = teacher.maGiaoVien || teacher.maGV || '';
    document.getElementById('hoten').value = teacher.hoTen || '';
    document.getElementById('gioitinh').value = teacher.gioiTinh || '';
    document.getElementById('ngaysinh').value = teacher.ngaySinh || '';
    document.getElementById('tobomon').value = teacher.toBoMon || '';
    document.getElementById('sdt').value = teacher.soDienThoai || '';
    document.getElementById('email').value = teacher.email || '';
    
    // Disable all inputs if readonly
    if (readonly) {
        const inputs = document.querySelectorAll('#teacher-form input, #teacher-form select');
        inputs.forEach(input => input.disabled = true);
    } else {
        const inputs = document.querySelectorAll('#teacher-form input, #teacher-form select');
        inputs.forEach(input => input.disabled = false);
    }
}

function closeModal() {
    document.getElementById('teacher-modal').classList.remove('show');
    
    // Re-enable all inputs
    const inputs = document.querySelectorAll('#teacher-form input, #teacher-form select');
    inputs.forEach(input => input.disabled = false);
}

// ========================================
// CRUD OPERATIONS
// ========================================
function saveTeacher() {
    const form = document.getElementById('teacher-form');
    
    // Validation
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', currentMode === 'add' ? 'create' : 'update');
    
    fetch('../../controller/cTeacherManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Lưu thành công!', 'success');
            closeModal();
            loadTeachers();
        } else {
            showToast(data.message || 'Lỗi khi lưu dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving teacher:', error);
        showToast('Lỗi kết nối: ' + error.message, 'error');
    });
}

function deleteTeacher(maGiaoVien) {
    currentTeacherId = maGiaoVien;
    
    // Check for constraints
    checkTeacherConstraints(maGiaoVien).then(hasConstraints => {
        document.getElementById('delete-message').textContent = 
            `Bạn có chắc chắn muốn xóa giáo viên "${maGiaoVien}" không?`;
        
        const constraintWarning = document.getElementById('constraint-warning');
        if (hasConstraints) {
            constraintWarning.style.display = 'block';
        } else {
            constraintWarning.style.display = 'none';
        }
        
        document.getElementById('confirm-delete-btn').onclick = function() {
            confirmDelete([maGiaoVien]);
        };
        
        document.getElementById('delete-modal').classList.add('show');
    });
}

function deleteSelected() {
    if (selectedTeachers.length === 0) return;
    
    document.getElementById('delete-message').textContent = 
        `Bạn có chắc chắn muốn xóa ${selectedTeachers.length} giáo viên đã chọn không?`;
    
    document.getElementById('constraint-warning').style.display = 'none';
    
    document.getElementById('confirm-delete-btn').onclick = function() {
        confirmDelete(selectedTeachers);
    };
    
    document.getElementById('delete-modal').classList.add('show');
}

async function checkTeacherConstraints(maGiaoVien) {
    try {
        const response = await fetch(`../../controller/cTeacherManagement.php?action=checkConstraints&maGiaoVien=${maGiaoVien}`);
        const data = await response.json();
        return data.hasConstraints || false;
    } catch (error) {
        console.error('Error checking constraints:', error);
        return false;
    }
}

function confirmDelete(teacherIds) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('teacherIds', JSON.stringify(teacherIds));
    
    fetch('../../controller/cTeacherManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Xóa thành công!', 'success');
            closeDeleteModal();
            selectedTeachers = [];
            document.getElementById('select-all').checked = false;
            loadTeachers();
        } else {
            showToast(data.message || 'Lỗi khi xóa dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting teachers:', error);
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
