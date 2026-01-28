#!/usr/bin/env node

/**
 * Script to create the initial admin user
 * Usage: node scripts/create-admin.js
 */

const crypto = require('crypto');
const http = require('http');

const ADMIN_EMAIL = 'info@caregate.co.uk';
const ADMIN_NAME = 'CareGate Administrator';
const API_BASE = process.env.API_BASE || 'http://localhost:3000';

// Generate a secure random password
function generatePassword() {
  const length = 16;
  const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
  let password = '';
  const bytes = crypto.randomBytes(length);
  
  for (let i = 0; i < length; i++) {
    password += charset[bytes[i] % charset.length];
  }
  
  return password;
}

// Send email with password (simulated for now)
async function sendPasswordEmail(email, password) {
  console.log('\n========================================');
  console.log('ADMIN PASSWORD EMAIL');
  console.log('========================================');
  console.log(`To: ${email}`);
  console.log(`Subject: Your CareGate Admin Account`);
  console.log('\nDear Administrator,');
  console.log('\nYour CareGate admin account has been created.');
  console.log('\nLogin Details:');
  console.log(`Email: ${email}`);
  console.log(`Password: ${password}`);
  console.log('\n⚠️  IMPORTANT: You will be required to change your password on first login.');
  console.log('\nPlease keep this information secure.');
  console.log('\nBest regards,');
  console.log('CareGate System');
  console.log('========================================\n');
  
  // In production, integrate with email service (SendGrid, AWS SES, etc.)
  // await emailService.send({
  //   to: email,
  //   subject: 'Your CareGate Admin Account',
  //   html: emailTemplate
  // });
}

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
    console.log('Creating admin user...\n');
    
    // Generate secure password
    const password = generatePassword();
    
    // Create admin user via API
    const response = await makeRequest('/api/auth/create-admin', {
      email: ADMIN_EMAIL,
      password: password,
      name: ADMIN_NAME
    });
    
    if (response && response.user) {
      console.log('✅ Admin user created successfully!');
      console.log(`   Email: ${response.user.email}`);
      console.log(`   Name: ${response.user.name}`);
      console.log(`   Role: ${response.user.role}`);
      
      // Send password via email
      await sendPasswordEmail(ADMIN_EMAIL, password);
      
      console.log('\n✅ Password has been sent to admin email address.');
      console.log('✅ Admin must change password on first login.\n');
    }
  } catch (error) {
    console.error('❌ Error:', error.message);
    
    if (error.message === 'User already exists') {
      console.log('\nℹ️  Admin user already exists. If you need to reset the password,');
      console.log('   please use the password reset feature.');
    } else {
      console.log('\n⚠️  Make sure the server is running at', API_BASE);
    }
    process.exit(1);
  }
}

// Run the script
createAdmin();
