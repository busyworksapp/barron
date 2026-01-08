<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Barron Production Management System</title>
    <link rel="stylesheet" href="assets/css/industrial.css">
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
        <div class="container">
            <div class="row justify-center">
                <div class="col-12 col-6" style="max-width: 450px;">
                    <div class="card" style="box-shadow: var(--shadow-lg);">
                        <div class="card-header text-center" style="padding: var(--spacing-lg);">
                            <h1 style="margin: 0; color: var(--color-white); font-size: var(--font-size-h1);">BARRON</h1>
                            <p style="margin: var(--spacing-sm) 0 0 0; color: var(--color-light); font-size: var(--font-size-small); text-transform: uppercase; letter-spacing: 1px;">Production Management System</p>
                        </div>
                        <div class="card-body" style="padding: var(--spacing-xl);">
                            <div id="alertContainer"></div>
                            
                            <form id="loginForm">
                                <div class="form-group">
                                    <label class="form-label required" for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="username@barron" required autocomplete="username" autofocus>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
                                        LOGIN
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <small class="text-muted">Forgot your password? Contact your administrator.</small>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center">
                            <small class="text-muted">&copy; 2026 Barron (Pty) Ltd. All rights reserved.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>
