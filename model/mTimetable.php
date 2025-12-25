<?php
    require_once __DIR__ . "/mConnect.php";

    class mTimeTable
    {
        /**
         * Lấy thời khóa biểu theo lớp từ bảng thoikhoabieu
         */
        public function mGetTimeTableByClass($maLop)
        {
            $p = new mConnect();
            $conn = $p->mConnect();
            
            if (!$conn) {
                return false;
            }

            $query = "SELECT 
                        tkb.maTKB,
                        tkb.tenMonHoc,
                        tkb.thoiGianHoc,
                        tkb.thuNgay,
                        tkb.tietHoc,
                        tkb.lop,
                        tkb.phong,
                        tkb.gv,
                        DAYOFWEEK(tkb.thuNgay) as thu,
                        mh.tenMonHoc as tenMonHocFull,
                        gv.hoTen as tenGiaoVien
                      FROM thoikhoabieu tkb
                      LEFT JOIN monhoc mh ON tkb.maMonHoc = mh.maMonHoc
                      LEFT JOIN giaovien gv ON tkb.maGV = gv.maGV
                      WHERE tkb.maLop = ?
                      ORDER BY tkb.thuNgay, tkb.thoiGianHoc";
        
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $p->mDisconnect($conn);
                return false;
            }
            
            $stmt->bind_param("i", $maLop);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stmt->close();
            $p->mDisconnect($conn);
            
            return $result;
        }
        
        /**
         * Lấy maLop từ maTaiKhoan của học sinh
         */
        public function mGetClassByAccount($maTaiKhoan)
        {
            $p = new mConnect();
            $conn = $p->mConnect();
            
            if (!$conn) {
                return false;
            }
            
            $query = "SELECT hs.maLop, l.tenLop, k.khoiLop 
                      FROM hocsinh hs
                      LEFT JOIN lophoc l ON hs.maLop = l.maLop
                      LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                      WHERE hs.maTaiKhoan = ?";
        
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $p->mDisconnect($conn);
                return false;
            }
            
            $stmt->bind_param("i", $maTaiKhoan);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                $p->mDisconnect($conn);
                return $row;
            }
            
            $stmt->close();
            $p->mDisconnect($conn);
            return false;
        }
        
        /**
         * Lấy danh sách ngày học từ bảng thoikhoabieu
         */
        public function mGetDistinctDates($maLop)
        {
            $p = new mConnect();
            $conn = $p->mConnect();
            
            if (!$conn) {
                return false;
            }
            
            $query = "SELECT DISTINCT thuNgay 
                      FROM thoikhoabieu 
                      WHERE maLop = ?
                      ORDER BY thuNgay";
        
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $p->mDisconnect($conn);
                return false;
            }
            
            $stmt->bind_param("i", $maLop);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stmt->close();
            $p->mDisconnect($conn);
            
            return $result;
        }
    }
?>