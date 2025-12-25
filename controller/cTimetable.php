<?php
    require_once __DIR__ . "/../model/mTimeTable.php";
    class cTimeTable{
        public function cGetYear(){
            $p = new mTimeTable();
            $result = $p->mGetYear();
            if($result && $result->num_rows>0){
                return $result; //lấy được dữ liệu year
            }else{
                return false; // không lấy được dữ liệu year
            }
        }

        public function cGetSemesterByYear($maNamHoc){
            $p = new mTimeTable();
            $result = $p->mGetSemesterByYear($maNamHoc);
            if($result && $result->num_rows>0){
                return $result; //lấy được dữ liệu semester
            }else{
                return false; // không lấy được dữ liệu semester
            }
        }

        public function cGetTimeTableByClass($maLop, $maNamHoc, $hocKy){
            $p = new mTimeTable();
            $result = $p->mGetTimeTableByClass($maLop, $maNamHoc, $hocKy);
            if($result && $result->num_rows>0){
                return $result; //lấy được dữ liệu thời khóa biểu
            }else{
                return false; // không lấy được dữ liệu thời khóa biểu
            }
        }
        
        public function cGetClassByAccount($maTaiKhoan){
            $p = new mTimeTable();
            $result = $p->mGetClassByAccount($maTaiKhoan);
            return $result; // trả về maLop hoặc false
        }
    }    
?>