const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const config = require('../config');

const router = express.Router();
const JWT_SECRET = config.JWT_SECRET;

// Register new user (worker or facility)
router.post('/register', async (req, res) => {
  try {
    const { email, password, name, role, skills, location, facilityType } = req.body;

    // Validate required fields
    if (!email || !password || !name || !role) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Validate role (admin cannot be created via registration)
    if (role === 'admin') {
      return res.status(400).json({ error: 'Invalid role' });
    }

    // Check if user already exists
    const existingUser = db.search('users', user => user.email === email);
    if (existingUser.length > 0) {
      return res.status(400).json({ error: 'User already exists' });
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);

    // Create user
    const userId = uuidv4();
    const user = db.create('users', userId, {
      email,
      password: hashedPassword,
      name,
      role, // 'worker', 'facility', or 'admin'
      skills: skills || [],
      location: location || {},
      facilityType: facilityType || null,
      verified: true, // Pre-vetted
      rating: 5.0,
      completedShifts: 0,
      mustChangePassword: false
    });

    // Generate token
    const token = jwt.sign(
      { userId: user.id, email: user.email, role: user.role },
      JWT_SECRET,
      { expiresIn: '30d' }
    );

    // Don't send password back
    const { password: _, ...userWithoutPassword } = user;

    res.status(201).json({
      message: 'User registered successfully',
      user: userWithoutPassword,
      token
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Email and password required' });
    }

    // Find user
    const users = db.search('users', user => user.email === email);
    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const user = users[0];

    // Check password
    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Generate token
    const token = jwt.sign(
      { userId: user.id, email: user.email, role: user.role },
      JWT_SECRET,
      { expiresIn: '30d' }
    );

    // Don't send password back
    const { password: _, ...userWithoutPassword } = user;

    res.json({
      message: 'Login successful',
      user: userWithoutPassword,
      token,
      mustChangePassword: user.mustChangePassword || false
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Change Password
router.post('/change-password', async (req, res) => {
  try {
    const { email, currentPassword, newPassword } = req.body;

    if (!email || !currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Find user
    const users = db.search('users', user => user.email === email);
    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = users[0];

    // Check current password
    const validPassword = await bcrypt.compare(currentPassword, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Current password is incorrect' });
    }

    // Hash new password
    const hashedPassword = await bcrypt.hash(newPassword, 10);

    // Update user
    db.update('users', user.id, {
      password: hashedPassword,
      mustChangePassword: false
    });

    res.json({ message: 'Password changed successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Create Admin User (internal endpoint - should be protected in production)
router.post('/create-admin', async (req, res) => {
  try {
    const { email, password, name } = req.body;

    if (!email || !password || !name) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Check if admin already exists
    const existingUser = db.search('users', user => user.email === email);
    if (existingUser.length > 0) {
      return res.status(400).json({ error: 'User already exists' });
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);

    // Create admin user
    const userId = uuidv4();
    const user = db.create('users', userId, {
      email,
      password: hashedPassword,
      name,
      role: 'admin',
      verified: true,
      mustChangePassword: true // Force password change on first login
    });

    // Don't send password back
    const { password: _, ...userWithoutPassword } = user;

    res.status(201).json({
      message: 'Admin user created successfully',
      user: userWithoutPassword
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
