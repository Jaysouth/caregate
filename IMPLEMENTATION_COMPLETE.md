# Implementation Complete: Auto Clock-In/Out with GPS

## Problem Statement

> "allow auto clock-in if care facility and care worker GPS is On and on the same location point and web app (off-site/switch-off/offline) auto clock-out. Only perform this function on duty time table schedule/resumption time"

## Solution Implemented ✅

A comprehensive GPS-based auto clock-in/out system with schedule enforcement and heartbeat monitoring.

## Features Delivered

### 1. Auto Clock-In ✅
- **GPS Verification**: Validates worker and facility are within 100 meters
- **Schedule Enforcement**: Only allows clock-in during shift hours (15 min early)
- **Coordinate Validation**: Validates lat/lng ranges to prevent invalid data
- **Audit Trail**: All GPS coordinates logged for review

### 2. Auto Clock-Out ✅
Three automatic triggers:
- **Off-site** (>500m): Worker moves away from facility → immediate clock-out
- **Offline** (5 min): No heartbeat received → background process clocks out
- **App closed**: Connection lost → detected via heartbeat timeout

### 3. Schedule Enforcement ✅
- Clock-in: 15 minutes early allowed
- Clock-out: 30 minutes late allowed
- Auto clock-out only during duty hours
- Outside schedule: operations blocked

### 4. Heartbeat System ✅
- Worker app sends location every 2-3 minutes
- Server validates distance from facility
- Immediate off-site detection
- Background processing for timeouts

## Technical Implementation

### Database Schema Changes
```sql
-- New fields added to wp_caregate_clock_records
clock_in_lat DECIMAL(10, 8)
clock_in_lng DECIMAL(11, 8)
clock_out_lat DECIMAL(10, 8)
clock_out_lng DECIMAL(11, 8)
facility_lat DECIMAL(10, 8)
facility_lng DECIMAL(11, 8)
gps_verified BOOLEAN DEFAULT 0
auto_clocked_out BOOLEAN DEFAULT 0
last_heartbeat DATETIME
```

### API Endpoints

**POST /wp-json/caregate/v1/clock/auto-in**
- Auto clock-in with GPS verification
- Validates proximity (100m) and schedule
- Returns clock record with GPS data

**POST /wp-json/caregate/v1/clock/heartbeat**
- Updates worker heartbeat
- Checks for off-site movement
- Auto clocks-out if >500m

### Background Processing

**WordPress Cron Job** (Every 5 minutes)
- Checks for stale heartbeats (>5 min old)
- Auto clocks-out offline workers
- Only processes during shift schedules
- Respects WordPress timezone

### Configuration

```php
// In class-caregate-clock.php
const GPS_PROXIMITY_THRESHOLD = 100;  // meters
const OFF_SITE_THRESHOLD = 500;       // meters  
const HEARTBEAT_TIMEOUT = 5;          // minutes

// Schedule allowances
$early_threshold = $start - (15 * 60);  // 15 min early
$late_threshold = $end + (30 * 60);     // 30 min late
```

## Testing Results ✅

All unit tests passing:

```
=== GPS Distance Calculation ===
Test 1 - Same location: 0 meters ✓ PASS
Test 2 - ~100m apart: 100.08 meters ✓ PASS
Test 3 - ~500m apart: 500.38 meters ✓ PASS
Test 4 - ~1km apart: 1000.75 meters ✓ PASS
Test 5 - Missing coords: PHP_FLOAT_MAX ✓ PASS

=== Shift Schedule Validation ===
Test 1 - During shift: ✓ PASS
Test 2 - 10 min before: ✓ PASS
Test 3 - 20 min before: ✓ PASS
Test 4 - 20 min after: ✓ PASS
Test 5 - 40 min after: ✓ PASS

=== Proximity Thresholds ===
Test 1 - 50m (allow clock-in): ✓ PASS
Test 2 - 150m (deny clock-in): ✓ PASS
Test 3 - 400m (stay clocked in): ✓ PASS
Test 4 - 600m (auto clock-out): ✓ PASS

=== Heartbeat Timeout ===
Test 1 - 2 min ago (not timeout): ✓ PASS
Test 2 - 6 min ago (timeout): ✓ PASS
Test 3 - 5 min boundary: ✓ PASS
```

## Code Quality ✅

**Code Review**: All issues addressed
- ✓ Added OFF_SITE_THRESHOLD constant
- ✓ Enhanced GPS validation (coordinate ranges)
- ✓ Improved documentation
- ✓ Fixed timezone handling
- ✓ Fixed typo in return value
- ✓ Fixed .gitignore formatting

**Security**: Considerations documented
- GPS coordinates logged for audit
- Schedule enforcement prevents misuse
- Coordinate validation prevents invalid data
- Note: GPS spoofing possible (client-side risk)

## Documentation ✅

**Created:**
- AUTO_CLOCK_GPS_FEATURE.md (9.7 KB) - Technical documentation
- AUTO_CLOCK_QUICK_REF.md (5.4 KB) - User guide
- test-auto-clock.php - Unit tests

**Updated:**
- README.md - Feature overview
- Code comments - PHPDoc throughout

## Usage Example

### Worker App (Client-Side)

```javascript
// 1. Auto clock-in when arriving
async function arriveAtFacility(shift) {
  const workerPos = await navigator.geolocation.getCurrentPosition();
  
  const response = await fetch('/wp-json/caregate/v1/clock/auto-in', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
      shiftId: shift.id,
      facilityId: shift.facilityId,
      workerLat: workerPos.coords.latitude,
      workerLng: workerPos.coords.longitude,
      facilityLat: shift.location.lat,
      facilityLng: shift.location.lng
    })
  });
  
  if (response.ok) {
    const data = await response.json();
    console.log('Clocked in!', data.distance + 'm from facility');
    startHeartbeat(); // Start sending location updates
  } else {
    const error = await response.json();
    alert('Cannot clock in: ' + error.message);
  }
}

// 2. Send heartbeat every 2-3 minutes while working
function startHeartbeat() {
  heartbeatInterval = setInterval(async () => {
    const pos = await navigator.geolocation.getCurrentPosition();
    
    await fetch('/wp-json/caregate/v1/clock/heartbeat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
        workerLat: pos.coords.latitude,
        workerLng: pos.coords.longitude
      })
    });
  }, 2 * 60 * 1000); // Every 2 minutes
}

// 3. Stop heartbeat when manually clocking out
function stopHeartbeat() {
  clearInterval(heartbeatInterval);
}
```

## Deployment

### Plugin Installation
1. Upload `caregate-wordpress-plugin.zip` to WordPress
2. Activate plugin
3. Database tables automatically updated
4. Cron job automatically scheduled

### Configuration (Optional)
Edit thresholds in `class-caregate-clock.php` if needed:
- GPS_PROXIMITY_THRESHOLD (default: 100m)
- OFF_SITE_THRESHOLD (default: 500m)
- HEARTBEAT_TIMEOUT (default: 5 min)

### Monitoring
- WordPress Admin → CareGate → Clock System
- View real-time clock records
- Check GPS verification status
- Review auto clock-out reasons

## Benefits

### For Workers
✓ Automatic clock-in (no manual entry)
✓ No forgotten clock-outs
✓ Accurate time tracking
✓ Fair pay calculation

### For Facilities
✓ Verifies worker presence at location
✓ Prevents time fraud
✓ Automatic timesheet creation
✓ Real-time worker tracking

### For Platform
✓ Reduces disputes
✓ Improves accuracy
✓ Enhances compliance
✓ Better data for billing

## Known Limitations

1. **GPS Spoofing**: Client can provide false coordinates
   - Mitigation: All GPS data logged for audit
   - Consider: IP geolocation cross-check (future)

2. **GPS Accuracy**: Varies by device (typically 5-50m)
   - Threshold set at 100m to account for this
   - Indoor accuracy may be worse

3. **Battery Impact**: Continuous GPS + heartbeat
   - Heartbeat only every 2-3 minutes
   - GPS can be disabled between heartbeats

4. **Internet Required**: Features need connectivity
   - Offline mode not supported
   - Future: Queue operations for sync

## Future Enhancements

1. IP geolocation verification
2. Historical location pattern analysis
3. Facility manager approval workflow
4. Offline mode with sync
5. Geofencing alerts
6. Analytics dashboard

## Support

- Technical Docs: AUTO_CLOCK_GPS_FEATURE.md
- User Guide: AUTO_CLOCK_QUICK_REF.md
- Tests: test-auto-clock.php
- Email: support@caregate.co.uk

---

**Status**: ✅ COMPLETE AND PRODUCTION READY

**Implementation Date**: January 29, 2026
**Plugin Version**: 1.0.0
**WordPress Compatibility**: 5.0+
**PHP Version**: 7.4+

**Tested**: ✅ All unit tests passing
**Reviewed**: ✅ Code review issues resolved
**Documented**: ✅ Complete documentation
**Deployed**: ✅ Plugin rebuilt and ready
