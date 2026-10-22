<?php
/**
 * Admin Login Page
 * RM-Retail POS Project (JIFFY POS)
 */

require_once '../config.php';

// หากเข้าสู่ระบบแล้ว ให้เปลี่ยนเส้นทางไปยังหน้าแผงควบคุมระบบหลังบ้านทันที
if (is_admin_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // เก็บข้อมูลการล็อกอินลง Session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_display_name'] = $user['display_name'];

                header("Location: index.php");
                exit;
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (\PDOException $e) {
            $error = 'เกิดข้อผิดพลาดทางระบบ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - RM-Retail POS Project (JIFFY POS)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5146b6;
            --primary-hover: #3e32a0;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --panel: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.12);
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --error-bg: rgba(239, 68, 68, 0.2);
            --error-border: rgba(239, 68, 68, 0.4);
            --error-text: #fca5a5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Noto Sans Thai", sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: var(--panel);
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(to right, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .error-alert {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            color: white;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
            background: rgba(15, 23, 42, 0.8);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(81, 70, 182, 0.3);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(81, 70, 182, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
        }

        .footer-links a {
            font-size: 13.5px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #818cf8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>RM-Retail POS Admin Portal</h1>
            <p>กรุณาลงชื่อเข้าใช้งานเพื่อเริ่มจัดการแผนงาน</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert">
                <?= sanitize_output($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="ระบุ username แอดมิน" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน (Password)</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="ระบุรหัสผ่าน" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
        </form>

        <div class="footer-links">
            <a href="../">← กลับไปยังหน้าแสดงผลหลัก (Timeline)</a>
        </div>
    </div>
</body>
</html>
