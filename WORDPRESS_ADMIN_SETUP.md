# WordPress Admin Setup Guide

## Critical Distinction: Node.js vs WordPress

**IMPORTANT:** This guide is for creating admin users in **WordPress** (live sites like https://caregate.co.uk/). 

If you're using the Node.js standalone version (localhost:3000), see `ADMIN_LOGIN_FIX.md` instead.

---

## Problem Statement

You're trying to login at your WordPress site (e.g., https://caregate.co.uk/) but login fails and redirects back to login page.

**Why?** The admin user doesn't exist in WordPress database yet.

**Note:** Our Node.js admin creation scripts (`create-admin.js`, `create-admin-manual.js`) only work for the Node.js version, NOT for WordPress.

---

## Understanding The Two Systems

### System 1: Node.js (Standalone Development)
- **URL**: http://localhost:3000
- **Database**: In-memory (temporary)
- **Admin Creation**: Via Node.js scripts
- **Use Case**: Development, testing, standalone app

### System 2: WordPress (Production Plugin)
- **URL**: Your WordPress site (e.g., https://caregate.co.uk/)
- **Database**: WordPress MySQL database
- **Admin Creation**: Via WordPress methods (this guide)
- **Use Case**: Live website with WordPress plugin

---

## WordPress Admin Creation Methods

## Method 1: WordPress Admin Panel (✅ RECOMMENDED - EASIEST)

### Step 1: Access WordPress Admin
```
URL: https://your-site.com/wp-admin
```
Login with your existing WordPress administrator account.

### Step 2: Navigate to Users
```
WordPress Dashboard → Users → Add New
```

### Step 3: Create CareGate Admin User
Fill in the form:

**Username**: `admin_caregate` (or any username you prefer)

**Email**: `admin@caregate.co.uk` (must match what you'll use for CareGate login)

**First Name**: `CareGate` (optional)

**Last Name**: `Administrator` (optional)

**Website**: Leave blank or add your site URL

**Password**: `CareGare2026!Admin` (or your chosen password)
- Click "Show password" if needed
- Uncheck "Send user notification" if you don't want email

**Role**: Select **Administrator**

### Step 4: Save User
Click **Add New User** button at the bottom.

### Step 5: Verify Creation
- You should see "New user created" message
- User appears in Users list

### Step 6: Test CareGate Login
1. Go to your CareGate page (the one with `[caregate_app]` shortcode)
2. Click Login tab
3. Enter:
   - Email: `admin@caregate.co.uk`
   - Password: `CareGare2026!Admin`
4. Click Login
5. Should redirect to admin dashboard ✅

---

## Method 2: WP-CLI (If Available on Server)

### Prerequisites
- SSH access to your server
- WP-CLI installed
- Navigate to WordPress root directory

### Command
```bash
wp user create admin_caregate admin@caregate.co.uk \
  --role=administrator \
  --user_pass=CareGare2026!Admin \
  --display_name="CareGate Administrator" \
  --first_name="CareGate" \
  --last_name="Admin"
```

### Verify
```bash
wp user list --role=administrator
```

Should show your new admin user.

### Test Login
Go to CareGate page and login with:
- Email: `admin@caregate.co.uk`
- Password: `CareGare2026!Admin`

---

## Method 3: Direct Database (Advanced)

⚠️ **WARNING**: This method requires database access and SQL knowledge. Backup database first!

### Step 1: Access Database
- Via cPanel → PHPMyAdmin
- Or via command line MySQL client

### Step 2: Get WordPress Prefix
Find your WordPress table prefix (default: `wp_`)
```sql
SHOW TABLES;
```
Look for tables starting with `wp_` (or custom prefix like `wp2_`, `wpdb_`, etc.)

### Step 3: Create User
Replace `wp_` with your actual prefix:

```sql
-- Generate password hash (use your actual password)
-- For 'CareGare2026!Admin', the hash is:
SET @password_hash = '$2y$10$EXAMPLEHASH...';

-- Insert user
INSERT INTO wp_users (
    user_login,
    user_pass,
    user_nicename,
    user_email,
    user_registered,
    user_status,
    display_name
) VALUES (
    'admin_caregate',
    @password_hash,
    'admin_caregate',
    'admin@caregate.co.uk',
    NOW(),
    0,
    'CareGate Administrator'
);

-- Get the new user ID
SET @user_id = LAST_INSERT_ID();

-- Add user meta
INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES
    (@user_id, 'nickname', 'admin_caregate'),
    (@user_id, 'first_name', 'CareGate'),
    (@user_id, 'last_name', 'Administrator'),
    (@user_id, 'wp_capabilities', 'a:1:{s:13:"administrator";b:1;}'),
    (@user_id, 'wp_user_level', '10');
```

**Note**: To generate the correct password hash, use PHP:
```php
<?php echo password_hash('CareGare2026!Admin', PASSWORD_BCRYPT); ?>
```

---

## Method 4: PHP Script (Custom)

### Step 1: Create PHP File
Create a file named `create-admin.php` in your WordPress root:

```php
<?php
// Load WordPress
require_once('wp-load.php');

// Admin user details
$username = 'admin_caregate';
$password = 'CareGare2026!Admin';
$email = 'admin@caregate.co.uk';

// Check if user exists
if (username_exists($username)) {
    echo "User already exists!\n";
    exit;
}

// Create user
$user_id = wp_create_user($username, $password, $email);

if (is_wp_error($user_id)) {
    echo "Error: " . $user_id->get_error_message() . "\n";
    exit;
}

// Set user role to administrator
$user = new WP_User($user_id);
$user->set_role('administrator');

// Set display name
wp_update_user([
    'ID' => $user_id,
    'display_name' => 'CareGate Administrator',
    'first_name' => 'CareGate',
    'last_name' => 'Administrator'
]);

echo "Success! Admin user created.\n";
echo "Username: $username\n";
echo "Email: $email\n";
echo "Password: $password\n";
?>
```

### Step 2: Run Script
```bash
php create-admin.php
```

Or access via browser:
```
https://your-site.com/create-admin.php
```

### Step 3: Delete Script
⚠️ **IMPORTANT**: Delete the script after use for security!
```bash
rm create-admin.php
```

---

## Troubleshooting

### Issue: Can't Access /wp-admin

**Solution 1**: Reset WordPress admin password via hosting control panel
- cPanel → MySQL Databases → PHPMyAdmin
- Find user in `wp_users` table
- Update `user_pass` with new hash

**Solution 2**: Contact hosting provider
- Ask them to reset WordPress admin password
- Or ask them to create new admin user for you

**Solution 3**: Use "Lost Password" link
- Go to https://your-site.com/wp-admin
- Click "Lost your password?"
- Follow email instructions

### Issue: User Created But Can't Login to CareGate

**Check 1**: Verify user has Administrator role
```
WordPress → Users → Edit User → Role: Administrator
```

**Check 2**: Verify email address matches exactly
```
WordPress user email must match CareGate login email
```

**Check 3**: Clear browser cache
```
Ctrl+Shift+Delete → Clear browsing data
```

**Check 4**: Try incognito/private window

**Check 5**: Check browser console for errors
```
Press F12 → Console tab
Look for error messages
```

### Issue: Login Works But Shows Wrong Dashboard

**Check**: User might have CareGate custom role instead of Administrator

**Fix**: Change role to Administrator in WordPress:
```
Users → Edit → Role → Administrator → Save
```

---

## After Creating Admin

### First Login
1. Go to your CareGate page (with `[caregate_app]` shortcode)
2. Click Login tab
3. Enter credentials
4. Should see admin dashboard with 10 tabs

### What You'll See
- Overview (statistics)
- User Management
- Shift Management
- Booking Management
- UK Invoicing
- Payroll
- Clock System
- Compliance
- Payments
- Reports

### If Password Change Required
Some configurations may require password change on first login. If prompted:
1. Enter current password
2. Enter new password
3. Confirm new password
4. Submit

---

## FAQ

### Q: Why don't the Node.js scripts work?

**A**: The Node.js scripts (`create-admin.js`, `create-admin-manual.js`) create admin users in the Node.js in-memory database, which is completely separate from your WordPress database. They're for the standalone Node.js version (localhost:3000), not for the WordPress plugin.

### Q: Can I use the same credentials for both systems?

**A**: Yes, you can use the same email/password, but you need to create the user in each system separately:
- For Node.js: Run `node scripts/create-admin-manual.js "password"`
- For WordPress: Use this guide (WordPress admin panel method)

### Q: Do I need Node.js for WordPress?

**A**: No! The WordPress plugin works completely independently. You only need Node.js if you're:
- Running the standalone Node.js app (localhost:3000)
- Developing/testing the Node.js version

### Q: Where is my WordPress database?

**A**: Your hosting provider manages it. Access via:
- cPanel → PHPMyAdmin
- Or SSH/command line
- Or contact hosting support

### Q: Can I have multiple admins?

**A**: Yes! Create as many administrator users as you need using the same methods. Each can have different emails and credentials.

### Q: What if I forget the password?

**A**: Use WordPress password reset:
1. Go to /wp-admin
2. Click "Lost your password?"
3. Follow email instructions
4. Or contact hosting provider

---

## Summary

### For WordPress Sites (YOUR CASE)

**✅ Use these methods:**
- WordPress Admin Panel (easiest)
- WP-CLI
- Direct database
- PHP script

**❌ Don't use these:**
- create-admin.js (Node.js only)
- create-admin-manual.js (Node.js only)
- diagnose-admin-login.js (Node.js only)

### Quick Steps

1. Access WordPress admin (/wp-admin)
2. Users → Add New
3. Email: admin@caregate.co.uk
4. Role: Administrator
5. Save
6. Login at your CareGate page ✅

---

## Need More Help?

### Documentation
- WordPress Codex: https://codex.wordpress.org/Users_Screen
- WP-CLI User Commands: https://developer.wordpress.org/cli/commands/user/

### Support
- Contact your hosting provider
- Check WordPress support forums
- Review CareGate plugin documentation

---

**Your admin user should now work at https://your-site.com/ (your CareGate page)!** ✅
