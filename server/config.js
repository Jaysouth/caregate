// Configuration constants for the application

module.exports = {
  // JWT Secret - must be set in production
  JWT_SECRET: process.env.JWT_SECRET || (process.env.NODE_ENV === 'production' 
    ? null 
    : 'caregate-secret-key-change-in-production'),
  
  // Platform fee percentage (0.15 = 15%)
  PLATFORM_FEE_PERCENTAGE: 0.15,
  
  // Maximum distance for shift matching (in km)
  MAX_MATCHING_DISTANCE: 50,
  
  // Urgency pricing multipliers
  PRICING: {
    URGENT_24H_MULTIPLIER: 1.3,  // 30% premium
    URGENT_48H_MULTIPLIER: 1.15, // 15% premium
    SKILL_ADVANCED_MULTIPLIER: 1.2,  // 20% premium
    SKILL_EXPERT_MULTIPLIER: 1.4     // 40% premium
  }
};
