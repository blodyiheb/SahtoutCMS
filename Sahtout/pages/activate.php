<?php
require_once '../includes/session.php'; // Includes config.php for database connections

$errors = [];
$success = '';

$token = $_GET['token'] ?? '';
if (!$token) {
    $errors[] = "Invalid activation link.";
} else {
    // Find pending account
    $stmt_select = $site_db->prepare("SELECT username, email, salt, verifier FROM pending_accounts WHERE token = ? AND activated = 0");
    if (!$stmt_select) {
        $errors[] = "Database query error: " . $site_db->error;
    } else {
        $stmt_select->bind_param('s', $token);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($result->num_rows === 0) {
            $errors[] = "Invalid or expired activation link.";
        } else {
            $account = $result->fetch_assoc();
            $stmt_select->close();

            // Insert into acore_auth.account
            $upper_username = strtoupper($account['username']);
            $stmt_insert = $auth_db->prepare("INSERT INTO account (username, salt, verifier, email, reg_mail, expansion) VALUES (?, ?, ?, ?, ?, 2)");
            if (!$stmt_insert) {
                $errors[] = "Database query error: " . $auth_db->error;
            } else {
                $stmt_insert->bind_param('sssss', $upper_username, $account['salt'], $account['verifier'], $account['email'], $account['email']);
                if ($stmt_insert->execute()) {
                    $stmt_insert->close();

                    // Mark as activated
                    $stmt_update = $site_db->prepare("UPDATE pending_accounts SET activated = 1 WHERE token = ?");
                    if (!$stmt_update) {
                        $errors[] = "Database query error: " . $site_db->error;
                    } else {
                        $stmt_update->bind_param('s', $token);
                        if ($stmt_update->execute()) {
                            $success = "Your account has been activated! You will be redirected to the login page shortly.";
                            header("Refresh: 3; url=/Sahtout/pages/login.php"); // Redirect after 3 seconds
                        } else {
                            $errors[] = "Failed to update activation status: " . $site_db->error;
                        }
                        $stmt_update->close();
                    }
                } else {
                    $errors[] = "Failed to activate account: " . $auth_db->error;
                }
            }
        }
    }
}

// Include header
$header_file = "../includes/header.php";
if (file_exists($header_file)) {
    include $header_file;
} else {
    die("Error: Header file not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Activate your account to join our World of Warcraft server adventure!">
    <meta name="robots" content="index">
    <title>Activate Account</title>
    <style>
        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
        }

        body {
            background: url('/sahtout/img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }

        .register-container {
            max-width: 700px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 8px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        .register-title {
            color: #ffd700;
            font-size: 2.8rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 1.2rem;
        }

        .register-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .register-form p.error {
            color: #ff0000;
            font-size: 1.5rem;
            margin: 0.5rem 0 0;
        }

        .register-form p.success {
            color: #00ff00;
            font-size: 1.5rem;
            margin: 0.5rem 0 0;
        }

        .login-link-container {
            margin-top: 1.5rem;
            font-size: 1.1rem;
            font-family: 'UnifrakturCook', sans-serif;
        }

        .login-link-container a {
            color: #ffd700;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-link-container a:hover {
            color: #ffe600;
            text-decoration: underline;
        }

        footer {
            width: 100%;
            margin: 0;
            padding: 1rem 0;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            html, body {
                width: 100%;
                overflow-x: hidden;
            }

            header {
                padding: 1rem 0;
            }

            main {
                padding: 0;
                margin-top: 100px;
            }

            .register-container {
                max-width: 100%;
                padding: 1.5rem;
            }

            .register-title {
                font-size: 2.2rem;
            }

            .login-link-container {
                font-size: 1rem;
                margin-top: 1rem;
            }

            footer {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="register-container">
            <h1 class="register-title">Activate Your Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="register-form">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="register-form">
                    <p class="success"><?php echo htmlspecialchars($success); ?></p>
                    <p class="login-link-container"><a href="/sahtout/pages/login.php">Click here to login</a></p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php
    $footer_file = __DIR__ . "/../includes/footer.php";
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        die("Error: Header file not found.");
    }
    ?>
</body>
</html>