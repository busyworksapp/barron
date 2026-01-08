/**
 * Barron Production Management System
 * Login Form Handler
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const alertContainer = document.getElementById('alertContainer');
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        handleLogin();
    });
    
    async function handleLogin() {
        // Clear previous alerts
        clearAlerts();
        
        // Get form data
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        // Validate inputs
        if (!username || !password) {
            showAlert('Please enter both username and password', 'danger');
            return;
        }
        
        // Disable button during request
        setLoading(true);
        
        try {
            const response = await fetch('api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('Login successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                showAlert(data.message || 'Login failed. Please try again.', 'danger');
                setLoading(false);
            }
            
        } catch (error) {
            console.error('Login error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
            setLoading(false);
        }
    }
    
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alertContainer.appendChild(alert);
        
        // Auto-dismiss success alerts
        if (type === 'success') {
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    }
    
    function clearAlerts() {
        alertContainer.innerHTML = '';
    }
    
    function setLoading(loading) {
        loginBtn.disabled = loading;
        if (loading) {
            loginBtn.textContent = 'LOGGING IN...';
        } else {
            loginBtn.textContent = 'LOGIN';
        }
    }
});
