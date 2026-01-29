# Admin Login Troubleshooting Guide

## Problem: Admin Login Returns to Login Page

If you're experiencing issues where admin login keeps returning to the login page, follow this comprehensive troubleshooting guide.

---

## Quick Diagnosis

Run this command to check admin user status:

```bash
node scripts/check-admin.js
```

This will tell you:
- ✅ If the server is running
- ✅ If the admin user exists
- ✅ How many users are in the database
- ✅ Next steps to take

---

## Common Issues & Solutions

### Issue 1: Admin User Doesn't Exist ❌

**Symptoms:**
- Login returns to login page
- No error message
- Browser console shows "Invalid credentials"

**Solution:**

1. Make sure server is running:
```bash
npm start
```

2. Create admin user:
```bash
node scripts/create-admin-manual.js "CareGare2026!Admin"
```

3. Try logging in again with:
   - Email: admin@caregate.co.uk
   - Password: CareGare2026!Admin

---

### Issue 2: Server Not Running ❌

**Symptoms:**
- Cannot connect to http://localhost:3000
- Browser shows "Cannot connect"
- API requests fail

**Solution:**

1. Start the server:
```bash
cd /path/to/caregate
npm start
```

2. Wait for message: "Server running on port 3000"

3. Access login at: http://localhost:3000

---

### Issue 3: Wrong Login Location ❌

**Important:** There are TWO different login pages!

**Node.js Login (Correct):**
- URL: http://localhost:3000
- This is where admin@caregate.co.uk works
- Clean interface without WordPress

**WordPress Login (Different):**
- URL: http://localhost/wp-admin
- This is for WordPress admin panel
- Uses different credentials

**Solution:** Make sure you're at http://localhost:3000

---

### Issue 4: Browser Cache ❌

**Symptoms:**
- Demo text still visible on login page
- Old version of the site loading
- Changes not appearing

**Solution:**

1. **Hard Refresh:**
   - Windows/Linux: Ctrl + Shift + R
   - Mac: Cmd + Shift + R

2. **Clear Cache:**
   - Chrome: Ctrl + Shift + Delete
   - Select "Cached images and files"
   - Click "Clear data"

3. **Or use Incognito/Private mode**

---

### Issue 5: JavaScript Errors ❌

**Symptoms:**
- Login button doesn't work
- Page doesn't respond to clicks
- Nothing happens when you submit

**Solution:**

1. Open browser console:
   - Press F12 or Right-click → Inspect
   - Click "Console" tab

2. Look for errors (red text)

3. Common errors and fixes:

**Error: "Cannot read property 'addEventListener' of null"**
- The HTML elements are missing
- Clear cache and refresh

**Error: "NetworkError" or "Failed to fetch"**
- Server not running
- Run: `npm start`

**Error: "Invalid credentials"**
- Admin user doesn't exist
- Run: `node scripts/create-admin-manual.js "CareGare2026!Admin"`

---

## Step-by-Step Verification

### Step 1: Verify Server is Running

```bash
# Terminal 1 - Start server
npm start

# You should see:
# > caregate@1.0.0 start
# > node server/server.js
# Server running on port 3000
```

### Step 2: Check Admin User Exists

```bash
# Terminal 2 - Check admin
node scripts/check-admin.js

# You should see either:
# ✅ ADMIN USER EXISTS
# or
# ❌ ADMIN USER NOT FOUND (with instructions)
```

### Step 3: Create Admin (If Needed)

```bash
# If admin doesn't exist:
node scripts/create-admin-manual.js "CareGare2026!Admin"

# You should see:
# ✅ Admin user created successfully!
```

### Step 4: Test Login

1. Open browser to: http://localhost:3000

2. Enter credentials:
   - Email: admin@caregate.co.uk
   - Password: CareGare2026!Admin

3. Click "Login"

4. **Expected Result:**
   - Password change modal appears (first login)
   - Or admin dashboard loads

5. **If it fails:**
   - Open browser console (F12)
   - Look for error messages
   - Check what's logged

---

## Debug Logging

The login process now includes detailed console logging:

1. Open browser console (F12)

2. Try to login

3. Look for these messages:

```
🔐 Attempting login with email: admin@caregate.co.uk
✅ Login successful: {user: {...}, token: "..."}
👤 User role: admin
🎯 Routing to admin dashboard
```

4. If you see `❌ Login failed`, check the error message

---

## Advanced Troubleshooting

### Check API Directly

Test the login API endpoint:

```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@caregate.co.uk","password":"CareGare2026!Admin"}'
```

**Expected Response:**
```json
{
  "user": {
    "id": "...",
    "email": "admin@caregate.co.uk",
    "name": "CareGate Administrator",
    "role": "admin"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "mustChangePassword": true
}
```

**Error Response:**
```json
{
  "error": "Invalid credentials"
}
```

### Check Database Users

```bash
curl http://localhost:3000/api/users | json_pp
```

This shows all users in the database. Look for one with:
- email: "admin@caregate.co.uk"
- role: "admin"

---

## Quick Reference Commands

### Essential Commands

```bash
# Check status
node scripts/check-admin.js

# Start server
npm start

# Create admin (specific password)
node scripts/create-admin-manual.js "CareGare2026!Admin"

# Create admin (random password)
node scripts/create-admin.js
```

### URLs

- **Node.js Login:** http://localhost:3000
- **Admin Dashboard:** http://localhost:3000 (after login)
- **WordPress Admin:** http://localhost/wp-admin (different!)

### Credentials

- **Email:** admin@caregate.co.uk
- **Password:** CareGare2026!Admin (or as set by you)

---

## Still Not Working?

If you've tried everything above and it still doesn't work:

### 1. Fresh Start

```bash
# Stop server (Ctrl+C)

# Install dependencies
npm install

# Start server
npm start

# Create admin
node scripts/create-admin-manual.js "CareGare2026!Admin"

# Open fresh browser (Incognito)
# Go to http://localhost:3000
# Login
```

### 2. Check Node.js Version

```bash
node --version
# Should be v14+ or higher
```

### 3. Check Port 3000

```bash
# Make sure nothing else is using port 3000
lsof -i :3000

# If something is there, kill it or use different port
```

### 4. Review Logs

Look at the server console output when you try to login. You should see:
```
POST /api/auth/login 200 OK
```

If you see 400 or 500 errors, there's an API issue.

---

## Success Indicators

You'll know it's working when:

✅ Server starts without errors
✅ `check-admin.js` shows admin exists
✅ Login page loads at localhost:3000
✅ Console shows successful login messages
✅ Password change modal appears (first time)
✅ Admin dashboard loads with 10 tabs

---

## Need More Help?

1. Check `ADMIN_LOGIN_FIX.md` for admin creation details
2. Check `DATABASE_FIX_SUMMARY.md` for WordPress plugin issues
3. Review `ADMIN_SETUP.md` for configuration
4. Open browser console and share any error messages

---

## Summary Flowchart

```
Start
  ↓
Is server running? (npm start)
  ↓ No → Start server
  ↓ Yes
  ↓
Does admin exist? (node scripts/check-admin.js)
  ↓ No → Create admin (node scripts/create-admin-manual.js "password")
  ↓ Yes
  ↓
At correct URL? (http://localhost:3000)
  ↓ No → Navigate to localhost:3000
  ↓ Yes
  ↓
Cache cleared? (Ctrl+Shift+R)
  ↓ No → Hard refresh
  ↓ Yes
  ↓
Login with:
  - admin@caregate.co.uk
  - CareGare2026!Admin
  ↓
Check console for logs
  ↓
Success! 🎉
```

---

**Last Updated:** January 29, 2026
**Version:** 1.0.3
