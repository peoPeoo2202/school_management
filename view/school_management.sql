-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 19, 2025 lúc 04:36 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `school_management`
--

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KiemTraTrungLichGiaoVien` (IN `p_maGV` INT, IN `p_thu` TINYINT, IN `p_tietBatDau` TINYINT, IN `p_tietKetThuc` TINYINT, IN `p_hocKy` TINYINT, IN `p_namHoc` VARCHAR(20))   BEGIN
  SELECT EXISTS(
    SELECT 1 FROM lichday
    WHERE maGV = p_maGV 
      AND thu = p_thu 
      AND hocKy = p_hocKy 
      AND namHoc = p_namHoc
      AND NOT (tietKetThuc < p_tietBatDau OR tietBatDau > p_tietKetThuc)
  ) AS trung;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KiemTraTrungLichPhong` (IN `p_maPhong` INT, IN `p_thu` TINYINT, IN `p_tietBatDau` TINYINT, IN `p_tietKetThuc` TINYINT, IN `p_hocKy` TINYINT, IN `p_namHoc` VARCHAR(20))   BEGIN
  SELECT EXISTS(
    SELECT 1 FROM lichday
    WHERE maPhong = p_maPhong AND thu = p_thu AND hocKy = p_hocKy AND namHoc = p_namHoc
      AND NOT (tietKetThuc < p_tietBatDau OR tietBatDau > p_tietKetThuc)
  ) AS trung;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_XepLoaiDanhHieu` (IN `p_maHS` INT, IN `p_namHoc` VARCHAR(20))   BEGIN
    DECLARE _hocLucHK1 VARCHAR(50);
    DECLARE _hocLucHK2 VARCHAR(50);
    DECLARE _hanhKiemHK1 VARCHAR(50);
    DECLARE _hanhKiemHK2 VARCHAR(50);
    DECLARE _diemTBHK1 DECIMAL(5,2);
    DECLARE _diemTBHK2 DECIMAL(5,2);
    DECLARE _diemTBCaNam DECIMAL(5,2);
    DECLARE _soMonDuoi5 INT;
    DECLARE _danhHieuID INT DEFAULT NULL;
    
    -- Lấy thông tin học lực
    SELECT loaiHocLuc, diemTBHK1, diemTBHK2, diemTBCaNam, soMonDuoi5
    INTO _hocLucHK1, _diemTBHK1, _diemTBHK2, _diemTBCaNam, _soMonDuoi5
    FROM hocluc
    WHERE maHS = p_maHS AND namHoc = p_namHoc;
    
    -- Lấy thông tin hạnh kiểm HK1
    SELECT loaiHK INTO _hanhKiemHK1
    FROM hanhkiem
    WHERE maHS = p_maHS AND hocKy = 1 AND namHoc = p_namHoc;
    
    -- Lấy thông tin hạnh kiểm HK2
    SELECT loaiHK INTO _hanhKiemHK2
    FROM hanhkiem
    WHERE maHS = p_maHS AND hocKy = 2 AND namHoc = p_namHoc;
    
    -- Xếp loại danh hiệu theo Thông tư 22
    -- Học sinh Xuất sắc: TB cả năm >= 9. 0, >= 6 môn TB >= 9.0, Học lực Tốt cả 2 HK, Hạnh kiểm Tốt cả 2 HK
    IF _diemTBCaNam >= 9.0 
       AND _hocLucHK1 = 'Tot' 
       AND _hanhKiemHK1 = 'Tot'
       AND _hanhKiemHK2 = 'Tot'
       AND _soMonDuoi5 = 0
       AND EXISTS (
           SELECT 1 FROM bangdiem 
           WHERE maHS = p_maHS AND namHoc = p_namHoc
           GROUP BY maHS
           HAVING COUNT(CASE WHEN tbDiem >= 9.0 THEN 1 END) >= 6
       ) THEN
        SET _danhHieuID = 1001; -- Học sinh xuất sắc
        
    -- Học sinh Giỏi: Học lực Tốt cả 2 HK, Hạnh kiểm Tốt cả 2 HK
    ELSEIF _hocLucHK1 = 'Tot'
           AND _hanhKiemHK1 = 'Tot'
           AND _hanhKiemHK2 = 'Tot' THEN
        SET _danhHieuID = 1002; -- Học sinh giỏi
        
    ELSE
        SET _danhHieuID = NULL; -- Không đạt danh hiệu
    END IF;
    
    -- Cập nhật danh hiệu vào bảng hocsinh
    UPDATE hocsinh 
    SET maDanhHieu = _danhHieuID
    WHERE maHS = p_maHS;
    
    -- Lưu vào bảng hocsinh_danhhieu (nếu có danh hiệu)
    IF _danhHieuID IS NOT NULL THEN
        INSERT INTO hocsinh_danhhieu (maHS, maDanhHieu, namHoc, hocKy, ghiChu)
        VALUES (p_maHS, _danhHieuID, p_namHoc, 2, 'Tự động xếp loại cuối năm học')
        ON DUPLICATE KEY UPDATE 
            maDanhHieu = _danhHieuID,
            ngayCapNhat = CURRENT_TIMESTAMP;
    END IF;
    
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_XepLoaiHanhKiem` (IN `p_maHS` INT, IN `p_hocKy` TINYINT, IN `p_namHoc` VARCHAR(20))   BEGIN
  DECLARE _soNghiCoPhep INT DEFAULT 0;
  DECLARE _soNghiKhongPhep INT DEFAULT 0;
  DECLARE _soLanVP INT DEFAULT 0;
  SELECT IFNULL(SUM(soBuoiNghiCoPhep),0), IFNULL(SUM(soBuoiNghiKhongPhep),0), IFNULL(SUM(soLanVP),0)
  INTO _soNghiCoPhep, _soNghiKhongPhep, _soLanVP
  FROM hanhkiem hk
  JOIN hocsinh hs ON hs.maHanhKiem = hk.maHanhKiem
  WHERE hs.maHS = p_maHS;

  IF _soNghiKhongPhep = 0 AND _soLanVP = 0 AND _soNghiCoPhep <= 3 THEN
    SELECT 'Tot' AS xepLoai;
  ELSEIF _soNghiKhongPhep <= 2 AND _soLanVP <= 2 AND _soNghiCoPhep <= 10 THEN
    SELECT 'Kha' AS xepLoai;
  ELSEIF _soNghiKhongPhep <= 5 AND _soLanVP <= 5 THEN
    SELECT 'Trung binh' AS xepLoai;
  ELSE
    SELECT 'Yeu' AS xepLoai;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_XepLoaiHocLuc` (IN `p_diemTB` DECIMAL(5,2))   BEGIN
  IF p_diemTB IS NULL THEN
    SELECT NULL AS xepLoai;
  ELSEIF p_diemTB >= 9.0 THEN
    SELECT 'Xuat sac' AS xepLoai;
  ELSEIF p_diemTB >= 8.0 THEN
    SELECT 'Gioi' AS xepLoai;
  ELSEIF p_diemTB >= 6.5 THEN
    SELECT 'Kha' AS xepLoai;
  ELSEIF p_diemTB >= 5.0 THEN
    SELECT 'Trung binh' AS xepLoai;
  ELSEIF p_diemTB >= 3.5 THEN
    SELECT 'Yeu' AS xepLoai;
  ELSE
    SELECT 'Kem' AS xepLoai;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sync_DanhHieuHocSinh` (IN `p_namHoc` VARCHAR(20))   BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_maHS INT;
    
    -- Cursor để duyệt qua tất cả học sinh có đủ dữ liệu
    DECLARE cur CURSOR FOR 
        SELECT DISTINCT hs.maHS
        FROM hocsinh hs
        INNER JOIN hocluc hl ON hs. maHS = hl.maHS
        INNER JOIN hanhkiem hk1 ON hs.maHS = hk1.maHS
        INNER JOIN hanhkiem hk2 ON hs.maHS = hk2.maHS
        WHERE hs.trangThaiHocTap = 'danghoc'
          AND hl.namHoc = p_namHoc
          AND hl.diemTBCaNam IS NOT NULL
          AND hk1.namHoc = p_namHoc AND hk1.hocKy = 1 AND hk1.loaiHK IS NOT NULL
          AND hk2.namHoc = p_namHoc AND hk2.hocKy = 2 AND hk2. loaiHK IS NOT NULL;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    -- Mở cursor
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_maHS;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Gọi procedure xếp loại cho từng học sinh
        BEGIN
            DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
            CALL sp_XepLoaiDanhHieu(v_maHS, p_namHoc);
        END;
    END LOOP;
    
    CLOSE cur;
    
    -- Thống kê kết quả
    SELECT 
        'Đồng bộ hoàn tất!' as status,
        COUNT(*) as tongSoHS,
        SUM(CASE WHEN maDanhHieu IS NOT NULL THEN 1 ELSE 0 END) as soCoDanhHieu,
        SUM(CASE WHEN maDanhHieu = 1001 THEN 1 ELSE 0 END) as soHSXuatSac,
        SUM(CASE WHEN maDanhHieu = 1002 THEN 1 ELSE 0 END) as soHSGioi
    FROM hocsinh
    WHERE trangThaiHocTap = 'danghoc';
    
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bainop`
--

CREATE TABLE `bainop` (
  `maBaiNop` int(11) NOT NULL,
  `maBaiTap` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `tenFile` varchar(255) DEFAULT NULL,
  `duongDan` varchar(500) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `ngayNop` datetime DEFAULT current_timestamp(),
  `trangThai` enum('Danop','Tre','Chuacham','Dacham') DEFAULT 'Chuacham',
  `diem` decimal(5,2) DEFAULT NULL,
  `nhanXet` text DEFAULT NULL,
  `ngayCham` datetime DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bainop`
--

INSERT INTO `bainop` (`maBaiNop`, `maBaiTap`, `maHS`, `tenFile`, `duongDan`, `noiDung`, `ngayNop`, `trangThai`, `diem`, `nhanXet`, `ngayCham`, `maGV`) VALUES
(1, 20013, 13001, '1764841158_693156c67060b_test-file.pdf', 'uploads/submissions/1764841158_693156c67060b_test-file.pdf', '', '2025-12-04 16:39:18', 'Dacham', 7.00, '', '2025-12-04 16:39:57', 6001),
(2, 20006, 13001, 'Bai-Tap-Toan-Cua-A1.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1.pdf', '', '2025-12-05 07:54:54', 'Chuacham', 5.00, '', '2025-12-04 16:09:25', 6001),
(3, 20020, 13001, '1764847541_69316fb51fc95_test-file.pdf', 'uploads/submissions/1764847541_69316fb51fc95_test-file.pdf', '', '2025-12-04 18:25:41', 'Chuacham', 2.00, '', '2025-12-04 18:25:33', 6001),
(4, 20006, 13001, 'Bai-Tap-Toan-Cua-A1-1764896594.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1-1764896594.pdf', '', '2025-12-05 08:03:14', 'Chuacham', NULL, NULL, NULL, NULL),
(5, 20006, 13001, 'Bai-Tap-Toan-Cua-A1-1764896641.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1-1764896641.pdf', '', '2025-12-05 08:04:01', 'Chuacham', NULL, NULL, NULL, NULL),
(6, 20006, 13001, 'Bai-Tap-Toan-Cua-A1-1764896667.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1-1764896667.pdf', '', '2025-12-05 08:04:27', 'Chuacham', NULL, NULL, NULL, NULL),
(7, 20006, 13001, 'Bai-Tap-Sinh-Cua-A1-1764897116.pdf', 'uploads/submissions/Bai-Tap-Sinh-Cua-A1-1764897116.pdf', '', '2025-12-05 08:11:56', 'Chuacham', NULL, NULL, NULL, NULL),
(8, 20020, 13001, 'Bai-Tap-Toan-Cua-A1-1764897448.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1-1764897448.pdf', '', '2025-12-05 08:17:28', 'Chuacham', NULL, NULL, NULL, NULL),
(9, 20020, 13001, 'Bai-Tap-Toan-Cua-A1-1764897817.pdf', 'uploads/submissions/Bai-Tap-Toan-Cua-A1-1764897817.pdf', '', '2025-12-05 08:23:37', 'Chuacham', NULL, NULL, NULL, NULL),
(15, 20022, 13001, 'Test-File-1764907431.docx', 'uploads/submissions/Test-File-1764907431.docx', '', '2025-12-05 11:03:51', 'Chuacham', NULL, NULL, NULL, NULL),
(16, 20022, 13001, 'Test-File-1764907596.pdf', 'uploads/submissions/Test-File-1764907596.pdf', '', '2025-12-05 11:06:36', 'Dacham', 4.00, '', '2025-12-05 21:05:02', 6001);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `baitap`
--

CREATE TABLE `baitap` (
  `maBaiTap` int(11) NOT NULL,
  `tenBaiTap` varchar(255) NOT NULL,
  `yeuCauBaiTap` text DEFAULT NULL,
  `thoiGianNop` datetime DEFAULT NULL,
  `choPhepNopTre` tinyint(1) DEFAULT 1 COMMENT '1: cho phép, 0: không cho phép',
  `anBai` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1: Ẩn bài, 0: không ẩn bài',
  `khoaBai` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1: khóa bài, 0: không khóa bài',
  `tenFile` varchar(255) DEFAULT NULL,
  `duongDan` varchar(500) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `baitap`
--

INSERT INTO `baitap` (`maBaiTap`, `tenBaiTap`, `yeuCauBaiTap`, `thoiGianNop`, `choPhepNopTre`, `anBai`, `khoaBai`, `tenFile`, `duongDan`, `maLop`, `maMonHoc`, `maGV`) VALUES
(20001, 'Bài tập Toán lớp 6A1 - Tuần 1', 'Làm bài 1-10 trang 25 SGK', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2001, 6001),
(20002, 'Bài tập Toán lớp 6A1 - Tuần 2', 'Làm bài 11-20 trang 30 SGK', '2024-11-22 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2001, 6001),
(20003, 'Bài tập Toán lớp 6A1 - Tuần 3', 'Ôn tập chương 1', '2024-11-29 23:59:00', 0, 0, 1, NULL, NULL, 10001, 2001, 6001),
(20004, 'Bài tập Toán lớp 6A2 - Tuần 1', 'Làm bài 1-10 trang 25 SGK', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10002, 2001, 6001),
(20005, 'Bài tập Toán lớp 6A2 - Tuần 2', 'Làm bài 11-20 trang 30 SGK', '2024-11-22 23:59:00', 1, 0, 0, NULL, NULL, 10002, 2001, 6001),
(20006, 'Bài tập Ngữ văn lớp 6A1 - Tuần 1', 'Viết đoạn văn tả cảnh thiên nhiên', '2024-11-16 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2004, 6012),
(20007, 'Bài tập Ngữ văn lớp 6A1 - Tuần 2', 'Phân tích bài thơ Lượm', '2024-11-23 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2004, 6012),
(20008, 'Bài tập Ngữ văn lớp 6A1 - Tuần 3', 'Soạn bài Cổng trường mở ra', '2024-11-30 23:59:00', 0, 0, 1, NULL, NULL, 10001, 2004, 6012),
(20009, 'Bài tập Vật lý lớp 6A2 - Tuần 1', 'Làm bài 1-5 trang 12 SGK', '2024-11-17 23:59:00', 1, 0, 0, NULL, NULL, 10002, 2002, 6002),
(20010, 'Bài tập Vật lý lớp 6A2 - Tuần 2', 'Làm bài 6-10 trang 18 SGK', '2024-11-24 23:59:00', 1, 0, 0, NULL, NULL, 10002, 2002, 6002),
(20011, 'Bài tập Sinh học lớp 6A1 - Tuần 1', 'Vẽ sơ đồ tế bào thực vật', '2024-11-18 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2007, 6006),
(20012, 'Bài tập Sinh học lớp 6A1 - Tuần 2', 'Trả lời câu hỏi SGK trang 25', '2024-11-25 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2007, 6006),
(20013, 'Bài tập Tiếng Anh lớp 6A1 - Unit 1', 'Complete exercises 1-5 page 20', '2024-11-19 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2009, 6005),
(20014, 'Bài tập Tiếng Anh lớp 6A1 - Unit 2', 'Write a paragraph about your family', '2024-11-26 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2009, 6005),
(20015, 'Bài tập Tiếng Anh lớp 6A2 - Unit 1', 'Complete exercises 1-5 page 20', '2024-11-19 23:59:00', 1, 0, 0, NULL, NULL, 10002, 2009, 6005),
(20016, 'Bài tập Tin học lớp 6A1 - Tuần 1', 'Thực hành soạn thảo Word bài 1', '2024-11-20 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2008, 6008),
(20017, 'Bài tập Tin học lớp 6A1 - Tuần 2', 'Thực hành định dạng văn bản Word', '2024-11-27 23:59:00', 1, 0, 0, NULL, NULL, 10001, 2008, 6008),
(20018, 'Bài tập Hóa học lớp 7A1 - Tuần 1', 'Làm bài 1-5 trang 20 SGK', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10003, 2003, 6003),
(20019, 'Bài tập Hóa học lớp 7A1 - Tuần 2', 'Cân bằng phương trình hóa học', '2024-11-22 23:59:00', 1, 0, 0, NULL, NULL, 10003, 2003, 6003),
(20020, 'Bài tập Ngữ văn lớp 7A1 - Tuần 1', 'Soạn bài Cổng trường mở ra', '2024-11-16 23:59:00', 1, 0, 0, NULL, NULL, 10003, 2004, 6004),
(20021, 'Bài tập Ngữ văn lớp 7A2 - Tuần 1', 'Viết đoạn văn nghị luận xã hội', '2024-11-16 23:59:00', 1, 0, 0, NULL, NULL, 10004, 2004, 6004),
(20022, 'Bài tập GDCD lớp 7A1 - Tuần 1', 'Trả lời câu hỏi bài 1 SGK', '2024-11-21 23:59:00', 1, 0, 0, NULL, NULL, 10003, 2010, 6010),
(20023, 'Bài tập Toán lớp 8A1 - Tuần 1', 'Giải phương trình bậc nhất một ẩn', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10005, 2001, 6001),
(20024, 'Bài tập Toán lớp 8A2 - Tuần 1', 'Giải phương trình bậc nhất một ẩn', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10006, 2001, 6001),
(20025, 'Bài tập Toán lớp 9A1 - Tuần 1', 'Giải hệ phương trình bằng phương pháp thế', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10007, 2001, 6001),
(20026, 'Bài tập Toán lớp 9A2 - Tuần 1', 'Giải hệ phương trình bằng phương pháp cộng', '2024-11-15 23:59:00', 1, 0, 0, NULL, NULL, 10008, 2001, 6001),
(20027, 'Bài tập Tiếng Anh lớp 8A1 - Unit 1', 'Reading comprehension exercises', '2024-11-19 23:59:00', 1, 0, 0, NULL, NULL, 10005, 2009, 6005),
(20028, 'Bài tập Tiếng Anh lớp 8A1 - Unit 2', 'Writing practice: describe your house', '2024-11-26 23:59:00', 1, 0, 0, NULL, NULL, 10005, 2009, 6005),
(20029, 'Bài tập Vật lý lớp 8A2 - Tuần 1', 'Bài tập về lực và chuyển động', '2024-11-17 23:59:00', 1, 0, 0, NULL, NULL, 10006, 2002, 6002),
(20030, 'Bài tập Vật lý lớp 8A2 - Tuần 2', 'Bài tập về áp suất chất lỏng', '2024-11-24 23:59:00', 1, 0, 0, NULL, NULL, 10006, 2002, 6002);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bangdiem`
--

CREATE TABLE `bangdiem` (
  `maBangDiem` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `hocKy` tinyint(1) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `diemTX1` decimal(3,1) DEFAULT NULL,
  `diemTX2` decimal(3,1) DEFAULT NULL,
  `diemTX3` decimal(3,1) DEFAULT NULL,
  `diemTX4` decimal(3,1) DEFAULT NULL,
  `diemGiuaKy` decimal(3,1) DEFAULT NULL,
  `diemCuoiKy` decimal(3,1) DEFAULT NULL,
  `tbDiem` decimal(3,1) DEFAULT NULL,
  `nhanXet` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bangdiem`
--

INSERT INTO `bangdiem` (`maBangDiem`, `maHS`, `maMonHoc`, `hocKy`, `namHoc`, `diemTX1`, `diemTX2`, `diemTX3`, `diemTX4`, `diemGiuaKy`, `diemCuoiKy`, `tbDiem`, `nhanXet`, `created_at`, `updated_at`) VALUES
(1, 13001, 2001, 1, '2024-2025', 8.0, 7.5, 8.5, 8.0, 8.5, 9.0, 8.7, 'chăm chỉ, ngoan, tốt.', '2024-11-17 12:00:00', '2025-11-25 13:14:10'),
(2, 13001, 2002, 1, '2024-2025', 7.5, 8.0, 7.0, 7.5, 8.0, 8.5, 7.8, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(3, 13001, 2003, 1, '2024-2025', 8.5, 8.0, 8.5, 8.0, 8.5, 9.0, 8.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(4, 13001, 2004, 1, '2024-2025', 9.0, 8.5, 9.0, 8.5, 9.0, 9.5, 8.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(5, 13001, 2005, 1, '2024-2025', 7.0, 7.5, 7.0, 7.5, 8.0, 8.0, 7.5, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(6, 13001, 2006, 1, '2024-2025', 8.0, 7.5, 8.0, 7.5, 8.5, 8.5, 8.0, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(7, 13001, 2007, 1, '2024-2025', 7.5, 8.0, 7.5, 8.0, 8.0, 8.5, 7.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(8, 13001, 2008, 1, '2024-2025', 9.0, 9.0, 9.5, 9.0, 9.5, 10.0, 9.3, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(9, 13001, 2009, 1, '2024-2025', 8.5, 8.0, 8.5, 8.0, 8.5, 9.0, 8.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(10, 13001, 2010, 1, '2024-2025', 8.0, 8.5, 8.0, 8.5, 8.5, 9.0, 8.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(11, 13001, 2001, 2, '2024-2025', 8.5, 8.0, 8.5, 8.5, 9.0, 9.0, 8.9, NULL, '2024-11-17 12:00:00', '2025-12-01 15:12:32'),
(12, 13001, 2002, 2, '2024-2025', 8.0, 8.0, 7.5, 8.0, 8.5, 8.5, 8.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(13, 13001, 2003, 2, '2024-2025', 8.5, 8.5, 8.0, 8.5, 9.0, 9.0, 8.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(14, 13001, 2004, 2, '2024-2025', 9.0, 9.0, 8.5, 9.0, 9.5, 9.5, 9.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(15, 13001, 2005, 2, '2024-2025', 7.5, 7.5, 7.0, 8.0, 8.0, 8.5, 7.8, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(16, 13001, 2006, 2, '2024-2025', 8.0, 8.5, 8.0, 8.0, 8.5, 9.0, 8.3, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(17, 13001, 2007, 2, '2024-2025', 8.0, 8.0, 7.5, 8.5, 8.5, 8.5, 8.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(18, 13001, 2008, 2, '2024-2025', 9.5, 9.0, 9.5, 9.5, 9.5, 10.0, 9.5, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(19, 13001, 2009, 2, '2024-2025', 8.5, 8.5, 8.0, 8.5, 9.0, 9.0, 8.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(20, 13001, 2010, 2, '2024-2025', 8.5, 8.5, 8.0, 8.5, 9.0, 9.0, 8.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(21, 13002, 2001, 1, '2024-2025', 10.0, 9.0, 9.5, 9.0, 9.5, 10.0, 9.6, NULL, '2024-11-17 12:00:00', '2025-12-16 17:50:19'),
(22, 13002, 2002, 1, '2024-2025', 8.5, 8.5, 8.0, 8.5, 9.0, 9.0, 8.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(23, 13002, 2003, 1, '2024-2025', 9.0, 8.5, 9.0, 8.5, 9.0, 9.5, 8.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(24, 13002, 2004, 1, '2024-2025', 9.5, 9.0, 9.5, 9.0, 9.5, 10.0, 9.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(25, 13002, 2005, 1, '2024-2025', 8.0, 8.0, 7.5, 8.0, 8.5, 8.5, 8.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(26, 13002, 2006, 1, '2024-2025', 8.5, 8.0, 8.5, 8.0, 8.5, 9.0, 8.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(27, 13002, 2007, 1, '2024-2025', 8.5, 8.5, 8.0, 8.5, 8.5, 9.0, 8.5, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(28, 13002, 2008, 1, '2024-2025', 9.5, 9.5, 9.5, 9.5, 10.0, 10.0, 9.7, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(29, 13002, 2009, 1, '2024-2025', 9.0, 8.5, 9.0, 8.5, 9.0, 9.5, 8.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(30, 13002, 2010, 1, '2024-2025', 8.5, 8.5, 8.0, 8.5, 9.0, 9.0, 8.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(31, 13002, 2001, 2, '2024-2025', 9.5, 9.0, 9.5, 9.5, 9.5, 10.0, 9.7, NULL, '2024-11-17 12:00:00', '2025-12-01 15:12:32'),
(32, 13002, 2002, 2, '2024-2025', 8.5, 9.0, 8.5, 8.5, 9.0, 9.0, 8.8, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(33, 13002, 2003, 2, '2024-2025', 9.0, 9.0, 8.5, 9.0, 9.5, 9.5, 9.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(34, 13002, 2004, 2, '2024-2025', 9.5, 9.5, 9.0, 9.5, 10.0, 10.0, 9.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(35, 13002, 2005, 2, '2024-2025', 8.5, 8.0, 8.0, 8.5, 8.5, 9.0, 8.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(36, 13002, 2006, 2, '2024-2025', 8.5, 8.5, 8.5, 8.5, 9.0, 9.0, 8.7, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(37, 13002, 2007, 2, '2024-2025', 8.5, 8.5, 8.5, 8.5, 9.0, 9.0, 8.7, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(38, 13002, 2008, 2, '2024-2025', 10.0, 9.5, 10.0, 9.5, 10.0, 10.0, 9.8, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(39, 13002, 2009, 2, '2024-2025', 9.0, 9.0, 8.5, 9.0, 9.5, 9.5, 9.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(40, 13002, 2010, 2, '2024-2025', 8.5, 9.0, 8.5, 8.5, 9.0, 9.0, 8.8, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(41, 13003, 2001, 1, '2024-2025', 7.0, 7.0, 6.5, 7.0, 7.5, 7.5, 7.4, NULL, '2024-11-17 12:00:00', '2025-11-25 13:04:03'),
(42, 13003, 2002, 1, '2024-2025', 6.5, 7.0, 6.5, 7.0, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(43, 13003, 2003, 1, '2024-2025', 7.0, 6.5, 7.0, 6.5, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(44, 13003, 2004, 1, '2024-2025', 8.0, 7.5, 8.0, 7.5, 8.0, 8.5, 7.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(45, 13003, 2005, 1, '2024-2025', 6.5, 6.5, 6.0, 6.5, 7.0, 7.0, 6.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(46, 13003, 2006, 1, '2024-2025', 7.0, 6.5, 7.0, 6.5, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(47, 13003, 2007, 1, '2024-2025', 6.5, 7.0, 6.5, 7.0, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(48, 13003, 2008, 1, '2024-2025', 8.0, 8.0, 7.5, 8.0, 8.5, 8.5, 8.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(49, 13003, 2009, 1, '2024-2025', 7.0, 7.0, 6.5, 7.0, 7.5, 7.5, 7.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(50, 13003, 2010, 1, '2024-2025', 7.5, 7.0, 7.5, 7.0, 7.5, 8.0, 7.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(51, 13003, 2001, 2, '2024-2025', 7.5, 7.0, 7.0, 7.5, 7.5, 8.0, 7.7, NULL, '2024-11-17 12:00:00', '2025-12-01 15:12:32'),
(52, 13003, 2002, 2, '2024-2025', 7.0, 7.0, 6.5, 7.0, 7.5, 7.5, 7.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(53, 13003, 2003, 2, '2024-2025', 7.0, 7.0, 7.0, 7.0, 7.5, 7.5, 7.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(54, 13003, 2004, 2, '2024-2025', 8.0, 8.0, 7.5, 8.0, 8.5, 8.5, 8.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(55, 13003, 2005, 2, '2024-2025', 6.5, 7.0, 6.5, 7.0, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(56, 13003, 2006, 2, '2024-2025', 7.0, 7.0, 7.0, 7.0, 7.5, 7.5, 7.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(57, 13003, 2007, 2, '2024-2025', 7.0, 7.0, 7.0, 7.0, 7.5, 7.5, 7.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(58, 13003, 2008, 2, '2024-2025', 8.0, 8.5, 8.0, 8.0, 8.5, 8.5, 8.3, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(59, 13003, 2009, 2, '2024-2025', 7.5, 7.0, 7.0, 7.5, 7.5, 8.0, 7.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(60, 13003, 2010, 2, '2024-2025', 7.5, 7.5, 7.0, 7.5, 8.0, 8.0, 7.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(61, 13004, 2001, 1, '2024-2025', 6.0, 6.0, 5.5, 6.0, 6.5, 6.5, 6.4, NULL, '2024-11-17 12:00:00', '2025-11-25 13:04:03'),
(62, 13004, 2002, 1, '2024-2025', 5.5, 6.0, 5.5, 6.0, 6.0, 6.5, 5.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(63, 13004, 2003, 1, '2024-2025', 6.0, 5.5, 6.0, 5.5, 6.0, 6.5, 5.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(64, 13004, 2004, 1, '2024-2025', 7.0, 6.5, 7.0, 6.5, 7.0, 7.5, 6.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(65, 13004, 2005, 1, '2024-2025', 5.5, 5.5, 5.0, 5.5, 6.0, 6.0, 5.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(66, 13004, 2006, 1, '2024-2025', 6.0, 5.5, 6.0, 5.5, 6.0, 6.5, 5.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(67, 13004, 2007, 1, '2024-2025', 5.5, 6.0, 5.5, 6.0, 6.0, 6.5, 5.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(68, 13004, 2008, 1, '2024-2025', 7.0, 7.0, 6.5, 7.0, 7.5, 7.5, 7.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(69, 13004, 2009, 1, '2024-2025', 6.0, 6.0, 5.5, 6.0, 6.5, 6.5, 6.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(70, 13004, 2010, 1, '2024-2025', 6.5, 6.0, 6.5, 6.0, 6.5, 7.0, 6.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(71, 13004, 2001, 2, '2024-2025', 6.5, 6.0, 6.0, 6.5, 6.5, 7.0, 6.7, NULL, '2024-11-17 12:00:00', '2025-12-01 15:12:32'),
(72, 13004, 2002, 2, '2024-2025', 6.0, 6.0, 5.5, 6.0, 6.5, 6.5, 6.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(73, 13004, 2003, 2, '2024-2025', 6.0, 6.0, 6.0, 6.0, 6.5, 6.5, 6.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(74, 13004, 2004, 2, '2024-2025', 7.0, 7.0, 6.5, 7.0, 7.5, 7.5, 7.1, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(75, 13004, 2005, 2, '2024-2025', 5.5, 6.0, 5.5, 6.0, 6.0, 6.5, 5.9, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(76, 13004, 2006, 2, '2024-2025', 6.0, 6.0, 6.0, 6.0, 6.5, 6.5, 6.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(77, 13004, 2007, 2, '2024-2025', 6.0, 6.0, 6.0, 6.0, 6.5, 6.5, 6.2, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(78, 13004, 2008, 2, '2024-2025', 7.0, 7.5, 7.0, 7.0, 7.5, 7.5, 7.3, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(79, 13004, 2009, 2, '2024-2025', 6.5, 6.0, 6.0, 6.5, 6.5, 7.0, 6.4, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(80, 13004, 2010, 2, '2024-2025', 6.5, 6.5, 6.0, 6.5, 7.0, 7.0, 6.6, NULL, '2024-11-17 12:00:00', '2024-11-17 12:00:00'),
(81, 13005, 2001, 1, '2024-2025', 2.0, 3.0, 5.0, NULL, 7.0, NULL, 5.8, 'Cần cố gắng thêm.', '2025-11-25 12:53:56', '2025-11-25 13:14:43'),
(82, 13006, 2001, 1, '2024-2025', 10.0, 10.0, 10.0, NULL, 10.0, 10.0, 10.0, NULL, '2025-11-25 13:04:03', '2025-11-25 13:04:03'),
(83, 13008, 2001, 1, '2024-2025', 2.0, 3.0, 4.0, 8.0, 10.0, NULL, 8.1, 'Cần phải cố gắng hơn.', '2025-11-25 13:08:21', '2025-11-26 04:33:30'),
(84, 13007, 2001, 1, '2024-2025', 5.0, 5.0, 10.0, 10.0, 8.0, 7.0, 7.4, 'Tốt, chăm chỉ nhưng cần cố gắng hơn.', '2025-11-25 13:28:32', '2025-11-25 13:28:32'),
(85, 13010, 2001, 1, '2024-2025', 9.0, 8.0, 8.0, 9.0, NULL, NULL, 8.5, 'Tốt, giỏi, ngoan.', '2025-11-26 04:40:30', '2025-11-26 04:40:30'),
(86, 13005, 2001, 2, '2024-2025', 4.0, NULL, NULL, NULL, NULL, NULL, 4.0, NULL, '2025-12-01 15:12:32', '2025-12-01 15:12:32'),
(87, 13009, 2001, 1, '2024-2025', 6.0, 3.0, NULL, NULL, NULL, NULL, 4.5, NULL, '2025-12-02 15:20:59', '2025-12-04 11:22:21'),
(88, 13011, 2001, 1, '2024-2025', 9.0, NULL, NULL, NULL, NULL, NULL, 9.0, NULL, '2025-12-05 14:10:42', '2025-12-05 14:10:42');

--
-- Bẫy `bangdiem`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatDanhHieu_BangDiem` AFTER UPDATE ON `bangdiem` FOR EACH ROW BEGIN
    DECLARE _namHoc VARCHAR(20);
    DECLARE _maHS INT;
    
    SET _maHS = NEW.maHS;
    SET _namHoc = NEW.namHoc;
    
    -- Chỉ xếp loại khi học kỳ 2 (cuối năm)
    IF NEW.hocKy = 2 AND NEW.tbDiem IS NOT NULL THEN
        -- Kiểm tra xem đã có đủ dữ liệu học lực và hạnh kiểm chưa
        IF EXISTS (
            SELECT 1 FROM hocluc 
            WHERE maHS = _maHS AND namHoc = _namHoc
        ) AND EXISTS (
            SELECT 1 FROM hanhkiem 
            WHERE maHS = _maHS AND hocKy = 2 AND namHoc = _namHoc
        ) THEN
            CALL sp_XepLoaiDanhHieu(_maHS, _namHoc);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `baocao_danop`
--

CREATE TABLE `baocao_danop` (
  `maBaoCao` int(11) NOT NULL,
  `tenBaoCao` varchar(255) NOT NULL,
  `loaiBaoCao` varchar(100) NOT NULL,
  `tenFile` varchar(255) NOT NULL,
  `duongDan` varchar(500) NOT NULL,
  `ngayNop` timestamp NOT NULL DEFAULT current_timestamp(),
  `moTa` text DEFAULT NULL,
  `ngayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `maGV` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `baocao_danop`
--

INSERT INTO `baocao_danop` (`maBaoCao`, `tenBaoCao`, `loaiBaoCao`, `tenFile`, `duongDan`, `ngayNop`, `moTa`, `ngayTao`, `ngayCapNhat`, `maGV`) VALUES
(5, 'baocao1', 'hoc-tap', 'baocao_7_1762038477.xls', 'C:\\xampp\\htdocs\\PTUD_1\\school_management/uploads/reports/1762138759_69081a8771c5f.xls', '2025-11-03 02:59:19', 'báo cáo', '2025-11-03 02:59:19', '2025-11-03 02:59:19', 6001),
(6, 'báo cáo 2', 'chuyen-can', 'bao_cao_chuyen-can_2025-11-03_04-23-39.xls', 'C:\\xampp\\htdocs\\PTUD_1\\school_management/uploads/reports/1762140246_6908205687202.xls', '2025-11-03 03:24:06', 'chuyên cần', '2025-11-03 03:24:06', '2025-11-03 03:24:06', 6001),
(7, 'báo cáo 11/10', 'hoc-tap', '1762138759_69081a8771c5f.xls', 'C:\\xampp\\htdocs\\PTUD_1\\school_management/uploads/reports/1762763180_6911a1acca08e.xls', '2025-11-10 08:26:20', '', '2025-11-10 08:26:20', '2025-11-10 08:26:20', 6003),
(8, 'Báo cáo kết quả học tập - 18/11/2025', 'hoc-tap', 'bao_cao_chuyen-can_2025-11-03_04-23-39.xls', 'C:\\xampp\\htdocs\\PTUD_1\\school_management/uploads/reports/1763408044_691b78ac4eda3.xls', '2025-11-17 19:34:04', 'hh', '2025-11-17 19:34:04', '2025-11-17 19:34:04', 6001);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bgh`
--

CREATE TABLE `bgh` (
  `maBGH` int(11) NOT NULL,
  `hoTen` varchar(255) DEFAULT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` varchar(10) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maYeuCau` int(11) DEFAULT NULL,
  `maDeThi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bgh`
--

INSERT INTO `bgh` (`maBGH`, `hoTen`, `ngaySinh`, `gioiTinh`, `email`, `soDienThoai`, `maTaiKhoan`, `maYeuCau`, `maDeThi`) VALUES
(5001, 'BGH 1', '1970-01-01', 'Nam', 'bgh1@example.com', '0921000001', 4501, NULL, NULL),
(5002, 'BGH 2', '1971-02-02', 'Nu', 'bgh2@example.com', '0921000002', 4502, NULL, NULL),
(5003, 'BGH 3', '1972-03-03', 'Nam', 'bgh3@example.com', '0921000003', 4503, NULL, NULL),
(5004, 'BGH 4', '1973-04-04', 'Nu', 'bgh4@example.com', '0921000004', 4504, NULL, NULL),
(5005, 'BGH 5', '1974-05-05', 'Nam', 'bgh5@example.com', '0921000005', 4505, NULL, NULL),
(5006, 'BGH 6', '1975-06-06', 'Nu', 'bgh6@example.com', '0921000006', 4506, NULL, NULL),
(5007, 'BGH 7', '1976-07-07', 'Nam', 'bgh7@example.com', '0921000007', 4507, NULL, NULL),
(5008, 'BGH 8', '1977-08-08', 'Nu', 'bgh8@example.com', '0921000008', 4508, NULL, NULL),
(5009, 'BGH 9', '1978-09-09', 'Nam', 'bgh9@example.com', '0921000009', 4509, NULL, NULL),
(5010, 'BGH 10', '1979-10-10', 'Nu', 'bgh10@example.com', '0921000010', 4510, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `buoiday_thucte`
--

CREATE TABLE `buoiday_thucte` (
  `maBuoiDay` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `ngayDay` date NOT NULL,
  `tietBatDau` tinyint(4) NOT NULL,
  `tietKetThuc` tinyint(4) NOT NULL,
  `soTietDay` int(11) NOT NULL,
  `chuDe` varchar(255) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `trangThai` enum('Hoan_thanh','Nghi_day','Day_bu') DEFAULT 'Hoan_thanh',
  `ghiChu` text DEFAULT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `ngayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `buoiday_thucte`
--

INSERT INTO `buoiday_thucte` (`maBuoiDay`, `maGV`, `maLop`, `maMonHoc`, `ngayDay`, `tietBatDau`, `tietKetThuc`, `soTietDay`, `chuDe`, `noiDung`, `trangThai`, `ghiChu`, `hocKy`, `namHoc`, `ngayTao`) VALUES
(1, 6001, 10001, 2001, '2024-09-01', 1, 2, 2, 'Hàm số bậc nhất', 'Định nghĩa và tính chất hàm số bậc nhất', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(2, 6001, 10001, 2001, '2024-09-03', 1, 2, 2, 'Đồ thị hàm số bậc nhất', 'Cách vẽ đồ thị hàm số bậc nhất', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(3, 6001, 10001, 2001, '2024-09-05', 1, 2, 2, 'Bài tập hàm số bậc nhất', 'Giải bài tập về hàm số bậc nhất', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(4, 6001, 10001, 2001, '2024-09-08', 1, 2, 2, 'Hàm số bậc hai', 'Định nghĩa hàm số bậc hai', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(5, 6001, 10001, 2001, '2024-09-10', 1, 2, 2, 'Parabol', 'Đồ thị hàm số bậc hai - Parabol', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(6, 6002, 10002, 2002, '2024-09-01', 3, 4, 2, 'Văn học Trung đại', 'Tổng quan văn học Trung đại Việt Nam', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(7, 6002, 10002, 2002, '2024-09-03', 3, 4, 2, 'Truyện Kiều', 'Phân tích đoạn trích Truyện Kiều', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(8, 6003, 10003, 2003, '2024-09-02', 2, 3, 2, 'Present Simple', 'Thì hiện tại đơn trong tiếng Anh', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55'),
(9, 6003, 10003, 2003, '2024-09-04', 2, 3, 2, 'Present Continuous', 'Thì hiện tại tiếp diễn', 'Hoan_thanh', NULL, 1, '2024-2025', '2025-11-02 12:42:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhhieu`
--

CREATE TABLE `danhhieu` (
  `maDanhHieu` int(11) NOT NULL,
  `tenDanhHieu` varchar(100) NOT NULL,
  `hocLucToiThieu` varchar(50) DEFAULT NULL,
  `hanhKiemToiThieu` varchar(50) DEFAULT NULL,
  `soViPhamToiDa` int(11) DEFAULT 0,
  `diemTBToiThieu` decimal(5,2) DEFAULT NULL,
  `yeuCauKhenThuong` tinyint(1) DEFAULT 0 COMMENT '1: Yêu cầu có khen thưởng, 0: Không bắt buộc',
  `moTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhhieu`
--

INSERT INTO `danhhieu` (`maDanhHieu`, `tenDanhHieu`, `hocLucToiThieu`, `hanhKiemToiThieu`, `soViPhamToiDa`, `diemTBToiThieu`, `yeuCauKhenThuong`, `moTa`) VALUES
(1001, 'Học sinh xuất sắc', 'Tot', 'Tot', 0, 9.00, 0, 'Học lực Tốt, rèn luyện Tốt và có ít nhất 6 môn TB cả năm từ 9.0 trở lên'),
(1002, 'Học sinh giỏi', 'Tot', 'Tot', 0, NULL, 0, 'Học lực Tốt và rèn luyện Tốt theo Thông tư 22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dethi`
--

CREATE TABLE `dethi` (
  `maDeThi` int(11) NOT NULL,
  `tenDeThi` varchar(255) NOT NULL,
  `loaiDeThi` varchar(100) NOT NULL DEFAULT 'de-thi',
  `tenFile` varchar(255) DEFAULT NULL,
  `duongDan` varchar(500) DEFAULT NULL,
  `moTa` text DEFAULT NULL,
  `ngayTao` timestamp NULL DEFAULT current_timestamp(),
  `ngayCapNhat` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `maGV` int(11) DEFAULT NULL COMMENT 'Giáo viên tạo đề',
  `maMonHoc` int(11) NOT NULL,
  `maKhoi` int(11) DEFAULT NULL COMMENT 'Khối học của đề thi',
  `maKyThi` int(11) DEFAULT NULL COMMENT 'Kỳ thi mà đề thi được chọn',
  `hocKy` tinyint(4) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `trangThai` enum('Chuaduyet','Daduyet','Dachon','Tuchoi') NOT NULL DEFAULT 'Chuaduyet' COMMENT 'Chuaduyet: GV vừa gửi, Daduyet: TTBM duyệt, Dachon: BGH chọn vào kỳ thi, Tuchoi: TTBM từ chối',
  `maTTBM` int(11) DEFAULT NULL COMMENT 'Trưởng tổ bộ môn duyệt đề',
  `ngayDuyet` datetime DEFAULT NULL COMMENT 'Ngày TTBM duyệt/từ chối đề',
  `lyDoDuyet` text DEFAULT NULL COMMENT 'Lý do TTBM duyệt hoặc từ chối đề'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dethi`
--

INSERT INTO `dethi` (`maDeThi`, `tenDeThi`, `loaiDeThi`, `tenFile`, `duongDan`, `moTa`, `ngayTao`, `ngayCapNhat`, `maGV`, `maMonHoc`, `maKhoi`, `maKyThi`, `hocKy`, `namHoc`, `trangThai`, `maTTBM`, `ngayDuyet`, `lyDoDuyet`) VALUES
(18001, 'Đề thi Toán HK1 - Khối 6', 'de-thi', 'de_thi_toan_hk1_mau.txt', '/uploads/exams/de_thi_toan_hk1_mau.txt', 'Đề thi giữa kỳ 1 môn Toán khối 6', '2025-11-20 08:00:00', '2025-12-16 18:06:07', 6001, 2001, 11001, 1, 1, '2024-2025', 'Dachon', 7001, '2025-11-26 08:30:00', 'Đề thi chuẩn cấp THCS, đúng cấu trúc ma trận'),
(18002, 'Đề thi Lý HK1 - Khối 6', 'de-thi', 'de_thi_vatly_hk1_mau.txt', '/uploads/exams/de_thi_vatly_hk1_mau.txt', 'Đề thi giữa kỳ 1 môn Vật lý khối 6', '2025-11-20 08:30:00', '2025-11-28 10:15:00', 6002, 2002, 11001, 1, 1, '2024-2025', 'Dachon', 7002, '2025-11-26 09:15:00', 'Đề đạt chuẩn THCS, phù hợp lứa tuổi'),
(18003, 'Đề thi Hóa HK1 - Khối 7', 'de-thi', 'de_thi_hoahoc_hk1_mau.txt', '/uploads/exams/de_thi_hoahoc_hk1_mau.txt', 'Đề thi giữa kỳ 1 môn Hóa học khối 7', '2025-11-20 09:00:00', '2025-11-28 10:30:00', 6003, 2003, 11002, 3, 1, '2024-2025', 'Dachon', 7003, '2025-11-26 10:00:00', 'Đề thi hợp lý cấp THCS, đúng chương trình'),
(18004, 'Đề thi Văn HK1 - Khối 7', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Ngữ văn khối 7', '2025-11-20 09:30:00', '2025-11-28 11:00:00', 6004, 2004, 11002, 3, 1, '2024-2025', 'Dachon', 7004, '2025-11-26 11:20:00', 'Nội dung phù hợp học sinh THCS'),
(18005, 'Đề thi Anh HK1 - Khối 8', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Tiếng Anh khối 8', '2025-11-20 10:00:00', '2025-11-28 11:30:00', 6005, 2009, 11003, 4, 1, '2024-2025', 'Dachon', 7005, '2025-11-27 08:00:00', 'Đề hay cho cấp THCS, duyệt'),
(18006, 'Đề thi Sinh HK1 - Khối 8', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Sinh học khối 8', '2025-11-21 08:00:00', '2025-11-28 13:00:00', 6006, 2007, 11003, 4, 1, '2024-2025', 'Dachon', 7006, '2025-11-27 09:30:00', 'Phê duyệt, đúg chuẩn THCS'),
(18007, 'Đề thi Địa HK1 - Khối 8', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Địa lý khối 8', '2025-11-21 08:30:00', '2025-11-28 13:30:00', 6007, 2006, 11003, 4, 1, '2024-2025', 'Dachon', 7007, '2025-11-27 10:45:00', 'Đạt yêu cầu chuyên môn THCS'),
(18008, 'Đề thi Tin HK1 - Khối 9', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Tin học khối 9', '2025-11-21 09:00:00', '2025-11-28 15:00:00', 6008, 2008, 11004, NULL, 1, '2024-2025', 'Dachon', 7008, '2025-11-27 14:00:00', 'Chấp thuận, đề tốt cho THCS'),
(18009, 'Đề thi GDCD HK1 - Khối 9', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn GDCD khối 9', '2025-11-21 09:30:00', '2025-11-28 15:30:00', 6009, 2010, 11004, NULL, 1, '2024-2025', 'Dachon', 7009, '2025-11-27 15:30:00', 'Đồng ý duyệt cho cấp 2'),
(18010, 'Đề thi Lịch sử HK1 - Khối 9', 'de-thi', NULL, NULL, 'Đề thi giữa kỳ 1 môn Lịch sử khối 9', '2025-11-21 10:00:00', '2025-11-28 16:00:00', 6010, 2005, 11004, NULL, 1, '2024-2025', 'Dachon', 7010, '2025-11-28 08:00:00', 'OK, phê duyệt đề THCS'),
(21001, 'Đề thi cuối kỳ môn Toán HK1 - Khối 6', 'de-thi-cuoi-ky', NULL, NULL, 'Đề thi cuối kỳ 1 môn Toán 2024-2025', '2025-11-26 14:22:07', '2025-12-02 14:00:00', 6001, 2001, 11001, 2, 1, '2024-2025', 'Dachon', 7001, '2025-12-02 10:00:00', 'Đề thi tốt THCS, đúng cấu trúc'),
(21002, 'Đề thi cuối kỳ môn Lý HK1 - Khối 7', 'de-thi-cuoi-ky', NULL, NULL, 'Đề thi cuối kỳ 1 môn Vật lý', '2025-11-26 14:22:42', '2025-12-02 14:15:00', 6002, 2002, 11002, 3, 1, '2024-2025', 'Dachon', 7002, '2025-12-02 11:30:00', 'Đồng ý duyệt đề THCS này'),
(21003, 'Đề thi cuối kỳ môn Hóa HK1 - Khối 8', 'de-thi-cuoi-ky', NULL, NULL, 'Đề thi cuối kỳ 1 môn Hóa học', '2025-11-27 10:00:00', '2025-12-04 16:30:00', 6003, 2003, 11003, 4, 1, '2024-2025', 'Dachon', 7003, '2025-12-04 16:00:00', 'Đạt chuẩn chuyên môn THCS'),
(21004, 'Đề thi thử môn Toán HK1', 'de-thi-thu', NULL, NULL, 'Đề thi thử giữa kỳ 1', '2025-11-26 14:49:03', '2025-12-03 09:00:00', 6001, 2001, 11001, NULL, 1, '2024-2025', 'Daduyet', 7001, '2025-12-03 09:00:00', 'Đề thi thử hợp lý cho THCS, có thể sử dụng'),
(21005, 'Đề thi cuối kỳ môn Văn HK2 - Khối 7', 'de-thi-cuoi-ky', NULL, NULL, 'Đề thi cuối kỳ 2 môn Ngữ văn', '2025-12-04 11:23:08', '2025-12-05 08:30:00', 6004, 2004, 11002, NULL, 2, '2024-2025', 'Daduyet', 7004, '2025-12-05 08:30:00', 'Chấp thuận, đề hay cho cấp 2'),
(21006, 'Đề thi Toán HK2 - phương án 2', 'de-thi', NULL, NULL, 'Đề thi cuối kỳ 2 Toán - dự phòng', '2025-12-01 10:00:00', '2025-12-03 14:00:00', 6001, 2001, 11001, NULL, 2, '2024-2025', 'Daduyet', 7001, '2025-12-03 14:00:00', 'Đề dự phòng tốt THCS, giữ lại'),
(21007, 'Đề thi Anh HK2 - Khối 8', 'de-thi', NULL, NULL, 'Đề thi cuối kỳ 2 Tiếng Anh', '2025-12-02 08:00:00', '2025-12-04 10:00:00', 6005, 2009, 11003, NULL, 2, '2024-2025', 'Daduyet', 7005, '2025-12-04 10:00:00', 'Đề đạt yêu cầu cấp THCS, phê duyệt'),
(21008, 'Đề thi Toán - Khối 6', 'de-thi', NULL, NULL, 'Đề thi mới nộp, chưa duyệt', '2025-12-05 14:22:13', '2025-12-06 08:23:43', 6001, 2001, 11001, NULL, 1, '2024-2025', 'Chuaduyet', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dethi_kythi`
--

CREATE TABLE `dethi_kythi` (
  `maDeThiKyThi` int(11) NOT NULL,
  `maDeThi` int(11) NOT NULL COMMENT 'Đề thi đã được duyệt',
  `maKyThi` int(11) NOT NULL COMMENT 'Kỳ thi',
  `maBGH` int(11) DEFAULT NULL COMMENT 'BGH chọn đề vào kỳ thi',
  `ngayChon` datetime DEFAULT current_timestamp() COMMENT 'Ngày BGH chọn đề',
  `ghiChu` text DEFAULT NULL COMMENT 'Ghi chú của BGH khi chọn đề'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dethi_kythi`
--

INSERT INTO `dethi_kythi` (`maDeThiKyThi`, `maDeThi`, `maKyThi`, `maBGH`, `ngayChon`, `ghiChu`) VALUES
(2, 18001, 1, 5001, '2025-12-17 01:06:07', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giaovien`
--

CREATE TABLE `giaovien` (
  `maGV` int(11) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` enum('Nam','Nu','Khac') DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `soDienThoai` varchar(50) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `toBoMon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giaovien`
--

INSERT INTO `giaovien` (`maGV`, `hoTen`, `ngaySinh`, `gioiTinh`, `email`, `soDienThoai`, `maTaiKhoan`, `toBoMon`) VALUES
(6001, 'Nguyen Van G1', '1978-01-15', 'Nam', 'gv1@example.com', '0901000001', 4101, 'Toán'),
(6002, 'Tran Thi G2', '1980-02-20', 'Nu', 'gv2@example.com', '0901000002', 4102, 'Vật lý'),
(6003, 'Le Van G3', '1985-03-10', 'Nam', 'gv3@example.com', '0901000003', 4103, 'Hóa'),
(6004, 'Pham Thi G4', '1979-04-12', 'Nu', 'gv4@example.com', '0901000004', 4104, 'Văn'),
(6005, 'Hoang Van G5', '1982-05-05', 'Nam', 'gv5@example.com', '0901000005', 4105, 'Anh'),
(6006, 'Vu Thi G6', '1983-06-06', 'Nu', 'gv6@example.com', '0901000006', 4106, 'Sinh'),
(6007, 'Do Van G7', '1977-07-07', 'Nam', 'gv7@example.com', '0901000007', 4107, 'Địa'),
(6008, 'Bui Thi G8', '1988-08-08', 'Nu', 'gv8@example.com', '0901000008', 4108, 'Tin'),
(6009, 'Pham Van G9', '1981-09-09', 'Nam', 'gv9@example.com', '0901000009', 4109, 'Thể dục'),
(6010, 'Dang Thi G10', '1984-10-10', 'Nu', 'gv10@example.com', '0901000010', 4110, 'GDCD'),
(6011, 'Tran Van CN1', '1980-01-20', 'Nam', 'gvcn1@example.com', '0901000011', 4111, 'Toán'),
(6012, 'Nguyen Thi CN2', '1982-03-15', 'Nu', 'gvcn2@example.com', '0901000012', 4112, 'Văn'),
(6013, 'Le Van CN3', '1979-05-10', 'Nam', 'gvcn3@example.com', '0901000013', 4113, 'Toán'),
(6014, 'Pham Thi CN4', '1981-07-25', 'Nu', 'gvcn4@example.com', '0901000014', 4114, 'Văn'),
(6015, 'Hoang Van CN5', '1983-09-12', 'Nam', 'gvcn5@example.com', '0901000015', 4115, 'Toán'),
(6016, 'Vu Thi CN6', '1980-11-30', 'Nu', 'gvcn6@example.com', '0901000016', 4116, 'Văn'),
(6017, 'Do Van CN7', '1978-02-18', 'Nam', 'gvcn7@example.com', '0901000017', 4117, 'Toán'),
(6018, 'Bui Thi CN8', '1984-04-22', 'Nu', 'gvcn8@example.com', '0901000018', 4118, 'Văn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hanhkiem`
--

CREATE TABLE `hanhkiem` (
  `maHanhKiem` int(11) NOT NULL,
  `maHS` int(11) NOT NULL COMMENT 'Mã học sinh',
  `hocKy` tinyint(4) NOT NULL COMMENT '1 hoặc 2',
  `namHoc` varchar(20) NOT NULL COMMENT 'VD: 2024-2025',
  `soBuoiNghiCoPhep` int(11) DEFAULT 0,
  `soBuoiNghiKhongPhep` int(11) DEFAULT 0,
  `nhanXet` text DEFAULT NULL COMMENT 'Nhận xét của giáo viên',
  `loaiHK` varchar(50) DEFAULT NULL COMMENT 'Tốt, Khá, Đạt, Chưa đạt',
  `soLanViPhamNhe` int(11) DEFAULT 0,
  `soLanViPhamTB` int(11) DEFAULT 0,
  `soLanViPhamNang` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hanhkiem`
--

INSERT INTO `hanhkiem` (`maHanhKiem`, `maHS`, `hocKy`, `namHoc`, `soBuoiNghiCoPhep`, `soBuoiNghiKhongPhep`, `nhanXet`, `loaiHK`, `soLanViPhamNhe`, `soLanViPhamTB`, `soLanViPhamNang`) VALUES
(9101, 13001, 1, '2024-2025', 1, 0, 'Chấp hành nội quy tốt, tích cực tham gia', 'Tốt', 0, 0, 0),
(9102, 13002, 1, '2024-2025', 2, 0, 'Cần rèn luyện thêm về ý thức kỷ luật', 'Tốt', 0, 0, 0),
(9103, 13003, 1, '2024-2025', 3, 1, 'Có vi phạm nhỏ, cần nhắc nhở', 'Khá', 1, 0, 0),
(9104, 13004, 1, '2024-2025', 6, 2, 'Cần cải thiện ý thức học tập', 'Khá', 0, 2, 0),
(9105, 13005, 1, '2024-2025', 10, 4, 'Vi phạm nhiều, cần giáo viên chủ nhiệm theo dõi', 'Chưa đạt', 0, 2, 2),
(9106, 13006, 1, '2024-2025', 0, 0, 'Gương mẫu về mọi mặt', 'Tốt', 0, 0, 0),
(9107, 13007, 1, '2024-2025', 1, 0, 'Tốt, chấp hành tốt nội quy', 'Tốt', 0, 0, 0),
(9108, 13008, 1, '2024-2025', 2, 0, 'Khá, cần giữ gìn', 'Tốt', 0, 0, 0),
(9109, 13009, 1, '2024-2025', 4, 1, 'Trung bình, cần rèn luyện', 'Khá', 1, 0, 0),
(9110, 13010, 1, '2024-2025', 3, 0, 'Khá, cần giảm số buổi nghỉ', 'Tốt', 0, 0, 0),
(9111, 13011, 1, '2024-2025', 0, 0, 'Tốt, không vi phạm', 'Tốt', 0, 0, 0),
(9112, 13012, 1, '2024-2025', 2, 0, 'Khá tốt', 'Tốt', 0, 0, 0),
(9113, 13013, 1, '2024-2025', 4, 1, 'Trung bình', 'Khá', 1, 0, 0),
(9114, 13014, 1, '2024-2025', 5, 2, 'Yếu, cần cải thiện', 'Đạt', 1, 1, 0),
(9115, 13015, 1, '2024-2025', 8, 3, 'Yếu kém', 'Chưa đạt', 0, 2, 1),
(9116, 13016, 1, '2024-2025', 0, 0, 'Gương mẫu', 'Tốt', 0, 0, 0),
(9117, 13017, 1, '2024-2025', 1, 0, 'Tốt', 'Tốt', 0, 0, 0),
(9118, 13018, 1, '2024-2025', 1, 0, 'Tốt', 'Tốt', 0, 0, 0),
(9119, 13019, 1, '2024-2025', 3, 0, 'Khá', 'Khá', 1, 0, 0),
(9120, 13020, 1, '2024-2025', 2, 0, 'Khá', 'Tốt', 0, 0, 0),
(9121, 13001, 2, '2024-2025', 0, 0, 'Tiến bộ rõ rệt, gương mẫu', 'Tốt', 0, 0, 0),
(9122, 13002, 2, '2024-2025', 1, 0, 'Cải thiện tốt, đạt loại tốt', 'Tốt', 0, 0, 0),
(9123, 13003, 2, '2024-2025', 2, 0, 'Có tiến bộ so với HK1', 'Tốt', 0, 0, 0),
(9124, 13004, 2, '2024-2025', 5, 1, 'Có cố gắng nhưng chưa đủ', 'Khá', 1, 0, 0),
(9125, 13005, 2, '2024-2025', 8, 3, 'Vẫn còn nhiều vi phạm', 'Chưa đạt', 0, 2, 1),
(9126, 13006, 2, '2024-2025', 0, 0, 'Luôn gương mẫu', 'Tốt', 0, 0, 0),
(9127, 13007, 2, '2024-2025', 0, 0, 'Duy trì tốt', 'Tốt', 0, 0, 0),
(9128, 13008, 2, '2024-2025', 1, 0, 'Tiến bộ, đạt loại tốt', 'Tốt', 0, 0, 0),
(9129, 13009, 2, '2024-2025', 3, 0, 'Cải thiện đáng kể', 'Tốt', 0, 0, 0),
(9130, 13010, 2, '2024-2025', 2, 0, 'Khá ổn định', 'Tốt', 0, 0, 0),
(9131, 13011, 2, '2024-2025', 0, 0, 'Tốt, giữ vững phẩm chất', 'Tốt', 0, 0, 0),
(9132, 13012, 2, '2024-2025', 1, 0, 'Tiến bộ', 'Tốt', 0, 0, 0),
(9133, 13013, 2, '2024-2025', 3, 0, 'Cải thiện', 'Tốt', 0, 0, 0),
(9134, 13014, 2, '2024-2025', 4, 1, 'Chưa nhiều tiến bộ', 'Khá', 1, 0, 0),
(9135, 13015, 2, '2024-2025', 7, 2, 'Vẫn còn yếu', 'Chưa đạt', 0, 1, 1),
(9136, 13016, 2, '2024-2025', 0, 0, 'Xuất sắc', 'Tốt', 0, 0, 0),
(9137, 13017, 2, '2024-2025', 0, 0, 'Tốt', 'Tốt', 0, 0, 0),
(9138, 13018, 2, '2024-2025', 0, 0, 'Tốt', 'Tốt', 0, 0, 0),
(9139, 13019, 2, '2024-2025', 2, 0, 'Khá, tiến bộ', 'Tốt', 0, 0, 0),
(9140, 13020, 2, '2024-2025', 1, 0, 'Cải thiện tốt', 'Tốt', 0, 0, 0);

--
-- Bẫy `hanhkiem`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatDanhHieu_HanhKiem` AFTER UPDATE ON `hanhkiem` FOR EACH ROW BEGIN
    DECLARE _namHoc VARCHAR(20);
    DECLARE _maHS INT;
    
    SET _maHS = NEW. maHS;
    SET _namHoc = NEW.namHoc;
    
    -- Chỉ xếp loại khi học kỳ 2 (cuối năm)
    IF NEW.hocKy = 2 AND NEW. loaiHK IS NOT NULL THEN
        -- Kiểm tra xem đã có đủ dữ liệu học lực chưa
        IF EXISTS (
            SELECT 1 FROM hocluc 
            WHERE maHS = _maHS AND namHoc = _namHoc AND diemTBCaNam IS NOT NULL
        ) THEN
            CALL sp_XepLoaiDanhHieu(_maHS, _namHoc);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hanhkiem_boloc`
--

CREATE TABLE `hanhkiem_boloc` (
  `id` int(11) NOT NULL,
  `maGV` int(11) NOT NULL COMMENT 'Mã giáo viên cấu hình',
  `loaiHK` varchar(50) NOT NULL COMMENT 'Loại hạnh kiểm: Tốt, Khá, Đạt, Chưa đạt',
  `maxNghiCoPhep` int(11) NOT NULL DEFAULT 0 COMMENT 'Số buổi nghỉ có phép tối đa',
  `maxNghiKhongPhep` int(11) NOT NULL DEFAULT 0 COMMENT 'Số buổi nghỉ không phép tối đa',
  `maxViPhamNhe` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần vi phạm nhẹ tối đa',
  `maxViPhamTB` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần vi phạm TB tối đa',
  `maxViPhamNang` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lần vi phạm nặng tối đa',
  `minHocLuc` varchar(50) DEFAULT NULL COMMENT 'Học lực tối thiểu yêu cầu',
  `ngayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hanhkiem_boloc`
--

INSERT INTO `hanhkiem_boloc` (`id`, `maGV`, `loaiHK`, `maxNghiCoPhep`, `maxNghiKhongPhep`, `maxViPhamNhe`, `maxViPhamTB`, `maxViPhamNang`, `minHocLuc`, `ngayTao`, `ngayCapNhat`) VALUES
(9, 6001, 'Tốt', 0, 0, 0, 0, 0, 'Tốt', '2025-12-02 14:10:54', '2025-12-02 14:10:54'),
(10, 6001, 'Khá', 1, 1, 0, 0, 0, 'Tốt', '2025-12-02 14:10:54', '2025-12-02 14:10:54'),
(11, 6001, 'Đạt', 0, 0, 0, 0, 0, 'Đạt', '2025-12-02 14:10:54', '2025-12-02 14:10:54'),
(12, 6001, 'Chưa đạt', 999, 999, 999, 999, 999, '', '2025-12-02 14:10:54', '2025-12-02 14:10:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hocluc`
--

CREATE TABLE `hocluc` (
  `maHocLuc` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `diemTBHK1` decimal(5,2) DEFAULT NULL,
  `diemTBHK2` decimal(5,2) DEFAULT NULL,
  `diemTBCaNam` decimal(5,2) DEFAULT NULL,
  `loaiHocLuc` varchar(50) DEFAULT NULL,
  `soMonDuoi5` int(11) DEFAULT 0,
  `nhanXet` text DEFAULT NULL COMMENT 'Nhận xét của giáo viên'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hocluc`
--

INSERT INTO `hocluc` (`maHocLuc`, `maHS`, `namHoc`, `diemTBHK1`, `diemTBHK2`, `diemTBCaNam`, `loaiHocLuc`, `soMonDuoi5`, `nhanXet`) VALUES
(9001, 13001, '2024-2025', 8.50, 8.70, 8.63, 'Tot', 0, 'Học sinh học tập tốt, có tiến bộ qua các học kỳ'),
(9002, 13002, '2024-2025', 7.00, 7.20, 7.13, 'Khá', 0, 'Cần cố gắng hơn ở một số môn'),
(9003, 13003, '2024-2025', 5.80, 6.00, 5.93, 'Dat', 1, 'Có tiến bộ nhưng cần học thêm'),
(9004, 13004, '2024-2025', 4.50, 4.80, 4.70, 'Chua dat', 3, 'Cần nỗ lực nhiều hơn'),
(9005, 13005, '2024-2025', 2.80, 3.20, 3.07, 'Chua dat', 5, 'Học lực yếu kém, cần bồi dưỡng'),
(9006, 13006, '2024-2025', 9.40, 9.50, 9.47, 'Tot', 0, 'Học sinh xuất sắc toàn diện'),
(9007, 13007, '2024-2025', 7.50, 7.60, 7.57, 'Khá', 0, 'Học lực khá ổn định'),
(9008, 13008, '2024-2025', 8.00, 8.30, 8.20, 'Tot', 0, 'Học sinh giỏi, tích cực học tập'),
(9009, 13009, '2024-2025', 6.00, 6.50, 6.33, 'Dat', 2, 'Trung bình, cần cố gắng thêm'),
(9010, 13010, '2024-2025', 7.80, 8.00, 7.93, 'Khá', 0, 'Học lực khá, có tiến bộ'),
(9011, 13011, '2024-2025', 8.20, 8.40, 8.33, 'Tot', 0, 'Học sinh giỏi'),
(9012, 13012, '2024-2025', 7.10, 7.30, 7.23, 'Khá', 0, 'Học lực khá'),
(9013, 13013, '2024-2025', 6.50, 6.70, 6.63, 'Khá', 1, 'Khá, cần cải thiện'),
(9014, 13014, '2024-2025', 5.00, 5.50, 5.33, 'Dat', 2, 'Trung bình'),
(9015, 13015, '2024-2025', 3.50, 4.00, 3.83, 'Chua dat', 4, 'Học lực yếu'),
(9016, 13016, '2024-2025', 9.20, 9.30, 9.27, 'Tot', 0, 'Xuất sắc'),
(9017, 13017, '2024-2025', 7.70, 7.90, 7.83, 'Khá', 0, 'Khá tốt'),
(9018, 13018, '2024-2025', 8.50, 8.60, 8.57, 'Tot', 0, 'Giỏi'),
(9019, 13019, '2024-2025', 6.20, 6.80, 6.60, 'Khá', 1, 'Khá'),
(9020, 13020, '2024-2025', 7.60, 7.80, 7.73, 'Khá', 0, 'Khá ổn định');

--
-- Bẫy `hocluc`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatDanhHieu_HocLuc` AFTER UPDATE ON `hocluc` FOR EACH ROW BEGIN
    DECLARE _namHoc VARCHAR(20);
    DECLARE _maHS INT;
    
    SET _maHS = NEW.maHS;
    SET _namHoc = NEW.namHoc;
    
    -- Chỉ xếp loại khi có điểm TB cả năm
    IF NEW.diemTBCaNam IS NOT NULL THEN
        -- Kiểm tra xem đã có đủ dữ liệu hạnh kiểm chưa
        IF EXISTS (
            SELECT 1 FROM hanhkiem 
            WHERE maHS = _maHS AND hocKy = 2 AND namHoc = _namHoc AND loaiHK IS NOT NULL
        ) THEN
            CALL sp_XepLoaiDanhHieu(_maHS, _namHoc);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hocsinh`
--

CREATE TABLE `hocsinh` (
  `maHS` int(11) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` enum('Nam','Nu','Khac') DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `trangThaiHocTap` varchar(50) DEFAULT 'danghoc',
  `maDanhHieu` int(11) DEFAULT NULL COMMENT 'Danh hiệu cuối năm học',
  `maPH` int(11) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hocsinh`
--

INSERT INTO `hocsinh` (`maHS`, `hoTen`, `ngaySinh`, `gioiTinh`, `diaChi`, `trangThaiHocTap`, `maDanhHieu`, `maPH`, `maTaiKhoan`, `maLop`) VALUES
(13001, 'HS A1', '2008-01-01', 'Nam', 'HN', 'danghoc', 1002, 8001, 4201, 10002),
(13002, 'HS A2', '2008-02-02', 'Nu', 'HN', 'danghoc', NULL, 8002, 4202, 10001),
(13003, 'HS A3', '2008-03-03', 'Nam', 'HN', 'danghoc', NULL, 8003, 4203, 10001),
(13004, 'HS A4', '2008-04-04', 'Nu', 'HN', 'danghoc', NULL, 8004, 4204, 10001),
(13005, 'HS A5', '2008-05-05', 'Nam', 'HN', 'danghoc', NULL, 8005, 4205, 10001),
(13006, 'HS A6', '2008-06-06', 'Nu', 'HN', 'danghoc', 1002, 8006, 4206, 10001),
(13007, 'HS A7', '2008-07-07', 'Nam', 'HN', 'danghoc', NULL, 8007, 4207, 10001),
(13008, 'HS A8', '2008-08-08', 'Nu', 'HN', 'danghoc', 1002, 8008, 4208, 10001),
(13009, 'HS A9', '2008-09-09', 'Nam', 'HN', 'danghoc', NULL, 8009, 4209, 10001),
(13010, 'HS A10', '2008-10-10', 'Nu', 'HN', 'danghoc', NULL, 8010, 4210, 10002),
(13011, 'HS B1', '2007-11-11', 'Nam', 'HN', 'danghoc', 1002, 8001, 4211, 10001),
(13012, 'HS B2', '2007-12-12', 'Nu', 'HN', 'danghoc', NULL, 8002, 4212, 10001),
(13013, 'HS B3', '2007-01-13', 'Nam', 'HN', 'danghoc', NULL, 8003, 4213, 10001),
(13014, 'HS B4', '2007-02-14', 'Nu', 'HN', 'danghoc', NULL, 8004, 4214, 10007),
(13015, 'HS B5', '2007-03-15', 'Nam', 'HN', 'danghoc', NULL, 8005, 4215, 10008),
(13016, 'HS B6', '2007-04-16', 'Nu', 'HN', 'danghoc', 1002, 8006, 4216, 10008),
(13017, 'HS B7', '2007-05-17', 'Nam', 'HN', 'danghoc', NULL, 8007, 4217, 10009),
(13018, 'HS B8', '2007-06-18', 'Nu', 'HN', 'danghoc', 1002, 8008, 4218, 10009),
(13019, 'HS B9', '2007-07-19', 'Nam', 'HN', 'danghoc', NULL, 8009, 4219, 10010),
(13020, 'HS B10', '2007-08-20', 'Nu', 'HN', 'danghoc', NULL, 8010, 4220, 10010);

--
-- Bẫy `hocsinh`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatSiSoLop_Delete` AFTER DELETE ON `hocsinh` FOR EACH ROW BEGIN
  IF OLD.maLop IS NOT NULL THEN
    UPDATE lophoc SET siSo = GREATEST(siSo - 1, 0) WHERE maLop = OLD.maLop;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_CapNhatSiSoLop_Insert` AFTER INSERT ON `hocsinh` FOR EACH ROW BEGIN
  IF NEW.maLop IS NOT NULL THEN
    UPDATE lophoc SET siSo = siSo + 1 WHERE maLop = NEW.maLop;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_CapNhatSiSoLop_Update` AFTER UPDATE ON `hocsinh` FOR EACH ROW BEGIN
  IF OLD.maLop IS NOT NULL AND (NEW.maLop IS NULL OR NEW.maLop <> OLD.maLop) THEN
    UPDATE lophoc SET siSo = GREATEST(siSo - 1, 0) WHERE maLop = OLD.maLop;
  END IF;
  IF NEW.maLop IS NOT NULL AND (OLD.maLop IS NULL OR NEW.maLop <> OLD.maLop) THEN
    UPDATE lophoc SET siSo = siSo + 1 WHERE maLop = NEW.maLop;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hocsinh_danhhieu`
--

CREATE TABLE `hocsinh_danhhieu` (
  `maHS` int(11) NOT NULL,
  `maDanhHieu` int(11) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `hocKy` tinyint(1) NOT NULL,
  `ghiChu` text DEFAULT NULL,
  `ngayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hocsinh_danhhieu`
--

INSERT INTO `hocsinh_danhhieu` (`maHS`, `maDanhHieu`, `namHoc`, `hocKy`, `ghiChu`, `ngayTao`, `ngayCapNhat`) VALUES
(13001, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09'),
(13006, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09'),
(13008, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09'),
(13011, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09'),
(13016, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09'),
(13018, 1002, '2024-2025', 2, 'Tự động xếp loại cuối năm học', '2025-12-06 03:31:09', '2025-12-06 03:31:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoso`
--

CREATE TABLE `hoso` (
  `maHoSo` int(11) NOT NULL,
  `ngayTao` date DEFAULT NULL,
  `doiTuongHienThi` varchar(255) DEFAULT NULL,
  `danToc` varchar(255) DEFAULT NULL,
  `tonGiao` varchar(255) DEFAULT NULL,
  `anhDaiDien` varchar(255) DEFAULT NULL,
  `diaChiTamTru` varchar(255) DEFAULT NULL,
  `diaChiThuongTru` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoso`
--

INSERT INTO `hoso` (`maHoSo`, `ngayTao`, `doiTuongHienThi`, `danToc`, `tonGiao`, `anhDaiDien`, `diaChiTamTru`, `diaChiThuongTru`) VALUES
(5001, '2020-01-01', 'Gv A', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5002, '2020-02-01', 'Gv B', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5003, '2020-03-01', 'HS1', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5004, '2020-04-01', 'HS2', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5005, '2020-05-01', 'PH1', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5006, '2020-06-01', 'HS3', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5007, '2020-07-01', 'HS4', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5008, '2020-08-01', 'HS5', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5009, '2020-09-01', 'HS6', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi'),
(5010, '2020-10-01', 'HS7', 'Kinh', 'Phat', '', 'Hanoi', 'Hanoi');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hosogiaovien`
--

CREATE TABLE `hosogiaovien` (
  `maGV` int(11) NOT NULL,
  `hoTen` varchar(255) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `trinhDoChuyenMon` varchar(255) DEFAULT NULL,
  `CCCD_CMND` varchar(255) DEFAULT NULL,
  `ngayVaoTruong` date DEFAULT NULL,
  `hoatDongChinhTri` varchar(255) DEFAULT NULL,
  `noiKetNap` varchar(255) DEFAULT NULL,
  `maHoSo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hosohocsinh`
--

CREATE TABLE `hosohocsinh` (
  `maHS` int(11) DEFAULT NULL,
  `ngayVaoTruong` date DEFAULT NULL,
  `doiTuongUuTien` varchar(255) DEFAULT NULL,
  `dienUuTien` varchar(255) DEFAULT NULL,
  `doiTuongChinhSach` varchar(255) DEFAULT NULL,
  `maHoSo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hosophuhuynh`
--

CREATE TABLE `hosophuhuynh` (
  `maPH` int(11) NOT NULL,
  `hoTen` varchar(255) DEFAULT NULL,
  `soCMT` varchar(20) DEFAULT NULL,
  `soDienThoai` varchar(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `moQuanHe` varchar(255) DEFAULT NULL,
  `maHS` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hosophuhuynh`
--

INSERT INTO `hosophuhuynh` (`maPH`, `hoTen`, `soCMT`, `soDienThoai`, `email`, `diaChi`, `moQuanHe`, `maHS`) VALUES
(8001, 'Nguyen Thi PH1', '111111111', '0911000001', 'ph1@example.com', 'HN', 'Mẹ', NULL),
(8002, 'Tran Van PH2', '222222222', '0911000002', 'ph2@example.com', 'HN', 'Cha', NULL),
(8003, 'Le Thi PH3', '333333333', '0911000003', 'ph3@example.com', 'HN', 'Mẹ', NULL),
(8004, 'Pham Van PH4', '444444444', '0911000004', 'ph4@example.com', 'HN', 'Cha', NULL),
(8005, 'Hoang Thi PH5', '555555555', '0911000005', 'ph5@example.com', 'HN', 'Mẹ', NULL),
(8006, 'Vu Van PH6', '666666666', '0911000006', 'ph6@example.com', 'HN', 'Cha', NULL),
(8007, 'Do Thi PH7', '777777777', '0911000007', 'ph7@example.com', 'HN', 'Mẹ', NULL),
(8008, 'Bui Van PH8', '888888888', '0911000008', 'ph8@example.com', 'HN', 'Cha', NULL),
(8009, 'Pham Thi PH9', '999999999', '0911000009', 'ph9@example.com', 'HN', 'Mẹ', NULL),
(8010, 'Dang Van PH10', '1010101010', '0911000010', 'ph10@example.com', 'HN', 'Cha', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kehoach_giangday`
--

CREATE TABLE `kehoach_giangday` (
  `maKeHoach` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `tongSoTietKeHoach` int(11) NOT NULL DEFAULT 0,
  `ghiChu` text DEFAULT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `kehoach_giangday`
--

INSERT INTO `kehoach_giangday` (`maKeHoach`, `maGV`, `maLop`, `maMonHoc`, `hocKy`, `namHoc`, `tongSoTietKeHoach`, `ghiChu`, `ngayTao`, `ngayCapNhat`) VALUES
(1, 6001, 10001, 2001, 1, '2024-2025', 70, 'Kế hoạch giảng dạy Toán lớp 6A1', '2025-11-02 12:42:55', '2025-11-10 10:00:50'),
(2, 6001, 10001, 2001, 2, '2024-2025', 75, 'Kế hoạch giảng dạy Toán lớp 6A1', '2025-11-02 12:42:55', '2025-11-10 10:00:55'),
(3, 6002, 10002, 2002, 1, '2024-2025', 45, 'Kế hoạch giảng dạy Văn lớp 6A2', '2025-11-02 12:42:55', '2025-11-10 10:00:59'),
(4, 6002, 10002, 2002, 2, '2024-2025', 50, 'Kế hoạch giảng dạy Văn lớp 6A2', '2025-11-02 12:42:55', '2025-11-10 10:01:03'),
(5, 6003, 10003, 2003, 1, '2024-2025', 35, 'Kế hoạch giảng dạy Anh lớp 6A3', '2025-11-02 12:42:55', '2025-11-10 10:01:10'),
(6, 6003, 10003, 2003, 2, '2024-2025', 40, 'Kế hoạch giảng dạy Anh lớp 6A3', '2025-11-02 12:42:55', '2025-11-10 10:01:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khenthuong`
--

CREATE TABLE `khenthuong` (
  `maKhenThuong` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayKhenThuong` date DEFAULT NULL,
  `hinhThuc` varchar(150) DEFAULT NULL COMMENT 'Giấy khen, Bằng khen, Huy chương,...',
  `noiDung` text DEFAULT NULL COMMENT 'Nội dung khen thưởng',
  `capKhenThuong` varchar(50) DEFAULT NULL COMMENT 'truong, huyen, tinh, quocgia',
  `linhVuc` varchar(100) DEFAULT NULL COMMENT 'Học tập, Thể thao, Văn nghệ, Hoạt động xã hội,...',
  `hocKy` tinyint(4) DEFAULT NULL COMMENT '1 hoặc 2',
  `namHoc` varchar(20) DEFAULT NULL COMMENT 'VD: 2024-2025'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khenthuong`
--

INSERT INTO `khenthuong` (`maKhenThuong`, `maHS`, `ngayKhenThuong`, `hinhThuc`, `noiDung`, `capKhenThuong`, `linhVuc`, `hocKy`, `namHoc`) VALUES
(15001, 13001, '2024-10-15', 'Giấy khen', 'Học sinh xuất sắc học kỳ 1', 'truong', 'Học tập', 1, '2024-2025'),
(15002, 13006, '2024-10-20', 'Bằng khen', 'Học sinh xuất sắc toàn diện', 'huyen', 'Học tập', 1, '2024-2025'),
(15003, 13008, '2024-10-18', 'Giấy khen', 'Học sinh giỏi cấp trường', 'truong', 'Học tập', 1, '2024-2025'),
(15004, 13016, '2024-10-22', 'Bằng khen', 'Học sinh xuất sắc cấp tỉnh', 'tinh', 'Học tập', 1, '2024-2025'),
(15005, 13018, '2024-10-19', 'Giấy khen', 'Học sinh giỏi', 'truong', 'Học tập', 1, '2024-2025'),
(15006, 13007, '2024-09-15', 'Giấy khen', 'Giải nhất Olympic Toán cấp huyện', 'huyen', 'Học tập', 1, '2024-2025'),
(15007, 13002, '2024-11-05', 'Giấy khen', 'Học sinh tiến bộ', 'truong', 'Học tập', 1, '2024-2025'),
(15008, 13010, '2024-09-20', 'Giấy khen', 'Tích cực hoạt động văn nghệ', 'truong', 'Văn nghệ', 1, '2024-2025'),
(15009, 13011, '2024-10-10', 'Giấy khen', 'Học sinh giỏi', 'truong', 'Học tập', 1, '2024-2025'),
(15010, 13017, '2024-10-12', 'Giấy khen', 'Tích cực hoạt động thể thao', 'truong', 'Thể thao', 1, '2024-2025'),
(15011, 13001, '2025-03-15', 'Giấy khen', 'Học sinh xuất sắc học kỳ 2', 'truong', 'Học tập', 2, '2024-2025'),
(15012, 13006, '2025-03-20', 'Bằng khen', 'Học sinh xuất sắc toàn diện cả năm', 'tinh', 'Học tập', 2, '2024-2025'),
(15013, 13008, '2025-03-18', 'Giấy khen', 'Học sinh giỏi học kỳ 2', 'truong', 'Học tập', 2, '2024-2025'),
(15014, 13016, '2025-03-22', 'Bằng khen', 'Thủ khoa khối 6', 'tinh', 'Học tập', 2, '2024-2025'),
(15015, 13018, '2025-03-19', 'Giấy khen', 'Học sinh giỏi cả năm', 'truong', 'Học tập', 2, '2024-2025'),
(15016, 13002, '2025-03-10', 'Giấy khen', 'Học sinh tiến bộ xuất sắc', 'truong', 'Học tập', 2, '2024-2025'),
(15017, 13012, '2025-03-12', 'Giấy khen', 'Học sinh tiến bộ', 'truong', 'Học tập', 2, '2024-2025'),
(15018, 13020, '2025-03-14', 'Giấy khen', 'Học sinh tiến bộ', 'truong', 'Học tập', 2, '2024-2025'),
(15019, 13011, '2025-02-20', 'Giấy khen', 'Giải nhì Olympic Toán cấp huyện', 'huyen', 'Học tập', 2, '2024-2025'),
(15020, 13007, '2025-02-25', 'Giấy khen', 'Tích cực hoạt động xã hội', 'truong', 'Hoạt động xã hội', 2, '2024-2025');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoi`
--

CREATE TABLE `khoi` (
  `maKhoi` int(11) NOT NULL,
  `khoiLop` varchar(10) NOT NULL,
  `soLuongHocSinh` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khoi`
--

INSERT INTO `khoi` (`maKhoi`, `khoiLop`, `soLuongHocSinh`) VALUES
(11001, '6', 0),
(11002, '7', 0),
(11003, '8', 0),
(11004, '9', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kyluat`
--

CREATE TABLE `kyluat` (
  `maKyLuat` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayKyLuat` date DEFAULT NULL,
  `hinhThuc` varchar(150) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `mucDoViPham` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `kyluat`
--

INSERT INTO `kyluat` (`maKyLuat`, `maHS`, `ngayKyLuat`, `hinhThuc`, `noiDung`, `mucDoViPham`) VALUES
(15101, 13011, '2024-09-01', 'Nhắc nhở', 'Đi trễ', 'nhe'),
(15102, 13012, '2024-09-02', 'Cảnh cáo', 'Gây ồn', 'trungbinh'),
(15103, 13013, '2024-09-03', 'Kỷ luật', 'Phá hoại', 'nang'),
(15104, 13014, '2024-09-04', 'Nhắc nhở', 'Không đồng phục', 'nhe'),
(15105, 13015, '2024-09-05', 'Cảnh cáo', 'Không làm bài', 'trungbinh'),
(15106, 13016, '2024-09-06', 'Kỷ luật', 'Bạo lực', 'nang'),
(15107, 13017, '2024-09-07', 'Nhắc nhở', 'Đi muộn', 'nhe'),
(15108, 13018, '2024-09-08', 'Cảnh cáo', 'Nói tục', 'trungbinh'),
(15109, 13019, '2024-09-09', 'Kỷ luật', 'Vi phạm nặng', 'ratnang'),
(15110, 13020, '2024-09-10', 'Nhắc nhở', 'Thiếu tập trung', 'nhe');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kythi`
--

CREATE TABLE `kythi` (
  `maKyThi` int(11) NOT NULL,
  `tenKyThi` varchar(255) NOT NULL,
  `loaiKyThi` enum('Giữa kỳ','Cuối kỳ','Thi lại') NOT NULL,
  `maKhoi` int(11) NOT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `ngayBatDau` date DEFAULT NULL,
  `ngayKetThuc` date DEFAULT NULL,
  `trangThai` enum('Dang_lap','Dang_thi','Ket_thuc') DEFAULT 'Dang_lap',
  `nguoiTao` int(11) DEFAULT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `moTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `kythi`
--

INSERT INTO `kythi` (`maKyThi`, `tenKyThi`, `loaiKyThi`, `maKhoi`, `hocKy`, `namHoc`, `ngayBatDau`, `ngayKetThuc`, `trangThai`, `nguoiTao`, `ngayTao`, `ngayCapNhat`, `moTa`) VALUES
(1, 'Kỳ thi giữa kỳ 1 - Khối 6', 'Giữa kỳ', 11001, 1, '2024-2025', '2024-11-01', '2024-11-15', 'Dang_lap', 5001, '2025-11-25 14:07:03', '2025-12-06 15:38:19', 'Kỳ thi giữa học kỳ 1 năm học 2024-2025 khối 10'),
(2, 'Kỳ thi cuối kỳ 1 - Khối 6', 'Cuối kỳ', 11001, 1, '2024-2025', '2025-01-05', '2025-01-20', 'Dang_lap', 5001, '2025-11-25 14:07:03', '2025-12-06 15:38:33', 'Kỳ thi cuối học kỳ 1 năm học 2024-2025 khối 10'),
(3, 'Kỳ thi giữa kỳ 1 - Khối 7', 'Giữa kỳ', 11002, 1, '2024-2025', '2024-11-01', '2024-11-15', 'Dang_lap', 5001, '2025-11-25 14:07:03', '2025-12-06 15:38:25', 'Kỳ thi giữa học kỳ 1 năm học 2024-2025 khối 11'),
(4, 'Kỳ thi cuối kỳ 1 - Khối 8', 'Cuối kỳ', 11003, 1, '2024-2025', '2025-01-05', '2025-01-20', 'Dang_lap', 5001, '2025-11-25 14:07:03', '2025-12-06 15:38:42', 'Kỳ thi cuối học kỳ 1 năm học 2024-2025 khối 12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich`
--

CREATE TABLE `lich` (
  `maLich` int(11) NOT NULL,
  `moTa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lich`
--

INSERT INTO `lich` (`maLich`, `moTa`) VALUES
(24001, 'Lịch thi HK1 Toán'),
(24002, 'Lịch chấm HK1'),
(24003, 'Lịch học bù');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichchamthi`
--

CREATE TABLE `lichchamthi` (
  `maLich` int(11) NOT NULL,
  `ngayChamThi` date DEFAULT NULL,
  `thoiGianChamThi` time DEFAULT NULL,
  `hinhThucChamThi` varchar(50) DEFAULT NULL,
  `maPhong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichchamthi`
--

INSERT INTO `lichchamthi` (`maLich`, `ngayChamThi`, `thoiGianChamThi`, `hinhThucChamThi`, `maPhong`) VALUES
(24002, '2025-06-10', '13:00:00', 'Tập thể', 12001);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichcoithi`
--

CREATE TABLE `lichcoithi` (
  `maLich` int(11) NOT NULL,
  `ngayThi` date DEFAULT NULL,
  `thoiGianThi` time DEFAULT NULL,
  `maPhong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichcoithi`
--

INSERT INTO `lichcoithi` (`maLich`, `ngayThi`, `thoiGianThi`, `maPhong`) VALUES
(24001, '2025-06-01', '08:00:00', 12001);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichday`
--

CREATE TABLE `lichday` (
  `maLichDay` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `maPhong` int(11) DEFAULT NULL,
  `thu` tinyint(4) NOT NULL,
  `tietBatDau` tinyint(4) NOT NULL,
  `tietKetThuc` tinyint(4) NOT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `trangThai` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichday`
--

INSERT INTO `lichday` (`maLichDay`, `maGV`, `maLop`, `maMonHoc`, `maPhong`, `thu`, `tietBatDau`, `tietKetThuc`, `hocKy`, `namHoc`, `trangThai`) VALUES
(16001, 6001, 10001, 2001, 12001, 2, 1, 2, 1, '2024-2025', 'active'),
(16002, 6002, 10002, 2002, 12002, 3, 2, 3, 1, '2024-2025', 'active'),
(16003, 6003, 10003, 2003, 12003, 4, 3, 4, 1, '2024-2025', 'active'),
(16004, 6004, 10004, 2004, 12004, 5, 4, 5, 1, '2024-2025', 'active'),
(16005, 6005, 10005, 2005, 12005, 2, 1, 2, 1, '2024-2025', 'active'),
(16006, 6006, 10006, 2006, 12006, 3, 2, 3, 1, '2024-2025', 'active'),
(16007, 6007, 10007, 2007, 12007, 4, 3, 4, 1, '2024-2025', 'active'),
(16008, 6008, 10008, 2008, 12008, 5, 4, 5, 1, '2024-2025', 'active'),
(16009, 6009, 10009, 2009, 12009, 2, 1, 2, 1, '2024-2025', 'active'),
(16010, 6010, 10010, 2010, 12010, 3, 2, 3, 1, '2024-2025', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsunhapxuat`
--

CREATE TABLE `lichsunhapxuat` (
  `maLichSu` int(11) NOT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `hanhDong` varchar(50) DEFAULT NULL,
  `bangDuLieu` varchar(100) DEFAULT NULL,
  `thoiGian` datetime DEFAULT current_timestamp(),
  `ghiChu` text DEFAULT NULL,
  `diaChiIP` varchar(45) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `duLieuCu` text DEFAULT NULL,
  `duLieuMoi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichsunhapxuat`
--

INSERT INTO `lichsunhapxuat` (`maLichSu`, `maTaiKhoan`, `hanhDong`, `bangDuLieu`, `thoiGian`, `ghiChu`, `diaChiIP`, `userAgent`, `duLieuCu`, `duLieuMoi`) VALUES
(28001, 4001, 'dangnhap', 'taikhoan', '2025-11-01 11:48:54', NULL, NULL, NULL, NULL, NULL),
(28002, 4003, 'themtk', 'giaovien', '2025-11-01 11:48:54', NULL, NULL, NULL, NULL, NULL),
(31000, 4001, 'them', 'taikhoan', '2025-11-17 23:27:33', 'Tạo tài khoản mới: khai (Nguyễn Quang Khải)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31001, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:29:57', 'trangThai: active -> disabled; ', NULL, NULL, NULL, NULL),
(31002, 4001, 'xoa', 'taikhoan', '2025-11-17 23:29:57', 'Xóa (vô hiệu hóa) tài khoản ID: 5004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31003, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:30:03', 'trangThai: active -> disabled; ', NULL, NULL, NULL, NULL),
(31004, 4001, 'xoa', 'taikhoan', '2025-11-17 23:30:03', 'Xóa (vô hiệu hóa) tài khoản ID: 5003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31005, 4001, 'xoa', 'taikhoan', '2025-11-17 23:30:08', 'Xóa (vô hiệu hóa) tài khoản ID: 5003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31006, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:45:28', 'trangThai: disabled -> active; ', NULL, NULL, NULL, NULL),
(31007, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:45:28', 'Cập nhật tài khoản ID: 5004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31008, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:45:33', 'email: NULL -> ; trangThai: disabled -> active; ', NULL, NULL, NULL, NULL),
(31009, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:45:33', 'Cập nhật tài khoản ID: 5003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31010, 4001, 'them', 'taikhoan', '2025-11-17 23:48:58', 'Tạo tài khoản mới: khai1 (ád)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31011, 4001, 'capnhat', 'taikhoan', '2025-11-17 23:49:07', 'trangThai: active -> locked; ', NULL, NULL, NULL, NULL),
(31012, 4001, 'khoa', 'taikhoan', '2025-11-17 23:49:07', 'Khóa tài khoản ID: 5005. Lý do: Khóa bởi quản trị viên', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31013, 4001, 'resetmatkhau', 'taikhoan', '2025-11-17 23:49:10', 'Reset mật khẩu cho tài khoản ID: 5005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, NULL),
(31014, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31015, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31016, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31017, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31018, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31019, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31020, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31021, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31022, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31023, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:55:05', 'loaiTaiKhoan: giaovien -> ; ', NULL, NULL, NULL, NULL),
(31024, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31025, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31026, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31027, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31028, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31029, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31030, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31031, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31032, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31033, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31034, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:57:48', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31035, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31036, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31037, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31038, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31039, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31040, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31041, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31042, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31043, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31044, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:58:38', 'loaiTaiKhoan: ttbm -> ; ', NULL, NULL, NULL, NULL),
(31045, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31046, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31047, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31048, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31049, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31050, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31051, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31052, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31053, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31054, NULL, 'capnhat', 'taikhoan', '2025-11-18 00:59:24', 'loaiTaiKhoan:  -> ttbm; ', NULL, NULL, NULL, NULL),
(31055, 4001, 'capnhat', 'taikhoan', '2025-12-01 22:45:32', 'trangThai: locked -> active; ', NULL, NULL, NULL, NULL),
(31056, 4001, 'mokhoa', 'taikhoan', '2025-12-01 22:45:32', 'Mở khóa tài khoản ID: 5005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL),
(31057, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:23:31', 'hoTen: ád -> Khải đẹp trai; ', NULL, NULL, NULL, NULL),
(31058, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:23:31', 'Cập nhật tài khoản ID: 5005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, NULL),
(31059, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:26:42', 'hoTen: Username Account -> Khải đẹp trai; email:  -> NULL; trangThai: active -> disabled; ', NULL, NULL, NULL, NULL),
(31060, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:26:42', 'Cập nhật tài khoản ID: 5003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, NULL),
(31061, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:27:38', 'trangThai: disabled -> active; ', NULL, NULL, NULL, NULL),
(31062, 4001, 'capnhat', 'taikhoan', '2025-12-19 15:27:38', 'Cập nhật tài khoản ID: 5003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, NULL),
(31063, 4001, 'them', 'taikhoan', '2025-12-19 15:28:33', 'Tạo tài khoản mới: peovn (Nguyễn Thành Trung)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsuxulydethi`
--

CREATE TABLE `lichsuxulydethi` (
  `maLichSu` int(11) NOT NULL,
  `maDeThi` int(11) NOT NULL COMMENT 'Đề thi được xử lý',
  `nguoiXuLy` int(11) NOT NULL COMMENT 'Người thực hiện hành động (maTaiKhoan)',
  `loaiXuLy` enum('TaoDe','DuyetDe','TuChoiDe','ChonVaoKyThi','BoChon','SuaDe') NOT NULL COMMENT 'Loại hành động xử lý',
  `trangThaiCu` enum('Chuaduyet','Daduyet','Dachon','Tuchoi') DEFAULT NULL COMMENT 'Trạng thái trước khi xử lý',
  `trangThaiMoi` enum('Chuaduyet','Daduyet','Dachon','Tuchoi') NOT NULL COMMENT 'Trạng thái sau khi xử lý',
  `lyDo` text DEFAULT NULL COMMENT 'Lý do duyệt/từ chối/chọn đề',
  `ngayXuLy` timestamp NULL DEFAULT current_timestamp() COMMENT 'Thời gian thực hiện'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsu_xuly_yeucau`
--

CREATE TABLE `lichsu_xuly_yeucau` (
  `maLichSu` int(11) NOT NULL,
  `maYeuCau` int(11) NOT NULL,
  `maBGH` int(11) NOT NULL,
  `trangThaiCu` varchar(50) DEFAULT NULL,
  `trangThaiMoi` varchar(50) NOT NULL,
  `lyDoTuChoi` text DEFAULT NULL,
  `ngayXuLy` datetime DEFAULT current_timestamp(),
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichsu_xuly_yeucau`
--

INSERT INTO `lichsu_xuly_yeucau` (`maLichSu`, `maYeuCau`, `maBGH`, `trangThaiCu`, `trangThaiMoi`, `lyDoTuChoi`, `ngayXuLy`, `ghiChu`) VALUES
(1, 23007, 5001, 'Choxuly', 'Dachapnhan', NULL, '2025-11-02 09:30:00', 'Đã xác minh giấy xác nhận từ bệnh viện.  Chấp nhận nghỉ phép 2 ngày.'),
(2, 23008, 5001, 'Choxuly', 'Dachapnhan', NULL, '2025-11-04 10:15:00', 'Yêu cầu hợp lý, có giấy mời đám cưới.  Đã thông báo trước đủ thời gian.'),
(3, 23009, 5002, 'Choxuly', 'Dachapnhan', NULL, '2025-11-06 14:20:00', 'Đã kiểm tra biên bản thi đua.  Điểm thưởng hợp lệ.  Đã cập nhật điểm vào hệ thống.'),
(4, 23010, 5002, 'Choxuly', 'Dachapnhan', NULL, '2025-11-09 11:00:00', 'Đã xác minh lỗi hệ thống qua file backup.  Chấp nhận sửa điểm và đã cập nhật.'),
(5, 23011, 5001, 'Choxuly', 'Tuchoi', 'Thời điểm xin nghỉ trùng với giai đoạn ôn tập cuối kỳ, ảnh hưởng đến tiến độ giảng dạy.  Lý do cá nhân không đủ căn cứ để chấp nhận.', '2025-11-03 16:30:00', 'Đã trao đổi trực tiếp với giáo viên. Đề nghị sắp xếp lại thời gian nghỉ phép sau kỳ thi. '),
(6, 23012, 5003, 'Choxuly', 'Tuchoi', 'Không có căn cứ rõ ràng để sửa điểm. Việc tăng điểm dựa trên hoàn cảnh cá nhân không phù hợp với quy chế đánh giá học sinh. ', '2025-11-08 09:45:00', 'Đã giải thích cho giáo viên về quy định đánh giá học sinh theo Thông tư 22/2021/TT-BGDĐT.'),
(7, 24003, 5001, 'Choxuly', 'Dachapnhan', NULL, '2025-12-17 00:50:19', 'ok'),
(8, 24004, 5001, 'Choxuly', 'Tuchoi', 'không được', '2025-12-17 01:01:05', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lophoc`
--

CREATE TABLE `lophoc` (
  `maLop` int(11) NOT NULL,
  `tenLop` varchar(50) NOT NULL,
  `siSo` int(11) DEFAULT 0,
  `namHoc` varchar(20) DEFAULT NULL,
  `maPhong` int(11) DEFAULT NULL,
  `maKhoi` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL,
  `maTKB` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lophoc`
--

INSERT INTO `lophoc` (`maLop`, `tenLop`, `siSo`, `namHoc`, `maPhong`, `maKhoi`, `maGV`, `maTKB`) VALUES
(10001, '6A1', 11, '2024-2025', 12001, 11001, 6011, NULL),
(10002, '6A2', 2, '2024-2025', 12002, 11001, 6012, NULL),
(10003, '7A1', 0, '2024-2025', 12003, 11002, 6013, NULL),
(10004, '7A2', 0, '2024-2025', 12004, 11002, 6014, NULL),
(10005, '8A1', 1, '2024-2025', 12005, 11003, 6015, NULL),
(10006, '8A2', 2, '2024-2025', 12006, 11003, 6016, NULL),
(10007, '9A1', 1, '2024-2025', 12007, 11004, 6017, NULL),
(10008, '9A2', 2, '2024-2025', 12008, 11004, 6018, NULL),
(10009, '9A3', 2, '2024-2025', 12009, 11004, NULL, NULL),
(10010, '9A4', 2, '2024-2025', 12010, 11004, 6002, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `monhoc`
--

CREATE TABLE `monhoc` (
  `maMonHoc` int(11) NOT NULL,
  `tenMonHoc` varchar(150) NOT NULL,
  `soTiet` int(11) DEFAULT NULL,
  `moTa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `monhoc`
--

INSERT INTO `monhoc` (`maMonHoc`, `tenMonHoc`, `soTiet`, `moTa`) VALUES
(2001, 'Toán', 140, 'Toán cơ bản'),
(2002, 'Vật lý', 120, 'Vật lý 10'),
(2003, 'Hóa học', 120, 'Hóa 10'),
(2004, 'Ngữ văn', 140, 'Ngữ văn'),
(2005, 'Lịch sử', 80, 'Lịch sử Việt Nam'),
(2006, 'Địa lý', 80, 'Địa lý'),
(2007, 'Sinh học', 100, 'Sinh 10'),
(2008, 'Tin học', 60, 'Tin cơ bản'),
(2009, 'Tiếng Anh', 140, 'Ngoại ngữ'),
(2010, 'GDCD', 40, 'Giáo dục công dân');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nghihoc`
--

CREATE TABLE `nghihoc` (
  `maNghiHoc` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayNghi` date DEFAULT NULL,
  `hocKy` tinyint(1) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `loaiNghi` enum('cophep','khongphep') DEFAULT 'cophep',
  `lyDo` text DEFAULT NULL,
  `nguoiDuyet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nghihoc`
--

INSERT INTO `nghihoc` (`maNghiHoc`, `maHS`, `ngayNghi`, `hocKy`, `namHoc`, `loaiNghi`, `lyDo`, `nguoiDuyet`) VALUES
(15001, 13001, '2024-10-05', 1, '2024-2025', 'cophep', 'Khám bệnh định kỳ', 6001),
(15002, 13001, '2024-10-12', 1, '2024-2025', 'cophep', 'Đi khám răng', 6001),
(15003, 13002, '2024-10-08', 1, '2024-2025', 'cophep', 'Đau bụng', 6001),
(15004, 13002, '2024-10-15', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6001),
(15005, 13003, '2024-10-10', 1, '2024-2025', 'cophep', 'Ốm sốt', 6002),
(15006, 13003, '2024-10-18', 1, '2024-2025', 'cophep', 'Khám mắt', 6002),
(15007, 13004, '2024-10-07', 1, '2024-2025', 'cophep', 'Đau đầu', 6002),
(15008, 13004, '2024-10-20', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6002),
(15009, 13005, '2024-10-09', 1, '2024-2025', 'cophep', 'Gia đình có việc', 6003),
(15010, 13005, '2024-10-16', 1, '2024-2025', 'cophep', 'Khám tổng quát', 6003),
(15011, 13006, '2024-10-11', 1, '2024-2025', 'cophep', 'Đau bụng', 6003),
(15012, 13006, '2024-10-22', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6003),
(15013, 13007, '2024-10-13', 1, '2024-2025', 'cophep', 'Cảm cúm', 6004),
(15014, 13007, '2024-10-25', 1, '2024-2025', 'cophep', 'Đi tiêm phòng', 6004),
(15015, 13008, '2024-10-14', 1, '2024-2025', 'cophep', 'Đau răng', 6004),
(15016, 13008, '2024-10-27', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6004),
(15017, 13009, '2024-10-17', 1, '2024-2025', 'cophep', 'Khám mắt', 6005),
(15018, 13009, '2024-10-28', 1, '2024-2025', 'cophep', 'Ốm sốt', 6005),
(15019, 13010, '2024-10-19', 1, '2024-2025', 'cophep', 'Gia đình có tang', 6005),
(15020, 13010, '2024-10-30', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6005),
(15021, 13011, '2024-10-21', 1, '2024-2025', 'cophep', 'Đau bụng', 6006),
(15022, 13011, '2024-11-02', 1, '2024-2025', 'cophep', 'Khám định kỳ', 6006),
(15023, 13012, '2024-10-23', 1, '2024-2025', 'cophep', 'Cảm cúm', 6006),
(15024, 13012, '2024-11-04', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6006),
(15025, 13013, '2024-10-24', 1, '2024-2025', 'cophep', 'Đau đầu', 6007),
(15026, 13013, '2024-11-05', 1, '2024-2025', 'cophep', 'Khám tai mũi họng', 6007),
(15027, 13014, '2024-10-26', 1, '2024-2025', 'cophep', 'Ốm sốt', 6007),
(15028, 13014, '2024-11-07', 1, '2024-2025', 'khongphep', 'Vắng mặt không phép', 6007),
(15029, 13015, '2024-10-29', 1, '2024-2025', 'cophep', 'Gia đình có việc', 6008),
(15030, 13015, '2024-11-08', 1, '2024-2025', 'cophep', 'Đau răng', 6008);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhomnguoidung`
--

CREATE TABLE `nhomnguoidung` (
  `maNhom` int(11) NOT NULL,
  `tenNhom` varchar(100) NOT NULL,
  `moTa` text DEFAULT NULL,
  `quyenHan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quyenHan`)),
  `ngayTao` datetime DEFAULT current_timestamp(),
  `nguoiTao` int(11) DEFAULT NULL,
  `ngayCapNhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `nguoiCapNhat` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhomnguoidung`
--

INSERT INTO `nhomnguoidung` (`maNhom`, `tenNhom`, `moTa`, `quyenHan`, `ngayTao`, `nguoiTao`, `ngayCapNhat`, `nguoiCapNhat`) VALUES
(3001, 'Quản trị viên', NULL, '[\"account.view\", \"account.create\", \"account.update\", \"account.delete\", \"account.lock\", \"account.unlock\", \"account.reset_password\", \"group.view\", \"group.create\", \"group.update\", \"group.delete\", \"audit.view\", \"system.admin\"]', '2025-11-17 22:38:58', NULL, '2025-11-17 22:38:59', NULL),
(3002, 'Ban giám hiệu', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3003, 'Giáo viên', NULL, '[\"account.view_own\", \"account.update_own\", \"report.view\", \"report.create\", \"report.submit\", \"schedule.view\", \"student.view\", \"grade.manage\"]', '2025-11-17 22:38:58', NULL, '2025-11-17 22:38:59', NULL),
(3004, 'Học sinh', NULL, '[\"account.view_own\", \"schedule.view\", \"grade.view\"]', '2025-11-17 22:38:58', NULL, '2025-11-17 22:38:59', NULL),
(3005, 'Phụ huynh', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3006, 'Giáo viên chủ nhiệm', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3007, 'Tổ trưởng bộ môn', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3008, 'Khách', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3009, 'Bảo mật', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL),
(3010, 'Kế toán', NULL, '[]', '2025-11-17 22:38:58', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancongchamdiem`
--

CREATE TABLE `phancongchamdiem` (
  `maPhanCong` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `loaiKiemTra` varchar(100) DEFAULT NULL,
  `ngayCham` date DEFAULT NULL,
  `hinhThucCham` varchar(100) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phancongchamdiem`
--

INSERT INTO `phancongchamdiem` (`maPhanCong`, `maGV`, `maLop`, `maMonHoc`, `loaiKiemTra`, `ngayCham`, `hinhThucCham`, `trangThai`) VALUES
(17101, 6001, 10001, 2001, 'Cuối kỳ', '2025-06-10', 'Tập thể', 'pending'),
(17102, 6002, 10002, 2002, 'Giữa kỳ', '2025-03-10', 'Tập thể', 'pending'),
(17103, 6003, 10003, 2003, 'Cuối kỳ', '2025-06-11', 'Tập thể', 'pending'),
(17104, 6004, 10004, 2004, 'Giữa kỳ', '2025-03-11', 'Tập thể', 'pending'),
(17105, 6005, 10005, 2005, 'Cuối kỳ', '2025-06-12', 'Tập thể', 'pending'),
(17106, 6006, 10006, 2006, 'Giữa kỳ', '2025-03-12', 'Tập thể', 'pending'),
(17107, 6007, 10007, 2007, 'Cuối kỳ', '2025-06-13', 'Tập thể', 'pending'),
(17108, 6008, 10008, 2008, 'Giữa kỳ', '2025-03-13', 'Tập thể', 'pending'),
(17109, 6009, 10009, 2009, 'Cuối kỳ', '2025-06-14', 'Tập thể', 'pending'),
(17110, 6010, 10010, 2010, 'Giữa kỳ', '2025-03-14', 'Tập thể', 'pending');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancongcoithi`
--

CREATE TABLE `phancongcoithi` (
  `maPhanCong` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `maPhong` int(11) DEFAULT NULL,
  `loaiKyThi` varchar(100) DEFAULT NULL,
  `ngayThi` date DEFAULT NULL,
  `gioBatDau` time DEFAULT NULL,
  `gioKetThuc` time DEFAULT NULL,
  `viTriCoiThi` varchar(100) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phancongcoithi`
--

INSERT INTO `phancongcoithi` (`maPhanCong`, `maGV`, `maLop`, `maMonHoc`, `maPhong`, `loaiKyThi`, `ngayThi`, `gioBatDau`, `gioKetThuc`, `viTriCoiThi`, `trangThai`) VALUES
(17001, 6001, 10001, 2001, 12001, 'Cuối kỳ', '2025-06-01', '08:00:00', '11:00:00', 'Giam thi 1', 'scheduled'),
(17002, 6002, 10002, 2002, 12002, 'Giữa kỳ', '2025-03-01', '09:00:00', '12:00:00', 'Giam thi 2', 'scheduled'),
(17003, 6003, 10003, 2003, 12003, 'Cuối kỳ', '2025-06-02', '08:00:00', '11:00:00', 'Giam thi 1', 'scheduled'),
(17004, 6004, 10004, 2004, 12004, 'Giữa kỳ', '2025-03-02', '09:00:00', '12:00:00', 'Giam thi 2', 'scheduled'),
(17005, 6005, 10005, 2005, 12005, 'Cuối kỳ', '2025-06-03', '08:00:00', '11:00:00', 'Giam thi 3', 'scheduled'),
(17006, 6006, 10006, 2006, 12006, 'Giữa kỳ', '2025-03-03', '09:00:00', '12:00:00', 'Giam thi 4', 'scheduled'),
(17007, 6007, 10007, 2007, 12007, 'Cuối kỳ', '2025-06-04', '08:00:00', '11:00:00', 'Giam thi 5', 'scheduled'),
(17008, 6008, 10008, 2008, 12008, 'Giữa kỳ', '2025-03-04', '09:00:00', '12:00:00', 'Giam thi 6', 'scheduled'),
(17009, 6009, 10009, 2009, 12009, 'Cuối kỳ', '2025-06-05', '08:00:00', '11:00:00', 'Giam thi 7', 'scheduled'),
(17010, 6010, 10010, 2010, 12010, 'Giữa kỳ', '2025-03-05', '09:00:00', '12:00:00', 'Giam thi 8', 'scheduled');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong_gvbm`
--

CREATE TABLE `phancong_gvbm` (
  `maPhanCong` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maLop` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `trangThai` enum('active','inactive','completed') DEFAULT 'active',
  `ngayPhanCong` datetime DEFAULT current_timestamp(),
  `nguoiPhanCong` int(11) DEFAULT NULL,
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân công Giáo viên Bộ môn';

--
-- Đang đổ dữ liệu cho bảng `phancong_gvbm`
--

INSERT INTO `phancong_gvbm` (`maPhanCong`, `maGV`, `maLop`, `maMonHoc`, `hocKy`, `namHoc`, `trangThai`, `ngayPhanCong`, `nguoiPhanCong`, `ghiChu`) VALUES
(1, 6001, 10001, 2001, 1, '2024-2025', 'active', '2025-12-13 13:58:14', 5001, 'GV Toán dạy lớp 6A1'),
(2, 6001, 10001, 2001, 2, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Toán dạy lớp 6A1 HK2'),
(3, 6002, 10002, 2002, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Lý dạy lớp 6A2'),
(4, 6002, 10002, 2002, 2, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Lý dạy lớp 6A2 HK2'),
(5, 6003, 10003, 2003, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Hóa dạy lớp 6A3'),
(6, 6003, 10003, 2003, 2, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Hóa dạy lớp 6A3 HK2'),
(7, 6004, 10004, 2004, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Văn dạy lớp 7A1'),
(8, 6004, 10004, 2004, 2, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Văn dạy lớp 7A1 HK2'),
(9, 6005, 10005, 2009, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Anh dạy lớp 7A2'),
(10, 6005, 10005, 2009, 2, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Anh dạy lớp 7A2 HK2'),
(11, 6001, 10005, 2001, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV Toán dạy lớp 7A2'),
(12, 6012, 10001, 2004, 1, '2024-2025', 'active', '2025-12-14 16:19:31', 5001, 'Phân công GVBM bởi BGH'),
(13, 6010, 10003, 2010, 1, '2024-2025', 'active', '2025-12-14 16:20:33', 5001, 'Phân công GVBM bởi BGH'),
(14, 6010, 10003, 2010, 2, '2024-2025', 'active', '2025-12-14 16:20:33', 5001, 'Phân công GVBM bởi BGH');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong_gvcn`
--

CREATE TABLE `phancong_gvcn` (
  `maPhanCongCN` int(11) NOT NULL,
  `maGV` int(11) NOT NULL COMMENT 'Mã giáo viên chủ nhiệm',
  `maLop` int(11) NOT NULL COMMENT 'Mã lớp học',
  `maMonHoc` int(11) DEFAULT NULL,
  `hocKy` tinyint(4) DEFAULT NULL,
  `namHoc` varchar(20) NOT NULL COMMENT 'VD: 2024-2025',
  `trangThai` enum('active','inactive','completed') DEFAULT 'active',
  `ngayPhanCong` datetime DEFAULT current_timestamp(),
  `nguoiPhanCong` int(11) DEFAULT NULL COMMENT 'BGH thực hiện phân công',
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân công Giáo viên Chủ nhiệm';

--
-- Đang đổ dữ liệu cho bảng `phancong_gvcn`
--

INSERT INTO `phancong_gvcn` (`maPhanCongCN`, `maGV`, `maLop`, `maMonHoc`, `hocKy`, `namHoc`, `trangThai`, `ngayPhanCong`, `nguoiPhanCong`, `ghiChu`) VALUES
(1, 6011, 10001, 2001, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 6A1'),
(2, 6012, 10002, 2004, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 6A2'),
(3, 6013, 10003, 2001, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 7A1'),
(4, 6014, 10004, 2004, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 7A2'),
(5, 6015, 10005, 2001, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 8A1'),
(6, 6016, 10006, 2004, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 8A2'),
(7, 6017, 10007, 2001, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 9A1'),
(8, 6018, 10008, 2004, 1, '2024-2025', 'active', '2024-08-15 08:00:00', 5001, 'GV chủ nhiệm lớp 9A2');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong_hocsinh_lop`
--

CREATE TABLE `phancong_hocsinh_lop` (
  `maPhanCongHS` int(11) NOT NULL,
  `maHS` int(11) NOT NULL COMMENT 'Mã học sinh',
  `maLop` int(11) NOT NULL COMMENT 'Mã lớp được phân công',
  `namHoc` varchar(20) NOT NULL COMMENT 'VD: 2024-2025',
  `loaiPhanCong` enum('daucap','chuyenlop','normal') DEFAULT 'normal' COMMENT 'daucap: HS mới vào đầu cấp, chuyenlop: chuyển lớp, normal: bình thường',
  `ngayPhanCong` datetime DEFAULT current_timestamp(),
  `nguoiPhanCong` int(11) DEFAULT NULL COMMENT 'BGH thực hiện phân công',
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử phân công học sinh vào lớp';

--
-- Đang đổ dữ liệu cho bảng `phancong_hocsinh_lop`
--

INSERT INTO `phancong_hocsinh_lop` (`maPhanCongHS`, `maHS`, `maLop`, `namHoc`, `loaiPhanCong`, `ngayPhanCong`, `nguoiPhanCong`, `ghiChu`) VALUES
(1, 13001, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(2, 13002, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(3, 13003, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(4, 13004, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(5, 13005, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(6, 13006, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(7, 13007, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(8, 13008, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(9, 13009, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(10, 13010, 10001, '2024-2025', 'daucap', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh đầu cấp 6'),
(11, 13011, 10001, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 7'),
(12, 13012, 10001, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 7'),
(13, 13013, 10001, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 7'),
(14, 13014, 10007, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(15, 13015, 10008, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(16, 13016, 10008, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(17, 13017, 10009, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(18, 13018, 10009, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(19, 13019, 10010, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(20, 13020, 10010, '2024-2025', 'normal', '2024-08-15 08:00:00', 5001, 'Phân lớp học sinh lớp 8'),
(21, 13003, 10001, '2024-2025', 'daucap', '2025-12-14 17:16:54', 5001, 'Phân lớp tự động bởi hệ thống'),
(22, 13001, 10002, '2024-2025', 'daucap', '2025-12-14 17:16:54', 5001, 'Phân lớp tự động bởi hệ thống'),
(23, 13004, 10001, '2024-2025', 'daucap', '2025-12-14 17:16:54', 5001, 'Phân lớp tự động bởi hệ thống'),
(24, 13010, 10002, '2024-2025', 'daucap', '2025-12-14 17:16:54', 5001, 'Phân lớp tự động bởi hệ thống'),
(25, 13002, 10001, '2024-2025', 'daucap', '2025-12-14 17:16:54', 5001, 'Phân lớp tự động bởi hệ thống');

--
-- Bẫy `phancong_hocsinh_lop`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatLopHocSinh_Insert` AFTER INSERT ON `phancong_hocsinh_lop` FOR EACH ROW BEGIN
  -- Cập nhật maLop trong bảng hocsinh khi có phân công mới
  UPDATE hocsinh SET maLop = NEW.maLop WHERE maHS = NEW.maHS;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong_lop_phong`
--

CREATE TABLE `phancong_lop_phong` (
  `maPhanCongLP` int(11) NOT NULL,
  `maLop` int(11) NOT NULL COMMENT 'Mã lớp học',
  `maPhong` int(11) NOT NULL COMMENT 'Mã phòng học',
  `namHoc` varchar(20) NOT NULL COMMENT 'VD: 2024-2025',
  `ngayPhanCong` datetime DEFAULT current_timestamp(),
  `nguoiPhanCong` int(11) DEFAULT NULL COMMENT 'BGH thực hiện phân công',
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân công phòng học cho lớp - theo năm học';

--
-- Đang đổ dữ liệu cho bảng `phancong_lop_phong`
--

INSERT INTO `phancong_lop_phong` (`maPhanCongLP`, `maLop`, `maPhong`, `namHoc`, `ngayPhanCong`, `nguoiPhanCong`, `ghiChu`) VALUES
(2, 10002, 12002, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng lý thuyết P102 cho lớp 6A2'),
(3, 10003, 12003, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng thực hành P103 cho lớp 7A1'),
(4, 10004, 12004, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng tin học P104 cho lớp 7A2'),
(5, 10005, 12005, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng thí nghiệm P105 cho lớp 8A1'),
(6, 10006, 12006, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng lý thuyết P106 cho lớp 8A2'),
(7, 10007, 12007, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng lý thuyết P107 cho lớp 9A1'),
(8, 10008, 12008, '2024-2025', '2024-08-15 08:00:00', 5001, 'Phòng thực hành P108 cho lớp 9A2'),
(10, 10001, 12001, '2024-2025', '2025-12-14 17:12:09', 5001, 'Phân công phòng học bởi BGH');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phong`
--

CREATE TABLE `phong` (
  `maPhong` int(11) NOT NULL,
  `tenPhong` varchar(100) NOT NULL,
  `loaiPhong` varchar(100) DEFAULT NULL,
  `dienTich` decimal(8,2) DEFAULT NULL,
  `soLuongSV` int(11) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Hoatdong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phong`
--

INSERT INTO `phong` (`maPhong`, `tenPhong`, `loaiPhong`, `dienTich`, `soLuongSV`, `trangThai`) VALUES
(12001, 'P101', 'Lý thuyết', 40.00, 40, 'Hoatdong'),
(12002, 'P102', 'Lý thuyết', 45.00, 45, 'Hoatdong'),
(12003, 'P103', 'Thực hành', 60.00, 30, 'Hoatdong'),
(12004, 'P104', 'Tin học', 50.00, 30, 'Hoatdong'),
(12005, 'P105', 'Thí nghiệm', 55.00, 30, 'Hoatdong'),
(12006, 'P106', 'Lý thuyết', 40.00, 40, 'Hoatdong'),
(12007, 'P107', 'Lý thuyết', 42.00, 40, 'Hoatdong'),
(12008, 'P108', 'Thực hành', 70.00, 30, 'Hoatdong'),
(12009, 'P109', 'Tin học', 48.00, 30, 'Hoatdong'),
(12010, 'P110', 'Đa chức năng', 80.00, 60, 'Hoatdong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phuhuynh`
--

CREATE TABLE `phuhuynh` (
  `maPH` int(11) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `soDienThoai` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phuhuynh`
--

INSERT INTO `phuhuynh` (`maPH`, `hoTen`, `soDienThoai`, `email`, `maTaiKhoan`, `diaChi`) VALUES
(8001, 'Nguyen Thi PH1', '0911000001', 'ph1@example.com', 4301, 'HN'),
(8002, 'Tran Van PH2', '0911000002', 'ph2@example.com', 4302, 'HN'),
(8003, 'Le Thi PH3', '0911000003', 'ph3@example.com', 4303, 'HN'),
(8004, 'Pham Van PH4', '0911000004', 'ph4@example.com', 4304, 'HN'),
(8005, 'Hoang Thi PH5', '0911000005', 'ph5@example.com', 4305, 'HN'),
(8006, 'Vu Van PH6', '0911000006', 'ph6@example.com', 4306, 'HN'),
(8007, 'Do Thi PH7', '0911000007', 'ph7@example.com', 4307, 'HN'),
(8008, 'Bui Van PH8', '0911000008', 'ph8@example.com', 4308, 'HN'),
(8009, 'Pham Thi PH9', '0911000009', 'ph9@example.com', 4309, 'HN'),
(8010, 'Dang Van PH10', '0911000010', 'ph10@example.com', 4310, 'HN');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quantrivien`
--

CREATE TABLE `quantrivien` (
  `maQuanTri` int(11) NOT NULL,
  `tenQuanTri` varchar(255) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` varchar(10) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maHoSo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan`
--

CREATE TABLE `taikhoan` (
  `maTaiKhoan` int(11) NOT NULL,
  `tenDangNhap` varchar(100) NOT NULL,
  `matKhau` varchar(255) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `loaiTaiKhoan` enum('quantrivien','giaovien','hocsinh','phuhuynh','bangiamhieu','ttbm') DEFAULT NULL,
  `trangThaiTaiKhoan` enum('active','locked','disabled') NOT NULL DEFAULT 'active',
  `maNhom` int(11) DEFAULT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `nguoiTao` int(11) DEFAULT NULL,
  `ngayCapNhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `nguoiCapNhat` int(11) DEFAULT NULL,
  `batBuocDoiMatKhau` tinyint(1) DEFAULT 0,
  `soLanDangNhapSai` int(11) DEFAULT 0,
  `lanDangNhapCuoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `taikhoan`
--

INSERT INTO `taikhoan` (`maTaiKhoan`, `tenDangNhap`, `matKhau`, `hoTen`, `email`, `soDienThoai`, `loaiTaiKhoan`, `trangThaiTaiKhoan`, `maNhom`, `ngayTao`, `nguoiTao`, `ngayCapNhat`, `nguoiCapNhat`, `batBuocDoiMatKhau`, `soLanDangNhapSai`, `lanDangNhapCuoi`) VALUES
(4001, 'admin', '5f4dcc3b5aa765d61d8327deb882cf99', 'Admin Root', NULL, NULL, 'quantrivien', 'active', 3001, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4002, 'bgh1', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 1', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4003, 'gv1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 1', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4004, 'gv2', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 2', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4005, 'hs1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 1', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4006, 'ph1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 1', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4007, 'qtv1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Quan Tri 1', NULL, NULL, 'quantrivien', 'active', 3001, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4008, 'ttbm1', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 1', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:57:48', NULL, 0, 0, NULL),
(4009, 'userA', '5f4dcc3b5aa765d61d8327deb882cf99', 'User A', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4010, 'userB', '5f4dcc3b5aa765d61d8327deb882cf99', 'User B', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4101, 'gv6001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6001', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4102, 'gv6002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6002', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4103, 'gv6003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6003', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4104, 'gv6004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6004', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4105, 'gv6005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6005', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4106, 'gv6006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6006', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4107, 'gv6007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6007', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4108, 'gv6008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6008', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4109, 'gv6009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6009', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4110, 'gv6010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6010', NULL, NULL, 'giaovien', 'active', 3003, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4111, 'gvcn6011', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6011', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4112, 'gvcn6012', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6012', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4113, 'gvcn6013', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6013', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4114, 'gvcn6014', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6014', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4115, 'gvcn6015', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6015', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4116, 'gvcn6016', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6016', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4117, 'gvcn6017', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6017', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4118, 'gvcn6018', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien CN 6018', NULL, NULL, 'giaovien', 'active', 3006, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4201, 'hs13001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13001', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4202, 'hs13002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13002', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4203, 'hs13003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13003', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4204, 'hs13004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13004', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4205, 'hs13005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13005', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4206, 'hs13006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13006', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4207, 'hs13007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13007', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4208, 'hs13008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13008', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4209, 'hs13009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13009', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4210, 'hs13010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13010', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4211, 'hs13011', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13011', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4212, 'hs13012', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13012', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4213, 'hs13013', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13013', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4214, 'hs13014', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13014', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4215, 'hs13015', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13015', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4216, 'hs13016', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13016', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4217, 'hs13017', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13017', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4218, 'hs13018', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13018', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4219, 'hs13019', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13019', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4220, 'hs13020', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13020', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4221, 'hs13021', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13021', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4222, 'hs13022', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13022', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4223, 'hs13023', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13023', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4224, 'hs13024', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13024', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4225, 'hs13025', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13025', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4226, 'hs13026', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13026', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4227, 'hs13027', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13027', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4228, 'hs13028', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13028', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4229, 'hs13029', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13029', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4230, 'hs13030', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13030', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4231, 'hs13031', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13031', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4232, 'hs13032', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13032', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4233, 'hs13033', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13033', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4234, 'hs13034', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13034', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4235, 'hs13035', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13035', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4236, 'hs13036', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13036', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4237, 'hs13037', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13037', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4238, 'hs13038', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13038', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4239, 'hs13039', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13039', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4240, 'hs13040', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13040', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4241, 'hs13041', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13041', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4242, 'hs13042', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13042', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4243, 'hs13043', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13043', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4244, 'hs13044', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13044', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4245, 'hs13045', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13045', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4246, 'hs13046', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13046', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4247, 'hs13047', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13047', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4248, 'hs13048', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13048', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4249, 'hs13049', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13049', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4250, 'hs13050', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13050', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4251, 'hs13051', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13051', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4252, 'hs13052', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13052', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4253, 'hs13053', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13053', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4254, 'hs13054', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13054', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4255, 'hs13055', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13055', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4256, 'hs13056', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13056', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4257, 'hs13057', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13057', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4258, 'hs13058', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13058', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4259, 'hs13059', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13059', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4260, 'hs13060', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13060', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4261, 'hs13061', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13061', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4262, 'hs13062', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13062', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4263, 'hs13063', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13063', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4264, 'hs13064', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13064', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4265, 'hs13065', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13065', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4266, 'hs13066', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13066', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4267, 'hs13067', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13067', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4268, 'hs13068', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13068', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4269, 'hs13069', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13069', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4270, 'hs13070', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13070', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4271, 'hs13071', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13071', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4272, 'hs13072', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13072', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4273, 'hs13073', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13073', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4274, 'hs13074', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13074', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4275, 'hs13075', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13075', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4276, 'hs13076', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13076', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4277, 'hs13077', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13077', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4278, 'hs13078', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13078', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4279, 'hs13079', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13079', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4280, 'hs13080', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13080', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4281, 'hs13081', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13081', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4282, 'hs13082', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13082', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4283, 'hs13083', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13083', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4284, 'hs13084', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13084', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4285, 'hs13085', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13085', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4286, 'hs13086', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13086', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4287, 'hs13087', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13087', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4288, 'hs13088', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13088', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4289, 'hs13089', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13089', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4290, 'hs13090', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13090', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4291, 'hs13091', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13091', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4292, 'hs13092', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13092', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4293, 'hs13093', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13093', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4294, 'hs13094', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13094', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4295, 'hs13095', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13095', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4296, 'hs13096', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13096', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4297, 'hs13097', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13097', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4298, 'hs13098', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13098', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4299, 'hs13099', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13099', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4300, 'hs13100', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13100', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4301, 'hs13101', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13101', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4302, 'hs13102', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13102', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4303, 'hs13103', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13103', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4304, 'hs13104', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13104', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4305, 'hs13105', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13105', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4306, 'hs13106', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13106', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4307, 'hs13107', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13107', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4308, 'hs13108', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13108', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4309, 'hs13109', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13109', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4310, 'hs13110', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13110', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4311, 'hs13111', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13111', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4312, 'hs13112', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13112', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4313, 'hs13113', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13113', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4314, 'hs13114', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13114', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4315, 'hs13115', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13115', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4316, 'hs13116', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13116', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4317, 'hs13117', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13117', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4318, 'hs13118', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13118', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4319, 'hs13119', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13119', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4320, 'hs13120', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13120', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4321, 'hs13121', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13121', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4322, 'hs13122', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13122', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4323, 'hs13123', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13123', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4324, 'hs13124', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13124', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4325, 'hs13125', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13125', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4326, 'hs13126', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13126', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4327, 'hs13127', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13127', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4328, 'hs13128', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13128', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4329, 'hs13129', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13129', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4330, 'hs13130', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13130', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4331, 'hs13131', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13131', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4332, 'hs13132', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13132', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4333, 'hs13133', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13133', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4334, 'hs13134', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13134', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4335, 'hs13135', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13135', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4336, 'hs13136', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13136', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4337, 'hs13137', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13137', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4338, 'hs13138', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13138', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4339, 'hs13139', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13139', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4340, 'hs13140', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13140', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4341, 'hs13141', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13141', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4342, 'hs13142', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13142', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4343, 'hs13143', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13143', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4344, 'hs13144', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13144', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4345, 'hs13145', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13145', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4346, 'hs13146', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13146', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4347, 'hs13147', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13147', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4348, 'hs13148', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13148', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4349, 'hs13149', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13149', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4350, 'hs13150', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13150', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4351, 'hs13151', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13151', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4352, 'hs13152', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13152', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4353, 'hs13153', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13153', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4354, 'hs13154', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13154', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4355, 'hs13155', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13155', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4356, 'hs13156', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13156', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4357, 'hs13157', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13157', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4358, 'hs13158', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13158', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4359, 'hs13159', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13159', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4360, 'hs13160', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13160', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4361, 'hs13161', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13161', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4362, 'hs13162', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13162', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4363, 'hs13163', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13163', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4364, 'hs13164', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13164', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4365, 'hs13165', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13165', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4366, 'hs13166', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13166', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4367, 'hs13167', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13167', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4368, 'hs13168', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13168', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4369, 'hs13169', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13169', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4370, 'hs13170', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13170', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4371, 'hs13171', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13171', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4372, 'hs13172', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13172', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4373, 'hs13173', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13173', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4374, 'hs13174', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13174', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4375, 'hs13175', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13175', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4376, 'hs13176', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13176', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4377, 'hs13177', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13177', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4378, 'hs13178', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13178', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4379, 'hs13179', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13179', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4380, 'hs13180', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13180', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4381, 'hs13181', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13181', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4382, 'hs13182', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13182', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4383, 'hs13183', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13183', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4384, 'hs13184', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13184', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4385, 'hs13185', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13185', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4386, 'hs13186', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13186', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4387, 'hs13187', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13187', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4388, 'hs13188', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13188', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4389, 'hs13189', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13189', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4390, 'hs13190', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13190', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4391, 'hs13191', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13191', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4392, 'hs13192', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13192', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4393, 'hs13193', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13193', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4394, 'hs13194', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13194', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4395, 'hs13195', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13195', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4396, 'hs13196', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13196', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4397, 'hs13197', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13197', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4398, 'hs13198', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13198', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4399, 'hs13199', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13199', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4400, 'hs13200', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13200', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4401, 'hs13201', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13201', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4402, 'hs13202', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13202', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4403, 'hs13203', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13203', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4404, 'hs13204', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13204', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4405, 'hs13205', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13205', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4406, 'hs13206', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13206', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4407, 'hs13207', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13207', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4408, 'hs13208', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13208', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4409, 'hs13209', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13209', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4410, 'hs13210', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13210', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4411, 'hs13211', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13211', NULL, NULL, 'hocsinh', 'active', 3004, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4501, 'bgh5001', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5001', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4502, 'bgh5002', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5002', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4503, 'bgh5003', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5003', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4504, 'bgh5004', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5004', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4505, 'bgh5005', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5005', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4506, 'bgh5006', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5006', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4507, 'bgh5007', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5007', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4508, 'bgh5008', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5008', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4509, 'bgh5009', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5009', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4510, 'bgh5010', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5010', NULL, NULL, 'bangiamhieu', 'active', 3002, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4601, 'ph8001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8001', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4602, 'ph8002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8002', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4603, 'ph8003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8003', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4604, 'ph8004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8004', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4605, 'ph8005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8005', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4606, 'ph8006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8006', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4607, 'ph8007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8007', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4608, 'ph8008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8008', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4609, 'ph8009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8009', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4610, 'ph8010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8010', NULL, NULL, 'phuhuynh', 'active', 3005, '2025-11-17 22:38:58', NULL, NULL, NULL, 0, 0, NULL),
(4701, 'ttbm7001', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7001', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4702, 'ttbm7002', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7002', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4703, 'ttbm7003', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7003', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4704, 'ttbm7004', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7004', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4705, 'ttbm7005', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7005', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4706, 'ttbm7006', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7006', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4707, 'ttbm7007', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7007', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4708, 'ttbm7008', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7008', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4709, 'ttbm7009', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7009', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(4710, 'ttbm7010', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7010', NULL, NULL, 'ttbm', 'active', 3007, '2025-11-17 22:38:58', NULL, '2025-11-18 00:59:24', NULL, 0, 0, NULL),
(5000, 'testuser', '5f4dcc3b5aa765d61d8327deb882cf99', 'Test User', NULL, NULL, 'quantrivien', 'active', 3001, '2025-11-17 22:43:54', NULL, NULL, NULL, 0, 0, NULL),
(5004, 'khai', '$2y$10$qvBF.2mGZXjurdHQAC9Z.ebGWA6TfctqCMWD5KYSUVZ7ngamx2OcK', 'Nguyễn Quang Khải', 'khainguyen3099@gmail.com', '0855370246', 'phuhuynh', 'active', 3005, '2025-11-17 23:27:33', 4001, '2025-11-17 23:45:28', 4001, 1, 0, NULL),
(5005, 'khai1', '$2y$10$usqHR0uY/XkbZJ4rm3uBnuF1En4.AZmwTQ6.05Em3MnhuGMIV.O86', 'Khải đẹp trai', 'khaitobeo@gmail.com', '0123456789', 'quantrivien', 'active', 3001, '2025-11-17 23:48:58', 4001, '2025-12-19 15:23:31', 4001, 1, 0, NULL),
(5006, 'peovn', '$2y$10$/fWPe71gwfLr7zqteiQ2l.Zkb0oMt/6BDaXM6/Y9eZ.b.LNKXHwy.', 'Nguyễn Thành Trung', 'trung@gmail.com', '0855370246', 'quantrivien', 'active', 3001, '2025-12-19 15:28:33', 4001, NULL, NULL, 1, 0, NULL),
(5007, 'username', '5f4dcc3b5aa765d61d8327deb882cf99', 'Username Account', NULL, NULL, 'quantrivien', 'active', 3001, '2025-12-19 21:50:07', NULL, NULL, NULL, 0, 0, NULL);

--
-- Bẫy `taikhoan`
--
DELIMITER $$
CREATE TRIGGER `trg_taikhoan_after_update` AFTER UPDATE ON `taikhoan` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thoikhoabieu`
--

CREATE TABLE `thoikhoabieu` (
  `maTKB` int(11) NOT NULL,
  `tenMonHoc` varchar(255) DEFAULT NULL,
  `thoiGianHoc` time DEFAULT NULL,
  `thuNgay` date DEFAULT NULL,
  `tietHoc` varchar(255) DEFAULT NULL,
  `lop` varchar(255) DEFAULT NULL,
  `phong` varchar(255) DEFAULT NULL,
  `gv` varchar(255) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thoikhoabieu`
--

INSERT INTO `thoikhoabieu` (`maTKB`, `tenMonHoc`, `thoiGianHoc`, `thuNgay`, `tietHoc`, `lop`, `phong`, `gv`, `maLop`, `maTaiKhoan`, `maGV`, `maMonHoc`) VALUES
(25001, 'Toán', '07:30:00', '2024-11-03', 'Tiết 1-2', '6A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(25002, 'Vật lý', '09:00:00', '2024-11-03', 'Tiết 3-4', '6A2', 'P102', 'Tran Thi G2', 10002, NULL, 6002, 2002),
(28000, 'Toán', '07:30:00', '2025-01-06', 'Tiết 1-2', '6A1', 'P201', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(28001, 'Ngữ văn', '09:00:00', '2025-01-06', 'Tiết 3-4', '6A1', 'P201', 'Pham Thi G4', 10001, NULL, 6004, 2004),
(28002, 'Tiếng Anh', '07:30:00', '2025-01-07', 'Tiết 1-2', '6A1', 'P202', 'Hoang Van G5', 10001, NULL, 6005, 2005),
(28003, 'Lịch sử', '09:00:00', '2025-01-07', 'Tiết 3-4', '6A1', 'P202', 'Do Van G7', 10001, NULL, 6007, 2007),
(28004, 'Địa lý', '07:30:00', '2025-01-08', 'Tiết 1-2', '6A1', 'P203', 'Do Van G7', 10001, NULL, 6007, 2007),
(28005, 'Sinh học', '09:00:00', '2025-01-08', 'Tiết 3-4', '6A1', 'P203', 'Vu Thi G6', 10001, NULL, 6006, 2006),
(28006, 'Hóa học', '07:30:00', '2025-01-09', 'Tiết 1-2', '6A1', 'P204', 'Le Van G3', 10001, NULL, 6003, 2003),
(28007, 'Tin học', '09:00:00', '2025-01-09', 'Tiết 3-4', '6A1', 'P204', 'Bui Thi G8', 10001, NULL, 6008, 2008),
(28008, 'GDCD', '07:30:00', '2025-01-10', 'Tiết 1-2', '6A1', 'P205', 'Dang Thi G10', 10001, NULL, 6010, 2010),
(28009, 'Thể dục', '09:00:00', '2025-01-10', 'Tiết 3-4', '6A1', 'SanTD', 'Pham Van G9', 10001, NULL, 6009, 2009),
(28010, 'Thể dục', '13:30:00', '2025-01-10', 'Tiết 6', '6A1', 'SanTD', 'Pham Van G9', 10001, NULL, 6009, 2009),
(28011, 'GDCD', '15:00:00', '2025-01-10', 'Tiết 7', '6A1', 'P205', 'Dang Thi G10', 10001, NULL, 6010, 2010),
(28014, 'GDCD', '07:00:00', '2025-12-19', 'Tiết 1', '9A4', 'P101', 'Dang Thi G10', 10010, NULL, 6010, 2010);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbao`
--

CREATE TABLE `thongbao` (
  `maThongBao` int(11) NOT NULL,
  `tieuDe` varchar(255) NOT NULL,
  `noiDung` text DEFAULT NULL,
  `loaiThongBao` varchar(100) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'chuagui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thongbao`
--

INSERT INTO `thongbao` (`maThongBao`, `tieuDe`, `noiDung`, `loaiThongBao`, `trangThai`) VALUES
(27001, 'Họp phụ huynh 10A1', 'Họp cuối HK1', 'phuhuynh', 'dagui'),
(27002, 'Thông báo thi HK1', 'Lịch thi được công bố', 'chung', 'dagui');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ttbm`
--

CREATE TABLE `ttbm` (
  `maTTBM` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `ttbm`
--

INSERT INTO `ttbm` (`maTTBM`, `maGV`, `maTaiKhoan`) VALUES
(7001, 6001, 4401),
(7002, 6002, 4402),
(7003, 6003, 4403),
(7004, 6004, 4404),
(7005, 6005, 4405),
(7006, 6006, 4406),
(7007, 6007, 4407),
(7008, 6008, 4408),
(7009, 6009, 4409),
(7010, 6010, 4410);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vipham`
--

CREATE TABLE `vipham` (
  `maViPham` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayViPham` date DEFAULT NULL,
  `loaiViPham` varchar(100) DEFAULT NULL COMMENT 'Loại vi phạm: Đi trễ, Không đồng phục, Vi phạm nội quy,...',
  `mucDoViPham` varchar(50) DEFAULT 'Nhe' COMMENT 'Nhe, Trung binh, Nang',
  `noiDungViPham` text DEFAULT NULL COMMENT 'Mô tả chi tiết vi phạm',
  `hinhThucXuLy` varchar(100) DEFAULT NULL COMMENT 'Nhắc nhở, Cảnh cáo, Kiểm điểm,...',
  `nguoiPhatHien` int(11) DEFAULT NULL COMMENT 'Mã giáo viên phát hiện',
  `hocKy` tinyint(4) DEFAULT NULL COMMENT '1 hoặc 2',
  `namHoc` varchar(20) DEFAULT NULL COMMENT 'VD: 2024-2025'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vipham`
--

INSERT INTO `vipham` (`maViPham`, `maHS`, `ngayViPham`, `loaiViPham`, `mucDoViPham`, `noiDungViPham`, `hinhThucXuLy`, `nguoiPhatHien`, `hocKy`, `namHoc`) VALUES
(15201, 13003, '2024-09-15', 'Nói chuyện riêng', 'Nhe', 'Nói chuyện trong giờ học', 'Nhắc nhở', 6003, 1, '2024-2025'),
(15202, 13004, '2024-09-20', 'Vắng mặt không phép', 'Trung binh', 'Nghỉ học không xin phép', 'Cảnh cáo', 6004, 1, '2024-2025'),
(15203, 13004, '2024-10-05', 'Vắng mặt không phép', 'Trung binh', 'Nghỉ học không xin phép lần 2', 'Cảnh cáo', 6004, 1, '2024-2025'),
(15204, 13005, '2024-09-25', 'Thiếu tôn trọng', 'Trung binh', 'Cãi lời giáo viên', 'Cảnh cáo', 6005, 1, '2024-2025'),
(15205, 13005, '2024-10-10', 'Gây rối', 'Nang', 'Gây rối trật tự lớp học', 'Kiểm điểm', 6005, 1, '2024-2025'),
(15206, 13005, '2024-10-20', 'Đánh nhau', 'Nang', 'Đánh bạn trong lớp', 'Kiểm điểm + Thông báo phụ huynh', 6006, 1, '2024-2025'),
(15207, 13005, '2024-11-01', 'Vắng không phép', 'Trung binh', 'Nghỉ học nhiều ngày', 'Cảnh cáo', 6005, 1, '2024-2025'),
(15208, 13009, '2024-09-18', 'Thiếu trật tự', 'Nhe', 'Ồn ào trong giờ học', 'Nhắc nhở', 6009, 1, '2024-2025'),
(15209, 13013, '2024-10-12', 'Không làm bài tập', 'Nhe', 'Không làm bài tập về nhà nhiều lần', 'Nhắc nhở', 6003, 1, '2024-2025'),
(15210, 13014, '2024-09-22', 'Đi trễ', 'Nhe', 'Đến lớp trễ', 'Nhắc nhở', 6001, 1, '2024-2025'),
(15211, 13014, '2024-10-15', 'Vắng không phép', 'Trung binh', 'Nghỉ học không phép', 'Cảnh cáo', 6004, 1, '2024-2025'),
(15212, 13015, '2024-09-28', 'Gây rối', 'Nang', 'Gây rối trong giờ học', 'Kiểm điểm', 6005, 1, '2024-2025'),
(15213, 13015, '2024-10-18', 'Thiếu tôn trọng', 'Trung binh', 'Cãi lời giáo viên', 'Cảnh cáo', 6006, 1, '2024-2025'),
(15214, 13015, '2024-11-05', 'Vắng không phép', 'Trung binh', 'Vắng nhiều ngày', 'Cảnh cáo', 6004, 1, '2024-2025'),
(15215, 13019, '2024-10-08', 'Không đồng phục', 'Nhe', 'Không mặc đồng phục đúng quy định', 'Nhắc nhở', 6002, 1, '2024-2025'),
(15216, 13004, '2025-01-20', 'Đi trễ', 'Nhe', 'Đến lớp trễ', 'Nhắc nhở', 6001, 2, '2024-2025'),
(15217, 13005, '2025-02-10', 'Gây rối', 'Nang', 'Gây rối trật tự', 'Kiểm điểm', 6005, 2, '2024-2025'),
(15218, 13005, '2025-02-25', 'Vắng không phép', 'Trung binh', 'Nghỉ học không phép', 'Cảnh cáo', 6004, 2, '2024-2025'),
(15219, 13005, '2025-03-10', 'Thiếu tôn trọng', 'Trung binh', 'Cãi lời giáo viên', 'Cảnh cáo', 6005, 2, '2024-2025'),
(15220, 13014, '2025-02-15', 'Không làm bài tập', 'Nhe', 'Không làm bài tập', 'Nhắc nhở', 6003, 2, '2024-2025'),
(15221, 13015, '2025-01-25', 'Vắng không phép', 'Trung binh', 'Nghỉ học không phép', 'Cảnh cáo', 6004, 2, '2024-2025'),
(15222, 13015, '2025-03-05', 'Gây rối', 'Nang', 'Gây rối lớp học', 'Kiểm điểm', 6005, 2, '2024-2025');

--
-- Bẫy `vipham`
--
DELIMITER $$
CREATE TRIGGER `trg_CapNhatHanhKiem_Delete_ViPham` AFTER DELETE ON `vipham` FOR EACH ROW BEGIN
    DECLARE _soViPhamNhe INT DEFAULT 0;
    DECLARE _soViPhamTB INT DEFAULT 0;
    DECLARE _soViPhamNang INT DEFAULT 0;
    DECLARE _loaiHK VARCHAR(50);
    
    -- Đếm lại tổng số vi phạm sau khi xóa
    SELECT 
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nhe' THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham IN ('Trung binh', 'Trungbinh') THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nang' THEN 1 ELSE 0 END), 0)
    INTO _soViPhamNhe, _soViPhamTB, _soViPhamNang
    FROM vipham
    WHERE maHS = OLD.maHS 
        AND hocKy = OLD.hocKy 
        AND namHoc = OLD. namHoc;
    
    -- Xác định loại hành kiểm
    IF _soViPhamNang > 0 OR _soViPhamTB >= 3 THEN
        SET _loaiHK = 'Yếu';
    ELSEIF _soViPhamTB >= 1 OR _soViPhamNhe >= 5 THEN
        SET _loaiHK = 'Trung bình';
    ELSEIF _soViPhamNhe >= 1 THEN
        SET _loaiHK = 'Khá';
    ELSE
        SET _loaiHK = 'Tốt';
    END IF;
    
    -- Cập nhật bản ghi hành kiểm
    UPDATE hanhkiem 
    SET soLanViPhamNhe = _soViPhamNhe,
        soLanViPhamTB = _soViPhamTB,
        soLanViPhamNang = _soViPhamNang,
        loaiHK = _loaiHK
    WHERE maHS = OLD.maHS 
        AND hocKy = OLD.hocKy 
        AND namHoc = OLD. namHoc;
        
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_CapNhatHanhKiem_Insert_ViPham` AFTER INSERT ON `vipham` FOR EACH ROW BEGIN
    DECLARE _soViPhamNhe INT DEFAULT 0;
    DECLARE _soViPhamTB INT DEFAULT 0;
    DECLARE _soViPhamNang INT DEFAULT 0;
    DECLARE _loaiHK VARCHAR(50);
    
    -- Đếm tổng số vi phạm theo từng loại
    SELECT 
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nhe' THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham IN ('Trung binh', 'Trungbinh') THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nang' THEN 1 ELSE 0 END), 0)
    INTO _soViPhamNhe, _soViPhamTB, _soViPhamNang
    FROM vipham
    WHERE maHS = NEW.maHS 
        AND hocKy = NEW.hocKy 
        AND namHoc = NEW. namHoc;
    
    -- Xác định loại hành kiểm
    IF _soViPhamNang > 0 OR _soViPhamTB >= 3 THEN
        SET _loaiHK = 'Yếu';
    ELSEIF _soViPhamTB >= 1 OR _soViPhamNhe >= 5 THEN
        SET _loaiHK = 'Trung bình';
    ELSEIF _soViPhamNhe >= 1 THEN
        SET _loaiHK = 'Khá';
    ELSE
        SET _loaiHK = 'Tốt';
    END IF;
    
    -- Cập nhật hoặc thêm mới bản ghi hành kiểm
    UPDATE hanhkiem 
    SET soLanViPhamNhe = _soViPhamNhe,
        soLanViPhamTB = _soViPhamTB,
        soLanViPhamNang = _soViPhamNang,
        loaiHK = _loaiHK
    WHERE maHS = NEW.maHS 
        AND hocKy = NEW.hocKy 
        AND namHoc = NEW. namHoc;
        
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_CapNhatHanhKiem_Update_ViPham` AFTER UPDATE ON `vipham` FOR EACH ROW BEGIN
    DECLARE _soViPhamNhe INT DEFAULT 0;
    DECLARE _soViPhamTB INT DEFAULT 0;
    DECLARE _soViPhamNang INT DEFAULT 0;
    DECLARE _loaiHK VARCHAR(50);
    
    -- Đếm lại tổng số vi phạm
    SELECT 
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nhe' THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham IN ('Trung binh', 'Trungbinh') THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN mucDoViPham = 'Nang' THEN 1 ELSE 0 END), 0)
    INTO _soViPhamNhe, _soViPhamTB, _soViPhamNang
    FROM vipham
    WHERE maHS = NEW.maHS 
        AND hocKy = NEW.hocKy 
        AND namHoc = NEW.namHoc;
    
    -- Xác định loại hành kiểm
    IF _soViPhamNang > 0 OR _soViPhamTB >= 3 THEN
        SET _loaiHK = 'Yếu';
    ELSEIF _soViPhamTB >= 1 OR _soViPhamNhe >= 5 THEN
        SET _loaiHK = 'Trung bình';
    ELSEIF _soViPhamNhe >= 1 THEN
        SET _loaiHK = 'Khá';
    ELSE
        SET _loaiHK = 'Tốt';
    END IF;
    
    -- Cập nhật bản ghi hành kiểm
    UPDATE hanhkiem 
    SET soLanViPhamNhe = _soViPhamNhe,
        soLanViPhamTB = _soViPhamTB,
        soLanViPhamNang = _soViPhamNang,
        loaiHK = _loaiHK
    WHERE maHS = NEW.maHS 
        AND hocKy = NEW.hocKy 
        AND namHoc = NEW. namHoc;
        
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_phancong_giangday`
-- (See below for the actual view)
--
CREATE TABLE `v_phancong_giangday` (
`maPhanCongGD` int(11)
,`maGV` int(11)
,`maLop` int(11)
,`maMonHoc` int(11)
,`hocKy` tinyint(4)
,`namHoc` varchar(20)
,`trangThai` varchar(9)
,`ngayPhanCong` datetime
,`nguoiPhanCong` int(11)
,`ghiChu` mediumtext
,`loaiPhanCong` varchar(4)
,`tenGV` varchar(150)
,`toBoMon` varchar(100)
,`tenLop` varchar(50)
,`maKhoi` int(11)
,`khoiLop` varchar(10)
,`tenMonHoc` varchar(150)
,`soTiet` int(11)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeucau`
--

CREATE TABLE `yeucau` (
  `maYeuCau` int(11) NOT NULL,
  `moTa` text DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Choxuly',
  `maYeuCauNghiPhep` int(11) DEFAULT NULL,
  `maYeuCauSuaDiem` int(11) DEFAULT NULL,
  `ngayGui` datetime DEFAULT current_timestamp(),
  `ngayXuLy` datetime DEFAULT NULL,
  `maBGH_XuLy` int(11) DEFAULT NULL,
  `loaiYeuCau` enum('NghiPhep','SuaDiem') NOT NULL,
  `minhChung` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `yeucau`
--

INSERT INTO `yeucau` (`maYeuCau`, `moTa`, `trangThai`, `maYeuCauNghiPhep`, `maYeuCauSuaDiem`, `ngayGui`, `ngayXuLy`, `maBGH_XuLy`, `loaiYeuCau`, `minhChung`) VALUES
(23001, 'Xin nghỉ phép do đi khám bệnh định kỳ', 'Choxuly', 21001, NULL, '2025-11-10 08:30:00', NULL, NULL, 'NghiPhep', 'giay_hen_kham.pdf'),
(23002, 'Xin nghỉ phép tham gia hội thảo chuyên môn', 'Choxuly', 21002, NULL, '2025-11-12 09:15:00', NULL, NULL, 'NghiPhep', 'giay_moi_hoi_thao.pdf'),
(23003, 'Xin nghỉ phép do việc gia đình đột xuất', 'Choxuly', 21003, NULL, '2025-11-15 14:20:00', NULL, NULL, 'NghiPhep', NULL),
(23004, 'Xin sửa điểm do nhập nhầm cột điểm', 'Choxuly', NULL, 22001, '2025-11-11 10:00:00', NULL, NULL, 'SuaDiem', 'anh_bai_thi.jpg'),
(23005, 'Xin sửa điểm do cộng sai tổng điểm', 'Choxuly', NULL, 22002, '2025-11-13 11:30:00', NULL, NULL, 'SuaDiem', 'bang_diem_chi_tiet.pdf'),
(23006, 'Xin sửa điểm do ghi nhầm học sinh', 'Choxuly', NULL, 22003, '2025-11-16 15:45:00', NULL, NULL, 'SuaDiem', 'danh_sach_lop.pdf'),
(23007, 'Xin nghỉ phép chăm con ốm', 'Dachapnhan', 21004, NULL, '2025-11-01 08:00:00', '2025-11-02 09:30:00', 5001, 'NghiPhep', 'giay_xac_nhan_benh_vien.pdf'),
(23008, 'Xin nghỉ phép tham dự đám cưới người thân', 'Dachapnhan', 21005, NULL, '2025-11-03 09:00:00', '2025-11-04 10:15:00', 5001, 'NghiPhep', 'giay_moi_dam_cuoi.pdf'),
(23009, 'Xin sửa điểm do quên nhập điểm thưởng', 'Dachapnhan', NULL, 22004, '2025-11-05 10:30:00', '2025-11-06 14:20:00', 5002, 'SuaDiem', 'bien_ban_thi_dua.pdf'),
(23010, 'Xin sửa điểm do lỗi hệ thống', 'Dachapnhan', NULL, 22005, '2025-11-08 13:00:00', '2025-11-09 11:00:00', 5002, 'SuaDiem', 'screenshot_loi.png'),
(23011, 'Xin nghỉ phép đi du lịch cá nhân', 'Tuchoi', 21006, NULL, '2025-11-02 14:00:00', '2025-11-03 16:30:00', 5001, 'NghiPhep', NULL),
(23012, 'Xin sửa điểm tăng điểm không có căn cứ', 'Tuchoi', NULL, 22006, '2025-11-07 11:00:00', '2025-11-08 09:45:00', 5003, 'SuaDiem', NULL),
(23013, 'Xin nghỉ phép do cần giải quyết công việc cá nhân', 'Choxuly', 21007, NULL, '2025-11-18 08:00:00', NULL, NULL, 'NghiPhep', NULL),
(23014, 'Xin sửa điểm do nhập sai loại điểm', 'Choxuly', NULL, 22007, '2025-11-18 09:30:00', NULL, NULL, 'SuaDiem', 'so_diem.pdf'),
(23015, 'Xin nghỉ phép tham gia bồi dưỡng nghiệp vụ', 'Choxuly', 21008, NULL, '2025-11-18 10:15:00', NULL, NULL, 'NghiPhep', 'thong_bao_boi_duong.pdf'),
(24003, 'Yêu cầu sửa điểm diemTX1 môn \r\n                                    Toán                                 - HK1 năm học 2024-2025', 'Dachapnhan', NULL, 23003, '2025-12-17 00:49:28', '2025-12-17 00:50:19', 5001, 'SuaDiem', 'request_1765907368_69419ba857bdc.pdf'),
(24004, 'Yêu cầu nghỉ phép từ 17/12/2025 đến 19/12/2025', 'Tuchoi', 22000, NULL, '2025-12-17 01:00:20', '2025-12-17 01:01:05', 5001, 'NghiPhep', 'request_1765908020_69419e34c6f55.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeucaunghiphep`
--

CREATE TABLE `yeucaunghiphep` (
  `maYeuCau` int(11) NOT NULL,
  `ngayBatDauNghi` date DEFAULT NULL,
  `ngayKetThucNghi` date DEFAULT NULL,
  `lyDo` text DEFAULT NULL,
  `minhChung` varchar(255) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `yeucaunghiphep`
--

INSERT INTO `yeucaunghiphep` (`maYeuCau`, `ngayBatDauNghi`, `ngayKetThucNghi`, `lyDo`, `minhChung`, `maGV`) VALUES
(21001, '2025-11-20', '2025-11-21', 'Đi khám bệnh định kỳ tại bệnh viện tỉnh, có giấy hẹn khám từ trước.', NULL, 6001),
(21002, '2025-11-25', '2025-11-26', 'Tham gia hội thảo \"Phương pháp giảng dạy hiện đại\" do Sở GD&ĐT tổ chức.', NULL, 6002),
(21003, '2025-11-22', '2025-11-22', 'Con nhỏ ốm đột xuất, cần ở nhà chăm sóc vì không có người thân trông giữ.', NULL, 6003),
(21004, '2025-11-05', '2025-11-06', 'Con bị sốt cao cần đưa vào viện, có giấy xác nhận từ bệnh viện.', NULL, 6004),
(21005, '2025-11-08', '2025-11-08', 'Tham dự đám cưới em ruột tại quê nhà, đã thông báo trước 1 tuần.', NULL, 6005),
(21006, '2025-11-10', '2025-11-15', 'Đi du lịch cùng gia đình trong thời gian cao điểm ôn thi cuối kỳ.', NULL, 6006),
(21007, '2025-11-23', '2025-11-24', 'Cần về quê lo hậu sự ông nội, có giấy báo tang.', NULL, 6007),
(21008, '2025-12-02', '2025-12-04', 'Tham gia khóa bồi dưỡng \"Ứng dụng công nghệ trong giảng dạy\" tại Hà Nội.', NULL, 6008),
(22000, '2025-12-17', '2025-12-19', 'bệnh nặng', 'request_1765908020_69419e34c6f55.jpg', 6011);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeucausuadiem`
--

CREATE TABLE `yeucausuadiem` (
  `maYeuCau` int(11) NOT NULL,
  `maGV` int(11) DEFAULT NULL,
  `diemHienTai` decimal(5,2) DEFAULT NULL,
  `diemDeNghiSua` decimal(5,2) DEFAULT NULL,
  `lyDoSuaDiem` text DEFAULT NULL,
  `minhChung` varchar(255) DEFAULT NULL,
  `maBangDiem` int(11) DEFAULT NULL,
  `maHS` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `hocKy` tinyint(4) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `loaiDiem` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `yeucausuadiem`
--

INSERT INTO `yeucausuadiem` (`maYeuCau`, `maGV`, `diemHienTai`, `diemDeNghiSua`, `lyDoSuaDiem`, `minhChung`, `maBangDiem`, `maHS`, `maMonHoc`, `hocKy`, `namHoc`, `loaiDiem`) VALUES
(22001, 6001, 7.00, 7.50, 'Nhập nhầm điểm 15 phút vào cột điểm miệng. Điểm đúng là 7.5 (có bài kiểm tra làm lại).', NULL, 1, 13001, 2001, 1, '2024-2025', 'diem15Phut1'),
(22002, 6002, 8.00, 8.50, 'Cộng sai tổng điểm, khi tính lại theo công thức thì điểm TB phải là 8.5 chứ không phải 8.0.', NULL, 2, 13001, 2002, 1, '2024-2025', 'tbDiem'),
(22003, 6003, 6.50, 7.00, 'Ghi nhầm điểm của học sinh Nguyễn Văn A với học sinh Trần Văn B cùng tên.', NULL, 3, 13001, 2003, 1, '2024-2025', 'diemGiuaKy'),
(22004, 6004, 8.50, 9.00, 'Quên cộng điểm thưởng cho học sinh đạt giải Nhất cuộc thi Văn học (0.5 điểm).', NULL, 4, 13001, 2004, 1, '2024-2025', 'diemCuoiKy'),
(22005, 6005, 6.00, 7.00, 'Lỗi hệ thống tự động làm mất điểm khi import từ Excel, có file backup chứng minh.', NULL, 5, 13001, 2005, 1, '2024-2025', 'diem1Tiet'),
(22006, 6006, 7.00, 9.00, 'Xin tăng điểm cho học sinh vì học sinh có hoàn cảnh khó khăn (không có căn cứ học tập).', NULL, 6, 13001, 2006, 1, '2024-2025', 'diemCuoiKy'),
(22007, 6007, 7.50, 8.00, 'Nhập sai loại điểm, điểm 8.0 là điểm 1 tiết nhưng lại nhập vào cột điểm 15 phút.', NULL, 7, 13001, 2007, 1, '2024-2025', 'diem15Phut2'),
(23003, 6011, 9.00, 10.00, 'hehe', 'request_1765907368_69419ba857bdc.pdf', 21, 13002, 2001, 1, '2024-2025', 'diemTX1');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_phancong_giangday`
--
DROP TABLE IF EXISTS `v_phancong_giangday`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_phancong_giangday`  AS SELECT `pc`.`maPhanCong` AS `maPhanCongGD`, `pc`.`maGV` AS `maGV`, `pc`.`maLop` AS `maLop`, `pc`.`maMonHoc` AS `maMonHoc`, `pc`.`hocKy` AS `hocKy`, `pc`.`namHoc` AS `namHoc`, `pc`.`trangThai` AS `trangThai`, `pc`.`ngayPhanCong` AS `ngayPhanCong`, `pc`.`nguoiPhanCong` AS `nguoiPhanCong`, `pc`.`ghiChu` AS `ghiChu`, 'GVBM' AS `loaiPhanCong`, `gv`.`hoTen` AS `tenGV`, `gv`.`toBoMon` AS `toBoMon`, `l`.`tenLop` AS `tenLop`, `l`.`maKhoi` AS `maKhoi`, `k`.`khoiLop` AS `khoiLop`, `mh`.`tenMonHoc` AS `tenMonHoc`, `mh`.`soTiet` AS `soTiet` FROM ((((`phancong_gvbm` `pc` join `giaovien` `gv` on(`pc`.`maGV` = `gv`.`maGV`)) join `lophoc` `l` on(`pc`.`maLop` = `l`.`maLop`)) join `khoi` `k` on(`l`.`maKhoi` = `k`.`maKhoi`)) join `monhoc` `mh` on(`pc`.`maMonHoc` = `mh`.`maMonHoc`))union all select `pcn`.`maPhanCongCN` AS `maPhanCongGD`,`pcn`.`maGV` AS `maGV`,`pcn`.`maLop` AS `maLop`,`pcn`.`maMonHoc` AS `maMonHoc`,`pcn`.`hocKy` AS `hocKy`,`pcn`.`namHoc` AS `namHoc`,`pcn`.`trangThai` AS `trangThai`,`pcn`.`ngayPhanCong` AS `ngayPhanCong`,`pcn`.`nguoiPhanCong` AS `nguoiPhanCong`,`pcn`.`ghiChu` AS `ghiChu`,'GVCN' AS `loaiPhanCong`,`gv`.`hoTen` AS `tenGV`,`gv`.`toBoMon` AS `toBoMon`,`l`.`tenLop` AS `tenLop`,`l`.`maKhoi` AS `maKhoi`,`k`.`khoiLop` AS `khoiLop`,`mh`.`tenMonHoc` AS `tenMonHoc`,`mh`.`soTiet` AS `soTiet` from ((((`phancong_gvcn` `pcn` join `giaovien` `gv` on(`pcn`.`maGV` = `gv`.`maGV`)) join `lophoc` `l` on(`pcn`.`maLop` = `l`.`maLop`)) join `khoi` `k` on(`l`.`maKhoi` = `k`.`maKhoi`)) left join `monhoc` `mh` on(`pcn`.`maMonHoc` = `mh`.`maMonHoc`))  ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bainop`
--
ALTER TABLE `bainop`
  ADD PRIMARY KEY (`maBaiNop`),
  ADD KEY `bainop_ibfk_1` (`maBaiTap`),
  ADD KEY `bainop_ibfk_2` (`maHS`),
  ADD KEY `bainop_ibfk_3` (`maGV`);

--
-- Chỉ mục cho bảng `baitap`
--
ALTER TABLE `baitap`
  ADD PRIMARY KEY (`maBaiTap`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `baitap_ibfk_5` (`maMonHoc`),
  ADD KEY `baitap_ibfk_maGV` (`maGV`);

--
-- Chỉ mục cho bảng `bangdiem`
--
ALTER TABLE `bangdiem`
  ADD PRIMARY KEY (`maBangDiem`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `idx_hocky_namhoc` (`hocKy`,`namHoc`);

--
-- Chỉ mục cho bảng `baocao_danop`
--
ALTER TABLE `baocao_danop`
  ADD PRIMARY KEY (`maBaoCao`),
  ADD KEY `maGV` (`maGV`);

--
-- Chỉ mục cho bảng `bgh`
--
ALTER TABLE `bgh`
  ADD PRIMARY KEY (`maBGH`),
  ADD KEY `maYeuCau` (`maYeuCau`),
  ADD KEY `maDeThi` (`maDeThi`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Chỉ mục cho bảng `buoiday_thucte`
--
ALTER TABLE `buoiday_thucte`
  ADD PRIMARY KEY (`maBuoiDay`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Chỉ mục cho bảng `danhhieu`
--
ALTER TABLE `danhhieu`
  ADD PRIMARY KEY (`maDanhHieu`);

--
-- Chỉ mục cho bảng `dethi`
--
ALTER TABLE `dethi`
  ADD PRIMARY KEY (`maDeThi`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maTTBM` (`maTTBM`),
  ADD KEY `fk_dethi_giaovien` (`maGV`),
  ADD KEY `idx_trangThai` (`trangThai`),
  ADD KEY `idx_hocKy_namHoc` (`hocKy`,`namHoc`),
  ADD KEY `idx_maKhoi` (`maKhoi`),
  ADD KEY `idx_maKyThi` (`maKyThi`);

--
-- Chỉ mục cho bảng `dethi_kythi`
--
ALTER TABLE `dethi_kythi`
  ADD PRIMARY KEY (`maDeThiKyThi`),
  ADD UNIQUE KEY `UK_dethi_kythi` (`maDeThi`,`maKyThi`),
  ADD KEY `FK_dethi_kythi_dethi` (`maDeThi`),
  ADD KEY `FK_dethi_kythi_kythi` (`maKyThi`),
  ADD KEY `FK_dethi_kythi_bgh` (`maBGH`);

--
-- Chỉ mục cho bảng `giaovien`
--
ALTER TABLE `giaovien`
  ADD PRIMARY KEY (`maGV`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Chỉ mục cho bảng `hanhkiem`
--
ALTER TABLE `hanhkiem`
  ADD PRIMARY KEY (`maHanhKiem`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `hocKy_namHoc` (`hocKy`,`namHoc`);

--
-- Chỉ mục cho bảng `hanhkiem_boloc`
--
ALTER TABLE `hanhkiem_boloc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maGV` (`maGV`);

--
-- Chỉ mục cho bảng `hocluc`
--
ALTER TABLE `hocluc`
  ADD PRIMARY KEY (`maHocLuc`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `namHoc` (`namHoc`);

--
-- Chỉ mục cho bảng `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD PRIMARY KEY (`maHS`),
  ADD KEY `maDanhHieu` (`maDanhHieu`),
  ADD KEY `maPH` (`maPH`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Chỉ mục cho bảng `hocsinh_danhhieu`
--
ALTER TABLE `hocsinh_danhhieu`
  ADD PRIMARY KEY (`maHS`),
  ADD KEY `fk_hs_dh_dh` (`maDanhHieu`);

--
-- Chỉ mục cho bảng `hoso`
--
ALTER TABLE `hoso`
  ADD PRIMARY KEY (`maHoSo`);

--
-- Chỉ mục cho bảng `hosogiaovien`
--
ALTER TABLE `hosogiaovien`
  ADD PRIMARY KEY (`maGV`),
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Chỉ mục cho bảng `hosohocsinh`
--
ALTER TABLE `hosohocsinh`
  ADD KEY `maHoSo` (`maHoSo`),
  ADD KEY `hosohocsinh_ibfk_2` (`maHS`);

--
-- Chỉ mục cho bảng `hosophuhuynh`
--
ALTER TABLE `hosophuhuynh`
  ADD PRIMARY KEY (`maPH`),
  ADD KEY `maHS` (`maHS`);

--
-- Chỉ mục cho bảng `kehoach_giangday`
--
ALTER TABLE `kehoach_giangday`
  ADD PRIMARY KEY (`maKeHoach`),
  ADD UNIQUE KEY `unique_kehoach` (`maGV`,`maLop`,`maMonHoc`,`hocKy`,`namHoc`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Chỉ mục cho bảng `khenthuong`
--
ALTER TABLE `khenthuong`
  ADD PRIMARY KEY (`maKhenThuong`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `hocKy_namHoc` (`hocKy`,`namHoc`);

--
-- Chỉ mục cho bảng `khoi`
--
ALTER TABLE `khoi`
  ADD PRIMARY KEY (`maKhoi`);

--
-- Chỉ mục cho bảng `kyluat`
--
ALTER TABLE `kyluat`
  ADD PRIMARY KEY (`maKyLuat`),
  ADD KEY `maHS` (`maHS`);

--
-- Chỉ mục cho bảng `kythi`
--
ALTER TABLE `kythi`
  ADD PRIMARY KEY (`maKyThi`),
  ADD KEY `FK_kythi_khoi` (`maKhoi`),
  ADD KEY `idx_kythi_khoi_trangthai` (`maKhoi`,`trangThai`);

--
-- Chỉ mục cho bảng `lich`
--
ALTER TABLE `lich`
  ADD PRIMARY KEY (`maLich`);

--
-- Chỉ mục cho bảng `lichchamthi`
--
ALTER TABLE `lichchamthi`
  ADD PRIMARY KEY (`maLich`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Chỉ mục cho bảng `lichcoithi`
--
ALTER TABLE `lichcoithi`
  ADD PRIMARY KEY (`maLich`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Chỉ mục cho bảng `lichday`
--
ALTER TABLE `lichday`
  ADD PRIMARY KEY (`maLichDay`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Chỉ mục cho bảng `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  ADD PRIMARY KEY (`maLichSu`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`),
  ADD KEY `idx_maTaiKhoan` (`maTaiKhoan`),
  ADD KEY `idx_hanhDong` (`hanhDong`),
  ADD KEY `idx_thoiGian` (`thoiGian`),
  ADD KEY `idx_bangDuLieu` (`bangDuLieu`);

--
-- Chỉ mục cho bảng `lichsuxulydethi`
--
ALTER TABLE `lichsuxulydethi`
  ADD PRIMARY KEY (`maLichSu`),
  ADD KEY `maDeThi` (`maDeThi`),
  ADD KEY `nguoiXuLy` (`nguoiXuLy`),
  ADD KEY `idx_loaiXuLy` (`loaiXuLy`),
  ADD KEY `idx_ngayXuLy` (`ngayXuLy`);

--
-- Chỉ mục cho bảng `lichsu_xuly_yeucau`
--
ALTER TABLE `lichsu_xuly_yeucau`
  ADD PRIMARY KEY (`maLichSu`),
  ADD KEY `maYeuCau` (`maYeuCau`),
  ADD KEY `maBGH` (`maBGH`);

--
-- Chỉ mục cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`maLop`),
  ADD KEY `maPhong` (`maPhong`),
  ADD KEY `maKhoi` (`maKhoi`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `FK_LopHoc_TKB` (`maTKB`);

--
-- Chỉ mục cho bảng `monhoc`
--
ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`maMonHoc`);

--
-- Chỉ mục cho bảng `nghihoc`
--
ALTER TABLE `nghihoc`
  ADD PRIMARY KEY (`maNghiHoc`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `nguoiDuyet` (`nguoiDuyet`),
  ADD KEY `idx_hocky_namhoc` (`hocKy`,`namHoc`);

--
-- Chỉ mục cho bảng `nhomnguoidung`
--
ALTER TABLE `nhomnguoidung`
  ADD PRIMARY KEY (`maNhom`);

--
-- Chỉ mục cho bảng `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  ADD PRIMARY KEY (`maPhanCong`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Chỉ mục cho bảng `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  ADD PRIMARY KEY (`maPhanCong`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Chỉ mục cho bảng `phancong_gvbm`
--
ALTER TABLE `phancong_gvbm`
  ADD PRIMARY KEY (`maPhanCong`),
  ADD UNIQUE KEY `unique_phancong_gvbm` (`maGV`,`maLop`,`maMonHoc`,`hocKy`,`namHoc`),
  ADD KEY `idx_gv_gvbm` (`maGV`),
  ADD KEY `idx_lop_gvbm` (`maLop`),
  ADD KEY `idx_mon_gvbm` (`maMonHoc`),
  ADD KEY `idx_hocky_namhoc_gvbm` (`hocKy`,`namHoc`),
  ADD KEY `idx_nguoi_phancong_gvbm` (`nguoiPhanCong`);

--
-- Chỉ mục cho bảng `phancong_gvcn`
--
ALTER TABLE `phancong_gvcn`
  ADD PRIMARY KEY (`maPhanCongCN`),
  ADD UNIQUE KEY `unique_gvcn_namhoc` (`maGV`,`namHoc`),
  ADD UNIQUE KEY `unique_lop_gvcn` (`maLop`,`namHoc`),
  ADD KEY `idx_gvcn` (`maGV`),
  ADD KEY `idx_lop_gvcn` (`maLop`),
  ADD KEY `idx_namhoc_gvcn` (`namHoc`),
  ADD KEY `idx_nguoi_phancong_gvcn` (`nguoiPhanCong`),
  ADD KEY `fk_gvcn_monhoc` (`maMonHoc`);

--
-- Chỉ mục cho bảng `phancong_hocsinh_lop`
--
ALTER TABLE `phancong_hocsinh_lop`
  ADD PRIMARY KEY (`maPhanCongHS`),
  ADD KEY `idx_hs_pchs` (`maHS`),
  ADD KEY `idx_lop_pchs` (`maLop`),
  ADD KEY `idx_namhoc_pchs` (`namHoc`),
  ADD KEY `idx_loai_phancong` (`loaiPhanCong`),
  ADD KEY `idx_nguoi_phancong_pchs` (`nguoiPhanCong`);

--
-- Chỉ mục cho bảng `phancong_lop_phong`
--
ALTER TABLE `phancong_lop_phong`
  ADD PRIMARY KEY (`maPhanCongLP`),
  ADD UNIQUE KEY `unique_lop_phong_namhoc` (`maLop`,`maPhong`,`namHoc`),
  ADD KEY `idx_lop_lp` (`maLop`),
  ADD KEY `idx_phong_lp` (`maPhong`),
  ADD KEY `idx_namhoc_lp` (`namHoc`),
  ADD KEY `idx_nguoi_phancong_lp` (`nguoiPhanCong`);

--
-- Chỉ mục cho bảng `phong`
--
ALTER TABLE `phong`
  ADD PRIMARY KEY (`maPhong`);

--
-- Chỉ mục cho bảng `phuhuynh`
--
ALTER TABLE `phuhuynh`
  ADD PRIMARY KEY (`maPH`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Chỉ mục cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD PRIMARY KEY (`maQuanTri`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`),
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Chỉ mục cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`maTaiKhoan`),
  ADD UNIQUE KEY `tenDangNhap` (`tenDangNhap`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `maNhom` (`maNhom`),
  ADD KEY `idx_loaiTaiKhoan` (`loaiTaiKhoan`),
  ADD KEY `idx_trangThaiTaiKhoan` (`trangThaiTaiKhoan`),
  ADD KEY `idx_ngayTao` (`ngayTao`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `fk_taikhoan_nguoiTao` (`nguoiTao`),
  ADD KEY `fk_taikhoan_nguoiCapNhat` (`nguoiCapNhat`);

--
-- Chỉ mục cho bảng `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  ADD PRIMARY KEY (`maTKB`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Chỉ mục cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`maThongBao`);

--
-- Chỉ mục cho bảng `ttbm`
--
ALTER TABLE `ttbm`
  ADD PRIMARY KEY (`maTTBM`),
  ADD UNIQUE KEY `maGV` (`maGV`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Chỉ mục cho bảng `vipham`
--
ALTER TABLE `vipham`
  ADD PRIMARY KEY (`maViPham`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `nguoiPhatHien` (`nguoiPhatHien`),
  ADD KEY `hocKy_namHoc` (`hocKy`,`namHoc`);

--
-- Chỉ mục cho bảng `yeucau`
--
ALTER TABLE `yeucau`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maYeuCauNghiPhep` (`maYeuCauNghiPhep`),
  ADD KEY `maYeuCauSuaDiem` (`maYeuCauSuaDiem`),
  ADD KEY `maBGH_XuLy` (`maBGH_XuLy`);

--
-- Chỉ mục cho bảng `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maGV` (`maGV`);

--
-- Chỉ mục cho bảng `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maBangDiem` (`maBangDiem`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bainop`
--
ALTER TABLE `bainop`
  MODIFY `maBaiNop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `baitap`
--
ALTER TABLE `baitap`
  MODIFY `maBaiTap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20031;

--
-- AUTO_INCREMENT cho bảng `bangdiem`
--
ALTER TABLE `bangdiem`
  MODIFY `maBangDiem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT cho bảng `baocao_danop`
--
ALTER TABLE `baocao_danop`
  MODIFY `maBaoCao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `bgh`
--
ALTER TABLE `bgh`
  MODIFY `maBGH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5011;

--
-- AUTO_INCREMENT cho bảng `buoiday_thucte`
--
ALTER TABLE `buoiday_thucte`
  MODIFY `maBuoiDay` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `danhhieu`
--
ALTER TABLE `danhhieu`
  MODIFY `maDanhHieu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2000;

--
-- AUTO_INCREMENT cho bảng `dethi`
--
ALTER TABLE `dethi`
  MODIFY `maDeThi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21013;

--
-- AUTO_INCREMENT cho bảng `dethi_kythi`
--
ALTER TABLE `dethi_kythi`
  MODIFY `maDeThiKyThi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `giaovien`
--
ALTER TABLE `giaovien`
  MODIFY `maGV` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7000;

--
-- AUTO_INCREMENT cho bảng `hanhkiem`
--
ALTER TABLE `hanhkiem`
  MODIFY `maHanhKiem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9141;

--
-- AUTO_INCREMENT cho bảng `hanhkiem_boloc`
--
ALTER TABLE `hanhkiem_boloc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `hocluc`
--
ALTER TABLE `hocluc`
  MODIFY `maHocLuc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9021;

--
-- AUTO_INCREMENT cho bảng `hocsinh`
--
ALTER TABLE `hocsinh`
  MODIFY `maHS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15000;

--
-- AUTO_INCREMENT cho bảng `hoso`
--
ALTER TABLE `hoso`
  MODIFY `maHoSo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6000;

--
-- AUTO_INCREMENT cho bảng `kehoach_giangday`
--
ALTER TABLE `kehoach_giangday`
  MODIFY `maKeHoach` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `khenthuong`
--
ALTER TABLE `khenthuong`
  MODIFY `maKhenThuong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15021;

--
-- AUTO_INCREMENT cho bảng `khoi`
--
ALTER TABLE `khoi`
  MODIFY `maKhoi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12000;

--
-- AUTO_INCREMENT cho bảng `kyluat`
--
ALTER TABLE `kyluat`
  MODIFY `maKyLuat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15111;

--
-- AUTO_INCREMENT cho bảng `kythi`
--
ALTER TABLE `kythi`
  MODIFY `maKyThi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `lich`
--
ALTER TABLE `lich`
  MODIFY `maLich` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25000;

--
-- AUTO_INCREMENT cho bảng `lichday`
--
ALTER TABLE `lichday`
  MODIFY `maLichDay` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16011;

--
-- AUTO_INCREMENT cho bảng `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  MODIFY `maLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31064;

--
-- AUTO_INCREMENT cho bảng `lichsuxulydethi`
--
ALTER TABLE `lichsuxulydethi`
  MODIFY `maLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `lichsu_xuly_yeucau`
--
ALTER TABLE `lichsu_xuly_yeucau`
  MODIFY `maLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `maLop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14000;

--
-- AUTO_INCREMENT cho bảng `monhoc`
--
ALTER TABLE `monhoc`
  MODIFY `maMonHoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3000;

--
-- AUTO_INCREMENT cho bảng `nghihoc`
--
ALTER TABLE `nghihoc`
  MODIFY `maNghiHoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15311;

--
-- AUTO_INCREMENT cho bảng `nhomnguoidung`
--
ALTER TABLE `nhomnguoidung`
  MODIFY `maNhom` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4000;

--
-- AUTO_INCREMENT cho bảng `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  MODIFY `maPhanCong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000;

--
-- AUTO_INCREMENT cho bảng `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  MODIFY `maPhanCong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19000;

--
-- AUTO_INCREMENT cho bảng `phancong_gvbm`
--
ALTER TABLE `phancong_gvbm`
  MODIFY `maPhanCong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `phancong_gvcn`
--
ALTER TABLE `phancong_gvcn`
  MODIFY `maPhanCongCN` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `phancong_hocsinh_lop`
--
ALTER TABLE `phancong_hocsinh_lop`
  MODIFY `maPhanCongHS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `phancong_lop_phong`
--
ALTER TABLE `phancong_lop_phong`
  MODIFY `maPhanCongLP` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `phong`
--
ALTER TABLE `phong`
  MODIFY `maPhong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13000;

--
-- AUTO_INCREMENT cho bảng `phuhuynh`
--
ALTER TABLE `phuhuynh`
  MODIFY `maPH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9000;

--
-- AUTO_INCREMENT cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  MODIFY `maQuanTri` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `maTaiKhoan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5008;

--
-- AUTO_INCREMENT cho bảng `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  MODIFY `maTKB` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28015;

--
-- AUTO_INCREMENT cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `maThongBao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000;

--
-- AUTO_INCREMENT cho bảng `ttbm`
--
ALTER TABLE `ttbm`
  MODIFY `maTTBM` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8000;

--
-- AUTO_INCREMENT cho bảng `vipham`
--
ALTER TABLE `vipham`
  MODIFY `maViPham` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15223;

--
-- AUTO_INCREMENT cho bảng `yeucau`
--
ALTER TABLE `yeucau`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24005;

--
-- AUTO_INCREMENT cho bảng `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22001;

--
-- AUTO_INCREMENT cho bảng `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23004;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bainop`
--
ALTER TABLE `bainop`
  ADD CONSTRAINT `bainop_ibfk_1` FOREIGN KEY (`maBaiTap`) REFERENCES `baitap` (`maBaiTap`) ON DELETE CASCADE,
  ADD CONSTRAINT `bainop_ibfk_2` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `bainop_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `baitap`
--
ALTER TABLE `baitap`
  ADD CONSTRAINT `baitap_ibfk_3` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL,
  ADD CONSTRAINT `baitap_ibfk_5` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE SET NULL,
  ADD CONSTRAINT `baitap_ibfk_maGV` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `bangdiem`
--
ALTER TABLE `bangdiem`
  ADD CONSTRAINT `bangdiem_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `bangdiem_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `baocao_danop`
--
ALTER TABLE `baocao_danop`
  ADD CONSTRAINT `baocao_danop_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `bgh`
--
ALTER TABLE `bgh`
  ADD CONSTRAINT `bgh_ibfk_1` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucau` (`maYeuCau`) ON DELETE SET NULL,
  ADD CONSTRAINT `bgh_ibfk_2` FOREIGN KEY (`maDeThi`) REFERENCES `dethi` (`maDeThi`) ON DELETE SET NULL,
  ADD CONSTRAINT `bgh_ibfk_3` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `buoiday_thucte`
--
ALTER TABLE `buoiday_thucte`
  ADD CONSTRAINT `buoiday_thucte_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `buoiday_thucte_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `buoiday_thucte_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `dethi`
--
ALTER TABLE `dethi`
  ADD CONSTRAINT `dethi_ibfk_1` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dethi_giaovien` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dethi_khoi` FOREIGN KEY (`maKhoi`) REFERENCES `khoi` (`maKhoi`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dethi_kythi` FOREIGN KEY (`maKyThi`) REFERENCES `kythi` (`maKyThi`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dethi_ttbm` FOREIGN KEY (`maTTBM`) REFERENCES `ttbm` (`maTTBM`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `dethi_kythi`
--
ALTER TABLE `dethi_kythi`
  ADD CONSTRAINT `FK_dethi_kythi_bgh` FOREIGN KEY (`maBGH`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_dethi_kythi_dethi` FOREIGN KEY (`maDeThi`) REFERENCES `dethi` (`maDeThi`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_dethi_kythi_kythi` FOREIGN KEY (`maKyThi`) REFERENCES `kythi` (`maKyThi`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `giaovien`
--
ALTER TABLE `giaovien`
  ADD CONSTRAINT `giaovien_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `hanhkiem`
--
ALTER TABLE `hanhkiem`
  ADD CONSTRAINT `hanhkiem_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hanhkiem_boloc`
--
ALTER TABLE `hanhkiem_boloc`
  ADD CONSTRAINT `conduct_criteria_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hocluc`
--
ALTER TABLE `hocluc`
  ADD CONSTRAINT `hocluc_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD CONSTRAINT `fk_hocsinh_lophoc` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hocsinh_phuhuynh` FOREIGN KEY (`maPH`) REFERENCES `phuhuynh` (`maPH`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hocsinh_taikhoan` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hocsinh_danhhieu`
--
ALTER TABLE `hocsinh_danhhieu`
  ADD CONSTRAINT `fk_hs_dh_dh` FOREIGN KEY (`maDanhHieu`) REFERENCES `danhhieu` (`maDanhHieu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hs_dh_hs` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hosogiaovien`
--
ALTER TABLE `hosogiaovien`
  ADD CONSTRAINT `hosogiaovien_ibfk_1` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE SET NULL,
  ADD CONSTRAINT `hosogiaovien_ibfk_2` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hosohocsinh`
--
ALTER TABLE `hosohocsinh`
  ADD CONSTRAINT `hosohocsinh_ibfk_1` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE CASCADE,
  ADD CONSTRAINT `hosohocsinh_ibfk_2` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hosophuhuynh`
--
ALTER TABLE `hosophuhuynh`
  ADD CONSTRAINT `hosophuhuynh_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `kehoach_giangday`
--
ALTER TABLE `kehoach_giangday`
  ADD CONSTRAINT `kehoach_giangday_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `kehoach_giangday_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `kehoach_giangday_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `khenthuong`
--
ALTER TABLE `khenthuong`
  ADD CONSTRAINT `khenthuong_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `kyluat`
--
ALTER TABLE `kyluat`
  ADD CONSTRAINT `kyluat_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `kythi`
--
ALTER TABLE `kythi`
  ADD CONSTRAINT `FK_kythi_khoi` FOREIGN KEY (`maKhoi`) REFERENCES `khoi` (`maKhoi`);

--
-- Các ràng buộc cho bảng `lichchamthi`
--
ALTER TABLE `lichchamthi`
  ADD CONSTRAINT `lichchamthi_ibfk_1` FOREIGN KEY (`maLich`) REFERENCES `lich` (`maLich`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichchamthi_ibfk_2` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lichcoithi`
--
ALTER TABLE `lichcoithi`
  ADD CONSTRAINT `lichcoithi_ibfk_1` FOREIGN KEY (`maLich`) REFERENCES `lich` (`maLich`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichcoithi_ibfk_2` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lichday`
--
ALTER TABLE `lichday`
  ADD CONSTRAINT `lichday_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_4` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  ADD CONSTRAINT `lichsunhapxuat_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lichsuxulydethi`
--
ALTER TABLE `lichsuxulydethi`
  ADD CONSTRAINT `fk_lichsu_dethi` FOREIGN KEY (`maDeThi`) REFERENCES `dethi` (`maDeThi`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lichsu_nguoixuly` FOREIGN KEY (`nguoiXuLy`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lichsu_xuly_yeucau`
--
ALTER TABLE `lichsu_xuly_yeucau`
  ADD CONSTRAINT `lichsu_xuly_yeucau_ibfk_1` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucau` (`maYeuCau`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lichsu_xuly_yeucau_ibfk_2` FOREIGN KEY (`maBGH`) REFERENCES `bgh` (`maBGH`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD CONSTRAINT `FK_LopHoc_TKB` FOREIGN KEY (`maTKB`) REFERENCES `thoikhoabieu` (`maTKB`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_2` FOREIGN KEY (`maKhoi`) REFERENCES `khoi` (`maKhoi`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `nghihoc`
--
ALTER TABLE `nghihoc`
  ADD CONSTRAINT `nghihoc_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `nghihoc_ibfk_2` FOREIGN KEY (`nguoiDuyet`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  ADD CONSTRAINT `phancongchamdiem_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongchamdiem_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongchamdiem_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  ADD CONSTRAINT `phancongcoithi_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_4` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phancong_gvbm`
--
ALTER TABLE `phancong_gvbm`
  ADD CONSTRAINT `phancong_gvbm_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_gvbm_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_gvbm_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_gvbm_ibfk_4` FOREIGN KEY (`nguoiPhanCong`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phancong_gvcn`
--
ALTER TABLE `phancong_gvcn`
  ADD CONSTRAINT `fk_gvcn_monhoc` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`),
  ADD CONSTRAINT `phancong_gvcn_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_gvcn_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_gvcn_ibfk_3` FOREIGN KEY (`nguoiPhanCong`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phancong_hocsinh_lop`
--
ALTER TABLE `phancong_hocsinh_lop`
  ADD CONSTRAINT `phancong_hocsinh_lop_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_hocsinh_lop_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_hocsinh_lop_ibfk_3` FOREIGN KEY (`nguoiPhanCong`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phancong_lop_phong`
--
ALTER TABLE `phancong_lop_phong`
  ADD CONSTRAINT `phancong_lop_phong_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_lop_phong_ibfk_2` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancong_lop_phong_ibfk_3` FOREIGN KEY (`nguoiPhanCong`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phuhuynh`
--
ALTER TABLE `phuhuynh`
  ADD CONSTRAINT `phuhuynh_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD CONSTRAINT `quantrivien_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL,
  ADD CONSTRAINT `quantrivien_ibfk_2` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `fk_taikhoan_nguoiCapNhat` FOREIGN KEY (`nguoiCapNhat`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_taikhoan_nguoiTao` FOREIGN KEY (`nguoiTao`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL,
  ADD CONSTRAINT `taikhoan_ibfk_1` FOREIGN KEY (`maNhom`) REFERENCES `nhomnguoidung` (`maNhom`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  ADD CONSTRAINT `thoikhoabieu_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `thoikhoabieu_ibfk_2` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL,
  ADD CONSTRAINT `thoikhoabieu_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ttbm`
--
ALTER TABLE `ttbm`
  ADD CONSTRAINT `ttbm_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `ttbm_ibfk_2` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `vipham`
--
ALTER TABLE `vipham`
  ADD CONSTRAINT `vipham_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `vipham_ibfk_2` FOREIGN KEY (`nguoiPhatHien`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `yeucau`
--
ALTER TABLE `yeucau`
  ADD CONSTRAINT `yeucau_ibfk_1` FOREIGN KEY (`maYeuCauNghiPhep`) REFERENCES `yeucaunghiphep` (`maYeuCau`) ON DELETE CASCADE,
  ADD CONSTRAINT `yeucau_ibfk_2` FOREIGN KEY (`maYeuCauSuaDiem`) REFERENCES `yeucausuadiem` (`maYeuCau`) ON DELETE CASCADE,
  ADD CONSTRAINT `yeucau_ibfk_3` FOREIGN KEY (`maBGH_XuLy`) REFERENCES `bgh` (`maBGH`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  ADD CONSTRAINT `yeucaunghiphep_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  ADD CONSTRAINT `yeucausuadiem_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `yeucausuadiem_ibfk_2` FOREIGN KEY (`maBangDiem`) REFERENCES `bangdiem` (`maBangDiem`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
