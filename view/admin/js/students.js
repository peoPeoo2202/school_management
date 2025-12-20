/**
 * STUDENTS MANAGEMENT - JavaScript
 * Handles CRUD operations, filtering, pagination, and modal interactions
 */

// ========================================
// GLOBAL VARIABLES & STATE
// ========================================
let currentPage = 1;
let recordsPerPage = 20;
let totalRecords = 0;
let selectedStudents = [];
let allStudents = [];
let currentFilters = {};
let currentMode = 'view'; // 'view', 'edit', 'add'
let currentStudentId = null;

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Enter key on filter inputs
    const filterInputs = document.querySelectorAll('.filter-section input');
    filterInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudents();
            }
        });
    });

    // Khối change event for form
    const khoiSelect = document.getElementById('khoi');
    if (khoiSelect) {
        khoiSelect.addEventListener('change', function() {
            updateLopOptions(this.value, 'lop');
        });
    }
}

// ========================================
// FILTER FUNCTIONS
// ========================================
function filterLopByKhoi() {
    const khoiValue = document.getElementById('filter-khoi').value;
    const lopSelect = document.getElementById('filter-lop');
    const allOptions = lopSelect.querySelectorAll('option');
    
    allOptions.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
            return;
        }
        
        const optionKhoi = option.getAttribute('data-khoi');
        if (!khoiValue || optionKhoi === khoiValue) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Reset lớp selection if current doesn't match
    const currentLop = lopSelect.value;
    const currentOption = lopSelect.querySelector(`option[value="${currentLop}"]`);
    if (currentOption && currentOption.style.display === 'none') {
        lopSelect.value = '';
    }
}

function updateLopOptions(khoiValue, targetSelectId) {
    const lopSelect = document.getElementById(targetSelectId);
    if (!lopSelect) return;
    
    // Clear current options except first
    lopSelect.innerHTML = '<option value="">-- Chọn lớp --</option>';
    
    if (!khoiValue) return;
    
    // Fetch classes for selected grade
    fetch(`../../controller/cStudentManagement.php?action=getLopByKhoi&khoi=${khoiValue}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.classes) {
                data.classes.forEach(lop => {
                    const option = document.createElement('option');
                    option.value = lop.maLop;
                    option.textContent = lop.tenLop;
                    lopSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });
}

function searchStudents() {
    currentFilters = {
        khoi: document.getElementById('filter-khoi').value,
        lop: document.getElementById('filter-lop').value,
        gioitinh: document.getElementById('filter-gioitinh').value,
        mahs: document.getElementById('filter-mahs').value,
        hoten: document.getElementById('filter-hoten').value
    };
    
    currentPage = 1;
    loadStudents();
}

function clearFilters() {
    document.getElementById('filter-khoi').value = '';
    document.getElementById('filter-lop').value = '';
    document.getElementById('filter-gioitinh').value = '';
    document.getElementById('filter-mahs').value = '';
    document.getElementById('filter-hoten').value = '';
    
    currentFilters = {};
    currentPage = 1;
    loadStudents();
}

// ========================================
// DATA LOADING
// ========================================
function loadStudents() {
    const tbody = document.getElementById('students-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>';
    
    // Build query string
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: recordsPerPage,
        ...currentFilters
    });
    
    fetch(`../../controller/cStudentManagement.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allStudents = data.students || [];
                totalRecords = data.total || 0;
                renderStudentsTable();
                updatePagination();
            } else {
                showToast(data.message || 'Lỗi khi tải dữ liệu', 'error');
                tbody.innerHTML = '<tr><td colspan="8" class="empty-row">Không thể tải dữ liệu</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
            showToast('Lỗi kết nối: ' + error.message, 'error');
            tbody.innerHTML = '<tr><td colspan="8" class="empty-row">Lỗi kết nối máy chủ</td></tr>';
        });
}

function renderStudentsTable() {
    const tbody = document.getElementById('students-tbody');
    
    if (allStudents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-row">Không tìm thấy học sinh nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = allStudents.map(student => `
        <tr>
            <td>
                <input type="checkbox" 
                       class="student-checkbox" 
                       value="${student.maHocSinh}"
                       onchange="updateSelectedCount()">
            </td>
            <td>${escapeHtml(student.maHocSinh)}</td>
            <td>${escapeHtml(student.hoTen)}</td>
            <td>${escapeHtml(student.gioiTinh)}</td>
            <td>${formatDate(student.ngaySinh)}</td>
            <td>${escapeHtml(student.tenLop || 'N/A')}</td>
            <td>${escapeHtml(student.khoi || 'N/A')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-view" onclick="viewStudent('${student.maHocSinh}')">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                    <button class="btn-icon btn-edit" onclick="editStudent('${student.maHocSinh}')">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteStudent('${student.maHocSinh}')">
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
    loadStudents();
}

function changeRecordsPerPage() {
    recordsPerPage = parseInt(document.getElementById('records-per-page').value);
    currentPage = 1;
    loadStudents();
}

// ========================================
// SELECTION MANAGEMENT
// ========================================
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    selectedStudents = Array.from(checkboxes).map(cb => cb.value);
    
    document.getElementById('selected-count').textContent = selectedStudents.length;
    document.getElementById('delete-selected-btn').disabled = selectedStudents.length === 0;
    
    // Update select-all checkbox state
    const selectAllCheckbox = document.getElementById('select-all');
    const allCheckboxes = document.querySelectorAll('.student-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && selectedStudents.length === allCheckboxes.length;
}

// ========================================
// MODAL FUNCTIONS
// ========================================
function openAddModal() {
    currentMode = 'add';
    currentStudentId = null;
    
    document.getElementById('modal-title').textContent = 'Thêm học sinh mới';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-plus"></i> Thêm học sinh';
    
    // Reset form
    document.getElementById('student-form').reset();
    document.getElementById('student-id').value = '';
    
    // Show only first tab
    switchTab('detail');
    
    // Hide other tabs for add mode
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach((tab, index) => {
        if (index === 0) {
            tab.style.display = 'block';
        } else {
            tab.style.display = 'none';
        }
    });
    
    document.getElementById('student-modal').classList.add('show');
}

function viewStudent(maHocSinh) {
    currentMode = 'view';
    currentStudentId = maHocSinh;
    
    document.getElementById('modal-title').textContent = 'Xem hồ sơ học sinh';
    document.getElementById('save-btn').style.display = 'none';
    
    // Show all tabs
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => tab.style.display = 'block');
    
    loadStudentDetail(maHocSinh, true);
    document.getElementById('student-modal').classList.add('show');
}

function editStudent(maHocSinh) {
    currentMode = 'edit';
    currentStudentId = maHocSinh;
    
    document.getElementById('modal-title').textContent = 'Chỉnh sửa hồ sơ học sinh';
    document.getElementById('save-btn').innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
    document.getElementById('save-btn').style.display = 'block';
    
    // Show all tabs
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => tab.style.display = 'block');
    
    loadStudentDetail(maHocSinh, false);
    document.getElementById('student-modal').classList.add('show');
}

function loadStudentDetail(maHocSinh, readonly) {
    fetch(`../../controller/cStudentManagement.php?action=getDetail&maHocSinh=${maHocSinh}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.student) {
                populateForm(data.student, readonly);
                
                // Load tabs data
                if (data.academic) loadAcademicData(data.academic);
                if (data.awards) loadAwardsData(data.awards);
                if (data.violations) loadViolationsData(data.violations);
                if (data.absences) loadAbsencesData(data.absences);
            } else {
                showToast(data.message || 'Không tìm thấy thông tin học sinh', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading student detail:', error);
            showToast('Lỗi khi tải thông tin: ' + error.message, 'error');
        });
}

function populateForm(student, readonly) {
    if (readonly) {
        // View mode - display as read-only info cards
        const detailTab = document.getElementById('tab-detail');
        detailTab.innerHTML = `
            <div class="view-mode-container">
                <h3 class="form-section-title">Thông tin chung</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Mã học sinh:</label>
                        <span>${escapeHtml(student.maHS || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Họ và tên:</label>
                        <span>${escapeHtml(student.hoTen || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Giới tính:</label>
                        <span>${escapeHtml(student.gioiTinh || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Ngày sinh:</label>
                        <span>${formatDate(student.ngaySinh)}</span>
                    </div>
                    <div class="info-item">
                        <label>Khối:</label>
                        <span>Khối ${escapeHtml(student.khoi || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Lớp:</label>
                        <span>${escapeHtml(student.tenLop || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Trạng thái:</label>
                        <span class="badge ${student.trangThaiHocTap === 'danghoc' ? 'badge-success' : 'badge-warning'}">${student.trangThaiHocTap === 'danghoc' ? 'Đang học' : escapeHtml(student.trangThaiHocTap || 'N/A')}</span>
                    </div>
                </div>

                <h3 class="form-section-title">Thông tin cá nhân</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Dân tộc:</label>
                        <span>${escapeHtml(student.danToc || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Địa chỉ:</label>
                        <span>${escapeHtml(student.diaChi || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Số điện thoại nhà:</label>
                        <span>${escapeHtml(student.sdtNha || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Số điện thoại di động:</label>
                        <span>${escapeHtml(student.sdtDiDong || 'N/A')}</span>
                    </div>
                </div>

                <h3 class="form-section-title">Thông tin gia đình</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Họ tên cha:</label>
                        <span>${escapeHtml(student.hoTenCha || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Nghề nghiệp cha:</label>
                        <span>${escapeHtml(student.ngheNghiepCha || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Họ tên mẹ:</label>
                        <span>${escapeHtml(student.hoTenMe || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Nghề nghiệp mẹ:</label>
                        <span>${escapeHtml(student.ngheNghiepMe || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <label>Phụ huynh liên hệ:</label>
                        <span>${escapeHtml(student.tenPhuHuynh || 'N/A')} ${student.sdtPhuHuynh ? '(' + escapeHtml(student.sdtPhuHuynh) + ')' : ''}</span>
                    </div>
                </div>
            </div>
        `;
    } else {
        // Edit mode - show form inputs
        document.getElementById('student-id').value = student.maHS || student.maHocSinh || '';
        document.getElementById('hoten').value = student.hoTen || '';
        document.getElementById('gioitinh').value = student.gioiTinh || '';
        document.getElementById('ngaysinh').value = student.ngaySinh || '';
        document.getElementById('tinh').value = student.tinhThanh || '';
        document.getElementById('xa').value = student.xaPhuong || '';
        document.getElementById('ngayvaotruong').value = student.ngayVaoTruong || '';
        document.getElementById('trangthai').value = student.trangThai || student.trangThaiHocTap || 'Đang học';
        document.getElementById('dantoc').value = student.danToc || '';
        document.getElementById('sdtnha').value = student.sdtNha || '';
        document.getElementById('sdtdidong').value = student.sdtDiDong || '';
        document.getElementById('hotencha').value = student.hoTenCha || '';
        document.getElementById('nghenghiepcha').value = student.ngheNghiepCha || '';
        document.getElementById('hotenme').value = student.hoTenMe || '';
        document.getElementById('nghenghiepme').value = student.ngheNghiepMe || '';
        
        // Set Khối and load Lớp
        if (student.khoi) {
            document.getElementById('khoi').value = student.khoi;
            updateLopOptions(student.khoi, 'lop');
            
            // Set Lớp after a short delay
            setTimeout(() => {
                if (student.maLop) {
                    document.getElementById('lop').value = student.maLop;
                }
            }, 300);
        }
        
        const inputs = document.querySelectorAll('#student-form input, #student-form select');
        inputs.forEach(input => input.disabled = false);
    }
}

function loadAcademicData(academic) {
    // Placeholder - implement based on your academic data structure
    const content = document.getElementById('academic-content');
    if (academic && academic.length > 0) {
        // Build academic table/cards here
        content.innerHTML = '<p class="empty-state">Dữ liệu học tập (chức năng đang phát triển)</p>';
    } else {
        content.innerHTML = '<p class="empty-state">Chưa có dữ liệu học tập</p>';
    }
}

function loadAwardsData(awards) {
    const tbody = document.getElementById('awards-tbody');
    if (awards && awards.length > 0) {
        tbody.innerHTML = awards.map(award => `
            <tr>
                <td>${formatDate(award.ngayKhenThuong)}</td>
                <td>${escapeHtml(award.hinhThuc || 'N/A')}</td>
                <td>${escapeHtml(award.capKhenThuong || 'N/A')}</td>
                <td>${escapeHtml(award.noiDung || 'N/A')}</td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Chưa có dữ liệu</td></tr>';
    }
}

function loadViolationsData(violations) {
    const tbody = document.getElementById('violations-tbody');
    if (violations && violations.length > 0) {
        tbody.innerHTML = violations.map(v => `
            <tr>
                <td>${formatDate(v.ngay)}</td>
                <td>${escapeHtml(v.loi)}</td>
                <td>${v.soLan}</td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Chưa có dữ liệu</td></tr>';
    }
}

function loadAbsencesData(absences) {
    const tbody = document.getElementById('absences-tbody');
    if (absences && absences.length > 0) {
        tbody.innerHTML = absences.map(a => `
            <tr>
                <td>${formatDate(a.ngay)}</td>
                <td>${escapeHtml(a.lyDo)}</td>
                <td>${a.tongSoNgay}</td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Chưa có dữ liệu</td></tr>';
    }
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
    document.getElementById('student-modal').classList.remove('show');
    
    // Re-enable all inputs
    const inputs = document.querySelectorAll('#student-form input, #student-form select');
    inputs.forEach(input => input.disabled = false);
}

// ========================================
// CRUD OPERATIONS
// ========================================
function saveStudent() {
    const form = document.getElementById('student-form');
    
    // Validation
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', currentMode === 'add' ? 'create' : 'update');
    
    fetch('../../controller/cStudentManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Lưu thành công!', 'success');
            closeModal();
            loadStudents();
        } else {
            showToast(data.message || 'Lỗi khi lưu dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving student:', error);
        showToast('Lỗi kết nối: ' + error.message, 'error');
    });
}

function deleteStudent(maHocSinh) {
    currentStudentId = maHocSinh;
    document.getElementById('delete-message').textContent = 
        `Bạn có chắc chắn muốn xóa học sinh "${maHocSinh}" không?`;
    
    document.getElementById('confirm-delete-btn').onclick = function() {
        confirmDelete([maHocSinh]);
    };
    
    document.getElementById('delete-modal').classList.add('show');
}

function deleteSelected() {
    if (selectedStudents.length === 0) return;
    
    document.getElementById('delete-message').textContent = 
        `Bạn có chắc chắn muốn xóa ${selectedStudents.length} học sinh đã chọn không?`;
    
    document.getElementById('confirm-delete-btn').onclick = function() {
        confirmDelete(selectedStudents);
    };
    
    document.getElementById('delete-modal').classList.add('show');
}

function confirmDelete(studentIds) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('studentIds', JSON.stringify(studentIds));
    
    fetch('../../controller/cStudentManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Xóa thành công!', 'success');
            closeDeleteModal();
            selectedStudents = [];
            document.getElementById('select-all').checked = false;
            loadStudents();
        } else {
            showToast(data.message || 'Lỗi khi xóa dữ liệu', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting students:', error);
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

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
