<?php
class mConnect
{
    public function mConnect()
    {
        $host = "localhost";
        $user = "school_management";                
        $pass = "123";                 
        $db   = "school_management";   

        $conn = mysqli_connect($host, $user, $pass, $db);
        if (!$conn) {
            die("Kết nối thất bại: " . mysqli_connect_error());
        }

        mysqli_set_charset($conn, "utf8mb4");
        return $conn;
    }

    public function mDisconnect($conn)
    {
        mysqli_close($conn);
    }
}
?>
