 <?php
include_once("mConnect.php");

class mUser
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Đăng nhập người dùng (an toàn với prepared statements)
     * Hỗ trợ cả MD5 (legacy) và bcrypt (mới)
     * @param string $name - Tên đăng nhập
     * @param string $pass - Mật khẩu (plaintext)
     * @return array|false - Thông tin user hoặc false nếu sai
     */
    public function mLogin($name, $pass)
    {
        if (!$this->conn) {
            return false;
        }

        // Sử dụng prepared statement để tránh SQL injection
        $sql = "SELECT maTaiKhoan, tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan 
                WHERE tenDangNhap = ? AND (trangThaiTaiKhoan = 'active' OR trangThaiTaiKhoan = 1)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false; // User không tồn tại
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Kiểm tra mật khẩu
        $hashedPassword = $user['matKhau'];
        
        // Kiểm tra xem có phải MD5 không (32 ký tự hex)
        if (strlen($hashedPassword) === 32 && ctype_xdigit($hashedPassword)) {
            // Legacy MD5 password
            if (md5($pass) === $hashedPassword) {
                // Đăng nhập thành công với MD5 - nên migrate sang bcrypt
                unset($user['matKhau']); // Không trả về hash
                return $user;
            } else {
                return false; // Sai mật khẩu
            }
        } else {
            // Bcrypt password (mới)
            if (password_verify($pass, $hashedPassword)) {
                unset($user['matKhau']); // Không trả về hash
                return $user;
            } else {
                return false; // Sai mật khẩu
            }
        }
    }

    /**
     * Lấy thông tin user theo maTaiKhoan
     */
    public function getUserById($maTaiKhoan)
    {
        $sql = "SELECT maTaiKhoan, tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan WHERE maTaiKhoan = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy thông tin user theo tenDangNhap
     */
    public function getUserByUsername($tenDangNhap)
    {
        $sql = "SELECT maTaiKhoan, tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan WHERE tenDangNhap = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tenDangNhap);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy mã giáo viên từ maTaiKhoan
     * @param int $maTaiKhoan
     * @return int|null - maGV hoặc null nếu không phải giáo viên
     */
    public function getTeacherIdByAccountId($maTaiKhoan)
    {
        $sql = "SELECT maGV FROM giaovien WHERE maTaiKhoan = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['maGV'];
        }
        
        $stmt->close();
        return null;
    }

    /**
     * Lấy danh sách tất cả tài khoản với phân trang và tìm kiếm
     * @param array $filters - Bộ lọc (tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom)
     * @param int $page - Trang hiện tại
     * @param int $limit - Số bản ghi mỗi trang
     * @return array - ['data' => [], 'total' => int, 'page' => int, 'limit' => int]
     */
    public function getAllAccounts($filters = [], $page = 1, $limit = 20)
    {
        $where = [];
        $params = [];
        $types = '';

        // Xây dựng điều kiện WHERE
        if (!empty($filters['tenDangNhap'])) {
            $where[] = "t.tenDangNhap LIKE ?";
            $params[] = '%' . $filters['tenDangNhap'] . '%';
            $types .= 's';
        }

        if (!empty($filters['hoTen'])) {
            $where[] = "t.hoTen LIKE ?";
            $params[] = '%' . $filters['hoTen'] . '%';
            $types .= 's';
        }

        if (!empty($filters['loaiTaiKhoan'])) {
            $where[] = "t.loaiTaiKhoan = ?";
            $params[] = $filters['loaiTaiKhoan'];
            $types .= 's';
        }

        if (!empty($filters['trangThaiTaiKhoan'])) {
            $where[] = "t.trangThaiTaiKhoan = ?";
            $params[] = $filters['trangThaiTaiKhoan'];
            $types .= 's';
        }

        if (!empty($filters['maNhom'])) {
            $where[] = "t.maNhom = ?";
            $params[] = $filters['maNhom'];
            $types .= 'i';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Đếm tổng số bản ghi
        $countSql = "SELECT COUNT(*) as total FROM taikhoan t $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();

        // Lấy dữ liệu với phân trang
        $offset = ($page - 1) * $limit;
        $dataSql = "SELECT t.maTaiKhoan, t.tenDangNhap, t.hoTen, t.email, t.soDienThoai, 
                           t.loaiTaiKhoan, t.trangThaiTaiKhoan, t.maNhom, n.tenNhom,
                           t.ngayTao, t.lanDangNhapCuoi, t.batBuocDoiMatKhau, t.soLanDangNhapSai
                    FROM taikhoan t
                    LEFT JOIN nhomnguoidung n ON t.maNhom = n.maNhom
                    $whereClause
                    ORDER BY t.ngayTao DESC
                    LIMIT ? OFFSET ?";

        $dataStmt = $this->conn->prepare($dataSql);
        
        if (!empty($params)) {
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            $dataStmt->bind_param($types, ...$params);
        } else {
            $dataStmt->bind_param('ii', $limit, $offset);
        }

        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $dataStmt->close();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Lấy thông tin chi tiết tài khoản theo ID
     */
    public function getAccountById($maTaiKhoan)
    {
        $sql = "SELECT t.*, n.tenNhom,
                       CONCAT(creator.hoTen, ' (', creator.tenDangNhap, ')') as nguoiTaoInfo,
                       CONCAT(updater.hoTen, ' (', updater.tenDangNhap, ')') as nguoiCapNhatInfo
                FROM taikhoan t
                LEFT JOIN nhomnguoidung n ON t.maNhom = n.maNhom
                LEFT JOIN taikhoan creator ON t.nguoiTao = creator.maTaiKhoan
                LEFT JOIN taikhoan updater ON t.nguoiCapNhat = updater.maTaiKhoan
                WHERE t.maTaiKhoan = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Không trả về mật khẩu
        if ($result) {
            unset($result['matKhau']);
        }
        
        return $result;
    }

    /**
     * Tạo tài khoản mới
     * @param array $data - Dữ liệu tài khoản
     * @return int|false - maTaiKhoan mới hoặc false nếu thất bại
     */
    public function createAccount($data)
    {
        // Validate required fields
        $required = ['tenDangNhap', 'matKhau', 'hoTen', 'loaiTaiKhoan'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        // Kiểm tra username đã tồn tại chưa
        if (!$this->checkUniqueUsername($data['tenDangNhap'])) {
            return false;
        }

        // Kiểm tra email unique nếu có
        if (!empty($data['email']) && !$this->checkUniqueEmail($data['email'])) {
            return false;
        }

        // Hash mật khẩu bằng bcrypt
        $hashedPassword = password_hash($data['matKhau'], PASSWORD_BCRYPT);

        // Build dynamic SQL based on which fields have values
        $columns = ['tenDangNhap', 'matKhau', 'hoTen', 'loaiTaiKhoan', 'trangThaiTaiKhoan', 'batBuocDoiMatKhau'];
        $values = [$data['tenDangNhap'], $hashedPassword, $data['hoTen'], $data['loaiTaiKhoan'], $data['trangThaiTaiKhoan'] ?? 'active', $data['batBuocDoiMatKhau'] ?? 1];
        $types = 'sssssi';
        
        if (!empty($data['email'])) {
            $columns[] = 'email';
            $values[] = $data['email'];
            $types .= 's';
        }
        
        if (!empty($data['soDienThoai'])) {
            $columns[] = 'soDienThoai';
            $values[] = $data['soDienThoai'];
            $types .= 's';
        }
        
        if (!empty($data['maNhom'])) {
            $columns[] = 'maNhom';
            $values[] = intval($data['maNhom']);
            $types .= 'i';
        }
        
        if (!empty($data['nguoiTao'])) {
            $columns[] = 'nguoiTao';
            $values[] = intval($data['nguoiTao']);
            $types .= 'i';
        }
        
        $columnsList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO taikhoan ($columnsList) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $maTaiKhoan = $stmt->insert_id;
            $stmt->close();

            // Log activity
            $nguoiTao = !empty($data['nguoiTao']) ? $data['nguoiTao'] : null;
            $this->logActivity($nguoiTao, 'them', 'taikhoan', 
                "Tạo tài khoản mới: {$data['tenDangNhap']} ({$data['hoTen']})");

            return $maTaiKhoan;
        }

        $stmt->close();
        return false;
    }

    /**
     * Cập nhật thông tin tài khoản
     */
    public function updateAccount($maTaiKhoan, $data, $nguoiCapNhat = null)
    {
        $fields = [];
        $params = [];
        $types = '';

        // Các trường có thể cập nhật
        $allowedFields = ['hoTen', 'email', 'soDienThoai', 'loaiTaiKhoan', 
                         'trangThaiTaiKhoan', 'maNhom', 'batBuocDoiMatKhau'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Xử lý đặc biệt cho maNhom - chỉ cập nhật nếu có giá trị hợp lệ
                if ($field === 'maNhom') {
                    $maNhom = $data[$field];
                    // Chỉ cập nhật nếu maNhom là số nguyên dương
                    if (!empty($maNhom) && is_numeric($maNhom) && intval($maNhom) > 0) {
                        $fields[] = "$field = ?";
                        $params[] = intval($maNhom);
                        $types .= 'i';
                    } else if ($maNhom === null || $maNhom === '') {
                        // Nếu muốn set NULL thì dùng cách này
                        $fields[] = "$field = NULL";
                        // Không thêm vào params
                    }
                    continue;
                }
                
                // Xử lý các trường email và soDienThoai - cho phép NULL
                if ($field === 'email' || $field === 'soDienThoai') {
                    if ($data[$field] === '' || $data[$field] === null) {
                        $fields[] = "$field = NULL";
                        continue;
                    }
                }
                
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                
                if ($field === 'batBuocDoiMatKhau') {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Thêm nguoiCapNhat và ngayCapNhat
        $fields[] = "nguoiCapNhat = ?";
        $fields[] = "ngayCapNhat = NOW()";
        $params[] = $nguoiCapNhat;
        $types .= 'i';

        $sql = "UPDATE taikhoan SET " . implode(', ', $fields) . " WHERE maTaiKhoan = ?";
        $params[] = $maTaiKhoan;
        $types .= 'i';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();

        if ($result) {
            // Log activity
            $this->logActivity($nguoiCapNhat, 'capnhat', 'taikhoan', 
                "Cập nhật tài khoản ID: $maTaiKhoan");
        }

        return $result;
    }

    /**
     * Xóa tài khoản (soft delete - chuyển trạng thái)
     */
    public function deleteAccount($maTaiKhoan, $nguoiXoa = null)
    {
        $sql = "UPDATE taikhoan 
                SET trangThaiTaiKhoan = 'disabled', 
                    nguoiCapNhat = ?,
                    ngayCapNhat = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $nguoiXoa, $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logActivity($nguoiXoa, 'xoa', 'taikhoan', 
                "Xóa (vô hiệu hóa) tài khoản ID: $maTaiKhoan");
        }

        return $result;
    }

    /**
     * Khóa tài khoản
     */
    public function lockAccount($maTaiKhoan, $nguoiKhoa = null, $lyDo = '')
    {
        $sql = "UPDATE taikhoan 
                SET trangThaiTaiKhoan = 'locked',
                    nguoiCapNhat = ?,
                    ngayCapNhat = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $nguoiKhoa, $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logActivity($nguoiKhoa, 'khoa', 'taikhoan', 
                "Khóa tài khoản ID: $maTaiKhoan. Lý do: $lyDo");
        }

        return $result;
    }

    /**
     * Mở khóa tài khoản
     */
    public function unlockAccount($maTaiKhoan, $nguoiMoKhoa = null)
    {
        $sql = "UPDATE taikhoan 
                SET trangThaiTaiKhoan = 'active',
                    soLanDangNhapSai = 0,
                    nguoiCapNhat = ?,
                    ngayCapNhat = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $nguoiMoKhoa, $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logActivity($nguoiMoKhoa, 'mokhoa', 'taikhoan', 
                "Mở khóa tài khoản ID: $maTaiKhoan");
        }

        return $result;
    }

    /**
     * Reset mật khẩu
     */
    public function resetPassword($maTaiKhoan, $newPassword, $nguoiReset = null)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE taikhoan 
                SET matKhau = ?,
                    batBuocDoiMatKhau = 1,
                    nguoiCapNhat = ?,
                    ngayCapNhat = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $hashedPassword, $nguoiReset, $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logActivity($nguoiReset, 'resetmatkhau', 'taikhoan', 
                "Reset mật khẩu cho tài khoản ID: $maTaiKhoan");
        }

        return $result;
    }

    /**
     * Đổi mật khẩu (user tự đổi)
     */
    public function changePassword($maTaiKhoan, $oldPassword, $newPassword)
    {
        // Lấy thông tin tài khoản
        $sql = "SELECT matKhau FROM taikhoan WHERE maTaiKhoan = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            return false;
        }

        // Verify old password
        if (!password_verify($oldPassword, $result['matKhau'])) {
            return false;
        }

        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE taikhoan 
                SET matKhau = ?,
                    batBuocDoiMatKhau = 0,
                    ngayCapNhat = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logActivity($maTaiKhoan, 'doimatkhau', 'taikhoan', 
                "Đổi mật khẩu tài khoản ID: $maTaiKhoan");
        }

        return $result;
    }

    /**
     * Kiểm tra username có duy nhất không
     */
    public function checkUniqueUsername($tenDangNhap, $excludeMaTaiKhoan = null)
    {
        if ($excludeMaTaiKhoan) {
            $sql = "SELECT COUNT(*) as count FROM taikhoan 
                    WHERE tenDangNhap = ? AND maTaiKhoan != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $tenDangNhap, $excludeMaTaiKhoan);
        } else {
            $sql = "SELECT COUNT(*) as count FROM taikhoan WHERE tenDangNhap = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $tenDangNhap);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] == 0;
    }

    /**
     * Kiểm tra email có duy nhất không
     */
    public function checkUniqueEmail($email, $excludeMaTaiKhoan = null)
    {
        if (empty($email)) {
            return true;
        }

        if ($excludeMaTaiKhoan) {
            $sql = "SELECT COUNT(*) as count FROM taikhoan 
                    WHERE email = ? AND maTaiKhoan != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $email, $excludeMaTaiKhoan);
        } else {
            $sql = "SELECT COUNT(*) as count FROM taikhoan WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] == 0;
    }

    /**
     * Ghi log hoạt động
     */
    public function logActivity($maTaiKhoan, $hanhDong, $bangDuLieu, $ghiChu = '', $diaChiIP = null, $userAgent = null)
    {
        if (!$diaChiIP) {
            $diaChiIP = $_SERVER['REMOTE_ADDR'] ?? null;
        }
        
        if (!$userAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        $sql = "INSERT INTO lichsunhapxuat 
                (maTaiKhoan, hanhDong, bangDuLieu, ghiChu, diaChiIP, userAgent, thoiGian)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssss", $maTaiKhoan, $hanhDong, $bangDuLieu, $ghiChu, $diaChiIP, $userAgent);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Tăng số lần đăng nhập sai và khóa tài khoản nếu vượt quá giới hạn
     */
    public function incrementFailedLogin($tenDangNhap)
    {
        $sql = "UPDATE taikhoan 
                SET soLanDangNhapSai = soLanDangNhapSai + 1,
                    trangThaiTaiKhoan = CASE 
                        WHEN soLanDangNhapSai >= 4 THEN 'locked'
                        ELSE trangThaiTaiKhoan
                    END
                WHERE tenDangNhap = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tenDangNhap);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Reset số lần đăng nhập sai khi đăng nhập thành công
     */
    public function resetFailedLogin($maTaiKhoan)
    {
        $sql = "UPDATE taikhoan 
                SET soLanDangNhapSai = 0,
                    lanDangNhapCuoi = NOW()
                WHERE maTaiKhoan = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Lấy danh sách nhóm người dùng
     */
    public function getAllGroups()
    {
        $sql = "SELECT maNhom, tenNhom, moTa, quyenHan FROM nhomnguoidung ORDER BY tenNhom";
        $result = $this->conn->query($sql);
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        
        return $groups;
    }

    /**
     * Sinh mật khẩu ngẫu nhiên
     */
    public function generatePassword($length = 12)
    {
        // Đảm bảo có ít nhất 1 chữ hoa, 1 chữ thường, 1 số
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        // Bắt đầu với ít nhất 1 ký tự mỗi loại
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        
        // Thêm các ký tự ngẫu nhiên còn lại
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 3; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Xáo trộn password để không có pattern cố định
        return str_shuffle($password);
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
?>