<?php
class mConnect
{
    public function mConnect()
    {
        $host = "localhost";
        $name = "hoai";
        $pass = "123";
        $db = "csdl";
        return mysqli_connect($host, $name, $pass, $db);
    }

    public function mDisconnect($conn)
    {
        $conn->close();
    }
}
