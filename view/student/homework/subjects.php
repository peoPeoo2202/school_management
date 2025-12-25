<?php
$subjects = $controller->getAllSubjectsForStudent($maHS);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<div class="homework-page">
    <div class="title-header">
        <h4><i class="fas fa-book"></i> Danh sách bài tập</h4>
    </div>

    <?php if (empty($subjects)): ?>
        <div class="empty-state">
            <i class="fas fa-graduation-cap"></i>
            <h3>Không có môn học</h3>
            <p>Không tìm thấy môn học nào trong hệ thống.</p>
        </div>
    <?php else: ?>

        <div class="filter-section">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th"></i> Tất cả
                </button>
                <button class="filter-btn" data-filter="have-homework">
                    <i class="fas fa-clipboard-check"></i> Có bài tập
                </button>
                <button class="filter-btn" data-filter="overdue">
                    <i class="fas fa-exclamation-triangle"></i> Có bài quá hạn
                </button>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Tìm kiếm môn học...">
                <i class="fas fa-search"></i>
            </div>
        </div>

        <div class="subject-grid-container">
            <div class="common-grid" id="subjectGrid">
                <?php foreach ($subjects as $subject): ?>
                    <?php if ((int)$subject['tongBaiTap'] > 0): ?>
                        <a href="index.php?page=submitHomework&subject=<?php echo (int)$subject['maMonHoc']; ?>"
                            class="subject-card"
                            data-name="<?php echo strtolower(htmlspecialchars($subject['tenMonHoc'])); ?>"
                            data-total="<?php echo (int)$subject['tongBaiTap']; ?>"
                            data-overdue="<?php echo (int)$subject['quaHan']; ?>">
                            <div class="subject-header">
                                <div class="subject-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h3 class="subject-name"><?php echo htmlspecialchars($subject['tenMonHoc']); ?></h3>
                            </div>

                            <div class="subject-body">
                                <div class="subject-stats">
                                    <div class="stat-item">
                                        <span class="stat-number total"><?php echo (int)$subject['tongBaiTap']; ?></span>
                                        <span class="stat-label">Số bài tập</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number submitted"><?php echo (int)$subject['daNop']; ?></span>
                                        <span class="stat-label">Đã nộp</span>

                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number overdue"><?php echo (int)$subject['quaHan']; ?></span>
                                        <span class="stat-label">Hết hạn</span>

                                    </div>
                                </div>
                            </div>

                            <div class="subject-footer">
                                <span class="view-homework-btn">
                                    <span>Xem chi tiết</span>
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="subject-card subject-card-no-homework"
                            data-name="<?php echo strtolower(htmlspecialchars($subject['tenMonHoc'])); ?>"
                            data-total="0"
                            data-overdue="0">

                            <div class="subject-header">
                                <div class="subject-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h3 class="subject-name"><?php echo htmlspecialchars($subject['tenMonHoc']); ?></h3>
                            </div>

                            <div class="subject-body">
                                <div class="subject-stats">
                                    <div class="stat-item">
                                        <span class="stat-number total">0</span>
                                        <span class="stat-label">Số bài tập</span>
                                    </div>

                                    <!-- 🔹 THÊM CỘT ĐÃ NỘP -->
                                    <div class="stat-item">
                                        <span class="stat-number submitted">0</span>
                                        <span class="stat-label">Đã nộp</span>
                                    </div>

                                    <div class="stat-item">
                                        <span class="stat-number overdue">0</span>
                                        <span class="stat-label">Hết hạn</span>
                                    </div>
                                </div>
                            </div>

                            <div class="subject-footer">
                                <span class="view-homework-btn">
                                    <i class="fas fa-inbox"></i>
                                    <span>Chưa có bài tập</span>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </div>

        <div class="no-results" id="noResults" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy kết quả</h3>
            <p>Thử tìm kiếm với từ khóa khác </p>
        </div>

    <?php endif; ?>
</div>

<script>
    function removeVietnameseTones(str) {
        str = str.toLowerCase();
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
        str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
        str = str.replace(/đ/g, 'd');
        return str;
    }

    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById('searchInput');
        const noResults = document.getElementById('noResults');
        const gridContainer = document.querySelector('.subject-grid-container');
        const subjectGrid = document.getElementById('subjectGrid');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const subjectCards = document.querySelectorAll('.subject-card');

        // Nếu trang này không có các phần tử filter/search thì thoát luôn
        if (!searchInput || !noResults || !gridContainer || !subjectGrid || subjectCards.length === 0) return;

        function filterSubjects(searchTerm, filter) {
            let visibleCount = 0;

            subjectCards.forEach(card => {
                const name = card.dataset.name || '';
                const nameNormalized = removeVietnameseTones(name);
                const total = parseInt(card.dataset.total || '0');
                const overdue = parseInt(card.dataset.overdue || '0');

                const matchesSearch = searchTerm === '' ||
                    name.includes(searchTerm) ||
                    nameNormalized.includes(searchTerm);

                let matchesFilter = true;
                if (filter === 'have-homework') matchesFilter = total > 0;
                else if (filter === 'overdue') matchesFilter = overdue > 0;

                if (matchesSearch && matchesFilter) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (visibleCount === 0) {
                subjectGrid.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                subjectGrid.style.display = 'grid';
                noResults.style.display = 'none';
            }
        }

        let currentFilter = 'all';

        searchInput.addEventListener('input', function() {
            const searchTerm = removeVietnameseTones(this.value.toLowerCase());
            filterSubjects(searchTerm, currentFilter);
        });

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                currentFilter = this.dataset.filter || 'all';
                const searchTerm = removeVietnameseTones(searchInput.value.toLowerCase());
                filterSubjects(searchTerm, currentFilter);
            });
        });
    });
</script>