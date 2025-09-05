<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Kết nối database (cần để lấy dữ liệu sản phẩm cũ)
require_once '../includes/db_connect.php';

$product_id = null;
$product = null; // Biến lưu thông tin sản phẩm
$error_message = ''; // Biến lưu thông báo lỗi

// 3. Lấy ID sản phẩm từ URL và kiểm tra tính hợp lệ
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']); // Chuyển thành số nguyên

    // 4. Truy vấn lấy thông tin sản phẩm cần sửa
    $sql = "SELECT id, name, description, image FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Edit Product - Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $error_message = "Lỗi hệ thống [DBP]. Không thể tải dữ liệu sản phẩm.";
    } else {
        $stmt->bind_param("i", $product_id); // i = integer
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc(); // Lấy dữ liệu sản phẩm vào biến $product
            } else {
                $error_message = "Không tìm thấy sản phẩm với ID này.";
            }
        } else {
            error_log("Edit Product - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
            $error_message = "Lỗi hệ thống [DBE]. Không thể tải dữ liệu sản phẩm.";
        }
        $stmt->close(); 
    }
} else {
    $error_message = "ID sản phẩm không hợp lệ hoặc bị thiếu.";
}

$status_message = '';
$message_type = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_message = 'Sản phẩm đã được cập nhật thành công!';
        $message_type = 'success';
    } elseif ($_GET['status'] == 'error') {
        $error_detail = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'Có lỗi xảy ra.';
        $status_message = 'Lỗi cập nhật sản phẩm: ' . $error_detail;
        $message_type = 'error';
    }
}

// Đóng kết nối DB sau khi đã lấy xong dữ liệu (hoặc nếu có lỗi sớm)
if ($conn && $conn->ping()) { $conn->close();}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Thú Cưng</title>
    <link rel="stylesheet" href="../style.css">
    
</head>
<body>
    <div class="admin-container">
        <h1>Chỉnh Sửa Thú Cưng</h1>

        <?php
        // Hiển thị thông báo thành công/lỗi nếu có từ trang xử lý
        if (!empty($status_message)) {
            echo '<p class="message ' . $message_type . '">' . $status_message . '</p>';
        }
        if (!empty($error_message)) {
            echo '<p class="message error">' . $error_message . '</p>';
            echo '<p class="back-link"><a href="index.php">&laquo; Quay lại Dashboard</a></p>';
        }
        elseif (empty($error_message) && $product) {
        ?>
            <form action="process_edit_product.php" method="POST" enctype="multipart/form-data">

                <?php // Input ẩn chứa ID sản phẩm ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php // Input ẩn chứa tên file ảnh hiện tại ?>
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image']); ?>">

                <div class="form-group">
                    <label for="product_name">Tên Thú Cưng:</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="product_description">Mô Tả Thú Cưng:</label>
                    <textarea id="product_description" name="product_description" rows="6"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Hình Ảnh Hiện Tại:</label>
                    <img src="../images/<?php echo htmlspecialchars($product['image']); ?>" alt="Ảnh hiện tại" class="current-image">
                </div>

                <div class="form-group">
                    <label for="new_product_image">Thay Ảnh Mới (Để trống nếu không muốn đổi):</label>
                    <input type="file" id="new_product_image" name="new_product_image" accept="image/png, image/jpeg, image/gif">
                     <small style="display: block; margin-top: 5px; color: #6c757d;">Chọn file ảnh mới (JPG, PNG, GIF, dưới 5MB).</small>
                </div>

                <div style="text-align: center;">
                     <button type="submit" name="update" class="submit-btn">Cập Nhật Thú Cưng</button>
                </div>

            </form>

            <p class="back-link"><a href="index.php">&laquo; Quay lại Dashboard</a></p>
            <?php
        } 
        ?>
    </div>
</body>
</html>