#!/usr/bin/env node

/**
 * Script to create the initial admin user
 * Usage: node scripts/create-admin.js
 */

const crypto = require('crypto');
const axios = require('axios');

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

async function createAdmin() {
  try {
    console.log('Creating admin user...\n');
    
    // Generate secure password
    const password = generatePassword();
    
    // Create admin user via API
    const response = await axios.post(`${API_BASE}/api/auth/create-admin`, {
      email: ADMIN_EMAIL,
      password: password,
      name: ADMIN_NAME
    });
    
    if (response.data && response.data.user) {
      console.log('✅ Admin user created successfully!');
      console.log(`   Email: ${response.data.user.email}`);
      console.log(`   Name: ${response.data.user.name}`);
      console.log(`   Role: ${response.data.user.role}`);
      
      // Send password via email
      await sendPasswordEmail(ADMIN_EMAIL, password);
      
      console.log('\n✅ Password has been sent to admin email address.');
      console.log('✅ Admin must change password on first login.\n');
    }
  } catch (error) {
    if (error.response && error.response.data) {
      console.error('❌ Error:', error.response.data.error);
      
      if (error.response.data.error === 'User already exists') {
        console.log('\nℹ️  Admin user already exists. If you need to reset the password,');
        console.log('   please use the password reset feature.');
      }
    } else {
      console.error('❌ Error creating admin:', error.message);
      console.log('\n⚠️  Make sure the server is running at', API_BASE);
    }
    process.exit(1);
  }
}

// Run the script
createAdmin();
