const express = require('express');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');

const router = express.Router();

// Get all facilities
router.get('/', authMiddleware, (req, res) => {
  try {
    const facilities = db.list('users', { role: 'facility' });
    
    const publicFacilities = facilities.map(f => ({
      id: f.id,
      name: f.name,
      facilityType: f.facilityType,
      location: f.location,
      rating: f.rating,
      verified: f.verified
    }));

    res.json({ facilities: publicFacilities });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get facility by ID
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const facility = db.read('users', req.params.id);
    
    if (!facility || facility.role !== 'facility') {
      return res.status(404).json({ error: 'Facility not found' });
    }

    const publicProfile = {
      id: facility.id,
      name: facility.name,
      facilityType: facility.facilityType,
      location: facility.location,
      rating: facility.rating,
      verified: facility.verified,
      completedShifts: facility.completedShifts
    };

    res.json({ facility: publicProfile });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
