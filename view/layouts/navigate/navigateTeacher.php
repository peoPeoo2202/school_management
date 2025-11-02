<?php
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION["maNhom"]) && $_SESSION["maNhom"] == "3006") {
    // Nhóm 3006: Giáo viên chủ nhiệm
    echo '
    <aside class="sidebar">
      <ul>
        <li><a href="../student/timeTable.php">Xem thời khóa biểu</a></li>
        <li><a href="../student/grades.php">Xem điểm</a></li>
        <li><a href="../teacher/lopGvcn.php">Xem lớp GVCN</a></li>
      </ul>
    </aside>';
} 
else if (isset($_SESSION["maNhom"]) && $_SESSION["maNhom"] == "3003") {
    // Nhóm 3003: Giáo viên bộ môn
    echo '
    <aside class="sidebar">
      <ul>
        <li><a href="../student/timeTable.php">Xem thời khóa biểu</a></li>
        <li><a href="../student/grades.php">Xem điểm</a></li>
      </ul>
    </aside>';
} 
else {
    echo '
    <aside class="sidebar">
      <ul>
        <li><a href="#">Không có quyền truy cập menu</a></li>
      </ul>
    </aside>';
}
?>
