<?php
// 1. Bắt đầu session và kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Nếu chưa đăng nhập, chuyển về trang login
    header("Location: login.php");
    exit();
}

// 2. Kết nối database vì chúng ta cần tương tác với CSDL
require_once '../includes/db_connect.php';

// 3. Khởi tạo biến trạng thái và thông báo
$status = 'error'; // Mặc định trạng thái là lỗi
$message = 'Yêu cầu không hợp lệ.'; // Thông báo mặc định

// 4. Kiểm tra xem form đã được gửi đi bằng phương thức POST chưa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    // 5. Lấy dữ liệu từ form gửi lên
    // Dùng trim() để loại bỏ khoảng trắng thừa ở đầu và cuối chuỗi
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $product_description = isset($_POST['product_description']) ? trim($_POST['product_description']) : '';

    // 6. Thực hiện kiểm tra dữ liệu đầu vào (Validation)
    if (empty($product_name)) {
        $message = "Tên sản phẩm không được để trống.";
    }
    // Kiểm tra xem file ảnh có được gửi lên không và có lỗi trong quá trình upload không
    elseif (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] != UPLOAD_ERR_OK) {
        // Xử lý các mã lỗi upload thường gặp
        $upload_error_code = $_FILES['product_image']['error'];
        switch ($upload_error_code) {
            case UPLOAD_ERR_INI_SIZE: // Lỗi vượt quá dung lượng cho phép trong php.ini
            case UPLOAD_ERR_FORM_SIZE: // Lỗi vượt quá dung lượng cho phép đã khai báo trong form HTML (MAX_FILE_SIZE)
                $message = "Kích thước file ảnh quá lớn so với mức cho phép.";
                break;
            case UPLOAD_ERR_PARTIAL: // Lỗi file chỉ được upload một phần
                $message = "File ảnh chỉ được tải lên một phần.";
                break;
            case UPLOAD_ERR_NO_FILE: // Lỗi không có file nào được chọn
                $message = "Bạn chưa chọn file hình ảnh nào.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR: // Lỗi không tìm thấy thư mục tạm
                $message = "Lỗi server: Không tìm thấy thư mục tạm.";
                break;
            case UPLOAD_ERR_CANT_WRITE: // Lỗi không thể ghi file vào đĩa
                $message = "Lỗi server: Không thể ghi file ảnh lên đĩa.";
                break;
            case UPLOAD_ERR_EXTENSION: // Lỗi do một extension PHP nào đó chặn việc upload
                $message = "Lỗi server: Upload file bị chặn bởi extension.";
                break;
            default:
                $message = "Có lỗi không xác định xảy ra trong quá trình upload ảnh (Mã lỗi: " . $upload_error_code . ").";
        }
    } else {
        // --- Dữ liệu tên sản phẩm và file ảnh có vẻ ổn, tiếp tục xử lý file ---

        $image_info = $_FILES['product_image'];
        $image_name_original = $image_info['name']; // Tên gốc của file
        $image_tmp_path = $image_info['tmp_name']; // Đường dẫn tạm thời của file trên server
        $image_size = $image_info['size']; // Kích thước file (bytes)

        // 7. Định nghĩa thư mục sẽ lưu trữ ảnh đã upload
        $upload_directory = "../images/";

        // **RẤT QUAN TRỌNG**: Đảm bảo thư mục này tồn tại và PHP có quyền ghi vào đó.
        if (!is_dir($upload_directory)) {
            // Cố gắng tạo thư mục nếu chưa có
            if (!mkdir($upload_directory, 0777, true) && !is_dir($upload_directory)) {
                 $message = "Lỗi: Không thể tạo thư mục lưu trữ ảnh.";
                 // Chuyển hướng sớm nếu không tạo được thư mục
                 header("Location: add_product.php?status=" . $status . "&msg=" . urlencode($message));
                 exit();
            }
        } elseif (!is_writable($upload_directory)) {
             $message = "Lỗi: Thư mục lưu trữ ảnh không có quyền ghi.";
             // Chuyển hướng sớm nếu không ghi được vào thư mục
             header("Location: add_product.php?status=" . $status . "&msg=" . urlencode($message));
             exit();
        }


        // 8. Tạo tên file mới, độc nhất để tránh bị ghi đè và tăng bảo mật
        $file_extension = strtolower(pathinfo($image_name_original, PATHINFO_EXTENSION));
        $unique_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
        $target_path = $upload_directory . $unique_filename; // Đường dẫn đầy đủ tới file sẽ lưu

        // 9. Kiểm tra các điều kiện của file ảnh (đuôi file, kích thước, loại file)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size_bytes = 5 * 1024 * 1024; // Giới hạn 5MB

        if (!in_array($file_extension, $allowed_extensions)) {
            $message = "Chỉ cho phép tải lên file ảnh có định dạng JPG, JPEG, PNG, GIF.";
        } elseif ($image_size > $max_file_size_bytes) {
            $message = "Kích thước file ảnh không được vượt quá 5MB.";
        } elseif (getimagesize($image_tmp_path) === false) {
            // Kiểm tra xem có phải file ảnh thực sự không bằng cách đọc thông tin ảnh
            $message = "File tải lên không phải là file ảnh hợp lệ.";
        } else {
            // --- File ảnh đã vượt qua các bước kiểm tra ---

            // 10. Di chuyển file từ thư mục tạm vào thư mục đích
            if (move_uploaded_file($image_tmp_path, $target_path)) {
                // ---- Upload file ảnh thành công! ----

                // 11. Chuẩn bị lưu thông tin vào cơ sở dữ liệu
                $sql = "INSERT INTO products (name, description, image) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    // Lỗi khi chuẩn bị câu lệnh SQL
                    error_log("Add Product - SQL Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    $message = "Lỗi hệ thống khi chuẩn bị lưu dữ liệu [DBP].";
                    // **Quan trọng:** Nếu không chuẩn bị được SQL, nên xóa file ảnh vừa upload để tránh rác
                    unlink($target_path);
                } else {
                    $stmt->bind_param("sss", $product_name, $product_description, $unique_filename);

                    // Thực thi câu lệnh INSERT
                    if ($stmt->execute()) {
                        // --- Thêm sản phẩm vào CSDL thành công ---
                        $status = 'success'; // Đổi trạng thái thành công
                        $message = "Sản phẩm đã được thêm thành công!";
                    } else {
                        // Lỗi khi thực thi câu lệnh INSERT
                        error_log("Add Product - SQL Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                        $message = "Lỗi hệ thống khi lưu dữ liệu [DBE].";
                        unlink($target_path);
                    }
                    $stmt->close();
                }
            } else {
                // Lỗi khi di chuyển file từ thư mục tạm sang thư mục đích
                $message = "Không thể di chuyển file ảnh đã tải lên. Vui lòng kiểm tra quyền ghi của thư mục 'images'.";
            }
        } 
    } 
} 

// 12. Đóng kết nối cơ sở dữ liệu (nếu nó đang mở)
if ($conn && $conn->ping()) {
    $conn->close();
}

// 13. Chuyển hướng người dùng trở lại trang add_product.php
header("Location: add_product.php?status=" . $status . "&msg=" . urlencode($message));
exit(); 

?>