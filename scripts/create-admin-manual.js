#!/usr/bin/env node

/**
 * Script to create admin user with specific password
 * Usage: node scripts/create-admin-manual.js <password>
 * Example: node scripts/create-admin-manual.js "CareGare2026!Admin"
 */

const http = require('http');

const ADMIN_EMAIL = 'admin@caregate.co.uk';
const ADMIN_NAME = 'CareGate Administrator';
const API_BASE = 'http://localhost:3000';

// Get password from command line or use provided one
const password = process.argv[2] || 'CareGare2026!Admin';

function makeRequest(path, data) {
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify(data);
    
    const options = {
      hostname: 'localhost',
      port: 3000,
      path: path,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    };
    
    const req = http.request(options, (res) => {
      let body = '';
      
      res.on('data', (chunk) => {
        body += chunk;
      });
      
      res.on('end', () => {
        try {
          const response = JSON.parse(body);
          if (res.statusCode >= 400) {
            reject(new Error(response.error || 'Request failed'));
          } else {
            resolve(response);
          }
        } catch (e) {
          reject(new Error('Invalid JSON response'));
        }
      });
    });
    
    req.on('error', (e) => {
      reject(e);
    });
    
    req.write(postData);
    req.end();
  });
}

async function createAdmin() {
  try {
    console.log('\n========================================');
    console.log('Creating Admin User');
    console.log('========================================\n');
    
    console.log('Email:', ADMIN_EMAIL);
    console.log('Name:', ADMIN_NAME);
    console.log('Password:', password);
    console.log('\n');
    
    // Create admin user via API
    const response = await makeRequest('/api/auth/create-admin', {
      email: ADMIN_EMAIL,
      password: password,
      name: ADMIN_NAME
    });
    
    if (response && response.user) {
      console.log('✅ Admin user created successfully!\n');
      console.log('========================================');
      console.log('ADMIN LOGIN CREDENTIALS');
      console.log('========================================');
      console.log(`Email: ${ADMIN_EMAIL}`);
      console.log(`Password: ${password}`);
      console.log('\n⚠️  Password change required on first login');
      console.log('========================================\n');
      
      console.log('✅ You can now login at http://localhost:3000\n');
    }
  } catch (error) {
    console.error('\n❌ Error:', error.message);
    
    if (error.message === 'User already exists') {
      console.log('\nℹ️  Admin user already exists!');
      console.log('   If the password is wrong, you need to:');
      console.log('   1. Delete the existing admin user, or');
      console.log('   2. Use the password change feature\n');
    } else {
      console.log('\n⚠️  Make sure the server is running at', API_BASE);
      console.log('   Start it with: npm start\n');
    }
    process.exit(1);
  }
}

// Check if server is running first
console.log('\n🔍 Checking if server is running...');
http.get('http://localhost:3000', () => {
  console.log('✅ Server is running\n');
  createAdmin();
}).on('error', () => {
  console.error('\n❌ Server is not running at http://localhost:3000');
  console.log('   Please start the server first with: npm start\n');
  process.exit(1);
});
