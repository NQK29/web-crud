<?php
session_start(); 
require_once '../includes/db_connect.php';

$stmt = null;
$login_error = null; 

// --- Chỉ xử lý khi request là POST (tức là form đã được submit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Kiểm tra xem username/password có bị trống không
    if (empty($username) || empty($password)) {
        $login_error = "Vui lòng nhập đủ tên đăng nhập và mật khẩu.";
    } else {
        // --- Dữ liệu hợp lệ, bắt đầu kiểm tra với DB ---
        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);

        // Kiểm tra xem prepare có lỗi không (do SQL sai hoặc lỗi kết nối)
        if ($stmt === false) {
            error_log("Login Prepare failed: (" . $conn->errno . ") " . $conn->error); // Ghi lỗi ra log của server
            $login_error = "Lỗi hệ thống [DBP], vui lòng thử lại sau."; // Thông báo lỗi chung cho người dùng
        } else {
            // Gắn tham số username vào câu lệnh SQL
            $stmt->bind_param("s", $username);

            // Thực thi câu lệnh
            if (!$stmt->execute()) {
                error_log("Login Execute failed: (" . $stmt->errno . ") " . $stmt->error); // Ghi lỗi ra log
                $login_error = "Lỗi hệ thống [DBE], vui lòng thử lại sau."; // Thông báo lỗi chung
            } else {
                // Lấy kết quả trả về
                $result = $stmt->get_result();

                // Kiểm tra xem có tìm thấy admin với username đó không (chỉ được có 1)
                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc(); // Lấy thông tin admin

                    // **Kiểm tra mật khẩu bằng password_verify()**
                    if (password_verify($password, $admin['password'])) {

                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];

                        $stmt->close();
                        $conn->close();

                        // Chuyển hướng đến trang dashboard admin
                        header("Location: index.php");
                        exit(); // Quan trọng: Dừng script sau khi chuyển hướng

                    } else {
                        $login_error = "Sai tên đăng nhập hoặc mật khẩu.";
                    }
                } else {
                    $login_error = "Sai tên đăng nhập hoặc mật khẩu.";
                }
            }
             if ($stmt) { 
                 $stmt->close();
             }
        }
    }


    if ($conn && $conn->ping()) { 
        $conn->close();
    }

    // Chuyển hướng NGƯỢC LẠI trang login KÈM theo thông báo lỗi
    header("Location: login.php?error=" . urlencode($login_error));
    exit(); 

} else {

    if ($conn && $conn->ping()) {
        $conn->close();
    }
    header("Location: login.php");
    exit(); 
}
?>