<?php
// login.php
session_start();

// ถ้าล็อคอินอยู่แล้ว ให้ไปหน้า Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR Dashboard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-body: #f4f7fa;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --accent: #2563eb;
            --accent-light: #3b82f6;
            --text-primary: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        /* Animated background orbs */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 400px; height: 400px;
            background: var(--accent);
            top: -100px; right: -100px;
        }
        body::after {
            width: 350px; height: 350px;
            background: #60a5fa;
            bottom: -80px; left: -80px;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            padding: 44px 40px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-icon {
            width: 64px; height: 64px; border-radius: 18px;
            background: linear-gradient(135deg, var(--accent), #3b82f6);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(37,99,235,0.25);
        }
        .login-icon i { font-size: 1.6rem; color: #fff; }

        .login-header h4 {
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 1.5rem;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .form-label {
            color: var(--text-muted) !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            background: #f8fafc !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-size: 0.9rem !important;
            color: var(--text-primary) !important;
            transition: all 0.3s;
        }
        .form-control::placeholder { color: #94a3b8; }
        .form-control:focus {
            background: #fff !important;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1) !important;
            border-color: var(--accent) !important;
        }

        .password-input-group .form-control {
            border-right: 0 !important;
            border-radius: 12px 0 0 12px !important;
        }

        .password-toggle {
            background: #f8fafc !important;
            border: 1px solid var(--border-color) !important;
            border-left: 0 !important;
            border-radius: 0 12px 12px 0 !important;
            color: var(--text-muted);
            min-width: 48px;
        }
        .password-toggle:hover, .password-toggle:focus {
            color: var(--accent);
            background: #fff !important;
            border-color: var(--accent) !important;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent), #3b82f6) !important;
            border: none !important;
            padding: 14px !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            width: 100%;
            margin-top: 12px;
            color: #fff !important;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(37,99,235,0.2);
        }
        .btn-login:hover {
            box-shadow: 0 12px 30px rgba(37,99,235,0.3);
            transform: translateY(-2px);
        }

        .error-msg {
            color: #ef4444;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
            display: none;
            padding: 12px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-icon">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <h4>HR Dashboard</h4>
        <p>Sign in to your account to continue</p>
    </div>

    <div id="error-msg" class="error-msg">Username หรือ Password ไม่ถูกต้อง</div>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter username" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group password-input-group">
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                <button type="button" class="btn password-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-login">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
        </button>
    </form>
</div>

<script>
const passwordInput = document.getElementById('loginPassword');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function() {
    const isHidden = passwordInput.type === 'password';
    const icon = this.querySelector('i');

    passwordInput.type = isHidden ? 'text' : 'password';
    this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    this.setAttribute('aria-pressed', String(isHidden));
    icon.classList.toggle('fa-eye', !isHidden);
    icon.classList.toggle('fa-eye-slash', isHidden);
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const errorMsg = document.getElementById('error-msg');

    fetch('api/auth_login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'index.php';
        } else {
            errorMsg.style.display = 'block';
            errorMsg.textContent = data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
});
</script>

</body>
</html>
