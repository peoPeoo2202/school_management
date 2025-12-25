/**
 * JavaScript for Exam Schedule Management
 * Quản lý Lịch thi
 */

// State variables
let currentExamFilters = {
    maKyThi: '',
    maMonHoc: ''
};
let examScheduleData = [];
let currentExamInfo = null;
let deleteExamId = null;

// DOM Elements - matching HTML IDs
const DOM = {
    filterKyThi: () => document.getElementById('filter-kythi'),
    filterMonHoc: () => document.getElementById('filter-monhoc'),
    examInfo: () => document.getElementById('exam-info'),
    examTitle: () => document.getElementById('exam-title'),
    examStatus: () => document.getElementById('exam-status'),
    examDateRange: () => document.getElementById('exam-date-range'),
    examKhoi: () => document.getElementById('exam-khoi'),
    examHocky: () => document.getElementById('exam-hocky'),
    examScheduleSection: () => document.getElementById('exam-schedule-section'),
    examTbody: () => document.getElementById('exam-tbody'),
    examEmptyState: () => document.getElementById('exam-empty-state'),
    examModal: () => document.getElementById('exam-modal'),
    examModalTitle: () => document.getElementById('exam-modal-title'),
    examForm: () => document.getElementById('exam-form'),
    examScheduleId: () => document.getElementById('exam-schedule-id'),
    examKyThiId: () => document.getElementById('exam-kythi-id'),
    examMonhoc: () => document.getElementById('exam-monhoc'),
    examNgay: () => document.getElementById('exam-ngay'),
    examGioStart: () => document.getElementById('exam-gio-start'),
    examGioEnd: () => document.getElementById('exam-gio-end'),
    examPhong: () => document.getElementById('exam-phong'),
    examGiaovien: () => document.getElementById('exam-giaovien'),
    examConflictWarning: () => document.getElementById('exam-conflict-warning'),
    examConflictMessage: () => document.getElementById('exam-conflict-message'),
    examDeleteModal: () => document.getElementById('exam-delete-modal'),
    toastContainer: () => document.getElementById('toast-container')
};

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initExamSchedule();
});

/**
 * Initialize exam schedule page
 */
function initExamSchedule() {
    // Kỳ thi change - load exam info
    const filterKyThi = DOM.filterKyThi();
    if (filterKyThi) {
        filterKyThi.addEventListener('change', function() {
            if (this.value) {
                loadExamInfo(this.value);
            } else {
                hideExamInfo();
            }
        });
    }
}

/**
 * Load exam info by maKyThi
 */
function loadExamInfo(maKyThi) {
    fetch(`../../controller/cScheduleManagement.php?action=getExamInfo&maKyThi=${maKyThi}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                currentExamInfo = data.data;
                showExamInfo(data.data);
            } else {
                hideExamInfo();
            }
        })
        .catch(error => {
            console.error('Error loading exam info:', error);
            hideExamInfo();
        });
}

/**
 * Show exam info card
 */
function showExamInfo(exam) {
    const examTitle = DOM.examTitle();
    const examStatus = DOM.examStatus();
    const examDateRange = DOM.examDateRange();
    const examKhoi = DOM.examKhoi();
    const examHocky = DOM.examHocky();
    const examInfo = DOM.examInfo();

    if (examTitle) examTitle.textContent = exam.tenKyThi || 'Kỳ thi';
    if (examStatus) {
        examStatus.textContent = exam.trangThai || 'Đang lập';
        examStatus.className = 'exam-status ' + getStatusClass(exam.trangThai);
    }
    if (examDateRange) examDateRange.textContent = `${formatDate(exam.ngayBatDau)} - ${formatDate(exam.ngayKetThuc)}`;
    if (examKhoi) examKhoi.textContent = `Khối ${exam.khoiLop || ''}`;
    if (examHocky) examHocky.textContent = `Học kỳ ${exam.hocKy || ''} - ${exam.namHoc || ''}`;
    
    if (examInfo) examInfo.style.display = 'block';
}

/**
 * Get status class for styling
 */
function getStatusClass(status) {
    switch (status) {
        case 'Đang diễn ra': return 'status-active';
        case 'Hoàn thành': return 'status-completed';
        default: return 'status-pending';
    }
}

/**
 * Hide exam info card
 */
function hideExamInfo() {
    const examInfo = DOM.examInfo();
    if (examInfo) examInfo.style.display = 'none';
    currentExamInfo = null;
    
    // Hide schedule section, show empty state
    const scheduleSection = DOM.examScheduleSection();
    const emptyState = DOM.examEmptyState();
    if (scheduleSection) scheduleSection.style.display = 'none';
    if (emptyState) emptyState.style.display = 'flex';
}

/**
 * Load exam schedule data - called by button onclick
 */
function loadExamSchedule() {
    const filterKyThi = DOM.filterKyThi();
    const filterMonHoc = DOM.filterMonHoc();
    
    const maKyThi = filterKyThi ? filterKyThi.value : '';
    const maMonHoc = filterMonHoc ? filterMonHoc.value : '';
    
    if (!maKyThi) {
        showToast('Vui lòng chọn kỳ thi', 'warning');
        return;
    }
    
    currentExamFilters = { maKyThi, maMonHoc };
    
    // Show loading
    const tbody = DOM.examTbody();
    if (tbody) {
        tbody.innerHTML = `
            <tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>
        `;
    }
    
    // Show schedule section, hide empty state
    const scheduleSection = DOM.examScheduleSection();
    const emptyState = DOM.examEmptyState();
    if (scheduleSection) scheduleSection.style.display = 'block';
    if (emptyState) emptyState.style.display = 'none';
    
    const params = new URLSearchParams(currentExamFilters);
    
    fetch(`../../controller/cScheduleManagement.php?action=getExamSchedule&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                examScheduleData = data.data || [];
                renderExamSchedule();
            } else {
                showToast(data.message || 'Lỗi tải lịch thi', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading exam schedule:', error);
            showToast('Lỗi kết nối server', 'error');
        });
}

/**
 * Clear exam filters
 */
function clearExamFilters() {
    const filterKyThi = DOM.filterKyThi();
    const filterMonHoc = DOM.filterMonHoc();
    
    if (filterKyThi) filterKyThi.value = '';
    if (filterMonHoc) filterMonHoc.value = '';
    
    currentExamFilters = { maKyThi: '', maMonHoc: '' };
    examScheduleData = [];
    hideExamInfo();
}

/**
 * Render exam schedule table
 */
function renderExamSchedule() {
    const tbody = DOM.examTbody();
    if (!tbody) return;
    
    if (examScheduleData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
                    <div style="padding: 40px;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; color: #bdc3c7; margin-bottom: 16px;"></i>
                        <p style="color: #7f8c8d;">Chưa có lịch thi nào</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    examScheduleData.forEach((exam, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${exam.tenMonHoc || ''}</strong></td>
                <td>${formatDate(exam.ngayThi)}</td>
                <td>${exam.gioBatDau || ''} - ${exam.gioKetThuc || ''}</td>
                <td>${exam.tenPhong || ''}</td>
                <td>${exam.giaoVienCoiThi || ''}</td>
                <td>
                    <div class="table-actions">
                        <button class="btn-icon btn-edit" onclick="editExamSchedule(${exam.maLichThi})" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" onclick="openDeleteExamModal(${exam.maLichThi})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Open modal to add new exam schedule
 */
function openAddExamModal() {
    if (!currentExamFilters.maKyThi) {
        showToast('Vui lòng chọn kỳ thi trước', 'warning');
        return;
    }
    
    const modalTitle = DOM.examModalTitle();
    const form = DOM.examForm();
    const examScheduleId = DOM.examScheduleId();
    const examKyThiId = DOM.examKyThiId();
    const examNgay = DOM.examNgay();
    
    if (modalTitle) modalTitle.textContent = 'Thêm lịch thi';
    if (form) form.reset();
    if (examScheduleId) examScheduleId.value = '';
    if (examKyThiId) examKyThiId.value = currentExamFilters.maKyThi;
    
    // Set min/max date for ngayThi based on exam info
    if (currentExamInfo && examNgay) {
        examNgay.min = currentExamInfo.ngayBatDau || '';
        examNgay.max = currentExamInfo.ngayKetThuc || '';
    }
    
    openModal('exam-modal');
}

/**
 * Edit existing exam schedule
 */
function editExamSchedule(maLichThi) {
    const exam = examScheduleData.find(e => e.maLichThi == maLichThi);
    if (!exam) {
        showToast('Không tìm thấy lịch thi', 'error');
        return;
    }
    
    const modalTitle = DOM.examModalTitle();
    const examScheduleId = DOM.examScheduleId();
    const examKyThiId = DOM.examKyThiId();
    const examMonhoc = DOM.examMonhoc();
    const examNgay = DOM.examNgay();
    const examGioStart = DOM.examGioStart();
    const examGioEnd = DOM.examGioEnd();
    const examPhong = DOM.examPhong();
    const examGiaovien = DOM.examGiaovien();
    
    if (modalTitle) modalTitle.textContent = 'Sửa lịch thi';
    if (examScheduleId) examScheduleId.value = exam.maLichThi;
    if (examKyThiId) examKyThiId.value = exam.maKyThi || currentExamFilters.maKyThi;
    if (examMonhoc) examMonhoc.value = exam.maMonHoc || '';
    if (examNgay) examNgay.value = exam.ngayThi || '';
    if (examGioStart) examGioStart.value = exam.gioBatDau || '';
    if (examGioEnd) examGioEnd.value = exam.gioKetThuc || '';
    if (examPhong) examPhong.value = exam.maPhong || '';
    
    // Set selected teachers
    if (examGiaovien) {
        const maGVList = exam.maGVCoiThi ? exam.maGVCoiThi.split(',') : [];
        for (let option of examGiaovien.options) {
            option.selected = maGVList.includes(option.value);
        }
    }
    
    openModal('exam-modal');
}

/**
 * Save exam schedule (create or update)
 */
function saveExamSchedule() {
    const examScheduleId = DOM.examScheduleId();
    const form = DOM.examForm();
    const examGiaovien = DOM.examGiaovien();
    
    if (!form) return;
    
    const maLichThi = examScheduleId ? examScheduleId.value : '';
    const formData = new FormData(form);
    formData.append('action', maLichThi ? 'updateExamSchedule' : 'createExamSchedule');
    
    // Get multiple selected teachers
    if (examGiaovien) {
        const selectedTeachers = Array.from(examGiaovien.selectedOptions).map(opt => opt.value);
        formData.set('maGVCoiThi', selectedTeachers.join(','));
    }
    
    fetch('../../controller/cScheduleManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Lưu thành công', 'success');
            closeModal('exam-modal');
            loadExamSchedule();
        } else {
            showToast(data.message || 'Lỗi khi lưu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving exam schedule:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

/**
 * Open delete confirmation modal
 */
function openDeleteExamModal(maLichThi) {
    deleteExamId = maLichThi;
    openModal('exam-delete-modal');
}

/**
 * Close delete modal
 */
function closeExamDeleteModal() {
    closeModal('exam-delete-modal');
    deleteExamId = null;
}

/**
 * Confirm and delete exam schedule
 */
function confirmDeleteExam() {
    if (!deleteExamId) return;
    
    const formData = new FormData();
    formData.append('action', 'deleteExamSchedule');
    formData.append('maLichThi', deleteExamId);
    
    fetch('../../controller/cScheduleManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa lịch thi thành công', 'success');
            closeModal('exam-delete-modal');
            deleteExamId = null;
            loadExamSchedule();
        } else {
            showToast(data.message || 'Lỗi khi xóa', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting exam schedule:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

/**
 * Close exam modal
 */
function closeExamModal() {
    closeModal('exam-modal');
}

/**
 * Format date to DD/MM/YYYY
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * Modal functions
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'exclamation-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
