<?php
$maBaiTap = (int)($_GET['id'] ?? 0);

$homework = $controller->getHomeworkDetails($maBaiTap);
if (!$homework) {
    echo "<p style='color:red; padding: 20px;'>Không tìm thấy bài tập!</p>";
    exit;
}
$subjectName = $homework['tenMonHoc'] ?? '';

$submission = $controller->getStudentSubmission($maBaiTap, $maHS);

$currentTimestamp  = time();
$deadlineTimestamp = strtotime($homework['thoiGianNop']);
$isOverdue = $currentTimestamp > $deadlineTimestamp;

// Quyền nộp
if (isset($homework['khoaBai']) && (int)$homework['khoaBai'] === 1) {
    $canSubmit = false;
    $submitReason = 'locked';
} else if ($isOverdue) {
    $lateAllowed = isset($homework['choPhepNopTre']) && ((string)$homework['choPhepNopTre'] === '1');
    if ($lateAllowed) {
        $canSubmit = true;
        $submitReason = 'late_allowed';
    } else {
        $canSubmit = false;
        $submitReason = $submission ? 'already_submitted_overdue' : 'overdue_not_allowed';
    }
} else {
    $canSubmit = true;
    $submitReason = 'on_time';
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!$canSubmit) {
        $message = ($submitReason === 'locked')
            ? 'Bài tập đã bị khóa. Học sinh không thể nộp bài!'
            : 'Bài tập đã hết hạn nộp và không cho phép nộp trễ! Vui lòng liên hệ giáo viên.';
        $messageType = 'error';
        $showAlert = true;
    } else {
        $result = $controller->submitHomework($maBaiTap, $maHS, $_FILES['file'], $_POST['noiDung'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        $showAlert = isset($result['showAlert']) && $result['showAlert'];
        $submission = $controller->getStudentSubmission($maBaiTap, $maHS);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp bài tập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="form-container">
        <div class="title-header">
            <i class="fas fa-book"></i>
            <h4> Danh sách bài tập
            </h4>
        </div>
        <div class="header-link">
            <h4>
                <i class="fa-solid fa-bars"></i>

                <a href="index.php?page=submitHomework">
                    Danh sách bài tập
                </a>
                <span class="separator">/</span>
                <a class="current-subject"
                    href="index.php?page=submitHomework&subject=<?php echo (int)$homework['maMonHoc']; ?>">
                    <?php echo htmlspecialchars($subjectName); ?>
                </a>
                <span class="separator">/</span>
                <span class="current-subject">
                    <?php echo htmlspecialchars($homework['tenBaiTap']); ?>
                </span>
            </h4>
        </div>


        <div>

            <?php if (isset($message)): ?>
                <?php if (isset($showAlert) && $showAlert): ?>
                    <script>
                        alert('<?php echo addslashes($message); ?>');
                    </script>
                <?php else: ?>
                    <div class="message <?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <h2 class="detail-row homework-detail-title"><i class="fas fa-info-circle"></i>Chi tiết bài tập</h2>

            <div class="homework-details">
                <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-book"></i><span>Tên bài tập:</span></div>
                    <div class="detail-value"><?php echo htmlspecialchars($homework['tenBaiTap']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-graduation-cap"></i><span>Môn học:</span></div>
                    <div class="detail-value"><?php echo htmlspecialchars($homework['tenMonHoc'] ?? 'N/A'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-clock"></i><span>Hạn nộp:</span></div>
                    <div class="detail-value">
                        <?php echo date('d/m/Y H:i', strtotime($homework['thoiGianNop'])); ?>
                    </div>
                </div>

                <?php if (($homework['choPhepNopTre'] ?? 0) == 1 && $isOverdue): ?>
                    <div class="detail-row">
                        <div class="detail-label"><i class="fa-solid fa-lock"></i></i><span>Nộp trễ:</span></div>
                        <div class="detail-value allow-late">
                            <i class="fas fa-check-circle"></i> Được phép nộp trễ
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($homework['yeuCauBaiTap'])): ?>
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-list-ul"></i><span>Yêu cầu:</span></div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($homework['yeuCauBaiTap'])); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($homework['tenFile'])): ?>
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-paperclip"></i><span>File bài tập:</span></div>
                        <div class="detail-value">
                            <a class="detail-homework-link" href="../../<?php echo htmlspecialchars($homework['duongDan']); ?>" download>
                                <i class="fas fa-download"></i> <?php echo htmlspecialchars($homework['tenFile']); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            // 1) Trạng thái nộp bài
            if (!empty($submission)) {
                $status = $submission['trangThai'] ?? ''; // Danop | Tre | Dacham | Chuacham...

                $submitText  = ($status === 'Tre') ? 'Nộp trễ' : 'Đã nộp';
                $submitColor = ($status === 'Tre') ? '#dc3545' : '#28a745';
                $submitIcon  = ($status === 'Tre') ? 'fa-triangle-exclamation' : 'fa-check-circle';

                // 2) Trạng thái chấm điểm
                $isGraded = (!empty($submission['diem']) || $status === 'Dacham');
                $gradeText = $isGraded ? 'Đã chấm' : 'Chưa chấm';
                $gradeStatusColor = $isGraded ? '#28a745' : '#ffb200';
                $gradeStatusIcon = $isGraded ? 'fa-clipboard-check' : 'fa-hourglass-half';
            } else {
                $submitText  = 'Chưa nộp';
                $submitColor = '#dc3545';
                $submitIcon  = 'fa-circle-xmark';

                $gradeText = '-';
                $gradeStatusColor = '#5081be';
                $gradeStatusIcon = 'fa-circle-xmark';
            }
            ?>


            <?php if ($submission): ?>
                <h2 class="detail-row homework-detail-title"><i class="fas fa-info-circle"></i>Chi tiết bài nộp</h2>

                <div class="submitted-section">

                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-calendar-alt"></i><span>Thời gian nộp:</span></div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($submission['ngayNop'])); ?></div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-info-circle"></i>
                            <span>Nộp bài:</span>
                        </div>

                        <div class="detail-value" style="color: <?php echo $submitColor; ?>;">

                            <i class="fas <?php echo $submitIcon; ?>"></i>
                            <?php echo $submitText; ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa-solid fa-pen"></i> <span>Chấm điểm:</span>
                        </div>

                        <div class="detail-value" style="color: <?php echo $gradeStatusColor; ?>;">
                            <i class="fas <?php echo $gradeStatusIcon; ?>"></i>
                            <?php echo $gradeText; ?>
                        </div>
                    </div>


                    <?php if (!empty($submission['noiDung'])): ?>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-align-left"></i><span>Nội dung:</span></div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($submission['noiDung'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($submission['tenFile'])): ?>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-file"></i><span>File đã nộp:</span></div>
                            <div class="detail-value">
                                <a class="detail-homework-link" href="../../<?php echo htmlspecialchars($submission['duongDan']); ?>" download>
                                    <i class="fas fa-download"></i> <?php echo htmlspecialchars($submission['tenFile']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($submission['diem'] !== null): ?>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-star"></i><span>Điểm số:</span></div>
                            <?php
                            $score = (float)($submission['diem'] ?? 0);
                            $color = ($score > 5) ? '#000' : '#dc3545';
                            ?>
                            <div class="detail-value detail-grade" style="color: <?php echo $color; ?>">
                                <?php echo number_format($score, 1); ?>/10
                            </div>

                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$canSubmit): ?>
                <h2 class="detail-row homework-detail-title "><i class="fas fa-lock"></i>Nộp bài tập</h2>
                <div class="form-section">
                    <!-- <h3><i class="fas fa-lock"></i> Nộp bài tập</h3> -->
                    <div class="homework-detail-locked">
                        <i class="fas fa-<?php echo $submitReason === 'locked' ? 'lock' : 'exclamation-circle'; ?>"></i>
                        <?php if ($submitReason === 'locked'): ?>
                            <h4>Bài tập đã bị khóa</h4>
                            <p>Giáo viên đã khóa bài tập này. Học sinh không thể nộp bài.</p>
                        <?php elseif ($submission): ?>
                            <h4>Không thể nộp lại bài tập</h4>
                            <p>Không được phép nộp lại sau khi đã quá thời hạn.</p>
                        <?php else: ?>
                            <h4>Bài tập đã hết hạn nộp</h4>
                            <p>
                                Thời gian nộp bài đã kết thúc lúc <strong><?php echo date('d/m/Y H:i', $deadlineTimestamp); ?></strong>.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <h2 class="detail-row homework-detail-title"><i class="fa-solid fa-circle-up"></i><?php echo $submission ? 'Nộp lại bài tập' : 'Nộp bài tập'; ?></h2>
                <div class="form-section">

                    <form class="form-submit-homework" method="POST" enctype="multipart/form-data" id="submitForm">
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Nội dung bài làm (tùy chọn)</label>
                            <textarea name="noiDung" id="noiDung" rows="4" placeholder="Nhập nội dung bài làm của bạn..."><?php echo isset($submission['noiDung']) ? htmlspecialchars($submission['noiDung']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-cloud-upload-alt"></i> Tải lên file bài làm (tùy chọn)</label>

                            <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('fileInput').click()">
                                <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="file-upload-text">Kéo thả file vào đây hoặc click để chọn file</div>
                                <div class="file-upload-hint">Định dạng: PDF, Word, Ảnh (JPG, PNG), ZIP, RAR. Tối đa 10MB</div>
                                <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar">
                            </div>

                            <div class="selected-file" id="selectedFile">
                                <div class="file-icon"><i class="fas fa-file-alt"></i></div>
                                <div class="file-info">
                                    <div class="file-name" id="fileName"></div>
                                    <div class="file-size" id="fileSize"></div>
                                </div>
                                <button type="button" class="remove-file" onclick="removeFile()">
                                    <i class="fa-solid fa-trash"></i> </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit" class="btn-submit-homework  ">
                                <i class="fas fa-paper-plane"></i> Nộp bài
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const selectedFile = document.getElementById('selectedFile');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitForm = document.getElementById('submitForm');

        if (fileInput && fileUploadArea && selectedFile) {
            fileInput.addEventListener('change', handleFileSelect);

            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            });
        }

        function handleFileSelect() {
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                selectedFile.classList.add('show');
            }
        }

        function removeFile() {
            if (fileInput) {
                fileInput.value = '';
                selectedFile.classList.remove('show');
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        if (submitForm) {
            submitForm.addEventListener('submit', function(e) {
                const noiDung = document.getElementById('noiDung').value.trim();
                const hasFile = fileInput.files.length > 0;

                if (!hasFile && !noiDung) {
                    e.preventDefault();
                    alert('Vui lòng tải lên file hoặc nhập nội dung bài làm!');
                    return false;
                }

                if (hasFile) {
                    const file = fileInput.files[0];
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        e.preventDefault();
                        alert('File quá lớn! Vui lòng chọn file nhỏ hơn 10MB.');
                        return false;
                    }
                }

                if (!confirm('Bạn có chắc chắn muốn nộp bài?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    </script>

</body>

</html>