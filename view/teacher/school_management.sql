-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 03:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KiemTraTrungLichGiaoVien` (IN `p_maGV` INT, IN `p_thu` TINYINT, IN `p_tietBatDau` TINYINT, IN `p_tietKetThuc` TINYINT, IN `p_hocKy` TINYINT, IN `p_namHoc` VARCHAR(20))   BEGIN
  SELECT EXISTS(
    SELECT 1 FROM lichday
    WHERE maGV = p_maGV AND thu = p_thu AND hocKy = p_hocKy AND namHoc = p_namHoc
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TinhDiemTrungBinhMon` (IN `p_maHS` INT, IN `p_maMonHoc` INT, IN `p_hocKy` TINYINT, IN `p_namHoc` VARCHAR(20))   BEGIN
  DECLARE _sum DECIMAL(10,4) DEFAULT 0;
  DECLARE _totalWeight DECIMAL(10,4) DEFAULT 0;
  SELECT
    IFNULL(SUM(CASE loaiDiem WHEN 'mieng' THEN diem*1 WHEN '15phut' THEN diem*1 WHEN '1tiet' THEN diem*2 WHEN 'giuaky' THEN diem*2 WHEN 'cuoiky' THEN diem*3 ELSE diem*1 END),0),
    IFNULL(SUM(CASE loaiDiem WHEN 'mieng' THEN 1 WHEN '15phut' THEN 1 WHEN '1tiet' THEN 2 WHEN 'giuaky' THEN 2 WHEN 'cuoiky' THEN 3 ELSE 1 END),0)
  INTO _sum, _totalWeight
  FROM bangdiem
  WHERE maHS = p_maHS AND maMonHoc = p_maMonHoc AND hocKy = p_hocKy AND namHoc = p_namHoc;

  IF _totalWeight = 0 THEN
    SELECT NULL AS diemTB;
  ELSE
    SELECT ROUND(_sum / _totalWeight,2) AS diemTB;
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

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `baitap`
--

CREATE TABLE `baitap` (
  `maBaiTap` int(11) NOT NULL,
  `tenBaiTap` varchar(255) NOT NULL,
  `yeuCauBaiTap` text DEFAULT NULL,
  `thoiGianNop` datetime DEFAULT NULL,
  `diemSo` decimal(5,2) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Chuanoop',
  `maHS` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maNhomBT` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `baitap`
--

INSERT INTO `baitap` (`maBaiTap`, `tenBaiTap`, `yeuCauBaiTap`, `thoiGianNop`, `diemSo`, `trangThai`, `maHS`, `maGV`, `maLop`, `maNhomBT`) VALUES
(20001, 'BT Toán 1', 'Làm bài 1-10', '2024-11-20 23:59:00', NULL, 'Chuanoop', 13001, 6001, 10001, 19001),
(20002, 'BT Lý 1', 'Làm bài 1-5', '2024-11-21 23:59:00', NULL, 'Chuanoop', 13002, 6002, 10002, 19002),
(20003, 'BT Hóa 1', 'Làm bài 1-3', '2024-11-22 23:59:00', NULL, 'Chuanoop', 13003, 6003, 10003, 19003),
(20004, 'BT Văn 1', 'Viết đoạn văn', '2024-11-23 23:59:00', NULL, 'Chuanoop', 13004, 6004, 10004, 19004),
(20005, 'BT Anh 1', 'Bài tập ngữ pháp', '2024-11-24 23:59:00', NULL, 'Chuanoop', 13005, 6005, 10005, 19005),
(20006, 'BT Toán 2', 'Làm bài 11-20', '2024-11-25 23:59:00', NULL, 'Chuanoop', 13006, 6001, 10001, 19001),
(20007, 'BT Lý 2', 'Làm bài 6-10', '2024-11-26 23:59:00', NULL, 'Chuanoop', 13007, 6002, 10002, 19002),
(20008, 'BT Hóa 2', 'Làm bài 4-8', '2024-11-27 23:59:00', NULL, 'Chuanoop', 13008, 6003, 10003, 19003),
(20009, 'BT Văn 2', 'Viết đoạn văn 2', '2024-11-28 23:59:00', NULL, 'Chuanoop', 13009, 6004, 10004, 19004),
(20010, 'BT Anh 2', 'Bài tập 2', '2024-11-29 23:59:00', NULL, 'Chuanoop', 13010, 6005, 10005, 19005);

-- --------------------------------------------------------

--
-- Table structure for table `bangdiem`
--

CREATE TABLE `bangdiem` (
  `maDiem` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `maGV` int(11) DEFAULT NULL,
  `hocKy` tinyint(4) NOT NULL,
  `namHoc` varchar(20) NOT NULL,
  `loaiDiem` enum('mieng','15phut','1tiet','giuaky','cuoiky') NOT NULL,
  `diem` decimal(4,2) NOT NULL,
  `lanThi` int(11) DEFAULT 1,
  `ghiChu` text DEFAULT NULL,
  `ngayNhap` datetime DEFAULT current_timestamp(),
  `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bangdiem`
--

INSERT INTO `bangdiem` (`maDiem`, `maHS`, `maMonHoc`, `maGV`, `hocKy`, `namHoc`, `loaiDiem`, `diem`, `lanThi`, `ghiChu`, `ngayNhap`, `ngayCapNhat`) VALUES
(14001, 13001, 2001, 6001, 1, '2024-2025', 'mieng', 8.00, 1, 'miệng', '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14002, 13001, 2001, 6001, 1, '2024-2025', '15phut', 7.50, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14003, 13001, 2001, 6001, 1, '2024-2025', '1tiet', 8.50, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14004, 13001, 2001, 6001, 1, '2024-2025', 'giuaky', 8.00, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14005, 13001, 2001, 6001, 1, '2024-2025', 'cuoiky', 8.20, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14006, 13002, 2001, 6001, 1, '2024-2025', 'mieng', 7.00, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14007, 13002, 2002, 6002, 1, '2024-2025', '1tiet', 7.50, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14008, 13003, 2003, 6003, 1, '2024-2025', '15phut', 6.50, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14009, 13004, 2004, 6004, 1, '2024-2025', 'cuoiky', 6.80, 1, NULL, '2025-11-01 11:48:54', '2025-11-01 11:48:54'),
(14010, 13005, 2005, 6005, 1, '2024-2025', 'mieng', 9.00, 1, '', '2025-11-01 11:48:54', '2025-11-01 11:48:54');

--
-- Triggers `bangdiem`
--
DELIMITER $$
CREATE TRIGGER `trg_KiemTraDiem_BeforeInsert` BEFORE INSERT ON `bangdiem` FOR EACH ROW BEGIN
  IF NEW.diem < 0 OR NEW.diem > 10 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Diem phai trong khoang 0-10';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_KiemTraDiem_BeforeUpdate` BEFORE UPDATE ON `bangdiem` FOR EACH ROW BEGIN
  IF NEW.diem < 0 OR NEW.diem > 10 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Diem phai trong khoang 0-10';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `baocao`
--

CREATE TABLE `baocao` (
  `maBaoCao` int(11) NOT NULL,
  `tenBaoCao` varchar(255) NOT NULL,
  `loaiBaoCao` varchar(100) DEFAULT NULL,
  `noiDung` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`noiDung`)),
  `hocKy` tinyint(4) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maKhoi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `baocao`
--

INSERT INTO `baocao` (`maBaoCao`, `tenBaoCao`, `loaiBaoCao`, `noiDung`, `hocKy`, `namHoc`, `maLop`, `maKhoi`) VALUES
(26001, 'Báo cáo điểm TB 10A1', 'diemtb', '{\"diemTB\": 8.2}', 1, '2024-2025', 10001, 11001),
(26002, 'Báo cáo sĩ số 10', 'siso', '{\"siso\": 20}', 1, '2024-2025', 10001, 11001);

-- --------------------------------------------------------

--
-- Table structure for table `bgh`
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
-- Dumping data for table `bgh`
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
-- Table structure for table `danhhieu`
--

CREATE TABLE `danhhieu` (
  `maDanhHieu` int(11) NOT NULL,
  `tenDanhHieu` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `danhhieu`
--

INSERT INTO `danhhieu` (`maDanhHieu`, `tenDanhHieu`) VALUES
(1001, 'Học sinh xuất sắc'),
(1002, 'Học sinh tiên tiến'),
(1003, 'Học sinh tiến bộ'),
(1004, 'Học sinh giỏi'),
(1005, 'Học sinh xuất sắc cấp thành phố'),
(1006, 'Danh hiệu Thủ khoa'),
(1007, 'Học sinh tiêu biểu'),
(1008, 'Học sinh có thành tích thể thao'),
(1009, 'Học sinh tích cực'),
(1010, 'Học sinh gương mẫu');

-- --------------------------------------------------------

--
-- Table structure for table `dethi`
--

CREATE TABLE `dethi` (
  `maDeThi` int(11) NOT NULL,
  `tenDeThi` varchar(255) NOT NULL,
  `maMonHoc` int(11) NOT NULL,
  `hocKy` tinyint(4) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Choduyet',
  `maBGH` int(11) DEFAULT NULL,
  `maTTBM` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dethi`
--

INSERT INTO `dethi` (`maDeThi`, `tenDeThi`, `maMonHoc`, `hocKy`, `namHoc`, `trangThai`, `maBGH`, `maTTBM`) VALUES
(18001, 'Đề thi Toán HK1', 2001, 1, '2024-2025', 'Daduyet', NULL, 7001),
(18002, 'Đề thi Lý HK1', 2002, 1, '2024-2025', 'Daduyet', NULL, 7002),
(18003, 'Đề thi Hóa HK1', 2003, 1, '2024-2025', 'Daduyet', NULL, 7003),
(18004, 'Đề thi Văn HK1', 2004, 1, '2024-2025', 'Daduyet', NULL, 7004),
(18005, 'Đề thi Anh HK1', 2009, 1, '2024-2025', 'Daduyet', NULL, 7005),
(18006, 'Đề thi Sinh HK1', 2007, 1, '2024-2025', 'Daduyet', NULL, 7006),
(18007, 'Đề thi Địa HK1', 2006, 1, '2024-2025', 'Daduyet', NULL, 7007),
(18008, 'Đề thi Tin HK1', 2008, 1, '2024-2025', 'Daduyet', NULL, 7008),
(18009, 'Đề thi GDCD HK1', 2010, 1, '2024-2025', 'Daduyet', NULL, 7009),
(18010, 'Đề thi Lịch sử HK1', 2005, 1, '2024-2025', 'Daduyet', NULL, 7010);

-- --------------------------------------------------------

--
-- Table structure for table `giaovien`
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
-- Dumping data for table `giaovien`
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
(6010, 'Dang Thi G10', '1984-10-10', 'Nu', 'gv10@example.com', '0901000010', 4110, 'GDCD');

-- --------------------------------------------------------

--
-- Table structure for table `hanhkiem`
--

CREATE TABLE `hanhkiem` (
  `maHanhKiem` int(11) NOT NULL,
  `tenHanhKiem` varchar(50) DEFAULT NULL,
  `soBuoiNghiCoPhep` int(11) DEFAULT 0,
  `soBuoiNghiKhongPhep` int(11) DEFAULT 0,
  `soLanVP` int(11) DEFAULT 0,
  `loaiHK` varchar(50) DEFAULT NULL,
  `maDanhHieu` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hanhkiem`
--

INSERT INTO `hanhkiem` (`maHanhKiem`, `tenHanhKiem`, `soBuoiNghiCoPhep`, `soBuoiNghiKhongPhep`, `soLanVP`, `loaiHK`, `maDanhHieu`) VALUES
(9101, 'Tốt', 1, 0, 0, 'Tot', 1001),
(9102, 'Khá', 2, 0, 1, 'Kha', 1002),
(9103, 'TB', 3, 1, 2, 'TB', 1003),
(9104, 'Yếu', 6, 2, 3, 'Yeu', 1004),
(9105, 'Kém', 10, 4, 5, 'Kem', 1005),
(9106, 'Tốt cao', 0, 0, 0, 'Tot', 1001),
(9107, 'Khá 2', 1, 0, 0, 'Kha', 1002),
(9108, 'TB2', 2, 1, 1, 'TB', 1003),
(9109, 'Yếu2', 5, 2, 2, 'Yeu', 1004),
(9110, 'Kém2', 9, 3, 4, 'Kem', 1005);

-- --------------------------------------------------------

--
-- Table structure for table `hocluc`
--

CREATE TABLE `hocluc` (
  `maHocLuc` int(11) NOT NULL,
  `tenHocLuc` varchar(50) DEFAULT NULL,
  `diemTBCacMon` decimal(5,2) DEFAULT NULL,
  `diemTBCaNam` decimal(5,2) DEFAULT NULL,
  `diemTBHocKy` decimal(5,2) DEFAULT NULL,
  `loaiHocLuc` varchar(50) DEFAULT NULL,
  `maDanhHieu` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hocluc`
--

INSERT INTO `hocluc` (`maHocLuc`, `tenHocLuc`, `diemTBCacMon`, `diemTBCaNam`, `diemTBHocKy`, `loaiHocLuc`, `maDanhHieu`) VALUES
(9001, 'Giỏi', 8.50, 8.60, 8.50, 'Gioi', 1001),
(9002, 'Khá', 7.00, 7.10, 7.00, 'Kha', 1002),
(9003, 'Trung bình', 5.80, 5.70, 5.80, 'TB', 1003),
(9004, 'Yếu', 4.50, 4.60, 4.50, 'Yeu', 1004),
(9005, 'Kém', 2.80, 2.90, 2.80, 'Kem', 1005),
(9006, 'Xuất sắc', 9.40, 9.50, 9.40, 'Xuat sac', 1006),
(9007, 'Khá', 7.50, 7.40, 7.50, 'Kha', 1002),
(9008, 'Giỏi', 8.00, 8.10, 8.00, 'Gioi', 1001),
(9009, 'Trung bình', 6.00, 6.10, 6.00, 'TB', 1003),
(9010, 'Khá cao', 7.80, 7.90, 7.80, 'Kha', 1002);

-- --------------------------------------------------------

--
-- Table structure for table `hocsinh`
--

CREATE TABLE `hocsinh` (
  `maHS` int(11) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` enum('Nam','Nu','Khac') DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `trangThaiHocTap` varchar(50) DEFAULT 'danghoc',
  `maHocLuc` int(11) DEFAULT NULL,
  `maHanhKiem` int(11) DEFAULT NULL,
  `maPH` int(11) DEFAULT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hocsinh`
--

INSERT INTO `hocsinh` (`maHS`, `hoTen`, `ngaySinh`, `gioiTinh`, `diaChi`, `trangThaiHocTap`, `maHocLuc`, `maHanhKiem`, `maPH`, `maTaiKhoan`, `maLop`) VALUES
(13001, 'HS A1', '2008-01-01', 'Nam', 'HN', 'danghoc', 9001, 9101, 8001, 4201, 10001),
(13002, 'HS A2', '2008-02-02', 'Nu', 'HN', 'danghoc', 9002, 9102, 8002, 4202, 10001),
(13003, 'HS A3', '2008-03-03', 'Nam', 'HN', 'danghoc', 9003, 9103, 8003, 4203, 10002),
(13004, 'HS A4', '2008-04-04', 'Nu', 'HN', 'danghoc', 9004, 9104, 8004, 4204, 10002),
(13005, 'HS A5', '2008-05-05', 'Nam', 'HN', 'danghoc', 9005, 9105, 8005, 4205, 10003),
(13006, 'HS A6', '2008-06-06', 'Nu', 'HN', 'danghoc', 9006, 9106, 8006, 4206, 10003),
(13007, 'HS A7', '2008-07-07', 'Nam', 'HN', 'danghoc', 9007, 9107, 8007, 4207, 10004),
(13008, 'HS A8', '2008-08-08', 'Nu', 'HN', 'danghoc', 9008, 9108, 8008, 4208, 10004),
(13009, 'HS A9', '2008-09-09', 'Nam', 'HN', 'danghoc', 9009, 9109, 8009, 4209, 10005),
(13010, 'HS A10', '2008-10-10', 'Nu', 'HN', 'danghoc', 9010, 9110, 8010, 4210, 10005),
(13011, 'HS B1', '2007-11-11', 'Nam', 'HN', 'danghoc', 9001, 9101, 8001, 4211, 10006),
(13012, 'HS B2', '2007-12-12', 'Nu', 'HN', 'danghoc', 9002, 9102, 8002, 4212, 10006),
(13013, 'HS B3', '2007-01-13', 'Nam', 'HN', 'danghoc', 9003, 9103, 8003, 4213, 10007),
(13014, 'HS B4', '2007-02-14', 'Nu', 'HN', 'danghoc', 9004, 9104, 8004, 4214, 10007),
(13015, 'HS B5', '2007-03-15', 'Nam', 'HN', 'danghoc', 9005, 9105, 8005, 4215, 10008),
(13016, 'HS B6', '2007-04-16', 'Nu', 'HN', 'danghoc', 9006, 9106, 8006, 4216, 10008),
(13017, 'HS B7', '2007-05-17', 'Nam', 'HN', 'danghoc', 9007, 9107, 8007, 4217, 10009),
(13018, 'HS B8', '2007-06-18', 'Nu', 'HN', 'danghoc', 9008, 9108, 8008, 4218, 10009),
(13019, 'HS B9', '2007-07-19', 'Nam', 'HN', 'danghoc', 9009, 9109, 8009, 4219, 10010),
(13020, 'HS B10', '2007-08-20', 'Nu', 'HN', 'danghoc', 9010, 9110, 8010, 4220, 10010);

--
-- Triggers `hocsinh`
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
-- Table structure for table `hoso`
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
-- Dumping data for table `hoso`
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
-- Table structure for table `hosogiaovien`
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
-- Table structure for table `hosohocsinh`
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
-- Table structure for table `hosophuhuynh`
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
-- Dumping data for table `hosophuhuynh`
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
-- Table structure for table `khenthuong`
--

CREATE TABLE `khenthuong` (
  `maKhenThuong` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayKhenThuong` date DEFAULT NULL,
  `hinhThuc` varchar(150) DEFAULT NULL,
  `diaDiem` varchar(150) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `capKhenThuong` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `khenthuong`
--

INSERT INTO `khenthuong` (`maKhenThuong`, `maHS`, `ngayKhenThuong`, `hinhThuc`, `diaDiem`, `noiDung`, `capKhenThuong`) VALUES
(15001, 13001, '2024-10-20', 'Giấy khen', 'Trường', 'Học sinh xuất sắc', 'truong'),
(15002, 13002, '2024-09-15', 'Giấy khen', 'Huyện', 'Học sinh giỏi', 'huyen'),
(15003, 13003, '2024-08-10', 'Khen thưởng', 'Tỉnh', 'Thành tích học tập', 'tinh'),
(15004, 13004, '2024-07-01', 'Giấy khen', 'Trường', 'Hoạt động tốt', 'truong'),
(15005, 13005, '2024-06-11', 'Giấy khen', 'Trường', 'Học sinh tiên tiến', 'truong'),
(15006, 13006, '2024-05-12', 'Giấy khen', 'Trường', 'Tham gia CLB', 'truong'),
(15007, 13007, '2024-04-13', 'Giấy khen', 'Huyện', 'VD', 'huyen'),
(15008, 13008, '2024-03-14', 'Giấy khen', 'Tỉnh', 'VD', 'tinh'),
(15009, 13009, '2024-02-15', 'Giấy khen', 'Trường', 'VD', 'truong'),
(15010, 13010, '2024-01-16', 'Giấy khen', 'Trường', 'VD', 'truong');

-- --------------------------------------------------------

--
-- Table structure for table `khoi`
--

CREATE TABLE `khoi` (
  `maKhoi` int(11) NOT NULL,
  `khoiLop` varchar(10) NOT NULL,
  `soLuongHocSinh` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `khoi`
--

INSERT INTO `khoi` (`maKhoi`, `khoiLop`, `soLuongHocSinh`) VALUES
(11001, '10', 0),
(11002, '11', 0),
(11003, '12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `kyluat`
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
-- Dumping data for table `kyluat`
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
-- Table structure for table `lich`
--

CREATE TABLE `lich` (
  `maLich` int(11) NOT NULL,
  `moTa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lich`
--

INSERT INTO `lich` (`maLich`, `moTa`) VALUES
(24001, 'Lịch thi HK1 Toán'),
(24002, 'Lịch chấm HK1'),
(24003, 'Lịch học bù');

-- --------------------------------------------------------

--
-- Table structure for table `lichchamthi`
--

CREATE TABLE `lichchamthi` (
  `maLich` int(11) NOT NULL,
  `ngayChamThi` date DEFAULT NULL,
  `thoiGianChamThi` time DEFAULT NULL,
  `hinhThucChamThi` varchar(50) DEFAULT NULL,
  `maPhong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lichchamthi`
--

INSERT INTO `lichchamthi` (`maLich`, `ngayChamThi`, `thoiGianChamThi`, `hinhThucChamThi`, `maPhong`) VALUES
(24002, '2025-06-10', '13:00:00', 'Tập thể', 12001);

-- --------------------------------------------------------

--
-- Table structure for table `lichcoithi`
--

CREATE TABLE `lichcoithi` (
  `maLich` int(11) NOT NULL,
  `ngayThi` date DEFAULT NULL,
  `thoiGianThi` time DEFAULT NULL,
  `maPhong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lichcoithi`
--

INSERT INTO `lichcoithi` (`maLich`, `ngayThi`, `thoiGianThi`, `maPhong`) VALUES
(24001, '2025-06-01', '08:00:00', 12001);

-- --------------------------------------------------------

--
-- Table structure for table `lichday`
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
-- Dumping data for table `lichday`
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
-- Table structure for table `lichsunhapxuat`
--

CREATE TABLE `lichsunhapxuat` (
  `maLichSu` int(11) NOT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `hanhDong` varchar(50) DEFAULT NULL,
  `bangDuLieu` varchar(100) DEFAULT NULL,
  `thoiGian` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lichsunhapxuat`
--

INSERT INTO `lichsunhapxuat` (`maLichSu`, `maTaiKhoan`, `hanhDong`, `bangDuLieu`, `thoiGian`) VALUES
(28001, 4001, 'dangnhap', 'taikhoan', '2025-11-01 11:48:54'),
(28002, 4003, 'themtk', 'giaovien', '2025-11-01 11:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `lophoc`
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
-- Dumping data for table `lophoc`
--

INSERT INTO `lophoc` (`maLop`, `tenLop`, `siSo`, `namHoc`, `maPhong`, `maKhoi`, `maGV`, `maTKB`) VALUES
(10001, '10A1', 2, '2024-2025', 12001, 11001, 6001, NULL),
(10002, '10A2', 2, '2024-2025', 12002, 11001, 6002, NULL),
(10003, '10A3', 2, '2024-2025', 12003, 11001, 6003, NULL),
(10004, '11A1', 2, '2024-2025', 12004, 11002, 6004, NULL),
(10005, '11A2', 2, '2024-2025', 12005, 11002, 6005, NULL),
(10006, '11A3', 2, '2024-2025', 12006, 11002, 6006, NULL),
(10007, '12A1', 2, '2024-2025', 12007, 11003, 6007, NULL),
(10008, '12A2', 2, '2024-2025', 12008, 11003, 6008, NULL),
(10009, '12A3', 2, '2024-2025', 12009, 11003, 6009, NULL),
(10010, '12A4', 2, '2024-2025', 12010, 11003, 6010, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `monhoc`
--

CREATE TABLE `monhoc` (
  `maMonHoc` int(11) NOT NULL,
  `tenMonHoc` varchar(150) NOT NULL,
  `soTiet` int(11) DEFAULT NULL,
  `moTa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monhoc`
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
-- Table structure for table `nghihoc`
--

CREATE TABLE `nghihoc` (
  `maNghiHoc` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayNghi` date DEFAULT NULL,
  `loaiNghi` enum('cophep','khongphep') DEFAULT 'cophep',
  `lyDo` text DEFAULT NULL,
  `nguoiDuyet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nghihoc`
--

INSERT INTO `nghihoc` (`maNghiHoc`, `maHS`, `ngayNghi`, `loaiNghi`, `lyDo`, `nguoiDuyet`) VALUES
(15301, 13001, '2024-10-10', 'cophep', 'Khám bệnh', 6001),
(15302, 13002, '2024-10-11', 'cophep', 'Khám bệnh', 6002),
(15303, 13003, '2024-10-12', 'khongphep', 'Vắng', 6003),
(15304, 13004, '2024-10-13', 'cophep', 'Đi công tác', 6004),
(15305, 13005, '2024-10-14', 'khongphep', 'Vắng', 6005),
(15306, 13006, '2024-10-15', 'cophep', 'Khám', 6006),
(15307, 13007, '2024-10-16', 'khongphep', 'Vắng', 6007),
(15308, 13008, '2024-10-17', 'cophep', 'Khám', 6008),
(15309, 13009, '2024-10-18', 'cophep', 'Khám', 6009),
(15310, 13010, '2024-10-19', 'khongphep', 'Vắng', 6010);

-- --------------------------------------------------------

--
-- Table structure for table `nhombaitap`
--

CREATE TABLE `nhombaitap` (
  `maNhomBT` int(11) NOT NULL,
  `tenNhomBT` varchar(200) NOT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhombaitap`
--

INSERT INTO `nhombaitap` (`maNhomBT`, `tenNhomBT`, `maLop`, `maMonHoc`, `maGV`) VALUES
(19001, 'Nhóm Toán 10A1', 10001, 2001, 6001),
(19002, 'Nhóm Lý 10A2', 10002, 2002, 6002),
(19003, 'Nhóm Hóa 10A3', 10003, 2003, 6003),
(19004, 'Nhóm Văn 11A1', 10004, 2004, 6004),
(19005, 'Nhóm Anh 11A2', 10005, 2009, 6005);

-- --------------------------------------------------------

--
-- Table structure for table `nhomnguoidung`
--

CREATE TABLE `nhomnguoidung` (
  `maNhom` int(11) NOT NULL,
  `tenNhom` varchar(100) NOT NULL,
  `quyenHan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quyenHan`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhomnguoidung`
--

INSERT INTO `nhomnguoidung` (`maNhom`, `tenNhom`, `quyenHan`) VALUES
(3001, 'Quản trị viên', '[]'),
(3002, 'Ban giám hiệu', '[]'),
(3003, 'Giáo viên', '[]'),
(3004, 'Học sinh', '[]'),
(3005, 'Phụ huynh', '[]'),
(3006, 'Giáo viên chủ nhiệm', '[]'),
(3007, 'Tổ trưởng bộ môn', '[]'),
(3008, 'Khách', '[]'),
(3009, 'Bảo mật', '[]'),
(3010, 'Kế toán', '[]');

-- --------------------------------------------------------

--
-- Table structure for table `phancongchamdiem`
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
-- Dumping data for table `phancongchamdiem`
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
-- Table structure for table `phancongcoithi`
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
-- Dumping data for table `phancongcoithi`
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
-- Table structure for table `phong`
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
-- Dumping data for table `phong`
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
-- Table structure for table `phuhuynh`
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
-- Dumping data for table `phuhuynh`
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
-- Table structure for table `quantrivien`
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
-- Table structure for table `taikhoan`
--

CREATE TABLE `taikhoan` (
  `maTaiKhoan` int(11) NOT NULL,
  `tenDangNhap` varchar(100) NOT NULL,
  `matKhau` varchar(255) NOT NULL,
  `hoTen` varchar(150) NOT NULL,
  `loaiTaiKhoan` enum('hocsinh','giaovien','phuhuynh','bangiamhieu','quantrivien') NOT NULL,
  `trangThaiTaiKhoan` tinyint(4) DEFAULT 1,
  `maNhom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`maTaiKhoan`, `tenDangNhap`, `matKhau`, `hoTen`, `loaiTaiKhoan`, `trangThaiTaiKhoan`, `maNhom`) VALUES
(4001, 'admin', '5f4dcc3b5aa765d61d8327deb882cf99', 'Admin Root', 'quantrivien', 1, 3001),
(4002, 'bgh1', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 1', 'bangiamhieu', 1, 3002),
(4003, 'gv1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 1', 'giaovien', 1, 3003),
(4004, 'gv2', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 2', 'giaovien', 1, 3003),
(4005, 'hs1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 1', 'hocsinh', 1, 3004),
(4006, 'ph1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 1', 'phuhuynh', 1, 3005),
(4007, 'qtv1', '5f4dcc3b5aa765d61d8327deb882cf99', 'Quan Tri 1', 'quantrivien', 1, 3001),
(4008, 'ttbm1', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 1', '', 1, 3007),
(4009, 'userA', '5f4dcc3b5aa765d61d8327deb882cf99', 'User A', 'giaovien', 1, 3003),
(4010, 'userB', '5f4dcc3b5aa765d61d8327deb882cf99', 'User B', 'giaovien', 1, 3003),
(4101, 'gv6001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6001', 'giaovien', 1, 3003),
(4102, 'gv6002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6002', 'giaovien', 1, 3003),
(4103, 'gv6003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6003', 'giaovien', 1, 3003),
(4104, 'gv6004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6004', 'giaovien', 1, 3003),
(4105, 'gv6005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6005', 'giaovien', 1, 3003),
(4106, 'gv6006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6006', 'giaovien', 1, 3003),
(4107, 'gv6007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6007', 'giaovien', 1, 3003),
(4108, 'gv6008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6008', 'giaovien', 1, 3003),
(4109, 'gv6009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6009', 'giaovien', 1, 3003),
(4110, 'gv6010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Giao Vien 6010', 'giaovien', 1, 3003),
(4201, 'hs13001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13001', 'hocsinh', 1, 3004),
(4202, 'hs13002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13002', 'hocsinh', 1, 3004),
(4203, 'hs13003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13003', 'hocsinh', 1, 3004),
(4204, 'hs13004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13004', 'hocsinh', 1, 3004),
(4205, 'hs13005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13005', 'hocsinh', 1, 3004),
(4206, 'hs13006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13006', 'hocsinh', 1, 3004),
(4207, 'hs13007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13007', 'hocsinh', 1, 3004),
(4208, 'hs13008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13008', 'hocsinh', 1, 3004),
(4209, 'hs13009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13009', 'hocsinh', 1, 3004),
(4210, 'hs13010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13010', 'hocsinh', 1, 3004),
(4211, 'hs13011', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13011', 'hocsinh', 1, 3004),
(4212, 'hs13012', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13012', 'hocsinh', 1, 3004),
(4213, 'hs13013', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13013', 'hocsinh', 1, 3004),
(4214, 'hs13014', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13014', 'hocsinh', 1, 3004),
(4215, 'hs13015', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13015', 'hocsinh', 1, 3004),
(4216, 'hs13016', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13016', 'hocsinh', 1, 3004),
(4217, 'hs13017', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13017', 'hocsinh', 1, 3004),
(4218, 'hs13018', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13018', 'hocsinh', 1, 3004),
(4219, 'hs13019', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13019', 'hocsinh', 1, 3004),
(4220, 'hs13020', '5f4dcc3b5aa765d61d8327deb882cf99', 'Hoc Sinh 13020', 'hocsinh', 1, 3004),
(4301, 'ph8001', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8001', 'phuhuynh', 1, 3005),
(4302, 'ph8002', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8002', 'phuhuynh', 1, 3005),
(4303, 'ph8003', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8003', 'phuhuynh', 1, 3005),
(4304, 'ph8004', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8004', 'phuhuynh', 1, 3005),
(4305, 'ph8005', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8005', 'phuhuynh', 1, 3005),
(4306, 'ph8006', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8006', 'phuhuynh', 1, 3005),
(4307, 'ph8007', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8007', 'phuhuynh', 1, 3005),
(4308, 'ph8008', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8008', 'phuhuynh', 1, 3005),
(4309, 'ph8009', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8009', 'phuhuynh', 1, 3005),
(4310, 'ph8010', '5f4dcc3b5aa765d61d8327deb882cf99', 'Phu Huynh 8010', 'phuhuynh', 1, 3005),
(4401, 'ttbm7001', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7001', 'giaovien', 1, 3007),
(4402, 'ttbm7002', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7002', 'giaovien', 1, 3007),
(4403, 'ttbm7003', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7003', 'giaovien', 1, 3007),
(4404, 'ttbm7004', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7004', 'giaovien', 1, 3007),
(4405, 'ttbm7005', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7005', 'giaovien', 1, 3007),
(4406, 'ttbm7006', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7006', 'giaovien', 1, 3007),
(4407, 'ttbm7007', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7007', 'giaovien', 1, 3007),
(4408, 'ttbm7008', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7008', 'giaovien', 1, 3007),
(4409, 'ttbm7009', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7009', 'giaovien', 1, 3007),
(4410, 'ttbm7010', '5f4dcc3b5aa765d61d8327deb882cf99', 'TTBM 7010', 'giaovien', 1, 3007),
(4501, 'bgh5001', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5001', 'bangiamhieu', 1, 3002),
(4502, 'bgh5002', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5002', 'bangiamhieu', 1, 3002),
(4503, 'bgh5003', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5003', 'bangiamhieu', 1, 3002),
(4504, 'bgh5004', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5004', 'bangiamhieu', 1, 3002),
(4505, 'bgh5005', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5005', 'bangiamhieu', 1, 3002),
(4506, 'bgh5006', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5006', 'bangiamhieu', 1, 3002),
(4507, 'bgh5007', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5007', 'bangiamhieu', 1, 3002),
(4508, 'bgh5008', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5008', 'bangiamhieu', 1, 3002),
(4509, 'bgh5009', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5009', 'bangiamhieu', 1, 3002),
(4510, 'bgh5010', '5f4dcc3b5aa765d61d8327deb882cf99', 'BGH 5010', 'bangiamhieu', 1, 3002);

-- --------------------------------------------------------

--
-- Table structure for table `thoikhoabieu`
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
-- Dumping data for table `thoikhoabieu`
--

INSERT INTO `thoikhoabieu` (`maTKB`, `tenMonHoc`, `thoiGianHoc`, `thuNgay`, `tietHoc`, `lop`, `phong`, `gv`, `maLop`, `maTaiKhoan`, `maGV`, `maMonHoc`) VALUES
(25001, 'Toán', '07:30:00', '2024-11-03', 'Tiết 1-2', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(25002, 'Vật lý', '09:00:00', '2024-11-03', 'Tiết 3-4', '10A2', 'P102', 'Tran Thi G2', 10002, NULL, 6002, 2002),
(26001, 'Toán', '07:30:00', '2024-11-04', 'Tiết 1', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(26002, 'Ngữ văn', '08:20:00', '2024-11-04', 'Tiết 2', '10A1', 'P105', 'Vo Thi G5', 10001, NULL, 6005, 2005),
(26003, 'Tiếng Anh', '09:30:00', '2024-11-04', 'Tiết 3', '10A1', 'P108', 'Hoang Lan G8', 10001, NULL, 6008, 2008),
(26004, 'Vật lý', '10:20:00', '2024-11-04', 'Tiết 4', '10A1', 'P102', 'Tran Thi G2', 10001, NULL, 6002, 2002),
(26005, 'Tin học', '11:10:00', '2024-11-04', 'Tiết 5', '10A1', 'P109', 'Nguyen Minh G9', 10001, NULL, 6009, 2009),
(26006, 'Hóa học', '13:30:00', '2024-11-04', 'Tiết 6', '10A1', 'P103', 'Le Thi G3', 10001, NULL, 6003, 2003),
(26007, 'Sinh học', '14:20:00', '2024-11-04', 'Tiết 7', '10A1', 'P104', 'Pham Van G4', 10001, NULL, 6004, 2004),
(26008, 'Địa lý', '15:30:00', '2024-11-04', 'Tiết 8', '10A1', 'P107', 'Bui Thi G7', 10001, NULL, 6007, 2007),
(26009, 'Lịch sử', '16:20:00', '2024-11-04', 'Tiết 9', '10A1', 'P106', 'Doan Van G6', 10001, NULL, 6006, 2006),
(26010, 'GDCD', '17:10:00', '2024-11-04', 'Tiết 10', '10A1', 'P110', 'Tran Quang G10', 10001, NULL, 6010, 2010),
(26011, 'Ngữ văn', '07:30:00', '2024-11-05', 'Tiết 1', '10A1', 'P105', 'Vo Thi G5', 10001, NULL, 6005, 2005),
(26012, 'Toán', '08:20:00', '2024-11-05', 'Tiết 2', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(26013, 'Tiếng Anh', '09:30:00', '2024-11-05', 'Tiết 3', '10A1', 'P108', 'Hoang Lan G8', 10001, NULL, 6008, 2008),
(26014, 'Hóa học', '10:20:00', '2024-11-05', 'Tiết 4', '10A1', 'P103', 'Le Thi G3', 10001, NULL, 6003, 2003),
(26015, 'Sinh học', '11:10:00', '2024-11-05', 'Tiết 5', '10A1', 'P104', 'Pham Van G4', 10001, NULL, 6004, 2004),
(26016, 'Vật lý', '13:30:00', '2024-11-05', 'Tiết 6', '10A1', 'P102', 'Tran Thi G2', 10001, NULL, 6002, 2002),
(26017, 'Tin học', '14:20:00', '2024-11-05', 'Tiết 7', '10A1', 'P109', 'Nguyen Minh G9', 10001, NULL, 6009, 2009),
(26018, 'Địa lý', '15:30:00', '2024-11-05', 'Tiết 8', '10A1', 'P107', 'Bui Thi G7', 10001, NULL, 6007, 2007),
(26019, 'Lịch sử', '16:20:00', '2024-11-05', 'Tiết 9', '10A1', 'P106', 'Doan Van G6', 10001, NULL, 6006, 2006),
(26020, 'GDCD', '17:10:00', '2024-11-05', 'Tiết 10', '10A1', 'P110', 'Tran Quang G10', 10001, NULL, 6010, 2010),
(26021, 'Toán', '07:30:00', '2024-11-06', 'Tiết 1', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(26022, 'Vật lý', '08:20:00', '2024-11-06', 'Tiết 2', '10A1', 'P102', 'Tran Thi G2', 10001, NULL, 6002, 2002),
(26023, 'Hóa học', '09:30:00', '2024-11-06', 'Tiết 3', '10A1', 'P103', 'Le Thi G3', 10001, NULL, 6003, 2003),
(26024, 'Sinh học', '10:20:00', '2024-11-06', 'Tiết 4', '10A1', 'P104', 'Pham Van G4', 10001, NULL, 6004, 2004),
(26025, 'Ngữ văn', '11:10:00', '2024-11-06', 'Tiết 5', '10A1', 'P105', 'Vo Thi G5', 10001, NULL, 6005, 2005),
(26026, 'Tiếng Anh', '13:30:00', '2024-11-06', 'Tiết 6', '10A1', 'P108', 'Hoang Lan G8', 10001, NULL, 6008, 2008),
(26027, 'Tin học', '14:20:00', '2024-11-06', 'Tiết 7', '10A1', 'P109', 'Nguyen Minh G9', 10001, NULL, 6009, 2009),
(26028, 'Địa lý', '15:30:00', '2024-11-06', 'Tiết 8', '10A1', 'P107', 'Bui Thi G7', 10001, NULL, 6007, 2007),
(26029, 'Lịch sử', '16:20:00', '2024-11-06', 'Tiết 9', '10A1', 'P106', 'Doan Van G6', 10001, NULL, 6006, 2006),
(26030, 'GDCD', '17:10:00', '2024-11-06', 'Tiết 10', '10A1', 'P110', 'Tran Quang G10', 10001, NULL, 6010, 2010),
(26031, 'Ngữ văn', '07:30:00', '2024-11-07', 'Tiết 1', '10A1', 'P105', 'Vo Thi G5', 10001, NULL, 6005, 2005),
(26032, 'Toán', '08:20:00', '2024-11-07', 'Tiết 2', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(26033, 'Tiếng Anh', '09:30:00', '2024-11-07', 'Tiết 3', '10A1', 'P108', 'Hoang Lan G8', 10001, NULL, 6008, 2008),
(26034, 'Vật lý', '10:20:00', '2024-11-07', 'Tiết 4', '10A1', 'P102', 'Tran Thi G2', 10001, NULL, 6002, 2002),
(26035, 'Hóa học', '11:10:00', '2024-11-07', 'Tiết 5', '10A1', 'P103', 'Le Thi G3', 10001, NULL, 6003, 2003),
(26036, 'Sinh học', '13:30:00', '2024-11-07', 'Tiết 6', '10A1', 'P104', 'Pham Van G4', 10001, NULL, 6004, 2004),
(26037, 'Tin học', '14:20:00', '2024-11-07', 'Tiết 7', '10A1', 'P109', 'Nguyen Minh G9', 10001, NULL, 6009, 2009),
(26038, 'Địa lý', '15:30:00', '2024-11-07', 'Tiết 8', '10A1', 'P107', 'Bui Thi G7', 10001, NULL, 6007, 2007),
(26039, 'Lịch sử', '16:20:00', '2024-11-07', 'Tiết 9', '10A1', 'P106', 'Doan Van G6', 10001, NULL, 6006, 2006),
(26040, 'GDCD', '17:10:00', '2024-11-07', 'Tiết 10', '10A1', 'P110', 'Tran Quang G10', 10001, NULL, 6010, 2010),
(26041, 'Toán', '07:30:00', '2024-11-08', 'Tiết 1', '10A1', 'P101', 'Nguyen Van G1', 10001, NULL, 6001, 2001),
(26042, 'Ngữ văn', '08:20:00', '2024-11-08', 'Tiết 2', '10A1', 'P105', 'Vo Thi G5', 10001, NULL, 6005, 2005),
(26043, 'Tiếng Anh', '09:30:00', '2024-11-08', 'Tiết 3', '10A1', 'P108', 'Hoang Lan G8', 10001, NULL, 6008, 2008),
(26044, 'Vật lý', '10:20:00', '2024-11-08', 'Tiết 4', '10A1', 'P102', 'Tran Thi G2', 10001, NULL, 6002, 2002),
(26045, 'Hóa học', '11:10:00', '2024-11-08', 'Tiết 5', '10A1', 'P103', 'Le Thi G3', 10001, NULL, 6003, 2003),
(26046, 'Sinh học', '13:30:00', '2024-11-08', 'Tiết 6', '10A1', 'P104', 'Pham Van G4', 10001, NULL, 6004, 2004),
(26047, 'Tin học', '14:20:00', '2024-11-08', 'Tiết 7', '10A1', 'P109', 'Nguyen Minh G9', 10001, NULL, 6009, 2009),
(26048, 'Địa lý', '15:30:00', '2024-11-08', 'Tiết 8', '10A1', 'P107', 'Bui Thi G7', 10001, NULL, 6007, 2007),
(26049, 'Lịch sử', '16:20:00', '2024-11-08', 'Tiết 9', '10A1', 'P106', 'Doan Van G6', 10001, NULL, 6006, 2006),
(26050, 'GDCD', '17:10:00', '2024-11-08', 'Tiết 10', '10A1', 'P110', 'Tran Quang G10', 10001, NULL, 6010, 2010);

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `maThongBao` int(11) NOT NULL,
  `tieuDe` varchar(255) NOT NULL,
  `noiDung` text DEFAULT NULL,
  `loaiThongBao` varchar(100) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'chuagui'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`maThongBao`, `tieuDe`, `noiDung`, `loaiThongBao`, `trangThai`) VALUES
(27001, 'Họp phụ huynh 10A1', 'Họp cuối HK1', 'phuhuynh', 'dagui'),
(27002, 'Thông báo thi HK1', 'Lịch thi được công bố', 'chung', 'dagui');

-- --------------------------------------------------------

--
-- Table structure for table `ttbm`
--

CREATE TABLE `ttbm` (
  `maTTBM` int(11) NOT NULL,
  `maGV` int(11) NOT NULL,
  `maTaiKhoan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ttbm`
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
-- Stand-in structure for view `view_diemtrungbinh_hocsinh`
-- (See below for the actual view)
--
CREATE TABLE `view_diemtrungbinh_hocsinh` (
`maHS` int(11)
,`hocKy` tinyint(4)
,`namHoc` varchar(20)
,`diemTB_guess` decimal(36,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_hocsinh_daydu`
-- (See below for the actual view)
--
CREATE TABLE `view_hocsinh_daydu` (
`maHS` int(11)
,`hoTen` varchar(150)
,`ngaySinh` date
,`gioiTinh` enum('Nam','Nu','Khac')
,`diaChi` varchar(255)
,`trangThaiHocTap` varchar(50)
,`maHocLuc` int(11)
,`maHanhKiem` int(11)
,`maPH` int(11)
,`maLop` int(11)
,`tenLop` varchar(50)
,`khoiLop` varchar(10)
,`tenHocLuc` varchar(50)
,`tenHanhKiem` varchar(50)
,`tenPhuHuynh` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_lichday_giaovien`
-- (See below for the actual view)
--
CREATE TABLE `view_lichday_giaovien` (
`maLichDay` int(11)
,`maGV` int(11)
,`maLop` int(11)
,`maMonHoc` int(11)
,`maPhong` int(11)
,`thu` tinyint(4)
,`tietBatDau` tinyint(4)
,`tietKetThuc` tinyint(4)
,`hocKy` tinyint(4)
,`namHoc` varchar(20)
,`trangThai` varchar(50)
,`tenGiaoVien` varchar(150)
,`tenLop` varchar(50)
,`tenMonHoc` varchar(150)
,`tenPhong` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_phancong_chamdiem`
-- (See below for the actual view)
--
CREATE TABLE `view_phancong_chamdiem` (
`maPhanCong` int(11)
,`maGV` int(11)
,`maLop` int(11)
,`maMonHoc` int(11)
,`loaiKiemTra` varchar(100)
,`ngayCham` date
,`hinhThucCham` varchar(100)
,`trangThai` varchar(50)
,`tenGV` varchar(150)
,`tenLop` varchar(50)
,`tenMonHoc` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_phancong_coithi`
-- (See below for the actual view)
--
CREATE TABLE `view_phancong_coithi` (
`maPhanCong` int(11)
,`maGV` int(11)
,`maLop` int(11)
,`maMonHoc` int(11)
,`maPhong` int(11)
,`loaiKyThi` varchar(100)
,`ngayThi` date
,`gioBatDau` time
,`gioKetThuc` time
,`viTriCoiThi` varchar(100)
,`trangThai` varchar(50)
,`tenGV` varchar(150)
,`tenLop` varchar(50)
,`tenMonHoc` varchar(150)
,`tenPhong` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_thongke_lophoc`
-- (See below for the actual view)
--
CREATE TABLE `view_thongke_lophoc` (
`maLop` int(11)
,`tenLop` varchar(50)
,`siSo` int(11)
,`khoiLop` varchar(10)
,`diemTB_lop` decimal(5,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `vipham`
--

CREATE TABLE `vipham` (
  `maViPham` int(11) NOT NULL,
  `maHS` int(11) NOT NULL,
  `ngayViPham` date DEFAULT NULL,
  `loiViPham` text DEFAULT NULL,
  `soLanViPham` int(11) DEFAULT 1,
  `nguoiPhatHien` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vipham`
--

INSERT INTO `vipham` (`maViPham`, `maHS`, `ngayViPham`, `loiViPham`, `soLanViPham`, `nguoiPhatHien`) VALUES
(15201, 13001, '2024-09-01', 'Đi trễ', 1, 6001),
(15202, 13002, '2024-09-02', 'Không đồng phục', 1, 6002),
(15203, 13003, '2024-09-03', 'Nói chuyện', 2, 6003),
(15204, 13004, '2024-09-04', 'Vắng mặt', 1, 6004),
(15205, 13005, '2024-09-05', 'Thiếu tôn trọng', 1, 6005),
(15206, 13006, '2024-09-06', 'Gây rối', 2, 6006),
(15207, 13007, '2024-09-07', 'Vật vã', 1, 6007),
(15208, 13008, '2024-09-08', 'Làm hư đồ', 1, 6008),
(15209, 13009, '2024-09-09', 'Thiếu trật tự', 1, 6009),
(15210, 13010, '2024-09-10', 'Vắng không phép', 1, 6010);

-- --------------------------------------------------------

--
-- Table structure for table `yeucau`
--

CREATE TABLE `yeucau` (
  `maYeuCau` int(11) NOT NULL,
  `moTa` text DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Choxuly',
  `maYeuCauNghiPhep` int(11) DEFAULT NULL,
  `maYeuCauSuaDiem` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `yeucau`
--

INSERT INTO `yeucau` (`maYeuCau`, `moTa`, `trangThai`, `maYeuCauNghiPhep`, `maYeuCauSuaDiem`) VALUES
(23001, 'Yêu cầu nghỉ phép 1', 'Choxuly', 21001, NULL),
(23002, 'Yêu cầu sửa điểm 1', 'Choxuly', NULL, 22001),
(23003, 'Yêu cầu nghỉ phép 2', 'Choxuly', 21002, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `yeucaunghiphep`
--

CREATE TABLE `yeucaunghiphep` (
  `maYeuCau` int(11) NOT NULL,
  `ngayBatDauNghi` date DEFAULT NULL,
  `ngayKetThucNghi` date DEFAULT NULL,
  `lyDo` text DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `yeucaunghiphep`
--

INSERT INTO `yeucaunghiphep` (`maYeuCau`, `ngayBatDauNghi`, `ngayKetThucNghi`, `lyDo`, `maGV`) VALUES
(21001, '2024-12-01', '2024-12-03', 'Khám bệnh', 6001),
(21002, '2024-12-05', '2024-12-06', 'Việc gia đình', 6002),
(21003, '2024-12-07', '2024-12-07', 'Lý do cá nhân', 6003);

-- --------------------------------------------------------

--
-- Table structure for table `yeucausuadiem`
--

CREATE TABLE `yeucausuadiem` (
  `maYeuCau` int(11) NOT NULL,
  `maGV` int(11) DEFAULT NULL,
  `diemHienTai` decimal(5,2) DEFAULT NULL,
  `diemDeNghiSua` decimal(5,2) DEFAULT NULL,
  `lyDoSuaDiem` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `yeucausuadiem`
--

INSERT INTO `yeucausuadiem` (`maYeuCau`, `maGV`, `diemHienTai`, `diemDeNghiSua`, `lyDoSuaDiem`) VALUES
(22001, 6001, 7.00, 7.50, 'Nhập nhầm'),
(22002, 6002, 6.50, 7.00, 'Nhập nhầm'),
(22003, 6003, 5.00, 5.50, 'Sai thông tin');

-- --------------------------------------------------------

--
-- Structure for view `view_diemtrungbinh_hocsinh`
--
DROP TABLE IF EXISTS `view_diemtrungbinh_hocsinh`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_diemtrungbinh_hocsinh`  AS SELECT `b`.`maHS` AS `maHS`, `b`.`hocKy` AS `hocKy`, `b`.`namHoc` AS `namHoc`, round(sum(case `b`.`loaiDiem` when 'mieng' then `b`.`diem` * 1 when '15phut' then `b`.`diem` * 1 when '1tiet' then `b`.`diem` * 2 when 'giuaky' then `b`.`diem` * 2 when 'cuoiky' then `b`.`diem` * 3 else `b`.`diem` * 1 end) / (count(0) / count(distinct `b`.`maMonHoc`) / 1 + 0.0000001),2) AS `diemTB_guess` FROM `bangdiem` AS `b` GROUP BY `b`.`maHS`, `b`.`hocKy`, `b`.`namHoc` ;

-- --------------------------------------------------------

--
-- Structure for view `view_hocsinh_daydu`
--
DROP TABLE IF EXISTS `view_hocsinh_daydu`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_hocsinh_daydu`  AS SELECT `hs`.`maHS` AS `maHS`, `hs`.`hoTen` AS `hoTen`, `hs`.`ngaySinh` AS `ngaySinh`, `hs`.`gioiTinh` AS `gioiTinh`, `hs`.`diaChi` AS `diaChi`, `hs`.`trangThaiHocTap` AS `trangThaiHocTap`, `hs`.`maHocLuc` AS `maHocLuc`, `hs`.`maHanhKiem` AS `maHanhKiem`, `hs`.`maPH` AS `maPH`, `hs`.`maLop` AS `maLop`, `l`.`tenLop` AS `tenLop`, `k`.`khoiLop` AS `khoiLop`, `hl`.`tenHocLuc` AS `tenHocLuc`, `hk`.`tenHanhKiem` AS `tenHanhKiem`, `ph`.`hoTen` AS `tenPhuHuynh` FROM (((((`hocsinh` `hs` left join `lophoc` `l` on(`hs`.`maLop` = `l`.`maLop`)) left join `khoi` `k` on(`l`.`maKhoi` = `k`.`maKhoi`)) left join `hocluc` `hl` on(`hs`.`maHocLuc` = `hl`.`maHocLuc`)) left join `hanhkiem` `hk` on(`hs`.`maHanhKiem` = `hk`.`maHanhKiem`)) left join `phuhuynh` `ph` on(`hs`.`maPH` = `ph`.`maPH`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_lichday_giaovien`
--
DROP TABLE IF EXISTS `view_lichday_giaovien`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_lichday_giaovien`  AS SELECT `ld`.`maLichDay` AS `maLichDay`, `ld`.`maGV` AS `maGV`, `ld`.`maLop` AS `maLop`, `ld`.`maMonHoc` AS `maMonHoc`, `ld`.`maPhong` AS `maPhong`, `ld`.`thu` AS `thu`, `ld`.`tietBatDau` AS `tietBatDau`, `ld`.`tietKetThuc` AS `tietKetThuc`, `ld`.`hocKy` AS `hocKy`, `ld`.`namHoc` AS `namHoc`, `ld`.`trangThai` AS `trangThai`, `g`.`hoTen` AS `tenGiaoVien`, `l`.`tenLop` AS `tenLop`, `m`.`tenMonHoc` AS `tenMonHoc`, `p`.`tenPhong` AS `tenPhong` FROM ((((`lichday` `ld` join `giaovien` `g` on(`ld`.`maGV` = `g`.`maGV`)) join `lophoc` `l` on(`ld`.`maLop` = `l`.`maLop`)) join `monhoc` `m` on(`ld`.`maMonHoc` = `m`.`maMonHoc`)) left join `phong` `p` on(`ld`.`maPhong` = `p`.`maPhong`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_phancong_chamdiem`
--
DROP TABLE IF EXISTS `view_phancong_chamdiem`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_phancong_chamdiem`  AS SELECT `pc`.`maPhanCong` AS `maPhanCong`, `pc`.`maGV` AS `maGV`, `pc`.`maLop` AS `maLop`, `pc`.`maMonHoc` AS `maMonHoc`, `pc`.`loaiKiemTra` AS `loaiKiemTra`, `pc`.`ngayCham` AS `ngayCham`, `pc`.`hinhThucCham` AS `hinhThucCham`, `pc`.`trangThai` AS `trangThai`, `g`.`hoTen` AS `tenGV`, `l`.`tenLop` AS `tenLop`, `m`.`tenMonHoc` AS `tenMonHoc` FROM (((`phancongchamdiem` `pc` left join `giaovien` `g` on(`pc`.`maGV` = `g`.`maGV`)) left join `lophoc` `l` on(`pc`.`maLop` = `l`.`maLop`)) left join `monhoc` `m` on(`pc`.`maMonHoc` = `m`.`maMonHoc`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_phancong_coithi`
--
DROP TABLE IF EXISTS `view_phancong_coithi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_phancong_coithi`  AS SELECT `pc`.`maPhanCong` AS `maPhanCong`, `pc`.`maGV` AS `maGV`, `pc`.`maLop` AS `maLop`, `pc`.`maMonHoc` AS `maMonHoc`, `pc`.`maPhong` AS `maPhong`, `pc`.`loaiKyThi` AS `loaiKyThi`, `pc`.`ngayThi` AS `ngayThi`, `pc`.`gioBatDau` AS `gioBatDau`, `pc`.`gioKetThuc` AS `gioKetThuc`, `pc`.`viTriCoiThi` AS `viTriCoiThi`, `pc`.`trangThai` AS `trangThai`, `g`.`hoTen` AS `tenGV`, `l`.`tenLop` AS `tenLop`, `m`.`tenMonHoc` AS `tenMonHoc`, `p`.`tenPhong` AS `tenPhong` FROM ((((`phancongcoithi` `pc` left join `giaovien` `g` on(`pc`.`maGV` = `g`.`maGV`)) left join `lophoc` `l` on(`pc`.`maLop` = `l`.`maLop`)) left join `monhoc` `m` on(`pc`.`maMonHoc` = `m`.`maMonHoc`)) left join `phong` `p` on(`pc`.`maPhong` = `p`.`maPhong`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_thongke_lophoc`
--
DROP TABLE IF EXISTS `view_thongke_lophoc`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_thongke_lophoc`  AS SELECT `l`.`maLop` AS `maLop`, `l`.`tenLop` AS `tenLop`, `l`.`siSo` AS `siSo`, `k`.`khoiLop` AS `khoiLop`, round(avg(`b`.`diem`),2) AS `diemTB_lop` FROM (((`lophoc` `l` left join `hocsinh` `hs` on(`hs`.`maLop` = `l`.`maLop`)) left join `bangdiem` `b` on(`b`.`maHS` = `hs`.`maHS`)) left join `khoi` `k` on(`l`.`maKhoi` = `k`.`maKhoi`)) GROUP BY `l`.`maLop` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `baitap`
--
ALTER TABLE `baitap`
  ADD PRIMARY KEY (`maBaiTap`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maNhomBT` (`maNhomBT`);

--
-- Indexes for table `bangdiem`
--
ALTER TABLE `bangdiem`
  ADD PRIMARY KEY (`maDiem`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `baocao`
--
ALTER TABLE `baocao`
  ADD PRIMARY KEY (`maBaoCao`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maKhoi` (`maKhoi`);

--
-- Indexes for table `bgh`
--
ALTER TABLE `bgh`
  ADD PRIMARY KEY (`maBGH`),
  ADD KEY `maYeuCau` (`maYeuCau`),
  ADD KEY `maDeThi` (`maDeThi`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `danhhieu`
--
ALTER TABLE `danhhieu`
  ADD PRIMARY KEY (`maDanhHieu`);

--
-- Indexes for table `dethi`
--
ALTER TABLE `dethi`
  ADD PRIMARY KEY (`maDeThi`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maTTBM` (`maTTBM`);

--
-- Indexes for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD PRIMARY KEY (`maGV`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `hanhkiem`
--
ALTER TABLE `hanhkiem`
  ADD PRIMARY KEY (`maHanhKiem`),
  ADD KEY `maDanhHieu` (`maDanhHieu`);

--
-- Indexes for table `hocluc`
--
ALTER TABLE `hocluc`
  ADD PRIMARY KEY (`maHocLuc`),
  ADD KEY `maDanhHieu` (`maDanhHieu`);

--
-- Indexes for table `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD PRIMARY KEY (`maHS`),
  ADD KEY `maHocLuc` (`maHocLuc`),
  ADD KEY `maHanhKiem` (`maHanhKiem`),
  ADD KEY `maPH` (`maPH`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `hoso`
--
ALTER TABLE `hoso`
  ADD PRIMARY KEY (`maHoSo`);

--
-- Indexes for table `hosogiaovien`
--
ALTER TABLE `hosogiaovien`
  ADD PRIMARY KEY (`maGV`),
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Indexes for table `hosohocsinh`
--
ALTER TABLE `hosohocsinh`
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Indexes for table `hosophuhuynh`
--
ALTER TABLE `hosophuhuynh`
  ADD PRIMARY KEY (`maPH`),
  ADD KEY `maHS` (`maHS`);

--
-- Indexes for table `khenthuong`
--
ALTER TABLE `khenthuong`
  ADD PRIMARY KEY (`maKhenThuong`),
  ADD KEY `maHS` (`maHS`);

--
-- Indexes for table `khoi`
--
ALTER TABLE `khoi`
  ADD PRIMARY KEY (`maKhoi`);

--
-- Indexes for table `kyluat`
--
ALTER TABLE `kyluat`
  ADD PRIMARY KEY (`maKyLuat`),
  ADD KEY `maHS` (`maHS`);

--
-- Indexes for table `lich`
--
ALTER TABLE `lich`
  ADD PRIMARY KEY (`maLich`);

--
-- Indexes for table `lichchamthi`
--
ALTER TABLE `lichchamthi`
  ADD PRIMARY KEY (`maLich`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Indexes for table `lichcoithi`
--
ALTER TABLE `lichcoithi`
  ADD PRIMARY KEY (`maLich`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Indexes for table `lichday`
--
ALTER TABLE `lichday`
  ADD PRIMARY KEY (`maLichDay`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Indexes for table `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  ADD PRIMARY KEY (`maLichSu`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `lophoc`
--
ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`maLop`),
  ADD KEY `maPhong` (`maPhong`),
  ADD KEY `maKhoi` (`maKhoi`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `FK_LopHoc_TKB` (`maTKB`);

--
-- Indexes for table `monhoc`
--
ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`maMonHoc`);

--
-- Indexes for table `nghihoc`
--
ALTER TABLE `nghihoc`
  ADD PRIMARY KEY (`maNghiHoc`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `nguoiDuyet` (`nguoiDuyet`);

--
-- Indexes for table `nhombaitap`
--
ALTER TABLE `nhombaitap`
  ADD PRIMARY KEY (`maNhomBT`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `nhomnguoidung`
--
ALTER TABLE `nhomnguoidung`
  ADD PRIMARY KEY (`maNhom`);

--
-- Indexes for table `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  ADD PRIMARY KEY (`maPhanCong`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  ADD PRIMARY KEY (`maPhanCong`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maPhong` (`maPhong`);

--
-- Indexes for table `phong`
--
ALTER TABLE `phong`
  ADD PRIMARY KEY (`maPhong`);

--
-- Indexes for table `phuhuynh`
--
ALTER TABLE `phuhuynh`
  ADD PRIMARY KEY (`maPH`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD PRIMARY KEY (`maQuanTri`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`),
  ADD KEY `maHoSo` (`maHoSo`);

--
-- Indexes for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`maTaiKhoan`),
  ADD UNIQUE KEY `tenDangNhap` (`tenDangNhap`),
  ADD KEY `maNhom` (`maNhom`);

--
-- Indexes for table `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  ADD PRIMARY KEY (`maTKB`),
  ADD KEY `maLop` (`maLop`),
  ADD KEY `maGV` (`maGV`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`maThongBao`);

--
-- Indexes for table `ttbm`
--
ALTER TABLE `ttbm`
  ADD PRIMARY KEY (`maTTBM`),
  ADD UNIQUE KEY `maGV` (`maGV`),
  ADD KEY `maTaiKhoan` (`maTaiKhoan`);

--
-- Indexes for table `vipham`
--
ALTER TABLE `vipham`
  ADD PRIMARY KEY (`maViPham`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `nguoiPhatHien` (`nguoiPhatHien`);

--
-- Indexes for table `yeucau`
--
ALTER TABLE `yeucau`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maYeuCauNghiPhep` (`maYeuCauNghiPhep`),
  ADD KEY `maYeuCauSuaDiem` (`maYeuCauSuaDiem`);

--
-- Indexes for table `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  ADD PRIMARY KEY (`maYeuCau`),
  ADD KEY `maGV` (`maGV`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `baitap`
--
ALTER TABLE `baitap`
  MODIFY `maBaiTap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20011;

--
-- AUTO_INCREMENT for table `bangdiem`
--
ALTER TABLE `bangdiem`
  MODIFY `maDiem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16000;

--
-- AUTO_INCREMENT for table `baocao`
--
ALTER TABLE `baocao`
  MODIFY `maBaoCao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29000;

--
-- AUTO_INCREMENT for table `bgh`
--
ALTER TABLE `bgh`
  MODIFY `maBGH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5011;

--
-- AUTO_INCREMENT for table `danhhieu`
--
ALTER TABLE `danhhieu`
  MODIFY `maDanhHieu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2000;

--
-- AUTO_INCREMENT for table `dethi`
--
ALTER TABLE `dethi`
  MODIFY `maDeThi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21000;

--
-- AUTO_INCREMENT for table `giaovien`
--
ALTER TABLE `giaovien`
  MODIFY `maGV` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7000;

--
-- AUTO_INCREMENT for table `hanhkiem`
--
ALTER TABLE `hanhkiem`
  MODIFY `maHanhKiem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11000;

--
-- AUTO_INCREMENT for table `hocluc`
--
ALTER TABLE `hocluc`
  MODIFY `maHocLuc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10000;

--
-- AUTO_INCREMENT for table `hocsinh`
--
ALTER TABLE `hocsinh`
  MODIFY `maHS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15000;

--
-- AUTO_INCREMENT for table `hoso`
--
ALTER TABLE `hoso`
  MODIFY `maHoSo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6000;

--
-- AUTO_INCREMENT for table `khenthuong`
--
ALTER TABLE `khenthuong`
  MODIFY `maKhenThuong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15011;

--
-- AUTO_INCREMENT for table `khoi`
--
ALTER TABLE `khoi`
  MODIFY `maKhoi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12000;

--
-- AUTO_INCREMENT for table `kyluat`
--
ALTER TABLE `kyluat`
  MODIFY `maKyLuat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15111;

--
-- AUTO_INCREMENT for table `lich`
--
ALTER TABLE `lich`
  MODIFY `maLich` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25000;

--
-- AUTO_INCREMENT for table `lichday`
--
ALTER TABLE `lichday`
  MODIFY `maLichDay` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16011;

--
-- AUTO_INCREMENT for table `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  MODIFY `maLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31000;

--
-- AUTO_INCREMENT for table `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `maLop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14000;

--
-- AUTO_INCREMENT for table `monhoc`
--
ALTER TABLE `monhoc`
  MODIFY `maMonHoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3000;

--
-- AUTO_INCREMENT for table `nghihoc`
--
ALTER TABLE `nghihoc`
  MODIFY `maNghiHoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15311;

--
-- AUTO_INCREMENT for table `nhombaitap`
--
ALTER TABLE `nhombaitap`
  MODIFY `maNhomBT` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19006;

--
-- AUTO_INCREMENT for table `nhomnguoidung`
--
ALTER TABLE `nhomnguoidung`
  MODIFY `maNhom` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4000;

--
-- AUTO_INCREMENT for table `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  MODIFY `maPhanCong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000;

--
-- AUTO_INCREMENT for table `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  MODIFY `maPhanCong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19000;

--
-- AUTO_INCREMENT for table `phong`
--
ALTER TABLE `phong`
  MODIFY `maPhong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13000;

--
-- AUTO_INCREMENT for table `phuhuynh`
--
ALTER TABLE `phuhuynh`
  MODIFY `maPH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9000;

--
-- AUTO_INCREMENT for table `quantrivien`
--
ALTER TABLE `quantrivien`
  MODIFY `maQuanTri` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `maTaiKhoan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5000;

--
-- AUTO_INCREMENT for table `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  MODIFY `maTKB` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28000;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `maThongBao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000;

--
-- AUTO_INCREMENT for table `ttbm`
--
ALTER TABLE `ttbm`
  MODIFY `maTTBM` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8000;

--
-- AUTO_INCREMENT for table `vipham`
--
ALTER TABLE `vipham`
  MODIFY `maViPham` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15211;

--
-- AUTO_INCREMENT for table `yeucau`
--
ALTER TABLE `yeucau`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24000;

--
-- AUTO_INCREMENT for table `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22000;

--
-- AUTO_INCREMENT for table `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  MODIFY `maYeuCau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23000;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `baitap`
--
ALTER TABLE `baitap`
  ADD CONSTRAINT `baitap_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE SET NULL,
  ADD CONSTRAINT `baitap_ibfk_2` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL,
  ADD CONSTRAINT `baitap_ibfk_3` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL,
  ADD CONSTRAINT `baitap_ibfk_4` FOREIGN KEY (`maNhomBT`) REFERENCES `nhombaitap` (`maNhomBT`) ON DELETE SET NULL;

--
-- Constraints for table `bangdiem`
--
ALTER TABLE `bangdiem`
  ADD CONSTRAINT `bangdiem_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `bangdiem_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `bangdiem_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `baocao`
--
ALTER TABLE `baocao`
  ADD CONSTRAINT `baocao_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL,
  ADD CONSTRAINT `baocao_ibfk_2` FOREIGN KEY (`maKhoi`) REFERENCES `khoi` (`maKhoi`) ON DELETE SET NULL;

--
-- Constraints for table `bgh`
--
ALTER TABLE `bgh`
  ADD CONSTRAINT `bgh_ibfk_1` FOREIGN KEY (`maYeuCau`) REFERENCES `yeucau` (`maYeuCau`) ON DELETE SET NULL,
  ADD CONSTRAINT `bgh_ibfk_2` FOREIGN KEY (`maDeThi`) REFERENCES `dethi` (`maDeThi`) ON DELETE SET NULL,
  ADD CONSTRAINT `bgh_ibfk_3` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `dethi`
--
ALTER TABLE `dethi`
  ADD CONSTRAINT `dethi_ibfk_1` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `dethi_ibfk_2` FOREIGN KEY (`maTTBM`) REFERENCES `ttbm` (`maTTBM`) ON DELETE SET NULL;

--
-- Constraints for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD CONSTRAINT `giaovien_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `hanhkiem`
--
ALTER TABLE `hanhkiem`
  ADD CONSTRAINT `hanhkiem_ibfk_1` FOREIGN KEY (`maDanhHieu`) REFERENCES `danhhieu` (`maDanhHieu`) ON DELETE SET NULL;

--
-- Constraints for table `hocluc`
--
ALTER TABLE `hocluc`
  ADD CONSTRAINT `hocluc_ibfk_1` FOREIGN KEY (`maDanhHieu`) REFERENCES `danhhieu` (`maDanhHieu`) ON DELETE SET NULL;

--
-- Constraints for table `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD CONSTRAINT `hocsinh_ibfk_1` FOREIGN KEY (`maHocLuc`) REFERENCES `hocluc` (`maHocLuc`) ON DELETE SET NULL,
  ADD CONSTRAINT `hocsinh_ibfk_2` FOREIGN KEY (`maHanhKiem`) REFERENCES `hanhkiem` (`maHanhKiem`) ON DELETE SET NULL,
  ADD CONSTRAINT `hocsinh_ibfk_3` FOREIGN KEY (`maPH`) REFERENCES `phuhuynh` (`maPH`) ON DELETE SET NULL,
  ADD CONSTRAINT `hocsinh_ibfk_4` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL,
  ADD CONSTRAINT `hocsinh_ibfk_5` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `hosogiaovien`
--
ALTER TABLE `hosogiaovien`
  ADD CONSTRAINT `hosogiaovien_ibfk_1` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE SET NULL;

--
-- Constraints for table `hosohocsinh`
--
ALTER TABLE `hosohocsinh`
  ADD CONSTRAINT `hosohocsinh_ibfk_1` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE CASCADE;

--
-- Constraints for table `hosophuhuynh`
--
ALTER TABLE `hosophuhuynh`
  ADD CONSTRAINT `hosophuhuynh_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Constraints for table `khenthuong`
--
ALTER TABLE `khenthuong`
  ADD CONSTRAINT `khenthuong_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Constraints for table `kyluat`
--
ALTER TABLE `kyluat`
  ADD CONSTRAINT `kyluat_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE;

--
-- Constraints for table `lichchamthi`
--
ALTER TABLE `lichchamthi`
  ADD CONSTRAINT `lichchamthi_ibfk_1` FOREIGN KEY (`maLich`) REFERENCES `lich` (`maLich`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichchamthi_ibfk_2` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Constraints for table `lichcoithi`
--
ALTER TABLE `lichcoithi`
  ADD CONSTRAINT `lichcoithi_ibfk_1` FOREIGN KEY (`maLich`) REFERENCES `lich` (`maLich`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichcoithi_ibfk_2` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Constraints for table `lichday`
--
ALTER TABLE `lichday`
  ADD CONSTRAINT `lichday_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichday_ibfk_4` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Constraints for table `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  ADD CONSTRAINT `lichsunhapxuat_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `lophoc`
--
ALTER TABLE `lophoc`
  ADD CONSTRAINT `FK_LopHoc_TKB` FOREIGN KEY (`maTKB`) REFERENCES `thoikhoabieu` (`maTKB`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_2` FOREIGN KEY (`maKhoi`) REFERENCES `khoi` (`maKhoi`) ON DELETE SET NULL,
  ADD CONSTRAINT `lophoc_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `nghihoc`
--
ALTER TABLE `nghihoc`
  ADD CONSTRAINT `nghihoc_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `nghihoc_ibfk_2` FOREIGN KEY (`nguoiDuyet`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `nhombaitap`
--
ALTER TABLE `nhombaitap`
  ADD CONSTRAINT `nhombaitap_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE SET NULL,
  ADD CONSTRAINT `nhombaitap_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE SET NULL,
  ADD CONSTRAINT `nhombaitap_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `phancongchamdiem`
--
ALTER TABLE `phancongchamdiem`
  ADD CONSTRAINT `phancongchamdiem_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongchamdiem_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongchamdiem_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Constraints for table `phancongcoithi`
--
ALTER TABLE `phancongcoithi`
  ADD CONSTRAINT `phancongcoithi_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `phancongcoithi_ibfk_4` FOREIGN KEY (`maPhong`) REFERENCES `phong` (`maPhong`) ON DELETE SET NULL;

--
-- Constraints for table `phuhuynh`
--
ALTER TABLE `phuhuynh`
  ADD CONSTRAINT `phuhuynh_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD CONSTRAINT `quantrivien_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL,
  ADD CONSTRAINT `quantrivien_ibfk_2` FOREIGN KEY (`maHoSo`) REFERENCES `hoso` (`maHoSo`) ON DELETE SET NULL;

--
-- Constraints for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `taikhoan_ibfk_1` FOREIGN KEY (`maNhom`) REFERENCES `nhomnguoidung` (`maNhom`) ON DELETE SET NULL;

--
-- Constraints for table `thoikhoabieu`
--
ALTER TABLE `thoikhoabieu`
  ADD CONSTRAINT `thoikhoabieu_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `thoikhoabieu_ibfk_2` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL,
  ADD CONSTRAINT `thoikhoabieu_ibfk_3` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE SET NULL;

--
-- Constraints for table `ttbm`
--
ALTER TABLE `ttbm`
  ADD CONSTRAINT `ttbm_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `ttbm_ibfk_2` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE SET NULL;

--
-- Constraints for table `vipham`
--
ALTER TABLE `vipham`
  ADD CONSTRAINT `vipham_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `vipham_ibfk_2` FOREIGN KEY (`nguoiPhatHien`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `yeucau`
--
ALTER TABLE `yeucau`
  ADD CONSTRAINT `yeucau_ibfk_1` FOREIGN KEY (`maYeuCauNghiPhep`) REFERENCES `yeucaunghiphep` (`maYeuCau`) ON DELETE SET NULL,
  ADD CONSTRAINT `yeucau_ibfk_2` FOREIGN KEY (`maYeuCauSuaDiem`) REFERENCES `yeucausuadiem` (`maYeuCau`) ON DELETE SET NULL;

--
-- Constraints for table `yeucaunghiphep`
--
ALTER TABLE `yeucaunghiphep`
  ADD CONSTRAINT `yeucaunghiphep_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `yeucausuadiem`
--
ALTER TABLE `yeucausuadiem`
  ADD CONSTRAINT `yeucausuadiem_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
