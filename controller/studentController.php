<?php
session_start();
include_once("../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
    header("Location: ../login.php");
    exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["hoTen"]);
$grades = $model->getStudentGrades($info['maHS']);
$schedule = $model->getStudentSchedule($info['maHS']);
?>
