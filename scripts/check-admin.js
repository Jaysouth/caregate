#!/usr/bin/env node

/**
 * Check Admin User Script
 * 
 * This script checks if the admin user exists in the Node.js in-memory database
 * and provides instructions for creating it if needed.
 */

const http = require('http');

const API_HOST = 'localhost';
const API_PORT = 3000;
const ADMIN_EMAIL = 'admin@caregate.co.uk';

console.log('========================================');
console.log('Checking Admin User Status');
console.log('========================================\n');

// Check if server is running
function checkServer() {
    return new Promise((resolve, reject) => {
        const req = http.get(`http://${API_HOST}:${API_PORT}/api/users`, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    const users = JSON.parse(data);
                    resolve(users);
                } catch (error) {
                    reject(new Error('Failed to parse response'));
                }
            });
        });

        req.on('error', (error) => {
            reject(error);
        });

        req.setTimeout(5000, () => {
            req.abort();
            reject(new Error('Request timeout'));
        });
    });
}

async function main() {
    try {
        console.log('🔍 Checking if server is running...');
        const users = await checkServer();
        
        console.log('✅ Server is running\n');
        
        // Check for admin user
        const admin = users.find(u => u.email === ADMIN_EMAIL);
        
        if (admin) {
            console.log('========================================');
            console.log('✅ ADMIN USER EXISTS');
            console.log('========================================\n');
            console.log('Email:', admin.email);
            console.log('Name:', admin.name);
            console.log('Role:', admin.role);
            console.log('\nThe admin user is already created.');
            console.log('\nYou can login at: http://localhost:3000');
            console.log('Email: admin@caregate.co.uk');
            console.log('Password: [Use the password you set]\n');
            
            if (admin.mustChangePassword) {
                console.log('⚠️  Note: Password change will be required on first login\n');
            }
            
            console.log('========================================');
        } else {
            console.log('========================================');
            console.log('❌ ADMIN USER NOT FOUND');
            console.log('========================================\n');
            console.log('The admin user has not been created yet.');
            console.log('\nTo create the admin user, run:\n');
            console.log('  node scripts/create-admin-manual.js "CareGare2026!Admin"');
            console.log('\nOr for a random secure password:');
            console.log('  node scripts/create-admin.js\n');
            console.log('========================================');
        }
        
        // Show all users (for debugging)
        console.log('\n📊 Total users in database:', users.length);
        console.log('\nUser breakdown:');
        const roles = users.reduce((acc, u) => {
            acc[u.role] = (acc[u.role] || 0) + 1;
            return acc;
        }, {});
        Object.entries(roles).forEach(([role, count]) => {
            console.log(`  ${role}: ${count}`);
        });
        console.log();
        
    } catch (error) {
        console.error('========================================');
        console.error('❌ ERROR');
        console.error('========================================\n');
        
        if (error.code === 'ECONNREFUSED') {
            console.error('Server is not running!');
            console.error('\nTo start the server, run:');
            console.error('  npm start');
            console.error('\nThen run this script again.\n');
        } else {
            console.error('Error:', error.message);
            console.error('\nPlease ensure the server is running on port 3000.\n');
        }
        
        console.error('========================================');
        process.exit(1);
    }
}

main();
