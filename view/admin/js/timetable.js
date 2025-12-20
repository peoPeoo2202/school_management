/**
 * JavaScript for Timetable Management
 * Quản lý Thời khóa biểu
 */

// State variables
let currentFilters = {
    maKhoi: '',
    maLop: '',
    hocKy: '1',
    namHoc: '2024-2025'
};
let timetableData = [];
let deleteLessonId = null;

/**
 * Filter Lớp by Khối (called from HTML onchange)
 */
function filterLopByKhoi() {
    const khoiSelect = document.getElementById('filter-khoi');
    const lopSelect = document.getElementById('filter-lop');
    const selectedKhoi = khoiSelect.value;
    
    // Get all options
    const options = lopSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') {
            // Keep "-- Chọn lớp --" visible
            return;
        }
        
        const khoiData = option.getAttribute('data-khoi');
        if (!selectedKhoi || khoiData === selectedKhoi) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Reset selection if current is hidden
    const currentOption = lopSelect.options[lopSelect.selectedIndex];
    if (currentOption && currentOption.style.display === 'none') {
        lopSelect.value = '';
    }
}

/**
 * Load Timetable (called from HTML onclick)
 */
function loadTimetable() {
    const maLop = document.getElementById('filter-lop').value;
    const hocKy = document.getElementById('filter-hocky').value;
    const namHoc = document.getElementById('filter-namhoc').value;
    
    if (!maLop) {
        showToast('Vui lòng chọn lớp', 'warning');
        return;
    }
    
    currentFilters = {
        maKhoi: document.getElementById('filter-khoi').value,
        maLop: maLop,
        hocKy: hocKy,
        namHoc: namHoc
    };
    
    // Update title
    const lopSelect = document.getElementById('filter-lop');
    const lopName = lopSelect.options[lopSelect.selectedIndex].text;
    document.getElementById('timetable-title').textContent = 'Thời khóa biểu ' + lopName;
    
    // Show timetable container, hide empty state
    document.getElementById('timetable-container').style.display = 'block';
    document.getElementById('empty-state').style.display = 'none';
    
    // Fetch data
    const params = new URLSearchParams({
        action: 'getTimetable',
        maLop: maLop,
        hocKy: hocKy,
        namHoc: namHoc
    });
    
    fetch('../../controller/cScheduleManagement.php?' + params)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                timetableData = data.data || [];
                renderTimetable();
            } else {
                showToast(data.message || 'Lỗi tải thời khóa biểu', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading timetable:', error);
            showToast('Lỗi kết nối server', 'error');
        });
}

/**
 * Render timetable grid
 */
function renderTimetable() {
    const tbody = document.getElementById('timetable-body');
    const days = [2, 3, 4, 5, 6, 7]; // Thứ 2 - Thứ 7
    const periods = 10;
    
    // Create lookup for lessons
    const lessonMap = {};
    timetableData.forEach(lesson => {
        for (let t = lesson.tietBatDau; t <= lesson.tietKetThuc; t++) {
            const key = `${lesson.thu}-${t}`;
            lessonMap[key] = lesson;
        }
    });
    
    // Track cells to skip (for merged cells)
    const skipCells = {};
    
    let html = '';
    
    for (let tiet = 1; tiet <= periods; tiet++) {
        html += '<tr>';
        html += `<td class="tiet-cell">Tiết ${tiet}</td>`;
        
        for (let thu of days) {
            const key = `${thu}-${tiet}`;
            
            if (skipCells[key]) {
                continue; // Skip this cell (merged)
            }
            
            const lesson = lessonMap[key];
            
            if (lesson && lesson.tietBatDau == tiet) {
                const rowspan = lesson.tietKetThuc - lesson.tietBatDau + 1;
                
                // Mark cells to skip
                for (let t = tiet + 1; t <= lesson.tietKetThuc; t++) {
                    skipCells[`${thu}-${t}`] = true;
                }
                
                html += `
                    <td class="scheduled-cell" rowspan="${rowspan}">
                        <div class="lesson-card ${rowspan > 1 ? 'multi-tiet' : ''}" data-id="${lesson.maLichDay}">
                            <div class="actions">
                                <button type="button" class="btn-edit-lesson" onclick="editLesson(${lesson.maLichDay})" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-delete-lesson" onclick="openDeleteModal(${lesson.maLichDay})" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="subject">${lesson.tenMonHoc || 'Môn học'}</div>
                            <div class="teacher"><i class="fas fa-user"></i> ${lesson.tenGV || ''}</div>
                            <div class="room"><i class="fas fa-door-open"></i> ${lesson.tenPhong || ''}</div>
                        </div>
                    </td>
                `;
            } else if (!lesson) {
                html += `
                    <td class="empty-cell" onclick="openAddLessonModal(${thu}, ${tiet})"></td>
                `;
            }
        }
        
        html += '</tr>';
    }
    
    tbody.innerHTML = html;
}

/**
 * Clear filters
 */
function clearFilters() {
    document.getElementById('filter-khoi').value = '';
    document.getElementById('filter-lop').value = '';
    document.getElementById('filter-hocky').value = '1';
    document.getElementById('filter-namhoc').value = '2024-2025';
    
    // Reset lop filter
    filterLopByKhoi();
    
    // Hide timetable, show empty state
    document.getElementById('timetable-container').style.display = 'none';
    document.getElementById('empty-state').style.display = 'block';
    
    timetableData = [];
}

/**
 * Open modal to add new lesson
 */
function openAddLessonModal(thu = '', tiet = '') {
    if (!currentFilters.maLop) {
        showToast('Vui lòng chọn lớp trước', 'warning');
        return;
    }
    
    document.getElementById('lesson-modal-title').textContent = 'Thêm tiết học';
    document.getElementById('lesson-form').reset();
    document.getElementById('lesson-id').value = '';
    document.getElementById('conflict-warning').style.display = 'none';
    
    if (thu) {
        document.getElementById('lesson-thu').value = thu;
    }
    if (tiet) {
        document.getElementById('lesson-tiet-start').value = tiet;
        document.getElementById('lesson-tiet-end').value = tiet;
    }
    
    document.getElementById('lesson-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Close lesson modal
 */
function closeLessonModal() {
    document.getElementById('lesson-modal').classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * Edit existing lesson
 */
function editLesson(maLichDay) {
    const lesson = timetableData.find(l => l.maLichDay == maLichDay);
    if (!lesson) {
        showToast('Không tìm thấy tiết học', 'error');
        return;
    }
    
    document.getElementById('lesson-modal-title').textContent = 'Sửa tiết học';
    document.getElementById('lesson-id').value = lesson.maLichDay;
    document.getElementById('lesson-thu').value = lesson.thu;
    document.getElementById('lesson-tiet-start').value = lesson.tietBatDau;
    document.getElementById('lesson-tiet-end').value = lesson.tietKetThuc;
    document.getElementById('lesson-monhoc').value = lesson.maMonHoc;
    document.getElementById('lesson-giaovien').value = lesson.maGV;
    document.getElementById('lesson-phong').value = lesson.maPhong;
    document.getElementById('conflict-warning').style.display = 'none';
    
    document.getElementById('lesson-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Save lesson (create or update)
 */
function saveLesson() {
    const maLichDay = document.getElementById('lesson-id').value;
    const thu = document.getElementById('lesson-thu').value;
    const tietBatDau = document.getElementById('lesson-tiet-start').value;
    const tietKetThuc = document.getElementById('lesson-tiet-end').value;
    const maMonHoc = document.getElementById('lesson-monhoc').value;
    const maGV = document.getElementById('lesson-giaovien').value;
    const maPhong = document.getElementById('lesson-phong').value;
    
    // Validate
    if (!thu || !tietBatDau || !tietKetThuc || !maMonHoc || !maGV || !maPhong) {
        showToast('Vui lòng điền đầy đủ thông tin', 'warning');
        return;
    }
    
    if (parseInt(tietKetThuc) < parseInt(tietBatDau)) {
        showToast('Tiết kết thúc phải lớn hơn hoặc bằng tiết bắt đầu', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', maLichDay ? 'updateLesson' : 'createLesson');
    formData.append('maLichDay', maLichDay);
    formData.append('maLop', currentFilters.maLop);
    formData.append('thu', thu);
    formData.append('tietBatDau', tietBatDau);
    formData.append('tietKetThuc', tietKetThuc);
    formData.append('maMonHoc', maMonHoc);
    formData.append('maGV', maGV);
    formData.append('maPhong', maPhong);
    formData.append('hocKy', currentFilters.hocKy);
    formData.append('namHoc', currentFilters.namHoc);
    
    fetch('../../controller/cScheduleManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Lưu thành công', 'success');
            closeLessonModal();
            loadTimetable();
        } else {
            // Show conflict warning
            document.getElementById('conflict-warning').style.display = 'flex';
            document.getElementById('conflict-message').textContent = data.message || 'Lỗi khi lưu';
            showToast(data.message || 'Lỗi khi lưu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving lesson:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

/**
 * Open delete confirmation modal
 */
function openDeleteModal(maLichDay) {
    deleteLessonId = maLichDay;
    document.getElementById('delete-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Close delete modal
 */
function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('active');
    document.body.style.overflow = '';
    deleteLessonId = null;
}

/**
 * Confirm and delete lesson
 */
function confirmDeleteLesson() {
    if (!deleteLessonId) return;
    
    const formData = new FormData();
    formData.append('action', 'deleteLesson');
    formData.append('maLichDay', deleteLessonId);
    
    fetch('../../controller/cScheduleManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa tiết học thành công', 'success');
            closeDeleteModal();
            loadTimetable();
        } else {
            showToast(data.message || 'Lỗi khi xóa', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting lesson:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

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