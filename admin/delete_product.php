<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Kết nối database
require_once '../includes/db_connect.php';

// 3. Khởi tạo biến trạng thái và thông báo
$status = 'error';
$message = 'Yêu cầu không hợp lệ hoặc ID sản phẩm bị thiếu.';
$image_to_delete = null; // Biến lưu tên file ảnh cần xóa

// 4. Lấy ID sản phẩm từ URL và kiểm tra
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // --- Bắt đầu transaction (tùy chọn nhưng tốt hơn để đảm bảo tính toàn vẹn) ---
    $conn->begin_transaction();

    try {
        // 5. LẤY TÊN FILE ẢNH TRƯỚC KHI XÓA KHỎI DB
        $sql_select_image = "SELECT image FROM products WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select_image);

        if ($stmt_select === false) {
            throw new Exception("Lỗi hệ thống [DBP SI]: Không thể chuẩn bị lấy tên ảnh.");
        }

        $stmt_select->bind_param("i", $product_id);
        if (!$stmt_select->execute()) {
             throw new Exception("Lỗi hệ thống [DBE SI]: Không thể thực thi lấy tên ảnh.");
        }

        $result_select = $stmt_select->get_result();
        if ($result_select->num_rows === 1) {
            $product_data = $result_select->fetch_assoc();
            $image_to_delete = $product_data['image']; // Lấy được tên file ảnh
        } else {
            // Không tìm thấy sản phẩm với ID này để xóa
            throw new Exception("Không tìm thấy sản phẩm để xóa (ID: " . $product_id . ").");
        }
        $stmt_select->close(); // Đóng statement select


        // 6. Chuẩn bị và thực thi câu lệnh DELETE
        $sql_delete = "DELETE FROM products WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        if ($stmt_delete === false) {
             throw new Exception("Lỗi hệ thống [DBP D]: Không thể chuẩn bị xóa sản phẩm.");
        }

        $stmt_delete->bind_param("i", $product_id);

        // Thực thi xóa khỏi DB
        if ($stmt_delete->execute()) {
            // Kiểm tra xem có dòng nào thực sự bị xóa không (để chắc chắn ID tồn tại)
            if ($stmt_delete->affected_rows > 0) {
                // ---- Xóa khỏi DB thành công ----
                $status = 'success';
                $message = "Sản phẩm đã được xóa thành công!";

                // 7. Xóa file ảnh vật lý (NẾU có tên file ảnh và xóa DB thành công)
                if (!empty($image_to_delete)) {
                    $image_path = "../images/" . $image_to_delete;
                    // Kiểm tra file tồn tại trước khi xóa
                    if (file_exists($image_path) && is_file($image_path)) {
                        if (!unlink($image_path)) {
                            // Ghi log lỗi xóa file nhưng không thay đổi status thành công chung
                            error_log("Could not delete image file after DB delete: " . $image_path);
                            $message .= " (Lưu ý: Có lỗi khi xóa file ảnh trên server)";
                        }
                    } else {
                         error_log("Image file not found for deletion: " . $image_path);
                         // $message .= " (Lưu ý: Không tìm thấy file ảnh để xóa)"; // Có thể không cần báo lỗi này
                    }
                }
                 // Commit transaction nếu mọi thứ ổn
                 $conn->commit();

            } else {
                // Lệnh execute thành công nhưng không có dòng nào bị ảnh hưởng -> ID không tồn tại?
                 throw new Exception("Không tìm thấy sản phẩm để xóa (affected_rows = 0).");
            }
        } else {
            // Lỗi khi thực thi DELETE
             throw new Exception("Lỗi hệ thống [DBE D]: Không thể xóa sản phẩm khỏi CSDL.");
        }
        $stmt_delete->close(); // Đóng statement delete

    } catch (Exception $e) {
        // ---- Có lỗi xảy ra trong quá trình ----
        $conn->rollback(); // Hoàn tác lại các thay đổi trong transaction
        $message = $e->getMessage(); // Lấy thông báo lỗi từ Exception
        error_log("Delete Product Error: " . $message . " (ID: " . $product_id . ")"); // Ghi log chi tiết
        $status = 'error'; // Đảm bảo status là error
    }

} // Kết thúc kiểm tra isset($_GET['id'])

// 8. Đóng kết nối database
if ($conn && $conn->ping()) {
    $conn->close();
}

// 9. Chuyển hướng người dùng về trang index.php kèm thông báo
header("Location: index.php?status=" . $status . "&msg=" . urlencode($message));
exit();

?>