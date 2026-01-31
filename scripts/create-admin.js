#!/usr/bin/env node

/**
 * Script to create the initial admin user
 * Usage: node scripts/create-admin.js
 */

require('dotenv').config();
const crypto = require('crypto');
const http = require('http');
const path = require('path');

// Import email service
const emailService = require(path.join(__dirname, '..', 'server', 'utils', 'emailService'));

const ADMIN_EMAIL = 'admin@caregate.co.uk';
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

// Send email with password
async function sendPasswordEmail(email, password, name) {
  console.log('\n📧 Sending admin credentials email to:', email);
  
  try {
    const result = await emailService.sendAdminCredentials(email, password, name);
    
    if (result.success) {
      console.log('✅ Email sent successfully!');
      
      // Also display in console as backup
      console.log('\n========================================');
      console.log('ADMIN LOGIN CREDENTIALS (BACKUP)');
      console.log('========================================');
      console.log(`Email: ${email}`);
      console.log(`Temporary Password: ${password}`);
      console.log('\n⚠️  Password change required on first login');
      console.log('========================================\n');
      
      return true;
    } else {
      console.error('❌ Failed to send email:', result.error);
      console.log('\n⚠️  Email sending failed. Displaying credentials here:');
      console.log('\n========================================');
      console.log('ADMIN LOGIN CREDENTIALS');
      console.log('========================================');
      console.log(`Email: ${email}`);
      console.log(`Temporary Password: ${password}`);
      console.log('\n⚠️  Password change required on first login');
      console.log('⚠️  PLEASE CONFIGURE SMTP SETTINGS IN .env');
      console.log('========================================\n');
      
      return false;
    }
  } catch (error) {
    console.error('❌ Error sending email:', error.message);
    
    // Fallback: display in console
    console.log('\n========================================');
    console.log('ADMIN LOGIN CREDENTIALS (FALLBACK)');
    console.log('========================================');
    console.log(`Email: ${email}`);
    console.log(`Temporary Password: ${password}`);
    console.log('\n⚠️  Password change required on first login');
    console.log('========================================\n');
    
    return false;
  }
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
      const emailSent = await sendPasswordEmail(ADMIN_EMAIL, password, response.user.name);
      
      if (emailSent) {
        console.log('\n✅ Admin credentials have been sent to:', ADMIN_EMAIL);
        console.log('✅ Please check the email inbox for login details.');
      } else {
        console.log('\n⚠️  Email not sent. Please configure SMTP settings in .env file');
        console.log('   See .env.example for configuration examples');
      }
      
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
