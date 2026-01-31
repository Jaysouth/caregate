# Frontend Admin Role Implementation Guide

## Overview

This guide provides complete implementation details for creating a frontend admin role with the following requirements:

1. **Frontend Admin Role** - Cannot access wp-admin (WordPress backend)
2. **Admin Capabilities** - Control frontend dashboard settings, UK Invoice, Payroll, Timesheet Clock
3. **Forgot Password** - Add forgot password link on login
4. **Logout Button** - Add logout to all frontend dashboards
5. **Admin Credentials** - admin@caregate.co.uk / CareGare2026!Admin

---

## Table of Contents

1. [Custom WordPress Role Creation](#custom-wordpress-role-creation)
2. [Block wp-admin Access](#block-wp-admin-access)
3. [Forgot Password Feature](#forgot-password-feature)
4. [Logout Button Implementation](#logout-button-implementation)
5. [Admin Dashboard Features](#admin-dashboard-features)
6. [Complete Code Examples](#complete-code-examples)
7. [Security Considerations](#security-considerations)

---

## Custom WordPress Role Creation

### 1. Create the Role

Add to `class-caregate-activator.php`:

```php
public static function activate() {
    global $wpdb;
    
    // ... existing code ...
    
    // Create custom frontend admin role
    self::create_frontend_admin_role();
}

private static function create_frontend_admin_role() {
    // Remove role if it exists (for updates)
    remove_role('caregate_frontend_admin');
    
    // Create the role with specific capabilities
    add_role('caregate_frontend_admin', 'CareGate Frontend Admin', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => false,
        
        // Custom capabilities
        'caregate_manage_settings' => true,
        'caregate_manage_invoices' => true,
        'caregate_manage_payroll' => true,
        'caregate_manage_clock' => true,
        'caregate_view_dashboard' => true,
    ));
}
```

### 2. Create Admin User Programmatically

Add to WordPress (via PHP script or WP-CLI):

```php
// Create the frontend admin user
$user_data = array(
    'user_login' => 'admin_caregate',
    'user_email' => 'admin@caregate.co.uk',
    'user_pass' => 'CareGare2026!Admin',
    'display_name' => 'CareGate Frontend Administrator',
    'role' => 'caregate_frontend_admin',
);

$user_id = wp_insert_user($user_data);

if (!is_wp_error($user_id)) {
    echo "Frontend admin created successfully!";
} else {
    echo "Error: " . $user_id->get_error_message();
}
```

---

## Block wp-admin Access

### 1. Prevent Dashboard Access

Add to `class-caregate-public.php`:

```php
public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    
    // Block wp-admin for frontend admin
    add_action('admin_init', array($this, 'block_frontend_admin_from_wpadmin'));
}

public function block_frontend_admin_from_wpadmin() {
    $user = wp_get_current_user();
    
    // Check if user has frontend admin role
    if (in_array('caregate_frontend_admin', (array) $user->roles)) {
        // Allow AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Redirect to frontend dashboard
        $frontend_url = home_url('/caregate-dashboard/'); // Adjust URL as needed
        wp_redirect($frontend_url);
        exit;
    }
}
```

### 2. Hide Admin Bar

Add to same class:

```php
public function hide_admin_bar_for_frontend_admin() {
    $user = wp_get_current_user();
    
    if (in_array('caregate_frontend_admin', (array) $user->roles)) {
        show_admin_bar(false);
    }
}
```

Register in constructor:
```php
add_action('after_setup_theme', array($this, 'hide_admin_bar_for_frontend_admin'));
```

---

## Forgot Password Feature

### 1. Add Link to Login Form

Modify `public/index.html`:

```html
<div class="caregate-login-form">
    <h2 class="caregate-login-title">Login</h2>
    
    <form id="login-form">
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="login-email" placeholder="Email" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="login-password" placeholder="Password" required>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Login</button>
        
        <!-- FORGOT PASSWORD LINK -->
        <div class="forgot-password-link" style="text-align: center; margin-top: 15px;">
            <a href="#" id="forgot-password-link" style="color: #6366f1; text-decoration: none;">
                Forgot Password?
            </a>
        </div>
    </form>
</div>
```

### 2. Forgot Password Modal

Add modal HTML:

```html
<!-- Forgot Password Modal -->
<div id="forgot-password-modal" class="caregate-modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Reset Password</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
        
        <form id="forgot-password-form">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="reset-email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
        
        <div id="reset-message" style="margin-top: 15px; display: none;"></div>
    </div>
</div>
```

### 3. JavaScript Handler

Add to `public/js/app.js`:

```javascript
// Forgot Password functionality
function initForgotPassword() {
    const forgotLink = document.getElementById('forgot-password-link');
    const modal = document.getElementById('forgot-password-modal');
    const closeBtn = modal?.querySelector('.close');
    const form = document.getElementById('forgot-password-form');
    
    if (forgotLink && modal) {
        // Open modal
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'flex';
        });
        
        // Close modal
        closeBtn?.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        // Close on outside click
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Handle form submission
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('reset-email').value;
            const messageDiv = document.getElementById('reset-message');
            
            try {
                const response = await fetch('/api/auth/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    messageDiv.style.display = 'block';
                    messageDiv.style.color = 'green';
                    messageDiv.textContent = 'Password reset link sent! Check your email.';
                    form.reset();
                } else {
                    messageDiv.style.display = 'block';
                    messageDiv.style.color = 'red';
                    messageDiv.textContent = data.message || 'Error sending reset link';
                }
            } catch (error) {
                messageDiv.style.display = 'block';
                messageDiv.style.color = 'red';
                messageDiv.textContent = 'Error: Could not connect to server';
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initForgotPassword();
});
```

### 4. Backend Endpoint

Add to `server/routes/auth.js`:

```javascript
// Forgot password endpoint
router.post('/forgot-password', async (req, res) => {
    try {
        const { email } = req.body;
        
        // Find user
        const user = users.find(u => u.email === email);
        if (!user) {
            // Don't reveal if email exists
            return res.json({ message: 'If that email exists, a reset link has been sent.' });
        }
        
        // Generate reset token
        const resetToken = crypto.randomBytes(32).toString('hex');
        const resetExpiry = Date.now() + 3600000; // 1 hour
        
        // Store token (in production, use database)
        user.resetToken = resetToken;
        user.resetExpiry = resetExpiry;
        
        // Send email (implement email service)
        const resetUrl = `${req.protocol}://${req.get('host')}/reset-password?token=${resetToken}`;
        
        // TODO: Send email with resetUrl
        console.log(`Password reset link: ${resetUrl}`);
        
        res.json({ message: 'If that email exists, a reset link has been sent.' });
    } catch (error) {
        res.status(500).json({ message: 'Server error' });
    }
});

// Reset password endpoint
router.post('/reset-password', async (req, res) => {
    try {
        const { token, newPassword } = req.body;
        
        // Find user with valid token
        const user = users.find(u => 
            u.resetToken === token && 
            u.resetExpiry > Date.now()
        );
        
        if (!user) {
            return res.status(400).json({ message: 'Invalid or expired reset token' });
        }
        
        // Hash new password
        const hashedPassword = await bcrypt.hash(newPassword, 10);
        user.password = hashedPassword;
        
        // Clear reset token
        delete user.resetToken;
        delete user.resetExpiry;
        
        res.json({ message: 'Password reset successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Server error' });
    }
});
```

### 5. CSS Styling

Add to `public/css/styles.css`:

```css
/* Forgot Password Link */
.forgot-password-link {
    text-align: center;
    margin-top: 15px;
}

.forgot-password-link a {
    color: #6366f1;
    text-decoration: none;
    font-size: 14px;
}

.forgot-password-link a:hover {
    text-decoration: underline;
}

/* Modal Styles */
.caregate-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.caregate-modal .modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    position: relative;
}

.caregate-modal .close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.caregate-modal .close:hover {
    color: #000;
}
```

---

## Logout Button Implementation

### 1. Add to All Dashboards

The logout button should be in the header of each dashboard. Modify `public/index.html`:

```html
<!-- Worker Dashboard -->
<div id="worker-dashboard" class="dashboard" style="display: none;">
    <div class="dashboard-header">
        <h1>CareGate - Worker</h1>
        <button onclick="logout()" class="logout-btn">Logout</button>
    </div>
    <!-- ... rest of dashboard ... -->
</div>

<!-- Facility Dashboard -->
<div id="facility-dashboard" class="dashboard" style="display: none;">
    <div class="dashboard-header">
        <h1>CareGate - Facility</h1>
        <button onclick="logout()" class="logout-btn">Logout</button>
    </div>
    <!-- ... rest of dashboard ... -->
</div>

<!-- Admin Dashboard -->
<div id="admin-dashboard" class="dashboard" style="display: none;">
    <div class="dashboard-header">
        <h1>CareGate - Admin</h1>
        <button onclick="logout()" class="logout-btn">Logout</button>
    </div>
    <!-- ... rest of dashboard ... -->
</div>
```

### 2. Logout Function

Add to `public/js/app.js`:

```javascript
function logout() {
    // Clear local storage
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    
    // Clear any session data
    currentUser = null;
    
    // Show login screen
    showLogin();
    
    // Show notification
    showNotification('Logged out successfully', 'success');
    
    console.log('🚪 User logged out');
}
```

### 3. CSS for Logout Button

```css
/* Logout Button */
.logout-btn {
    background-color: #ef4444;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: #dc2626;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.dashboard-header h1 {
    margin: 0;
    font-size: 24px;
    color: #6366f1;
}
```

---

## Admin Dashboard Features

### 1. Create Admin Dashboard View

Add to `public/index.html`:

```html
<div id="admin-dashboard" class="dashboard" style="display: none;">
    <div class="dashboard-header">
        <h1>CareGate - Frontend Admin</h1>
        <button onclick="logout()" class="logout-btn">Logout</button>
    </div>
    
    <div class="admin-nav">
        <button onclick="showAdminTab('settings')" class="admin-nav-btn active">
            Dashboard Settings
        </button>
        <button onclick="showAdminTab('invoices')" class="admin-nav-btn">
            UK Invoices
        </button>
        <button onclick="showAdminTab('payroll')" class="admin-nav-btn">
            Payroll
        </button>
        <button onclick="showAdminTab('clock')" class="admin-nav-btn">
            Timesheet Clock
        </button>
    </div>
    
    <!-- Settings Tab -->
    <div id="admin-settings" class="admin-tab active">
        <h2>Frontend Dashboard Settings</h2>
        <div class="settings-section">
            <h3>Platform Configuration</h3>
            <form id="admin-settings-form">
                <div class="form-group">
                    <label>Platform Fee (%)</label>
                    <input type="number" id="platform-fee" value="15" min="0" max="100">
                </div>
                <div class="form-group">
                    <label>Max Matching Distance (km)</label>
                    <input type="number" id="max-distance" value="50">
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
    
    <!-- Invoices Tab -->
    <div id="admin-invoices" class="admin-tab">
        <h2>UK Invoices Management</h2>
        <div id="invoices-list"></div>
    </div>
    
    <!-- Payroll Tab -->
    <div id="admin-payroll" class="admin-tab">
        <h2>Payroll Management</h2>
        <div id="payroll-list"></div>
    </div>
    
    <!-- Clock Tab -->
    <div id="admin-clock" class="admin-tab">
        <h2>Timesheet Clock Monitoring</h2>
        <div id="clock-records"></div>
    </div>
</div>
```

### 2. Admin Tab Navigation

Add to `public/js/app.js`:

```javascript
function showAdminTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.style.display = 'none';
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.admin-nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(`admin-${tabName}`);
    const selectedBtn = event.target;
    
    if (selectedTab) {
        selectedTab.classList.add('active');
        selectedTab.style.display = 'block';
    }
    
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }
    
    // Load data for the tab
    loadAdminTabData(tabName);
}

async function loadAdminTabData(tabName) {
    switch(tabName) {
        case 'invoices':
            await loadInvoices();
            break;
        case 'payroll':
            await loadPayroll();
            break;
        case 'clock':
            await loadClockRecords();
            break;
    }
}
```

### 3. Permission Checks

Add to each admin function:

```javascript
function checkAdminPermission() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || user.role !== 'admin') {
        showNotification('Access denied', 'error');
        showLogin();
        return false;
    }
    return true;
}

async function loadInvoices() {
    if (!checkAdminPermission()) return;
    
    // Load invoices...
}
```

---

## Complete Implementation Steps

### For WordPress Plugin:

1. **Update `class-caregate-activator.php`**
   - Add `create_frontend_admin_role()` method
   - Call it in `activate()` method

2. **Update `class-caregate-public.php`**
   - Add `block_frontend_admin_from_wpadmin()` method
   - Add `hide_admin_bar_for_frontend_admin()` method
   - Register hooks in constructor

3. **Update `class-caregate-api.php`**
   - Add `/auth/forgot-password` endpoint
   - Add `/auth/reset-password` endpoint

4. **Update frontend template**
   - Add forgot password link
   - Add forgot password modal
   - Add logout buttons to all dashboards

5. **Create admin user**
   - Via WordPress admin panel
   - Or via WP-CLI
   - With role `caregate_frontend_admin`

### For Node.js Version:

1. **Update `server/routes/auth.js`**
   - Add forgot password endpoint
   - Add reset password endpoint
   - Add email sending logic

2. **Update `public/index.html`**
   - Add forgot password link
   - Add forgot password modal
   - Add logout buttons
   - Add admin dashboard HTML

3. **Update `public/js/app.js`**
   - Add forgot password handler
   - Add logout function
   - Add admin tab navigation
   - Add admin permission checks

4. **Update `public/css/styles.css`**
   - Add modal styles
   - Add logout button styles
   - Add admin dashboard styles

---

## Security Considerations

### 1. Token Security
- Use crypto.randomBytes for tokens
- Set expiry time (1 hour recommended)
- Store tokens securely
- Clear tokens after use

### 2. Password Reset
- Don't reveal if email exists
- Rate limit reset requests
- Use HTTPS in production
- Validate token before reset

### 3. Role Permissions
- Check permissions on API endpoints
- Verify user role on each request
- Block unauthorized access
- Log permission violations

### 4. Session Management
- Use secure session tokens
- Implement token expiry
- Clear tokens on logout
- Validate tokens on each request

---

## Testing Checklist

- [ ] Frontend admin role created
- [ ] wp-admin blocked for frontend admin
- [ ] Admin bar hidden for frontend admin
- [ ] Forgot password link appears on login
- [ ] Forgot password modal works
- [ ] Password reset email sent
- [ ] Password reset successful
- [ ] Logout button on worker dashboard
- [ ] Logout button on facility dashboard
- [ ] Logout button on admin dashboard
- [ ] Logout clears session
- [ ] Admin dashboard displays
- [ ] Admin tabs navigation works
- [ ] Admin permissions enforced
- [ ] Settings management works
- [ ] Invoice access works
- [ ] Payroll access works
- [ ] Clock access works

---

## Support

For implementation assistance, refer to:
- WORDPRESS_ADMIN_SETUP.md
- ADMIN_SETUP.md
- PLUGIN_INSTALLATION.md

---

**Status**: Complete Implementation Guide ✅
**Ready for Development**: Yes 🚀
