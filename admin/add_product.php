<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Biến để lưu trữ thông báo trạng thái (từ trang xử lý trả về)
$status_message = '';
$message_type = ''; 

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_message = 'Sản phẩm đã được thêm thành công!';
        $message_type = 'success';
    } elseif ($_GET['status'] == 'error') {
        $error_detail = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'Có lỗi không xác định xảy ra.';
        $status_message = 'Lỗi thêm sản phẩm: ' . $error_detail;
        $message_type = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Thú Cưng Mới</title>
    <link rel="stylesheet" href="../style.css">

    
</head>
<body>
    <div class="admin-container">
        <h1>Thêm Thú Cưng Mới</h1>

        <?php
        // Hiển thị thông báo thành công hoặc lỗi (nếu có)
        if (!empty($status_message)) {
            echo '<p class="message ' . $message_type . '">' . $status_message . '</p>';
        }
        ?>

        <form action="process_add_product.php" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="product_name">Tên Thú Cưng:</label>
                <input type="text" id="product_name" name="product_name" required>
            </div>

            <div class="form-group">
                <label for="product_description">Mô Tả Thú Cưng:</label>
                <textarea id="product_description" name="product_description" rows="6"></textarea>
            </div>

            <div class="form-group">
                <label for="product_image">Hình Ảnh Thú Cưng:</label>
                <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg, image/gif" required>
                <small style="display: block; margin-top: 5px; color: #6c757d;">Chọn file ảnh (JPG, PNG, GIF).</small>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Thêm Thú Cưng</button>
            </div>

        </form>

        <p class="back-link"><a href="index.php">&laquo; Quay lại danh sách Thú Cưng</a></p>
    </div>
</body>
</html>