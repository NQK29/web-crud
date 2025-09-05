<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Kết nối database
require_once '../includes/db_connect.php';

// 3. Khởi tạo biến trạng thái, thông báo và ID sản phẩm
$status = 'error';
$message = 'Yêu cầu không hợp lệ hoặc có lỗi xảy ra.';
$product_id = null; // Sẽ lấy từ form, cần để chuyển hướng lại đúng trang edit

// 4. Chỉ xử lý khi form được submit bằng POST và nút 'update' (name của nút submit) được nhấn
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    // 5. Lấy dữ liệu từ form gửi lên
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $product_description = isset($_POST['product_description']) ? trim($_POST['product_description']) : '';
    $current_image_filename = isset($_POST['current_image']) ? trim($_POST['current_image']) : ''; // Lấy tên file ảnh hiện tại từ input ẩn

    // Biến để lưu tên file ảnh sẽ được cập nhật vào database
    $image_filename_to_update = $current_image_filename; 
    $new_image_uploaded = false; 
    $old_image_path_to_delete = null; 
    $upload_directory = "../images/"; 

    // 6. Kiểm tra dữ liệu cơ bản (ID và Tên sản phẩm)
    if (empty($product_id)) {
        $message = "Lỗi: Không xác định được ID sản phẩm cần cập nhật.";
        header("Location: index.php?status=" . $status . "&msg=" . urlencode($message));
        exit();
    } elseif (empty($product_name)) {
        $message = "Tên sản phẩm không được để trống.";
        goto redirect_with_message;
    }

    // --- Dữ liệu ID và Tên hợp lệ, tiếp tục xử lý ảnh (nếu có) ---

    // 7. Kiểm tra và xử lý nếu có file ảnh MỚI được upload
    if (isset($_FILES['new_product_image']) && $_FILES['new_product_image']['error'] == UPLOAD_ERR_OK) {
        $new_image_info = $_FILES['new_product_image'];
        $new_image_name_original = $new_image_info['name'];
        $new_image_tmp_path = $new_image_info['tmp_name'];
        $new_image_size = $new_image_info['size'];

        $file_extension = strtolower(pathinfo($new_image_name_original, PATHINFO_EXTENSION));
        $new_unique_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
        $target_path = $upload_directory . $new_unique_filename; 

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size_bytes = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_extension, $allowed_extensions)) {
            $message = "Ảnh mới: Chỉ chấp nhận file định dạng JPG, JPEG, PNG, GIF.";
            goto redirect_with_message;
        } elseif ($new_image_size > $max_file_size_bytes) {
            $message = "Ảnh mới: Kích thước file không được vượt quá 5MB.";
            goto redirect_with_message;
        } elseif (getimagesize($new_image_tmp_path) === false) {
            $message = "Ảnh mới: File tải lên không phải là ảnh hợp lệ.";
            goto redirect_with_message;
        } else {
            // --- Ảnh mới hợp lệ, di chuyển vào thư mục images ---
            if (move_uploaded_file($new_image_tmp_path, $target_path)) {
                // Upload ảnh mới thành công
                $image_filename_to_update = $new_unique_filename; // Đặt tên file mới để cập nhật vào DB
                $new_image_uploaded = true; // Đánh dấu đã có ảnh mới
                // Lưu đường dẫn ảnh cũ để xóa sau khi cập nhật DB thành công
                if (!empty($current_image_filename)) {
                    $old_image_path_to_delete = $upload_directory . $current_image_filename;
                }
            } else {
                // Lỗi khi di chuyển file
                $message = "Không thể lưu file ảnh mới. Vui lòng kiểm tra quyền ghi thư mục 'images'.";
                goto redirect_with_message;
            }
        }
    } elseif (isset($_FILES['new_product_image']) && $_FILES['new_product_image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Có lỗi khác xảy ra khi upload ảnh mới (ví dụ: quá dung lượng...)
        $message = "Có lỗi xảy ra khi tải lên ảnh mới (Mã lỗi: " . $_FILES['new_product_image']['error'] . "). Cập nhật bị hủy.";
        goto redirect_with_message;
    }
    // 8. Chuẩn bị câu lệnh SQL UPDATE
    $sql = "UPDATE products SET name = ?, description = ?, image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Edit Product - SQL Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $message = "Lỗi hệ thống khi chuẩn bị cập nhật [DBP].";
        // Nếu đã lỡ upload ảnh mới thành công thì phải xóa đi vì DB lỗi
        if ($new_image_uploaded) {
            unlink($upload_directory . $image_filename_to_update);
        }
        goto redirect_with_message;
    } else {
        // Gắn tham số vào câu lệnh (tên, mô tả, tên file ảnh (mới hoặc cũ), id)
        $stmt->bind_param("sssi", $product_name, $product_description, $image_filename_to_update, $product_id);

        // Thực thi câu lệnh UPDATE
        if ($stmt->execute()) {
            // --- Cập nhật Database thành công ---
            $status = 'success';
            $message = "Sản phẩm đã được cập nhật thành công!";

            // 9. Xóa ảnh cũ (chỉ khi cập nhật DB thành công VÀ có ảnh mới đã được upload thành công)
            if ($new_image_uploaded && $old_image_path_to_delete !== null) {
                // Kiểm tra file ảnh cũ có tồn tại không trước khi xóa
                if (file_exists($old_image_path_to_delete) && is_file($old_image_path_to_delete)) {
                    if (!unlink($old_image_path_to_delete)) {
                        // Ghi log nếu xóa file cũ thất bại, nhưng không báo lỗi cho người dùng vì DB đã cập nhật
                        error_log("Could not delete old image file: " . $old_image_path_to_delete);
                    }
                } else {
                     error_log("Old image file not found or is not a file: " . $old_image_path_to_delete);
                }
            }
        } else {
            // Lỗi khi thực thi UPDATE
            error_log("Edit Product - SQL Execute failed: (" . $stmt->errno . ") " . $stmt->error);
            $message = "Lỗi hệ thống khi cập nhật dữ liệu [DBE].";
            // Nếu đã lỡ upload ảnh mới thành công thì phải xóa đi vì DB lỗi
            if ($new_image_uploaded) {
                unlink($upload_directory . $image_filename_to_update);
            }
        }
        $stmt->close();
    }
} 

// Label để nhảy tới khi có lỗi và cần chuyển hướng
redirect_with_message:

// 10. Đóng kết nối database
if ($conn && $conn->ping()) {
    $conn->close();
}

// 11. Chuyển hướng người dùng về trang edit_product.php
// Luôn kèm theo ID sản phẩm để quay lại đúng trang edit, cùng với trạng thái và thông báo
if ($product_id !== null) {
     header("Location: edit_product.php?id=" . $product_id . "&status=" . $status . "&msg=" . urlencode($message));
} else {
    // Trường hợp hiếm gặp $product_id bị null thì về trang index
    header("Location: index.php?status=error&msg=" . urlencode("Đã xảy ra lỗi không xác định ID sản phẩm."));
}
exit(); 

?>