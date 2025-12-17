<?php
/**
 * ============================================
 * Shared Login Form Partial
 * ============================================
 * 
 * Reusable login form component.
 * 
 * Variables:
 * - $loginError: Error message to display
 * - $formAction: Form action URL
 * - $title: Page title
 * - $subtitle: Page subtitle
 * - $logo: Logo image path
 * 
 * ============================================
 */

$title = $title ?? 'Admin Login';
$subtitle = $subtitle ?? 'Enter password to continue';
$logo = $logo ?? 'logoPM.png';
$formAction = $formAction ?? '';
$loginError = $loginError ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo) ?>">
    <link rel="stylesheet" href="public/assets/css/common.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Background effects */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 20s infinite ease-in-out;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            right: -150px;
            animation: float 25s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -50px) scale(1.1); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(40px, 50px) scale(1.05); }
        }
        
        .login-container {
            background: rgba(30, 30, 60, 0.9);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-xl), 0 0 100px rgba(99, 102, 241, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 30px rgba(99, 102, 241, 0.4));
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        
        .login-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            color: var(--error);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 3rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.1rem;
            transition: color var(--transition-normal);
        }
        
        .toggle-password:hover {
            color: var(--primary);
        }
        
        .remember-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .remember-group label {
            color: var(--text-muted);
            font-size: 0.9rem;
            cursor: pointer;
            margin: 0;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .login-footer a {
            color: var(--primary);
            transition: color var(--transition-normal);
        }
        
        .login-footer a:hover {
            color: var(--primary-light);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo">
            </div>
            <h1 class="login-title"><?= htmlspecialchars($title) ?></h1>
            <p class="login-subtitle"><?= htmlspecialchars($subtitle) ?></p>
        </div>
        
        <?php if ($loginError): ?>
        <div class="login-error">
            <span>✕</span>
            <?= htmlspecialchars($loginError) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
            <input type="hidden" name="admin_login" value="1">
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="Enter admin password"
                        required 
                        autofocus
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        👁
                    </button>
                </div>
            </div>
            
            <div class="remember-group">
                <input type="checkbox" id="remember_me" name="remember_me" value="1" class="form-checkbox">
                <label for="remember_me">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn btn-primary login-btn">
                🔐 Sign In
            </button>
        </form>
        
        <?php if (!empty($footerLinks)): ?>
        <div class="login-footer">
            <?php foreach ($footerLinks as $link): ?>
            <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['text']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const btn = document.querySelector('.toggle-password');
            
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        }
    </script>
</body>
</html>

