#!/usr/bin/env node

/**
 * CareGate Admin Login Diagnostic Tool
 * 
 * This script performs comprehensive testing of the admin login flow
 * to identify exactly why login might be failing.
 * 
 * Usage: node scripts/diagnose-admin-login.js
 */

const http = require('http');

// Configuration
const SERVER_URL = 'http://localhost:3000';
const ADMIN_EMAIL = 'admin@caregate.co.uk';
const ADMIN_PASSWORD = 'CareGare2026!Admin';

// Colors for output
const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m'
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function apiCall(path, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, SERVER_URL);
        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                try {
                    const jsonData = JSON.parse(body);
                    if (res.statusCode >= 200 && res.statusCode < 300) {
                        resolve(jsonData);
                    } else {
                        reject(new Error(jsonData.error || `Status: ${res.statusCode}`));
                    }
                } catch (e) {
                    reject(new Error(`Parse error: ${e.message}`));
                }
            });
        });

        req.on('error', (error) => reject(error));

        if (data) {
            req.write(JSON.stringify(data));
        }

        req.end();
    });
}

async function checkServerRunning() {
    log('\n========================================', 'cyan');
    log('Step 1: Checking if server is running...', 'cyan');
    log('========================================', 'cyan');

    try {
        await apiCall('/api/users');
        log('✅ Server is running at ' + SERVER_URL, 'green');
        return true;
    } catch (error) {
        log('❌ Server is not running!', 'red');
        log('\n🔧 FIX:', 'yellow');
        log('Start the Node.js server first:', 'yellow');
        log('  npm start\n', 'cyan');
        log('Then run this diagnostic again.', 'yellow');
        return false;
    }
}

async function checkAdminExists() {
    log('\n========================================', 'cyan');
    log('Step 2: Checking if admin user exists...', 'cyan');
    log('========================================', 'cyan');

    try {
        const users = await apiCall('/api/users');
        
        const workers = users.filter(u => u.role === 'worker');
        const facilities = users.filter(u => u.role === 'facility');
        const admins = users.filter(u => u.role === 'admin');
        
        log(`📊 Total users in database: ${users.length}`, 'blue');
        log(`   - Workers: ${workers.length}`);
        log(`   - Facilities: ${facilities.length}`);
        log(`   - Admins: ${admins.length}`);

        const admin = users.find(u => u.email === ADMIN_EMAIL);
        
        if (!admin) {
            log('\n❌ PROBLEM FOUND: Admin user does not exist!', 'red');
            log('\n========================================', 'yellow');
            log('🔧 HOW TO FIX', 'yellow');
            log('========================================', 'yellow');
            log('\nThe admin user has not been created yet.\n', 'yellow');
            log('Run this command to create it:', 'yellow');
            log(`  node scripts/create-admin-manual.js "${ADMIN_PASSWORD}"`, 'cyan');
            log('\nThen try logging in at:', 'yellow');
            log(`  ${SERVER_URL}`, 'cyan');
            return false;
        }

        log('\n✅ Admin user found!', 'green');
        log(`   Email: ${admin.email}`);
        log(`   Name: ${admin.name}`);
        log(`   Role: ${admin.role}`);
        
        return true;
    } catch (error) {
        log(`❌ Error checking users: ${error.message}`, 'red');
        return false;
    }
}

async function testLogin() {
    log('\n========================================', 'cyan');
    log('Step 3: Testing login with credentials...', 'cyan');
    log('========================================', 'cyan');

    try {
        const response = await apiCall('/api/auth/login', 'POST', {
            email: ADMIN_EMAIL,
            password: ADMIN_PASSWORD
        });

        log('✅ Login successful!', 'green');
        log(`   Token: ${response.token.substring(0, 50)}...`);
        
        return response;
    } catch (error) {
        log('❌ Login failed: ' + error.message, 'red');
        log('\n🔧 FIX:', 'yellow');
        log('The admin user exists but the password doesn\'t match.\n', 'yellow');
        log('Options:', 'yellow');
        log('1. Use the correct password', 'yellow');
        log('2. Reset password with:', 'yellow');
        log(`   node scripts/create-admin-manual.js "NewPassword123!"`, 'cyan');
        return null;
    }
}

async function checkRole(loginResponse) {
    log('\n========================================', 'cyan');
    log('Step 4: Checking admin role and routing...', 'cyan');
    log('========================================', 'cyan');

    if (!loginResponse || !loginResponse.user) {
        log('❌ Cannot check role - login failed', 'red');
        return false;
    }

    const user = loginResponse.user;
    
    if (user.role !== 'admin') {
        log(`❌ User role is incorrect: ${user.role}`, 'red');
        log('   Expected: admin', 'yellow');
        return false;
    }

    log(`✅ User role is correct: ${user.role}`, 'green');
    
    if (loginResponse.mustChangePassword) {
        log('⚠️  Password change required: true', 'yellow');
        log('   (User will be prompted to change password on first login)', 'yellow');
    } else {
        log('✅ Password change required: false', 'green');
    }

    return true;
}

function showSummary(success) {
    log('\n========================================', 'cyan');
    if (success) {
        log('🎉 SUCCESS!', 'green');
    } else {
        log('❌ DIAGNOSTIC COMPLETE', 'red');
    }
    log('========================================', 'cyan');

    if (success) {
        log('\nEverything is working correctly!\n', 'green');
        log('The admin can login at:', 'green');
        log(`  ${SERVER_URL}`, 'cyan');
        log('\nUse these credentials:', 'green');
        log(`  Email: ${ADMIN_EMAIL}`, 'cyan');
        log(`  Password: ${ADMIN_PASSWORD}`, 'cyan');
        log('\nIf the browser still returns to login:', 'yellow');
        log('1. Clear browser cache (Ctrl+Shift+Delete)', 'yellow');
        log('2. Try incognito/private window', 'yellow');
        log('3. Check browser console (F12) for errors', 'yellow');
        log('4. Look for console logs with emoji (🔐 ✅ 👤 🎯)', 'yellow');
    } else {
        log('\nFollow the fix instructions above to resolve the issue.', 'yellow');
        log('\nAfter applying the fix, run this diagnostic again:', 'yellow');
        log('  node scripts/diagnose-admin-login.js', 'cyan');
    }
}

async function main() {
    log('\n🔍 CareGate Admin Login Diagnostic Tool', 'cyan');
    log('========================================', 'cyan');
    log('\nTesting admin login for:', 'blue');
    log(`  Email: ${ADMIN_EMAIL}`, 'cyan');
    log(`  Password: ${ADMIN_PASSWORD}`, 'cyan');

    // Step 1: Check server
    const serverRunning = await checkServerRunning();
    if (!serverRunning) {
        showSummary(false);
        process.exit(1);
    }

    // Step 2: Check admin exists
    const adminExists = await checkAdminExists();
    if (!adminExists) {
        showSummary(false);
        process.exit(1);
    }

    // Step 3: Test login
    const loginResponse = await testLogin();
    if (!loginResponse) {
        showSummary(false);
        process.exit(1);
    }

    // Step 4: Check role
    const roleCorrect = await checkRole(loginResponse);
    if (!roleCorrect) {
        showSummary(false);
        process.exit(1);
    }

    // All tests passed!
    showSummary(true);
    process.exit(0);
}

// Run diagnostic
main().catch(error => {
    log(`\n❌ Unexpected error: ${error.message}`, 'red');
    log('\nPlease check:', 'yellow');
    log('1. Server is running: npm start', 'yellow');
    log('2. Port 3000 is not blocked', 'yellow');
    log('3. No firewall issues', 'yellow');
    process.exit(1);
});
