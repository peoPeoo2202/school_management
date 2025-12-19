<?php
$maMonHoc = (int)($_GET['subject'] ?? 0);

$homeworks = $controller->getAllHomeworkForStudent($maHS, $maMonHoc);
$subjects  = $controller->getAllSubjectsForStudent($maHS);

$subjectName = '';
foreach ($subjects as $subj) {
    if ((int)$subj['maMonHoc'] === $maMonHoc) {
        $subjectName = $subj['tenMonHoc'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách bài tập - <?php echo htmlspecialchars($subjectName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .empty-state {
            background: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 0 20px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6c757d;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #adb5bd;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="title-header">
        <h4>
            <i class="fas fa-home"></i> Danh sách bài tập
            <!-- <span class="separator">/</span>
            <?php echo htmlspecialchars($subjectName); ?> -->
        </h4>
    </div>
    <div>
        
    </div>
    <h4 class="header-link">
        <i class="fa-solid fa-bars"></i>
        <a href="index.php?page=submitHomework">
            Danh sách bài tập
        </a>
        <span class="separator">/</span>
        <span class="current-subject">
            <?php echo htmlspecialchars($subjectName); ?>
        </span>
    </h4>
    <div class="homework-detail-container">
        <?php if (empty($homeworks)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Chưa có bài tập được giao</h3>
                <p>Môn <?php echo htmlspecialchars($subjectName); ?> hiện tại chưa có bài tập nào được giao.</p>
                <a href="index.php?page=submitHomework" class="btn-card btn-submit" style="margin-top: 20px; display: inline-flex;">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách môn học
                </a>
            </div>
        <?php else: ?>
            <div class="common-grid">
                <?php foreach ($homeworks as $hw):
                    $deadlineStr = $hw['thoiGianNop'];
                    $deadline = strtotime($deadlineStr);
                    $now = time();
                    $isLate = $now > $deadline;

                    $hasSubmitted = ((int)$hw['daNop'] === 1);
                    $isLocked = isset($hw['khoaBai']) && (int)$hw['khoaBai'] === 1;

                    if ($isLocked) {
                        $canSubmit = false;
                        $lockReason = 'locked';
                    } else if (!$isLate) {
                        $canSubmit = true;
                        $lockReason = '';
                    } else {
                        $lateAllowed = isset($hw['choPhepNopTre']) && ((string)$hw['choPhepNopTre'] === '1');
                        $canSubmit = $lateAllowed;
                        $lockReason = $lateAllowed ? '' : 'overdue';
                    }
                ?>
                    <div class="homework-item">
                        <div class="header-card">
                            <div class="homework-title"><?php echo htmlspecialchars($hw['tenBaiTap']); ?></div>
                        </div>

                        <div class="homework-body">
                            <div class="homework-info-grid">
                                <div class="info-row-card">
                                    <i class="fas fa-clock"></i>
                                    <div class="info-label-card">Hạn nộp:</div>
                                </div>

                                <div class="info-value-card">
                                    <span class="deadline-badge <?php echo $isLate ? 'deadline-warning' : 'deadline-ok'; ?>">
                                        <i class="<?php echo $isLate ? 'fa-solid fa-lock' : 'fa-solid fa-calendar-days'; ?>"></i>
                                        <?php echo date('d/m/Y H:i', $deadline); ?>
                                    </span>
                                </div>

                                <div class="info-row-card requirement-row">
                                    <!-- LEFT: icon + label -->
                                    <div class="requirement-left">
                                        <i class="fas fa-list-ul"></i>
                                        <div class="info-label-card">Yêu cầu:</div>
                                    </div>

                                    <!-- RIGHT: value -->
                                    <div class="info-value-card requirement-value">
                                        <?php if (!empty($hw['yeuCauBaiTap'])): ?>
                                            <?php echo htmlspecialchars(substr($hw['yeuCauBaiTap'], 0, 80)); ?>
                                            <?php echo strlen($hw['yeuCauBaiTap']) > 80 ? '...' : ''; ?>
                                        <?php else: ?>
                                            <span class="empty-requirement-text">
                                                Không có yêu cầu bài tập
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                            <?php
                            // 1) Trạng thái nộp bài
                            if ($hasSubmitted) {
                                $submitText = ($hw['trangThai'] === 'Tre') ? 'Nộp trễ' : 'Đã nộp';
                                $submitColor = ($hw['trangThai'] === 'Tre') ? '#FF0000' : '#28a745';
                                $submitIcon = ($hw['trangThai'] === 'Tre') ? 'fa-triangle-exclamation' : 'fa-check-circle';
                            } else {
                                $submitText = 'Chưa nộp';
                                $submitColor = '#FF0000'; // màu xám
                                $submitIcon = 'fa-circle-xmark';
                            }


                            // 2) Trạng thái chấm điểm
                            $isGraded = (!empty($hw['diem']) || $hw['trangThai'] === 'Dacham');
                            $gradeText = $isGraded ? 'Đã chấm' : 'Chưa chấm';
                            $gradeStatusColor = $isGraded ? '#5081be' : '#ffb200';
                            $gradeStatusIcon = $isGraded ? 'fa-clipboard-check' : 'fa-hourglass-half';
                            ?>

                            <div class="submission-status">
                                <!-- Nộp bài -->
                                <div class="status-row">
                                    <span class="status-label">Nộp bài:</span>
                                    <span class="status-value" style="color: <?php echo $submitColor; ?>;">
                                        <i class="fas <?php echo $submitIcon; ?>"></i>
                                        <?php echo $submitText; ?>
                                    </span>
                                </div>

                                <!-- Chấm điểm -->
                                <div class="status-row">
                                    <span class="status-label">Chấm điểm:</span>
                                    <?php if ($submitText === 'Chưa nộp'): ?>
                                        <span class="status-value empty-value">-</span>
                                    <?php else: ?>
                                        <span class="status-value" style="color: <?php echo $gradeStatusColor; ?>;">
                                            <i class="fas <?php echo $gradeStatusIcon; ?>"></i>
                                            <?php echo $gradeText; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Ngày nộp -->
                                <div class="status-row">
                                    <span class="status-label">Ngày nộp:</span>
                                    <?php if ($submitText === 'Chưa nộp'): ?>
                                        <span class="status-value empty-value">-</span>
                                    <?php else: ?>
                                        <span class="status-value submit-time">
                                            <?php echo date('d/m/Y H:i', strtotime($hw['ngayNop'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Điểm số -->
                                <div class="status-row">
                                    <span class="status-label">Điểm số:</span>
                                    <?php if ($submitText === 'Chưa nộp'): ?>
                                        <span class="status-value empty-value">-</span>
                                    <?php elseif ($hw['diem'] !== null && $hw['diem'] !== ''): ?>
                                        <?php
                                        $score = (float)$hw['diem'];
                                        $scoreColor = ($score > 5) ? '#000' : '#dc3545';
                                        ?>
                                        <span class="grade-value" style="color: <?php echo $scoreColor; ?>;">
                                            <?php echo $score; ?>/10
                                        </span>
                                    <?php else: ?>
                                        <span class="status-value empty-value">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="homework-actions">
                                <?php if (!$hasSubmitted): ?>
                                    <?php if ($canSubmit): ?>
                                        <a href="index.php?page=submitHomework&id=<?php echo (int)$hw['maBaiTap']; ?>" class="btn-card btn-submit">
                                            <i class="fas fa-upload"></i> Nộp bài
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-card btn-disabled" disabled title="<?php echo $lockReason === 'locked' ? 'Bài tập đã bị khóa' : 'Bài tập đã hết hạn nộp'; ?>">
                                            <i class="fas fa-lock"></i> <?php echo $lockReason === 'locked' ? 'Đã khóa' : 'Đã hết hạn'; ?>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="index.php?page=submitHomework&id=<?php echo (int)$hw['maBaiTap']; ?>" class="btn-card btn-view">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </a>

                                    <?php if ($canSubmit): ?>
                                        <a href="index.php?page=submitHomework&id=<?php echo (int)$hw['maBaiTap']; ?>" class="btn-card btn-submit">
                                            <i class="fas fa-redo"></i> Nộp lại
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-card btn-disabled" disabled title="<?php echo $lockReason === 'locked' ? 'Bài tập đã bị khóa' : 'Bài tập đã hết hạn nộp'; ?>">
                                            <i class="fas fa-lock"></i> <?php echo $lockReason === 'locked' ? 'Đã khóa' : 'Đã hết hạn'; ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>


</body>

</html>