# Auto Clock-In/Out Quick Reference

## For Care Workers

### How to Auto Clock-In

1. **Enable GPS on your device**
   - Go to device settings
   - Enable location services
   - Allow CareGate app to access location

2. **Arrive at the facility**
   - Be within 100 meters of the facility location
   - Open the CareGate app

3. **Auto Clock-In**
   - App will detect you're at the facility
   - Tap "Auto Clock-In" button
   - System verifies your location
   - You're clocked in automatically!

### Requirements
- ✓ GPS/Location services enabled
- ✓ Within 100 meters of facility
- ✓ Within shift schedule time (can clock in 15 min early)
- ✓ Internet connection

### While Working

**Stay Connected:**
- Keep the app running in background
- Your phone sends location updates every 2-3 minutes
- This prevents automatic clock-out

**If You Leave:**
- Moving more than 500m from facility = auto clock-out
- Closing the app = auto clock-out after 5 minutes
- Losing internet = auto clock-out after 5 minutes

### Auto Clock-Out Triggers

| Trigger | Description | Timing |
|---------|-------------|--------|
| Off-site | You move >500m from facility | Immediate |
| Offline | App loses connection | After 5 minutes |
| App closed | You close the app | After 5 minutes |

## For Facility Managers

### Setup

1. **Set Facility GPS Location**
   - Go to Facility Profile
   - Enter accurate GPS coordinates
   - Or allow app to detect current location

2. **Create Shifts with Location**
   - When creating shifts, include GPS coordinates
   - System uses this for auto clock-in verification

### Monitoring

**Real-Time Dashboard:**
- See which workers are currently clocked in
- View worker locations (if GPS enabled)
- Check last heartbeat timestamps

**Clock Records:**
- View all clock-in/out events
- Filter by auto vs manual
- See GPS verification status
- Review auto clock-out reasons

### Verification Flags

- `gps_verified`: Clock-in was verified by GPS
- `auto_clocked_out`: Worker was auto-clocked out
- `clock_in_method`: Shows "auto_gps" for auto clock-ins
- `clock_out_method`: Shows "auto_offline" or "auto_off_site"

## Troubleshooting

### Worker Can't Auto Clock-In

**Check:**
1. GPS enabled on device? ✓
2. Within 100m of facility? ✓
3. Within shift schedule? ✓
4. Not already clocked in? ✓

**Common Issues:**
- **"GPS too far"**: Worker needs to be closer to facility
- **"Outside schedule"**: Too early or too late for shift
- **"Missing GPS"**: Location services not enabled
- **"Already clocked in"**: Worker already has active shift

### Worker Auto-Clocked Out Unexpectedly

**Possible Reasons:**
1. **Moved off-site**: Left facility area (>500m)
2. **Lost connection**: Internet/data connection dropped
3. **App closed**: App was closed or phone turned off
4. **Low battery**: Device entered power-saving mode

**Solutions:**
- Keep app running in background
- Ensure stable internet connection
- Keep device charged
- Disable battery optimization for app

### Facility Not Receiving Updates

**Check:**
1. Facility GPS coordinates set correctly
2. Workers have GPS enabled
3. Internet connection stable
4. WordPress cron running (every 5 min)

**Debug:**
```bash
# Check cron status
wp cron event list

# Manually trigger auto clock-out processing
wp cron event run caregate_process_auto_clockouts

# Check recent clock records
SELECT * FROM wp_caregate_clock_records 
WHERE clock_out_time IS NULL 
ORDER BY clock_in_time DESC;
```

## Configuration

### Thresholds (Admin Only)

Edit in `caregate-plugin/includes/class-caregate-clock.php`:

```php
// Auto clock-in proximity
const GPS_PROXIMITY_THRESHOLD = 100; // meters

// Heartbeat timeout
const HEARTBEAT_TIMEOUT = 5; // minutes

// Off-site distance (in update_heartbeat method)
if ($distance > 500) { // meters
    // Auto clock-out
}
```

### Schedule Allowances

```php
// Early clock-in allowance
$early_threshold = $start - (15 * 60); // 15 minutes

// Late clock-out allowance  
$late_threshold = $end + (30 * 60); // 30 minutes
```

## API Quick Reference

### Auto Clock-In
```
POST /wp-json/caregate/v1/clock/auto-in
Content-Type: application/json
Authorization: Bearer <token>

{
  "shiftId": 123,
  "facilityId": 456,
  "workerLat": 51.5074,
  "workerLng": -0.1278,
  "facilityLat": 51.5075,
  "facilityLng": -0.1279
}
```

### Heartbeat
```
POST /wp-json/caregate/v1/clock/heartbeat
Content-Type: application/json
Authorization: Bearer <token>

{
  "workerLat": 51.5074,
  "workerLng": -0.1278
}
```

## Best Practices

### For Workers
1. ✓ Enable GPS before arriving at facility
2. ✓ Keep app running during entire shift
3. ✓ Ensure stable internet connection
4. ✓ Keep device charged
5. ✓ Clock out manually if leaving temporarily

### For Facilities
1. ✓ Set accurate GPS coordinates
2. ✓ Create shifts with proper schedules
3. ✓ Monitor dashboard regularly
4. ✓ Review auto clock-outs daily
5. ✓ Adjust thresholds if too many false triggers

### For Admins
1. ✓ Ensure WordPress cron is running
2. ✓ Monitor server logs for errors
3. ✓ Regular backup of clock records
4. ✓ Review GPS accuracy issues
5. ✓ Update plugin regularly

## Support

- **Documentation**: See AUTO_CLOCK_GPS_FEATURE.md
- **Technical Issues**: Check WordPress error logs
- **GPS Problems**: Verify device location settings
- **Schedule Issues**: Review shift start/end times

---

**Version**: 1.0.0  
**Last Updated**: January 29, 2026
