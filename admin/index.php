<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Nếu chưa đăng nhập, đá về trang login
    header("Location: login.php");
    exit();
}

// 2. Kết nối database
// Lưu ý đường dẫn này là đi ra khỏi thư mục admin rồi vào includes
require_once '../includes/db_connect.php';

// 3. Lấy username admin từ session để hiển thị lời chào (tùy chọn)
$admin_username = $_SESSION['admin_username'] ?? 'Admin'; // Nếu không có username thì dùng 'Admin'

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị</title>
    <link rel="stylesheet" href="../style.css">
    
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Chào mừng, <?php echo htmlspecialchars($admin_username); ?>!</h1>
            <div class="admin-actions">
                <a href="logout.php" class="btn btn-secondary">Đăng Xuất</a>
            </div>
        </div>

        <div class="product-management">
            <h2>Quản Lý Thú Cưng</h2>
            <div class="admin-actions" style="margin-bottom: 20px;">
                <a href="add_product.php" class="btn btn-primary">Thêm Thú Cưng Mới</a>
            </div>

            <?php
            // 4. Truy vấn lấy tất cả sản phẩm từ database
            // Sắp xếp theo ID giảm dần để sản phẩm mới nhất lên đầu
            $sql = "SELECT id, name, description, image FROM products ORDER BY id DESC";
            $result = $conn->query($sql);

            // 5. Kiểm tra và hiển thị dữ liệu
            if ($result && $result->num_rows > 0) {
                echo '<table class="product-table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th style="width: 5%;">ID</th>'; // Đặt độ rộng cột
                echo '<th style="width: 10%;">Hình ảnh</th>';
                echo '<th style="width: 25%;">Tên Sản Phẩm</th>';
                echo '<th style="width: 40%;">Mô tả (ngắn)</th>';
                echo '<th style="width: 20%;">Hành động</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                // 6. Lặp qua từng dòng sản phẩm
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td><img src="../images/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '"></td>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    $description = strip_tags($row["description"]); // Bỏ thẻ HTML nếu có
                    $short_desc = mb_substr($description, 0, 120, 'UTF-8'); // Lấy 120 ký tự UTF-8
                    echo '<td>' . htmlspecialchars($short_desc) . (mb_strlen($description, 'UTF-8') > 120 ? '...' : '') . '</td>';

                    // 7. Các nút Sửa và Xóa
                    echo '<td class="actions">';
                    echo '<a href="edit_product.php?id=' . $row['id'] . '" class="btn btn-warning">Sửa</a>';
                    echo '<a href="delete_product.php?id=' . $row['id'] . '" class="btn btn-danger" onclick="return confirm(\'Bạn có chắc chắn muốn xóa sản phẩm [' . htmlspecialchars(addslashes($row['name'])) . '] không?\');">Xóa</a>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            } else {
                // Nếu không có sản phẩm nào
                echo '<p class="no-products">Hiện tại chưa có sản phẩm nào trong cơ sở dữ liệu.</p>';
            }
            // 8. Đóng kết nối database
            $conn->close();
            ?>
        </div> 
    </div> 
</body>
</html>