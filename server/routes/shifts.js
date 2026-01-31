const express = require('express');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const authMiddleware = require('../middleware/auth');
const config = require('../config');

const router = express.Router();

// Calculate distance between two locations (simplified)
function calculateDistance(loc1, loc2) {
  if (!loc1 || !loc2 || !loc1.lat || !loc2.lat) return 999;
  
  const R = 6371; // Earth's radius in km
  const dLat = (loc2.lat - loc1.lat) * Math.PI / 180;
  const dLon = (loc2.lng - loc1.lng) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(loc1.lat * Math.PI / 180) * Math.cos(loc2.lat * Math.PI / 180) *
    Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

// Calculate dynamic pricing based on urgency and demand
function calculateDynamicPricing(baseRate, startTime, skillLevel) {
  let rate = baseRate;
  
  // Urgency multiplier (within 24 hours)
  const hoursUntilShift = (new Date(startTime) - new Date()) / (1000 * 60 * 60);
  
  // Only apply urgency premium for future shifts
  if (hoursUntilShift > 0 && hoursUntilShift < 24) {
    rate *= config.PRICING.URGENT_24H_MULTIPLIER;
  } else if (hoursUntilShift > 0 && hoursUntilShift < 48) {
    rate *= config.PRICING.URGENT_48H_MULTIPLIER;
  }
  
  // Skill level multiplier
  if (skillLevel === 'advanced') {
    rate *= config.PRICING.SKILL_ADVANCED_MULTIPLIER;
  } else if (skillLevel === 'expert') {
    rate *= config.PRICING.SKILL_EXPERT_MULTIPLIER;
  }
  
  return Math.round(rate * 100) / 100;
}

// Create a new shift (facility only)
router.post('/', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'facility') {
      return res.status(403).json({ error: 'Only facilities can create shifts' });
    }

    const {
      title,
      description,
      startTime,
      endTime,
      requiredSkills,
      location,
      baseRate,
      skillLevel
    } = req.body;

    if (!title || !startTime || !endTime || !requiredSkills || !location || !baseRate) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    const shiftId = uuidv4();
    const dynamicRate = calculateDynamicPricing(baseRate, startTime, skillLevel || 'basic');

    const shift = db.create('shifts', shiftId, {
      facilityId: req.user.userId,
      title,
      description,
      startTime: new Date(startTime),
      endTime: new Date(endTime),
      requiredSkills,
      location,
      baseRate,
      dynamicRate,
      skillLevel: skillLevel || 'basic',
      status: 'open', // open, filled, completed, cancelled
      assignedWorkerId: null
    });

    res.status(201).json({
      message: 'Shift created successfully',
      shift
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get all shifts with optional filters
router.get('/', authMiddleware, (req, res) => {
  try {
    const { status, skills, startDate, endDate } = req.query;
    
    let shifts = db.list('shifts');

    // Filter by status
    if (status) {
      shifts = shifts.filter(s => s.status === status);
    }

    // Filter by date range
    if (startDate) {
      shifts = shifts.filter(s => new Date(s.startTime) >= new Date(startDate));
    }
    if (endDate) {
      shifts = shifts.filter(s => new Date(s.startTime) <= new Date(endDate));
    }

    // Filter by skills if user is a worker
    if (req.user.role === 'worker' && skills) {
      const requiredSkills = skills.split(',');
      shifts = shifts.filter(shift => 
        shift.requiredSkills.some(skill => requiredSkills.includes(skill))
      );
    }

    res.json({ shifts });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get matched shifts for a worker (intelligent matching)
router.get('/matches', authMiddleware, (req, res) => {
  try {
    if (req.user.role !== 'worker') {
      return res.status(403).json({ error: 'Only workers can get matched shifts' });
    }

    const worker = db.read('users', req.user.userId);
    if (!worker) {
      return res.status(404).json({ error: 'Worker not found' });
    }

    // Get all open shifts
    const openShifts = db.list('shifts', { status: 'open' });

    // Calculate match score for each shift
    const matchedShifts = openShifts.map(shift => {
      let matchScore = 0;

      // Skill matching (40% weight)
      const skillMatch = shift.requiredSkills.filter(skill => 
        worker.skills.includes(skill)
      ).length / shift.requiredSkills.length;
      matchScore += skillMatch * 40;

      // Location proximity (30% weight)
      const distance = calculateDistance(worker.location, shift.location);
      const locationScore = Math.max(0, (config.MAX_MATCHING_DISTANCE - distance) / config.MAX_MATCHING_DISTANCE);
      matchScore += locationScore * 30;

      // Availability (30% weight) - simplified, assume available
      matchScore += 30;

      return {
        ...shift,
        matchScore: Math.round(matchScore),
        distance: Math.round(distance * 10) / 10
      };
    });

    // Sort by match score
    matchedShifts.sort((a, b) => b.matchScore - a.matchScore);

    res.json({
      matches: matchedShifts.filter(s => s.matchScore >= 50) // Only show good matches
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get specific shift details
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const shift = db.read('shifts', req.params.id);
    if (!shift) {
      return res.status(404).json({ error: 'Shift not found' });
    }
    res.json({ shift });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Update shift
router.put('/:id', authMiddleware, (req, res) => {
  try {
    const shift = db.read('shifts', req.params.id);
    if (!shift) {
      return res.status(404).json({ error: 'Shift not found' });
    }

    if (shift.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized to update this shift' });
    }

    const updated = db.update('shifts', req.params.id, req.body);
    res.json({ message: 'Shift updated successfully', shift: updated });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Delete shift
router.delete('/:id', authMiddleware, (req, res) => {
  try {
    const shift = db.read('shifts', req.params.id);
    if (!shift) {
      return res.status(404).json({ error: 'Shift not found' });
    }

    if (shift.facilityId !== req.user.userId) {
      return res.status(403).json({ error: 'Not authorized to delete this shift' });
    }

    db.delete('shifts', req.params.id);
    res.json({ message: 'Shift deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;
