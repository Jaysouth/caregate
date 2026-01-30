<?php
/**
 * CareGate Frontend Display
 * Shortcode: [caregate_app]
 *
 * @package    CareGate
 * @subpackage CareGate/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
    <div id="app">
        <!-- Login/Register Screen -->
        <div id="auth-screen" class="screen active">
            <div class="container">
                <div class="logo">
                    <h1>🏥 CareGate</h1>
                    <p>On-Demand Care Staffing Platform</p>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="showTab('login', event)">Login</button>
                    <button class="tab" onclick="showTab('register', event)">Register</button>
                </div>

                <!-- Login Form -->
                <form id="login-form" class="auth-form" onsubmit="return false;">
                    <h2>Login</h2>
                    <input type="email" id="login-email" placeholder="Email" required>
                    <input type="password" id="login-password" placeholder="Password" required>
                    <button type="button" class="btn btn-primary" onclick="handleLogin()">Login</button>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="javascript:void(0);" id="forgot-password-link" style="color: #6366f1; text-decoration: none; font-size: 14px;" onclick="alert('Password reset feature coming soon!')">Forgot Password?</a>
                    </div>
                    <p class="demo-info" style="margin-top: 15px; text-align: center; padding: 10px; background-color: #f3f4f6; border-radius: 8px; font-size: 14px;">Demo: worker@test.com / password123</p>
                </form>

                <!-- Register Form -->
                <form id="register-form" class="auth-form" style="display: none;" onsubmit="return false;">
                    <h2>Register</h2>
                    <input type="text" id="reg-name" placeholder="Full Name" required>
                    <input type="email" id="reg-email" placeholder="Email" required>
                    <input type="password" id="reg-password" placeholder="Password" required>
                    
                    <label>I am a:</label>
                    <select id="reg-role" onchange="toggleRoleFields()">
                        <option value="worker">Healthcare Worker</option>
                        <option value="facility">Care Facility</option>
                    </select>

                    <div id="worker-fields">
                        <input type="text" id="reg-skills" placeholder="Skills (comma-separated)" value="Nursing, Elderly Care">
                    </div>

                    <div id="facility-fields" style="display: none;">
                        <input type="text" id="reg-facility-type" placeholder="Facility Type" value="Care Home">
                    </div>

                    <button type="button" class="btn btn-primary" onclick="handleRegister()">Register</button>
                </form>

                <div id="auth-error" class="error-message"></div>
            </div>
        </div>

        <!-- Worker Dashboard -->
        <div id="worker-screen" class="screen">
            <nav class="navbar">
                <h2>CareGate - Worker</h2>
                <button onclick="logout()" class="btn btn-small">Logout</button>
            </nav>
            
            <div class="container">
                <div class="welcome">
                    <h3 id="worker-name">Welcome</h3>
                    <div class="stats">
                        <div class="stat">
                            <span class="stat-value" id="worker-rating">5.0</span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="worker-shifts">0</span>
                            <span class="stat-label">Shifts</span>
                        </div>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="switchWorkerTab('matches', event)">Matched Shifts</button>
                    <button class="tab" onclick="switchWorkerTab('bookings', event)">My Bookings</button>
                    <button class="tab" onclick="switchWorkerTab('compliance', event)">Compliance</button>
                </div>

                <div id="worker-matches" class="tab-content">
                    <h3>Matched Shifts for You</h3>
                    <div id="matches-list" class="shifts-list"></div>
                </div>

                <div id="worker-bookings" class="tab-content" style="display: none;">
                    <h3>My Bookings</h3>
                    <div id="bookings-list" class="bookings-list"></div>
                </div>

                <div id="worker-compliance" class="tab-content" style="display: none;">
                    <h3>Compliance Status</h3>
                    <div id="compliance-info" class="compliance-info"></div>
                </div>
            </div>
        </div>

        <!-- Facility Dashboard -->
        <div id="facility-screen" class="screen">
            <nav class="navbar">
                <h2>CareGate - Facility</h2>
                <button onclick="logout()" class="btn btn-small">Logout</button>
            </nav>
            
            <div class="container">
                <div class="welcome">
                    <h3 id="facility-name">Welcome</h3>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="switchFacilityTab('shifts', event)">My Shifts</button>
                    <button class="tab" onclick="switchFacilityTab('create', event)">Create Shift</button>
                    <button class="tab" onclick="switchFacilityTab('bookings', event)">Applications</button>
                </div>

                <div id="facility-shifts" class="tab-content">
                    <h3>My Posted Shifts</h3>
                    <div id="facility-shifts-list" class="shifts-list"></div>
                </div>

                <div id="facility-create" class="tab-content" style="display: none;">
                    <h3>Create New Shift</h3>
                    <form id="create-shift-form">
                        <input type="text" id="shift-title" placeholder="Shift Title" required>
                        <textarea id="shift-description" placeholder="Description" rows="3"></textarea>
                        <label>Start Time</label>
                        <input type="datetime-local" id="shift-start" required>
                        <label>End Time</label>
                        <input type="datetime-local" id="shift-end" required>
                        <input type="text" id="shift-skills" placeholder="Required Skills (comma-separated)" value="Nursing, Elderly Care">
                        <input type="number" id="shift-rate" placeholder="Base Rate (£/hour)" step="0.01" value="20.00" required>
                        <label>Skill Level</label>
                        <select id="shift-skill-level">
                            <option value="basic">Basic</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Create Shift</button>
                    </form>
                </div>

                <div id="facility-bookings" class="tab-content" style="display: none;">
                    <h3>Shift Applications</h3>
                    <div id="facility-bookings-list" class="bookings-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Basic authentication handlers
    function handleLogin() {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        const errorDiv = document.getElementById('auth-error');
        
        if (!email || !password) {
            errorDiv.textContent = 'Please enter email and password';
            errorDiv.style.display = 'block';
            return;
        }
        
        errorDiv.style.display = 'none';
        
        // Show demo message for now
        alert('Authentication functionality requires implementation. Please refer to OPTION_A_IMPLEMENTATION_GUIDE.md for complete working code.\n\nFor demo purposes:\n- Email: worker@test.com\n- Password: password123');
        
        // Simulate login for demo
        if (email === 'worker@test.com' && password === 'password123') {
            showScreen('worker');
            document.getElementById('worker-name').textContent = 'Welcome, Sarah Johnson';
        }
    }
    
    function handleRegister() {
        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;
        const role = document.getElementById('reg-role').value;
        const errorDiv = document.getElementById('auth-error');
        
        if (!name || !email || !password) {
            errorDiv.textContent = 'Please fill in all required fields';
            errorDiv.style.display = 'block';
            return;
        }
        
        errorDiv.style.display = 'none';
        
        // Show demo message
        alert('Registration functionality requires implementation. Please refer to OPTION_A_IMPLEMENTATION_GUIDE.md for complete working code.');
    }
    
    function showTab(tab, event) {
        if (event) {
            event.preventDefault();
            const tabs = document.querySelectorAll('.tabs .tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        if (tab === 'login') {
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('register-form').style.display = 'none';
        } else {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        }
    }
    
    function toggleRoleFields() {
        const role = document.getElementById('reg-role').value;
        document.getElementById('worker-fields').style.display = role === 'worker' ? 'block' : 'none';
        document.getElementById('facility-fields').style.display = role === 'facility' ? 'block' : 'none';
    }
    
    function showScreen(screen) {
        const screens = document.querySelectorAll('.screen');
        screens.forEach(s => s.classList.remove('active'));
        
        if (screen === 'worker') {
            document.getElementById('worker-screen').classList.add('active');
        } else if (screen === 'facility') {
            document.getElementById('facility-screen').classList.add('active');
        } else {
            document.getElementById('auth-screen').classList.add('active');
        }
    }
    
    function logout() {
        showScreen('auth');
        document.getElementById('login-email').value = '';
        document.getElementById('login-password').value = '';
    }
    
    function switchWorkerTab(tab, event) {
        if (event) {
            const tabs = event.target.parentElement.querySelectorAll('.tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        document.getElementById('worker-matches').style.display = 'none';
        document.getElementById('worker-bookings').style.display = 'none';
        document.getElementById('worker-compliance').style.display = 'none';
        
        if (tab === 'matches') {
            document.getElementById('worker-matches').style.display = 'block';
            document.getElementById('matches-list').innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><div style="font-size:48px;">📋</div><p>No matched shifts available</p></div>';
        } else if (tab === 'bookings') {
            document.getElementById('worker-bookings').style.display = 'block';
            document.getElementById('bookings-list').innerHTML = '<div style="text-align:center;padding:40px;color:#999;">No bookings yet</div>';
        } else if (tab === 'compliance') {
            document.getElementById('worker-compliance').style.display = 'block';
            document.getElementById('compliance-info').innerHTML = '<div style="text-align:center;padding:40px;color:#999;">Compliance documents pending</div>';
        }
    }
    
    function switchFacilityTab(tab, event) {
        if (event) {
            const tabs = event.target.parentElement.querySelectorAll('.tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        document.getElementById('facility-shifts').style.display = 'none';
        document.getElementById('facility-create').style.display = 'none';
        document.getElementById('facility-bookings').style.display = 'none';
        
        if (tab === 'shifts') {
            document.getElementById('facility-shifts').style.display = 'block';
            document.getElementById('facility-shifts-list').innerHTML = '<div style="text-align:center;padding:40px;color:#999;">No shifts posted yet</div>';
        } else if (tab === 'create') {
            document.getElementById('facility-create').style.display = 'block';
        } else if (tab === 'bookings') {
            document.getElementById('facility-bookings').style.display = 'block';
            document.getElementById('facility-bookings-list').innerHTML = '<div style="text-align:center;padding:40px;color:#999;">No applications yet</div>';
        }
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        switchWorkerTab('matches');
        switchFacilityTab('shifts');
    });
    </script>

    <style>
    #app { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; }
    .screen { display: none; }
    .screen.active { display: block; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .logo { text-align: center; margin-bottom: 30px; }
    .logo h1 { font-size: 2.5em; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .logo p { color: #fff; margin-top: 10px; }
    .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .tabs .tab { flex: 1; padding: 15px; background: rgba(255,255,255,0.2); border: none; color: #fff; border-radius: 10px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
    .tabs .tab.active { background: #fff; color: #667eea; font-weight: 600; }
    .auth-form { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .auth-form h2 { color: #667eea; margin-top: 0; margin-bottom: 20px; }
    .auth-form input, .auth-form select, .auth-form textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
    .auth-form label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
    .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; }
    .btn-primary { background: #667eea; color: #fff; width: 100%; }
    .btn-primary:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
    .btn-small { padding: 8px 16px; font-size: 14px; }
    .error-message { color: #e53e3e; background: #fff5f5; padding: 12px; border-radius: 8px; margin-top: 15px; display: none; }
    .navbar { background: rgba(255,255,255,0.1); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .navbar h2 { color: #fff; margin: 0; }
    .welcome { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
    .welcome h3 { color: #667eea; margin-top: 0; }
    .stats { display: flex; gap: 40px; margin-top: 15px; }
    .stat { text-align: center; }
    .stat-value { display: block; font-size: 32px; font-weight: 700; color: #667eea; }
    .stat-label { display: block; color: #999; font-size: 14px; }
    .tab-content { background: #fff; padding: 20px; border-radius: 15px; }
    .tab-content h3 { color: #667eea; margin-top: 0; }
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 20px; }
    </style>
