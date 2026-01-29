#!/usr/bin/env node

/**
 * Script to build/rebuild the WordPress plugin ZIP file
 * Usage: node scripts/build-plugin.js
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const PLUGIN_DIR = 'caregate-plugin';
const OUTPUT_ZIP = 'caregate-wordpress-plugin.zip';
const TEMP_DIR = path.join('/tmp', 'caregate-build');

console.log('🔨 Building CareGate WordPress Plugin ZIP...\n');

// Clean up any existing temp directory
if (fs.existsSync(TEMP_DIR)) {
  console.log('🧹 Cleaning up previous build...');
  execSync(`rm -rf ${TEMP_DIR}`);
}

// Create temp directory
fs.mkdirSync(TEMP_DIR, { recursive: true });

// Copy plugin files to temp directory
console.log('📦 Copying plugin files...');
execSync(`cp -r ${PLUGIN_DIR} ${TEMP_DIR}/caregate`);

// Verify ADMIN_LOGIN.md exists
const adminLoginPath = path.join(TEMP_DIR, 'caregate', 'ADMIN_LOGIN.md');
if (fs.existsSync(adminLoginPath)) {
  console.log('✅ ADMIN_LOGIN.md included in package');
} else {
  console.log('⚠️  Warning: ADMIN_LOGIN.md not found in plugin directory');
}

// List important files
console.log('\n📋 Plugin package contents:');
const files = execSync(`find ${TEMP_DIR}/caregate -type f -name "*.php" -o -name "*.md" -o -name "*.txt" -o -name "*.css" -o -name "*.js" | wc -l`);
console.log(`   Total files: ${files.toString().trim()}`);

// Check for admin login file
const hasAdminLogin = execSync(`find ${TEMP_DIR}/caregate -name "ADMIN_LOGIN.md" | wc -l`);
if (parseInt(hasAdminLogin.toString().trim()) > 0) {
  console.log('   ✓ Admin login details: ADMIN_LOGIN.md');
}

// Check for readme
const hasReadme = execSync(`find ${TEMP_DIR}/caregate -name "readme.txt" | wc -l`);
if (parseInt(hasReadme.toString().trim()) > 0) {
  console.log('   ✓ WordPress readme: readme.txt');
}

// Create ZIP file
console.log('\n📦 Creating ZIP archive...');

// Get project root directory
const projectRoot = path.join(__dirname, '..');
const outputPath = path.join(projectRoot, OUTPUT_ZIP);

// Remove old ZIP if it exists
if (fs.existsSync(outputPath)) {
  fs.unlinkSync(outputPath);
  console.log('   Removed old ZIP file');
}

// Create new ZIP from temp directory
process.chdir(TEMP_DIR);
execSync(`zip -r ${OUTPUT_ZIP} caregate -q`);

// Move ZIP to project root
const zipSource = path.join(TEMP_DIR, OUTPUT_ZIP);
fs.copyFileSync(zipSource, outputPath);

// Get ZIP size
const stats = fs.statSync(outputPath);
const fileSizeInKB = Math.round(stats.size / 1024);

console.log(`✅ Created ${OUTPUT_ZIP} (${fileSizeInKB} KB)`);

// Clean up temp directory
console.log('\n🧹 Cleaning up...');
execSync(`rm -rf ${TEMP_DIR}`);

console.log('\n✨ Build complete!\n');
console.log('📦 Plugin ZIP: ' + OUTPUT_ZIP);
console.log('📄 Included: Admin login details (ADMIN_LOGIN.md)');
console.log('\nNext steps:');
console.log('1. Upload ' + OUTPUT_ZIP + ' to WordPress');
console.log('2. Activate the plugin');
console.log('3. Login with credentials from ADMIN_LOGIN.md');
console.log('4. Change the default password on first login\n');
