<?php
class mConnect
{
    public function mConnect()
    {
        $host = "localhost";
        $user = "root";                
        $pass = "";                 
        $db   = "school_management";   

        $conn = mysqli_connect($host, $user, $pass, $db);
        if (!$conn) {
            die("Kết nối thất bại: " . mysqli_connect_error());
        }

        mysqli_set_charset($conn, "utf8mb4");
        
        // ĐẶT TIMEZONE CHO MySQL - QUAN TRỌNG để time() của PHP khớp với DATETIME của MySQL
        mysqli_query($conn, "SET time_zone = '+07:00'");
        
        return $conn;
    }

    public function mDisconnect($conn)
    {
        mysqli_close($conn);
    }
}
?>
