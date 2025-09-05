<?php
// 1. Kết nối Database 
require_once 'includes/db_connect.php';

// 2. Nhúng Header 
require_once 'includes/header.php';
?>

<h1>Các giống loài nổi bật</h1>
<div class="product-list">
    <?php
    $sql = "SELECT id, name, description, image FROM products ORDER BY id DESC LIMIT 6"; // Lấy 6 SP mới nhất
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<div class="product-item">';
            echo '<img src="images/' . htmlspecialchars($row["image"]) . '" alt="' . htmlspecialchars($row["name"]) . '" width="150">';
            echo '<h2>' . htmlspecialchars($row["name"]) . '</h2>';
            // Hiển thị mô tả ngắn gọn
            $short_desc = mb_substr(strip_tags($row["description"]), 0, 80, 'UTF-8'); // Lấy 80 ký tự đầu
            echo '<p>' . htmlspecialchars($short_desc) . (mb_strlen($row["description"], 'UTF-8') > 80 ? '...' : '') . '</p>';
            echo '</div>';
        }
    } else {
        echo "<p>Hiện chưa có sản phẩm nào.</p>";
    }
    ?>
</div>

<?php
// 3. Nhúng Footer 
require_once 'includes/footer.php';
?>