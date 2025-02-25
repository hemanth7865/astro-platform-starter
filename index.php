<?php
session_start();
require_once 'config.php';

// If user is already logged in, redirect based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $redirect_page = ($_SESSION['role'] === 'admin') ? "admin_dashboard.php" : 
                     (($_SESSION['role'] === 'choreographer') ? "choreographer_dashboard.php" : "dancer_dashboard.php");
    header("Location: $redirect_page");
    exit;
}

// Handle registration
$reg_error = '';
if (isset($_POST['register'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $pass  = $_POST['password'];
    $hash  = password_hash($pass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $reg_error = "Email already exists.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
        $reg_error = ($stmt->execute([$name, $email, $hash, $role])) ? "Registration successful. Please login." : "Registration failed. Try again.";
    }
}

// Handle login
$login_error = '';
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        $redirect_page = ($user['role'] === 'admin') ? "admin_dashboard.php" : 
                         (($user['role'] === 'choreographer') ? "choreographer_dashboard.php" : "dancer_dashboard.php");
        header("Location: $redirect_page");
        exit;
    } else {
        $login_error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DANCE ON - Login / Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0062cc;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            padding: 15px;
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-header img {
            height: 50px;
            margin-right: 10px;
        }
        .form-control, .btn {
            border-radius: 10px;
        }
        .alert {
            border-radius: 10px;
        }
        .footer {
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 20px;
        }
        .footer a {
            text-decoration: none;
            color: #0062cc;
        }
        @media (max-width: 768px) {
            .form-section {
                margin-top: 20px;
            }
            .card-header h3 {
                font-size: 1.2rem;
            }
        }
        @media (max-width: 576px) {
            .logo-container img {
                width: 100%;
                height: auto;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            .card-header img {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 text-center logo-container">
                <img src="logo.png" alt="Dance On Logo" class="img-fluid">
            </div>
            <div class="col-lg-6 col-md-8">
                <div class="card">
                    <div class="card-header">
                        
                        <h3>DANCE ON</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Registration Section -->
                            <div class="col-md-6 form-section">
                                <h5>Register</h5>
                                <?php if ($reg_error) : ?>
                                    <div class="alert alert-info"><?php echo $reg_error; ?></div>
                                <?php endif; ?>
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="dancer">Dancer</option>
                                            <option value="choreographer">Choreographer</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                                </form>
                            </div>

                            <!-- Login Section -->
                            <div class="col-md-6 form-section">
                                <h5>Login</h5>
                                <?php if ($login_error) : ?>
                                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                                <?php endif; ?>
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-success w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; 2025 Dance Competition. All rights reserved. <a href="#">Terms & Conditions</a> | <a href="#">Privacy Policy</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
