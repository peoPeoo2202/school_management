-- ====================================================================
-- Migration: Upgrade Account Management Schema
-- Purpose: Add missing columns, constraints, and indexes for account management
-- Date: 2025-11-17
-- ====================================================================

-- Step 1: Backup existing data (run this manually before migration)
-- CREATE TABLE taikhoan_backup AS SELECT * FROM taikhoan;

-- Step 2: Add missing columns to taikhoan table
ALTER TABLE `taikhoan`
ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL AFTER `hoTen`,
ADD COLUMN `soDienThoai` VARCHAR(20) NULL DEFAULT NULL AFTER `email`,
ADD COLUMN `ngayTao` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `maNhom`,
ADD COLUMN `nguoiTao` INT(11) NULL DEFAULT NULL AFTER `ngayTao`,
ADD COLUMN `ngayCapNhat` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `nguoiTao`,
ADD COLUMN `nguoiCapNhat` INT(11) NULL DEFAULT NULL AFTER `ngayCapNhat`,
ADD COLUMN `batBuocDoiMatKhau` TINYINT(1) DEFAULT 0 AFTER `nguoiCapNhat`,
ADD COLUMN `soLanDangNhapSai` INT DEFAULT 0 AFTER `batBuocDoiMatKhau`,
ADD COLUMN `lanDangNhapCuoi` DATETIME NULL DEFAULT NULL AFTER `soLanDangNhapSai`;

-- Step 3: Modify trangThaiTaiKhoan from tinyint to proper ENUM
-- First, update existing values to match new ENUM
UPDATE `taikhoan` SET `trangThaiTaiKhoan` = 1 WHERE `trangThaiTaiKhoan` != 0;

-- Modify column to ENUM type
ALTER TABLE `taikhoan`
MODIFY COLUMN `trangThaiTaiKhoan` ENUM('active', 'locked', 'disabled') NOT NULL DEFAULT 'active';

-- Step 4: Update existing records to use proper status values
UPDATE `taikhoan` SET `trangThaiTaiKhoan` = 'active' WHERE `trangThaiTaiKhoan` = '1';

-- Step 5: Add unique constraint for email (if provided)
ALTER TABLE `taikhoan`
ADD UNIQUE KEY `unique_email` (`email`);

-- Step 6: Add indexes for better query performance
ALTER TABLE `taikhoan`
ADD INDEX `idx_loaiTaiKhoan` (`loaiTaiKhoan`),
ADD INDEX `idx_trangThaiTaiKhoan` (`trangThaiTaiKhoan`),
ADD INDEX `idx_ngayTao` (`ngayTao`),
ADD INDEX `idx_email` (`email`);

-- Step 7: Add foreign key for nguoiTao and nguoiCapNhat (self-referencing)
ALTER TABLE `taikhoan`
ADD CONSTRAINT `fk_taikhoan_nguoiTao` 
    FOREIGN KEY (`nguoiTao`) REFERENCES `taikhoan` (`maTaiKhoan`) 
    ON DELETE SET NULL,
ADD CONSTRAINT `fk_taikhoan_nguoiCapNhat` 
    FOREIGN KEY (`nguoiCapNhat`) REFERENCES `taikhoan` (`maTaiKhoan`) 
    ON DELETE SET NULL;

-- Step 8: Enhance lichsunhapxuat table
ALTER TABLE `lichsunhapxuat`
ADD COLUMN `ghiChu` TEXT NULL DEFAULT NULL AFTER `thoiGian`,
ADD COLUMN `diaChiIP` VARCHAR(45) NULL DEFAULT NULL AFTER `ghiChu`,
ADD COLUMN `userAgent` VARCHAR(255) NULL DEFAULT NULL AFTER `diaChiIP`,
ADD COLUMN `duLieuCu` TEXT NULL DEFAULT NULL AFTER `userAgent`,
ADD COLUMN `duLieuMoi` TEXT NULL DEFAULT NULL AFTER `duLieuCu`;

-- Add indexes for lichsunhapxuat
ALTER TABLE `lichsunhapxuat`
ADD INDEX `idx_maTaiKhoan` (`maTaiKhoan`),
ADD INDEX `idx_hanhDong` (`hanhDong`),
ADD INDEX `idx_thoiGian` (`thoiGian`),
ADD INDEX `idx_bangDuLieu` (`bangDuLieu`);

-- Step 9: Enhance nhomnguoidung with audit columns
ALTER TABLE `nhomnguoidung`
ADD COLUMN `moTa` TEXT NULL DEFAULT NULL AFTER `tenNhom`,
ADD COLUMN `ngayTao` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `quyenHan`,
ADD COLUMN `nguoiTao` INT(11) NULL DEFAULT NULL AFTER `ngayTao`,
ADD COLUMN `ngayCapNhat` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `nguoiTao`,
ADD COLUMN `nguoiCapNhat` INT(11) NULL DEFAULT NULL AFTER `ngayCapNhat`;

-- Step 10: Prevent deletion of groups that are still referenced
-- This is enforced by existing FK constraint, but we add ON DELETE RESTRICT
ALTER TABLE `taikhoan`
DROP FOREIGN KEY `taikhoan_ibfk_1`;

ALTER TABLE `taikhoan`
ADD CONSTRAINT `taikhoan_ibfk_1` 
    FOREIGN KEY (`maNhom`) REFERENCES `nhomnguoidung` (`maNhom`) 
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- Step 11: Create view for account management with group info
CREATE OR REPLACE VIEW `v_account_management` AS
SELECT 
    t.maTaiKhoan,
    t.tenDangNhap,
    t.hoTen,
    t.email,
    t.soDienThoai,
    t.loaiTaiKhoan,
    t.trangThaiTaiKhoan,
    t.maNhom,
    n.tenNhom,
    t.ngayTao,
    t.lanDangNhapCuoi,
    t.batBuocDoiMatKhau,
    t.soLanDangNhapSai,
    CONCAT(creator.hoTen, ' (', creator.tenDangNhap, ')') as nguoiTaoInfo,
    CONCAT(updater.hoTen, ' (', updater.tenDangNhap, ')') as nguoiCapNhatInfo
FROM taikhoan t
LEFT JOIN nhomnguoidung n ON t.maNhom = n.maNhom
LEFT JOIN taikhoan creator ON t.nguoiTao = creator.maTaiKhoan
LEFT JOIN taikhoan updater ON t.nguoiCapNhat = updater.maTaiKhoan;

-- Step 12: Create stored procedure for logging activities
DELIMITER //

CREATE OR REPLACE PROCEDURE sp_logActivity(
    IN p_maTaiKhoan INT,
    IN p_hanhDong VARCHAR(50),
    IN p_bangDuLieu VARCHAR(100),
    IN p_ghiChu TEXT,
    IN p_diaChiIP VARCHAR(45),
    IN p_userAgent VARCHAR(255),
    IN p_duLieuCu TEXT,
    IN p_duLieuMoi TEXT
)
BEGIN
    INSERT INTO lichsunhapxuat (
        maTaiKhoan, hanhDong, bangDuLieu, ghiChu, 
        diaChiIP, userAgent, duLieuCu, duLieuMoi, thoiGian
    ) VALUES (
        p_maTaiKhoan, p_hanhDong, p_bangDuLieu, p_ghiChu,
        p_diaChiIP, p_userAgent, p_duLieuCu, p_duLieuMoi, NOW()
    );
END //

DELIMITER ;

-- Step 13: Create trigger to auto-log account changes
DELIMITER //

CREATE OR REPLACE TRIGGER `trg_taikhoan_after_update`
AFTER UPDATE ON `taikhoan`
FOR EACH ROW
BEGIN
    DECLARE v_changes TEXT;
    SET v_changes = '';
    
    -- Track which fields changed
    IF OLD.hoTen != NEW.hoTen THEN
        SET v_changes = CONCAT(v_changes, 'hoTen: ', OLD.hoTen, ' -> ', NEW.hoTen, '; ');
    END IF;
    
    IF OLD.email != NEW.email OR (OLD.email IS NULL AND NEW.email IS NOT NULL) OR (OLD.email IS NOT NULL AND NEW.email IS NULL) THEN
        SET v_changes = CONCAT(v_changes, 'email: ', COALESCE(OLD.email, 'NULL'), ' -> ', COALESCE(NEW.email, 'NULL'), '; ');
    END IF;
    
    IF OLD.loaiTaiKhoan != NEW.loaiTaiKhoan THEN
        SET v_changes = CONCAT(v_changes, 'loaiTaiKhoan: ', OLD.loaiTaiKhoan, ' -> ', NEW.loaiTaiKhoan, '; ');
    END IF;
    
    IF OLD.trangThaiTaiKhoan != NEW.trangThaiTaiKhoan THEN
        SET v_changes = CONCAT(v_changes, 'trangThai: ', OLD.trangThaiTaiKhoan, ' -> ', NEW.trangThaiTaiKhoan, '; ');
    END IF;
    
    IF OLD.maNhom != NEW.maNhom OR (OLD.maNhom IS NULL AND NEW.maNhom IS NOT NULL) OR (OLD.maNhom IS NOT NULL AND NEW.maNhom IS NULL) THEN
        SET v_changes = CONCAT(v_changes, 'maNhom: ', COALESCE(OLD.maNhom, 'NULL'), ' -> ', COALESCE(NEW.maNhom, 'NULL'), '; ');
    END IF;
    
    -- Only log if there were actual changes
    IF v_changes != '' THEN
        INSERT INTO lichsunhapxuat (maTaiKhoan, hanhDong, bangDuLieu, ghiChu, thoiGian)
        VALUES (NEW.nguoiCapNhat, 'capnhat', 'taikhoan', v_changes, NOW());
    END IF;
END //

DELIMITER ;

-- Step 14: Insert sample admin group permissions (if quyenHan is empty)
UPDATE `nhomnguoidung` 
SET `quyenHan` = JSON_ARRAY(
    'account.view', 'account.create', 'account.update', 'account.delete',
    'account.lock', 'account.unlock', 'account.reset_password',
    'group.view', 'group.create', 'group.update', 'group.delete',
    'audit.view', 'system.admin'
)
WHERE `maNhom` = 3001 AND (`quyenHan` IS NULL OR `quyenHan` = '[]');

UPDATE `nhomnguoidung` 
SET `quyenHan` = JSON_ARRAY(
    'account.view_own', 'account.update_own',
    'report.view', 'report.create', 'report.submit',
    'schedule.view', 'student.view', 'grade.manage'
)
WHERE `maNhom` = 3003 AND (`quyenHan` IS NULL OR `quyenHan` = '[]');

UPDATE `nhomnguoidung` 
SET `quyenHan` = JSON_ARRAY(
    'account.view_own', 'schedule.view', 'grade.view'
)
WHERE `maNhom` = 3004 AND (`quyenHan` IS NULL OR `quyenHan` = '[]');

-- Step 15: Verification queries (run after migration to verify)
-- SELECT COUNT(*) as total_accounts FROM taikhoan;
-- SELECT trangThaiTaiKhoan, COUNT(*) as count FROM taikhoan GROUP BY trangThaiTaiKhoan;
-- SELECT * FROM v_account_management LIMIT 10;
-- SHOW COLUMNS FROM taikhoan;
-- SHOW INDEXES FROM taikhoan;

-- ====================================================================
-- ROLLBACK SCRIPT (use with caution)
-- ====================================================================
-- To rollback this migration, uncomment and run the following:

/*
-- Remove trigger
DROP TRIGGER IF EXISTS `trg_taikhoan_after_update`;

-- Remove stored procedure
DROP PROCEDURE IF EXISTS `sp_logActivity`;

-- Remove view
DROP VIEW IF EXISTS `v_account_management`;

-- Remove new columns from taikhoan
ALTER TABLE `taikhoan`
DROP COLUMN `lanDangNhapCuoi`,
DROP COLUMN `soLanDangNhapSai`,
DROP COLUMN `batBuocDoiMatKhau`,
DROP COLUMN `nguoiCapNhat`,
DROP COLUMN `ngayCapNhat`,
DROP COLUMN `nguoiTao`,
DROP COLUMN `ngayTao`,
DROP COLUMN `soDienThoai`,
DROP COLUMN `email`;

-- Revert trangThaiTaiKhoan back to tinyint
ALTER TABLE `taikhoan`
MODIFY COLUMN `trangThaiTaiKhoan` TINYINT(4) DEFAULT 1;

-- Remove new columns from lichsunhapxuat
ALTER TABLE `lichsunhapxuat`
DROP COLUMN `duLieuMoi`,
DROP COLUMN `duLieuCu`,
DROP COLUMN `userAgent`,
DROP COLUMN `diaChiIP`,
DROP COLUMN `ghiChu`;

-- Remove new columns from nhomnguoidung
ALTER TABLE `nhomnguoidung`
DROP COLUMN `nguoiCapNhat`,
DROP COLUMN `ngayCapNhat`,
DROP COLUMN `nguoiTao`,
DROP COLUMN `ngayTao`,
DROP COLUMN `moTa`;

-- Restore original FK constraint for maNhom
ALTER TABLE `taikhoan`
DROP FOREIGN KEY `taikhoan_ibfk_1`;

ALTER TABLE `taikhoan`
ADD CONSTRAINT `taikhoan_ibfk_1` 
    FOREIGN KEY (`maNhom`) REFERENCES `nhomnguoidung` (`maNhom`) 
    ON DELETE SET NULL;

-- Restore from backup if needed
-- INSERT INTO taikhoan SELECT * FROM taikhoan_backup;
*/
