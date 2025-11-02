<?php
include_once("mConnect.php");
include_once("mAccount.php");

class mStudent
{
    private $conn;
    private $mAccount;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
        $this->mAccount = new mAccount();
    }

    /** 
     * Lấy thông tin học sinh dựa vào tài khoản đăng nhập
     */
public function getStudentInfoByAccount($tenDangNhap)
{
    $sql = "SELECT hs.maHS, hs.hoTen, lh.tenLop, kh.khoiLop
            FROM taikhoan tk
            JOIN hocsinh hs ON tk.maTaiKhoan = hs.maHS
            JOIN lophoc lh ON hs.maLop = lh.maLop
            JOIN khoi kh ON lh.maKhoi = kh.maKhoi
            WHERE tk.tenDangNhap = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $tenDangNhap);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}





    /**
     * Lấy điểm trung bình của học sinh theo từng môn
     */
    public function getStudentGrades($maHS)
    {
        $sql = "SELECT mh.tenMonHoc, 
                       ROUND(AVG(bd.diem),2) AS diemTB
                FROM bangdiem bd
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ?
                GROUP BY mh.tenMonHoc";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Lấy thời khóa biểu của học sinh theo mã học sinh
     */
    public function getStudentSchedule($maHS)
    {
        $sql = "SELECT mh.tenMonHoc, ld.thu, ld.tietBatDau, ld.tietKetThuc
                FROM hocsinh hs
                JOIN lichday ld ON hs.maLop = ld.maLop
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                WHERE hs.maHS = ?
                ORDER BY ld.thu, ld.tietBatDau";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Tạo học sinh mới (kèm tài khoản)
     * @param array $data - Dữ liệu học sinh: hoTen, ngaySinh, gioiTinh, diaChi, maHocLuc, maHanhKiem, maPH, maLop
     * @param array $accountData - Dữ liệu tài khoản: tenDangNhap, matKhau
     * @return array - ['success' => bool, 'maHS' => int, 'maTaiKhoan' => int, 'message' => string]
     */
    public function createStudent($data, $accountData)
    {
        // Kiểm tra tài khoản đã tồn tại chưa
        if ($this->mAccount->accountExists($accountData['tenDangNhap'])) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        // Bắt đầu transaction
        $this->conn->begin_transaction();

        try {
            // 1. Tạo tài khoản
            $maTaiKhoan = $this->mAccount->createAccount(
                $accountData['tenDangNhap'],
                $accountData['matKhau'],
                $data['hoTen'],
                'hocsinh',
                3004 // maNhom học sinh
            );

            if (!$maTaiKhoan) {
                throw new Exception("Không thể tạo tài khoản");
            }

            // 2. Tạo học sinh với maTaiKhoan
            $sql = "INSERT INTO hocsinh (hoTen, ngaySinh, gioiTinh, diaChi, trangThaiHocTap, maHocLuc, maHanhKiem, maPH, maTaiKhoan, maLop) 
                    VALUES (?, ?, ?, ?, 'danghoc', ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                "ssssiiii",
                $data['hoTen'],
                $data['ngaySinh'],
                $data['gioiTinh'],
                $data['diaChi'],
                $data['maHocLuc'],
                $data['maHanhKiem'],
                $data['maPH'],
                $maTaiKhoan,
                $data['maLop']
            );

            if (!$stmt->execute()) {
                throw new Exception("Không thể tạo học sinh: " . $stmt->error);
            }

            $maHS = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'maHS' => $maHS,
                'maTaiKhoan' => $maTaiKhoan,
                'message' => 'Tạo học sinh thành công'
            ];

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật thông tin học sinh
     */
    public function updateStudent($maHS, $data)
    {
        $sql = "UPDATE hocsinh 
                SET hoTen = ?, ngaySinh = ?, gioiTinh = ?, diaChi = ?, maHocLuc = ?, maHanhKiem = ?, maPH = ?, maLop = ?
                WHERE maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssiiiii",
            $data['hoTen'],
            $data['ngaySinh'],
            $data['gioiTinh'],
            $data['diaChi'],
            $data['maHocLuc'],
            $data['maHanhKiem'],
            $data['maPH'],
            $data['maLop'],
            $maHS
        );

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Lấy thông tin học sinh theo maHS
     */
    public function getStudentById($maHS)
    {
        $sql = "SELECT hs.*, lh.tenLop, kh.khoiLop, tk.tenDangNhap, tk.trangThaiTaiKhoan,
                       ph.hoTen as tenPhuHuynh, ph.soDienThoai as sdtPhuHuynh
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy danh sách tất cả học sinh
     */
    public function getAllStudents()
    {
        $sql = "SELECT hs.*, lh.tenLop, kh.khoiLop, tk.tenDangNhap
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                ORDER BY hs.maHS DESC";
        
        $result = $this->conn->query($sql);
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }

    /**
     * Xóa học sinh (cũng xóa tài khoản liên kết)
     */
    public function deleteStudent($maHS)
    {
        $this->conn->begin_transaction();

        try {
            // Lấy maTaiKhoan trước
            $student = $this->getStudentById($maHS);
            if (!$student) {
                throw new Exception("Không tìm thấy học sinh");
            }

            // Xóa học sinh
            $sql = "DELETE FROM hocsinh WHERE maHS = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $maHS);
            if (!$stmt->execute()) {
                throw new Exception("Không thể xóa học sinh");
            }
            $stmt->close();

            // Xóa tài khoản nếu có
            if ($student['maTaiKhoan']) {
                $this->mAccount->deleteAccount($student['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Xóa học sinh thành công'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ngắt kết nối
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
