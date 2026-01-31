const express = require('express');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');

const router = express.Router();

// Get current user profile
router.get('/me', authMiddleware, (req, res) => {
  try {
    const user = db.read('users', req.user.userId);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    const { password, ...userWithoutPassword } = user;
    res.json({ user: userWithoutPassword });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Update user profile
router.put('/me', authMiddleware, (req, res) => {
  try {
    const { password, email, ...updateData } = req.body;
    
    const updated = db.update('users', req.user.userId, updateData);
    if (!updated) {
      return res.status(404).json({ error: 'User not found' });
    }

    const { password: _, ...userWithoutPassword } = updated;
    res.json({ message: 'Profile updated successfully', user: userWithoutPassword });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get user by ID (public profile)
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const user = db.read('users', req.params.id);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Return only public information
    const publicProfile = {
      id: user.id,
      name: user.name,
      role: user.role,
      skills: user.skills,
      rating: user.rating,
      completedShifts: user.completedShifts,
      verified: user.verified
    };

    res.json({ user: publicProfile });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
