/**
 * JavaScript for Permission Management
 * Quản lý phân quyền người dùng
 */

// =============================================
// STATE VARIABLES
// =============================================
let accountsData = [];
let selectedAccount = null;
let selectedAccounts = [];
let originalPermissions = [];
let currentPermissions = [];
let isMultiSelectMode = false;
let hasUnsavedChanges = false;

// =============================================
// DOM READY
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    loadAccounts();
});

// =============================================
// ACCOUNT MANAGEMENT
// =============================================

/**
 * Load danh sách tài khoản
 */
function loadAccounts(search = '', group = 'all') {
    const listEl = document.getElementById('accounts-list');
    listEl.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
    
    let url = `../../controller/cPermissionManagement.php?action=getAccounts`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (group && group !== 'all') url += `&group=${group}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                accountsData = data.data;
                renderAccountsList();
            } else {
                showToast(data.message || 'Lỗi tải danh sách', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading accounts:', error);
            showToast('Lỗi kết nối server', 'error');
        });
}

/**
 * Render danh sách tài khoản
 */
function renderAccountsList() {
    const listEl = document.getElementById('accounts-list');
    
    if (accountsData.length === 0) {
        listEl.innerHTML = `
            <div class="empty-state" style="padding: 40px; text-align: center;">
                <i class="fas fa-users" style="font-size: 48px; color: #bdc3c7; margin-bottom: 15px;"></i>
                <p>Không tìm thấy tài khoản nào</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    accountsData.forEach(account => {
        const isSelected = selectedAccount && selectedAccount.maTaiKhoan === account.maTaiKhoan;
        const isMultiSelected = selectedAccounts.includes(account.maTaiKhoan);
        const avatarClass = getAvatarClass(account.loaiTaiKhoan);
        const initial = account.hoTen ? account.hoTen.charAt(0).toUpperCase() : 'U';
        
        html += `
            <div class="account-item ${isSelected ? 'selected' : ''} ${isMultiSelected ? 'multi-selected' : ''}" 
                 data-id="${account.maTaiKhoan}"
                 onclick="selectAccount(${account.maTaiKhoan})">
                ${isMultiSelectMode ? `
                    <input type="checkbox" class="account-checkbox" 
                           ${isMultiSelected ? 'checked' : ''}
                           onclick="event.stopPropagation(); toggleAccountSelection(${account.maTaiKhoan})">
                ` : ''}
                <div class="account-avatar ${avatarClass}">${initial}</div>
                <div class="account-info">
                    <div class="account-name">${escapeHtml(account.hoTen)}</div>
                    <div class="account-meta">ID: ${account.maTaiKhoan} | @${escapeHtml(account.tenDangNhap)}</div>
                </div>
                <span class="account-role">${account.tenNhom || 'Chưa phân nhóm'}</span>
            </div>
        `;
    });
    
    listEl.innerHTML = html;
}

/**
 * Lấy class cho avatar dựa trên loại tài khoản
 */
function getAvatarClass(loaiTaiKhoan) {
    const mapping = {
        'quantrivien': 'avatar-admin',
        'bangiamhieu': 'avatar-bgh',
        'giaovien': 'avatar-giaovien',
        'giaovien_cn': 'avatar-gvcn',
        'ttbm': 'avatar-ttbm',
        'hocsinh': 'avatar-hocsinh',
        'phuhuynh': 'avatar-phuhuynh'
    };
    return mapping[loaiTaiKhoan] || '';
}

/**
 * Chọn tài khoản
 */
function selectAccount(maTaiKhoan) {
    if (isMultiSelectMode) {
        toggleAccountSelection(maTaiKhoan);
        return;
    }
    
    // Kiểm tra có thay đổi chưa lưu không
    if (hasUnsavedChanges) {
        if (!confirm('Bạn có thay đổi chưa lưu. Bạn có muốn tiếp tục không?')) {
            return;
        }
    }
    
    const account = accountsData.find(a => a.maTaiKhoan === maTaiKhoan);
    if (!account) return;
    
    selectedAccount = account;
    hasUnsavedChanges = false;
    
    // Update UI
    renderAccountsList();
    loadAccountPermissions(maTaiKhoan);
}

/**
 * Load quyền của tài khoản
 */
function loadAccountPermissions(maTaiKhoan) {
    fetch(`../../controller/cPermissionManagement.php?action=getAccountPermissions&maTaiKhoan=${maTaiKhoan}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                originalPermissions = data.data.permissions || [];
                currentPermissions = [...originalPermissions];
                renderPermissionsForm(data.data.account);
            } else {
                showToast(data.message || 'Lỗi tải quyền', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading permissions:', error);
            showToast('Lỗi kết nối server', 'error');
        });
}

/**
 * Render form quyền
 */
function renderPermissionsForm(account) {
    // Hide empty state, show form
    document.getElementById('empty-selection').style.display = 'none';
    document.getElementById('permissions-form').style.display = 'block';
    document.getElementById('permissions-footer').style.display = 'flex';
    
    // Update account info
    document.getElementById('selected-info').textContent = `Đang chỉnh sửa: ${account.hoTen}`;
    document.getElementById('detail-avatar').textContent = account.hoTen ? account.hoTen.charAt(0).toUpperCase() : 'U';
    document.getElementById('detail-name').textContent = account.hoTen;
    document.getElementById('detail-meta').textContent = `Mã: ${account.maTaiKhoan} | Nhóm: ${account.tenNhom || 'Chưa phân nhóm'}`;
    
    // Update checkboxes
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = currentPermissions.includes(checkbox.value);
        updatePermissionItemStyle(checkbox);
    });
    
    updateUnsavedWarning();
}

/**
 * Xử lý khi thay đổi quyền
 */
function onPermissionChange(checkbox) {
    const value = checkbox.value;
    
    if (checkbox.checked) {
        if (!currentPermissions.includes(value)) {
            currentPermissions.push(value);
        }
    } else {
        currentPermissions = currentPermissions.filter(p => p !== value);
    }
    
    updatePermissionItemStyle(checkbox);
    checkForChanges();
}

/**
 * Cập nhật style cho permission item
 */
function updatePermissionItemStyle(checkbox) {
    const item = checkbox.closest('.permission-item');
    if (item) {
        if (checkbox.checked) {
            item.classList.add('checked');
        } else {
            item.classList.remove('checked');
        }
    }
}

/**
 * Kiểm tra có thay đổi không
 */
function checkForChanges() {
    const original = [...originalPermissions].sort();
    const current = [...currentPermissions].sort();
    
    hasUnsavedChanges = JSON.stringify(original) !== JSON.stringify(current);
    updateUnsavedWarning();
}

/**
 * Hiển thị cảnh báo chưa lưu
 */
function updateUnsavedWarning() {
    const warning = document.getElementById('unsaved-warning');
    if (warning) {
        warning.style.display = hasUnsavedChanges ? 'flex' : 'none';
    }
}

/**
 * Chọn tất cả trong nhóm
 */
function selectAllInGroup(groupKey) {
    document.querySelectorAll(`.permission-group[data-group="${groupKey}"] input[name="permissions[]"]`).forEach(checkbox => {
        checkbox.checked = true;
        if (!currentPermissions.includes(checkbox.value)) {
            currentPermissions.push(checkbox.value);
        }
        updatePermissionItemStyle(checkbox);
    });
    checkForChanges();
}

/**
 * Bỏ chọn tất cả trong nhóm
 */
function deselectAllInGroup(groupKey) {
    document.querySelectorAll(`.permission-group[data-group="${groupKey}"] input[name="permissions[]"]`).forEach(checkbox => {
        checkbox.checked = false;
        currentPermissions = currentPermissions.filter(p => p !== checkbox.value);
        updatePermissionItemStyle(checkbox);
    });
    checkForChanges();
}

/**
 * Toggle expand/collapse nhóm
 */
function toggleGroup(groupKey) {
    const group = document.querySelector(`.permission-group[data-group="${groupKey}"]`);
    const body = group.querySelector('.permission-group-body');
    const icon = group.querySelector('.permission-group-title i');
    
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.className = 'fas fa-chevron-down';
    } else {
        body.style.display = 'none';
        icon.className = 'fas fa-chevron-right';
    }
}

/**
 * Reset quyền về ban đầu
 */
function resetPermissions() {
    currentPermissions = [...originalPermissions];
    
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = currentPermissions.includes(checkbox.value);
        updatePermissionItemStyle(checkbox);
    });
    
    hasUnsavedChanges = false;
    updateUnsavedWarning();
    showToast('Đã khôi phục quyền ban đầu', 'info');
}

/**
 * Lưu quyền
 */
function savePermissions() {
    if (!selectedAccount) {
        showToast('Chưa chọn tài khoản', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'saveAccountPermissions');
    formData.append('maTaiKhoan', selectedAccount.maTaiKhoan);
    currentPermissions.forEach(p => formData.append('permissions[]', p));
    
    fetch('../../controller/cPermissionManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Phân quyền thành công', 'success');
            originalPermissions = [...currentPermissions];
            hasUnsavedChanges = false;
            updateUnsavedWarning();
            
            // Reload accounts to update permissions
            loadAccounts();
        } else {
            showToast(data.message || 'Lỗi khi lưu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving permissions:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

// =============================================
// MULTI-SELECT MODE
// =============================================

/**
 * Toggle chế độ chọn nhiều
 */
function toggleMultiSelect() {
    isMultiSelectMode = !isMultiSelectMode;
    selectedAccounts = [];
    
    const toolbar = document.getElementById('multi-select-toolbar');
    if (isMultiSelectMode) {
        toolbar.classList.add('active');
    } else {
        toolbar.classList.remove('active');
    }
    
    renderAccountsList();
    updateMultiSelectCount();
}

/**
 * Toggle chọn tài khoản trong chế độ multi-select
 */
function toggleAccountSelection(maTaiKhoan) {
    const index = selectedAccounts.indexOf(maTaiKhoan);
    if (index > -1) {
        selectedAccounts.splice(index, 1);
    } else {
        selectedAccounts.push(maTaiKhoan);
    }
    
    renderAccountsList();
    updateMultiSelectCount();
}

/**
 * Cập nhật số lượng đã chọn
 */
function updateMultiSelectCount() {
    document.getElementById('selected-count').textContent = selectedAccounts.length;
}

/**
 * Xóa tất cả selection
 */
function clearSelection() {
    selectedAccounts = [];
    renderAccountsList();
    updateMultiSelectCount();
}

/**
 * Áp dụng quyền cho nhiều tài khoản
 */
function applyBulkPermissions() {
    if (selectedAccounts.length === 0) {
        showToast('Chưa chọn tài khoản nào', 'warning');
        return;
    }
    
    // Collect current permissions from form
    const permissions = [];
    document.querySelectorAll('input[name="permissions[]"]:checked').forEach(checkbox => {
        permissions.push(checkbox.value);
    });
    
    if (permissions.length === 0) {
        showToast('Chưa chọn quyền nào', 'warning');
        return;
    }
    
    if (!confirm(`Bạn có chắc muốn áp dụng quyền cho ${selectedAccounts.length} tài khoản?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'saveBulkPermissions');
    selectedAccounts.forEach(id => formData.append('accountIds[]', id));
    permissions.forEach(p => formData.append('permissions[]', p));
    
    fetch('../../controller/cPermissionManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Phân quyền hàng loạt thành công', 'success');
            clearSelection();
            loadAccounts();
        } else {
            showToast(data.message || 'Lỗi khi lưu', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving bulk permissions:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

// =============================================
// SEARCH & FILTER
// =============================================

/**
 * Tìm kiếm tài khoản
 */
let searchTimeout;
function searchAccounts() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const search = document.getElementById('search-account').value;
        const activeTab = document.querySelector('.filter-tab.active');
        const group = activeTab ? activeTab.dataset.filter : 'all';
        loadAccounts(search, group);
    }, 300);
}

/**
 * Lọc theo nhóm
 */
function filterByGroup(group) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.dataset.filter == group) {
            tab.classList.add('active');
        }
    });
    
    const search = document.getElementById('search-account').value;
    loadAccounts(search, group);
}

// =============================================
// GROUP MANAGEMENT
// =============================================

/**
 * Mở modal quản lý nhóm
 */
function openGroupModal() {
    document.getElementById('group-modal').classList.add('active');
    loadGroups();
}

/**
 * Đóng modal quản lý nhóm
 */
function closeGroupModal() {
    document.getElementById('group-modal').classList.remove('active');
}

/**
 * Load danh sách nhóm
 */
function loadGroups() {
    fetch('../../controller/cPermissionManagement.php?action=getGroups')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderGroupList(data.data);
            }
        })
        .catch(error => console.error('Error loading groups:', error));
}

/**
 * Render danh sách nhóm
 */
function renderGroupList(groups) {
    const listEl = document.getElementById('group-list');
    const protectedGroups = [3001, 3002, 3003, 3004, 3005];
    
    let html = '';
    groups.forEach(group => {
        html += `
            <div class="group-item" data-id="${group.maNhom}">
                <div>
                    <div class="group-name">${escapeHtml(group.tenNhom)}</div>
                    <div class="group-count">${group.soTaiKhoan} tài khoản | ${(group.quyenHan || []).length} quyền</div>
                </div>
                <div class="group-actions">
                    <button class="btn-icon btn-edit" onclick="editGroup(${group.maNhom})" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${!protectedGroups.includes(group.maNhom) ? `
                        <button class="btn-icon btn-delete" onclick="deleteGroup(${group.maNhom})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    listEl.innerHTML = html;
}

/**
 * Mở form thêm nhóm
 */
function openAddGroupForm() {
    document.getElementById('add-group-form').style.display = 'block';
    document.getElementById('new-group-name').value = '';
    document.getElementById('new-group-desc').value = '';
}

/**
 * Hủy thêm nhóm
 */
function cancelAddGroup() {
    document.getElementById('add-group-form').style.display = 'none';
}

/**
 * Lưu nhóm mới
 */
function saveNewGroup() {
    const tenNhom = document.getElementById('new-group-name').value.trim();
    const moTa = document.getElementById('new-group-desc').value.trim();
    
    if (!tenNhom) {
        showToast('Vui lòng nhập tên nhóm', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'createGroup');
    formData.append('tenNhom', tenNhom);
    formData.append('moTa', moTa);
    
    fetch('../../controller/cPermissionManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Tạo nhóm thành công', 'success');
            cancelAddGroup();
            loadGroups();
            // Reload page to update filter tabs
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Lỗi tạo nhóm', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating group:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

/**
 * Sửa nhóm
 */
function editGroup(maNhom) {
    const groupName = prompt('Nhập tên nhóm mới:');
    if (!groupName) return;
    
    const formData = new FormData();
    formData.append('action', 'updateGroup');
    formData.append('maNhom', maNhom);
    formData.append('tenNhom', groupName);
    
    fetch('../../controller/cPermissionManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật nhóm thành công', 'success');
            loadGroups();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Lỗi cập nhật nhóm', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating group:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

/**
 * Xóa nhóm
 */
function deleteGroup(maNhom) {
    if (!confirm('Bạn có chắc muốn xóa nhóm này?')) return;
    
    const formData = new FormData();
    formData.append('action', 'deleteGroup');
    formData.append('maNhom', maNhom);
    
    fetch('../../controller/cPermissionManagement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Xóa nhóm thành công', 'success');
            loadGroups();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Lỗi xóa nhóm', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting group:', error);
        showToast('Lỗi kết nối server', 'error');
    });
}

// =============================================
// OTHER FEATURES
// =============================================

/**
 * Xuất báo cáo phân quyền
 */
function exportPermissions() {
    showToast('Tính năng đang phát triển', 'info');
}

/**
 * Xem lịch sử thay đổi
 */
function viewAuditLog() {
    showToast('Tính năng đang phát triển', 'info');
}

/**
 * Modal xác nhận thoát
 */
function closeConfirmExitModal() {
    document.getElementById('confirm-exit-modal').classList.remove('active');
}

function confirmExit() {
    hasUnsavedChanges = false;
    closeConfirmExitModal();
}

// =============================================
// UTILITIES
// =============================================

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Hiển thị toast
 */
function showToast(message, type = 'info') {
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

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});
