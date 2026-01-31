<?php
/**
 * GPS Distance Calculation Accuracy Tests
 *
 * Tests the Haversine formula implementation for accuracy
 * with real UK locations.
 *
 * @package CareGate
 */

class GPS_Accuracy_Tests {

    /**
     * Calculate distance using Haversine formula (same as in CareGate_Shifts).
     */
    private static function calculate_distance($loc1, $loc2) {
        if (empty($loc1['lat']) || empty($loc2['lat'])) {
            return 999;
        }

        $earth_radius = 6371; // km

        $dLat = deg2rad($loc2['lat'] - $loc1['lat']);
        $dLon = deg2rad($loc2['lng'] - $loc1['lng']);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($loc1['lat'])) * cos(deg2rad($loc2['lat'])) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earth_radius * $c;
    }

    /**
     * Run all GPS accuracy tests.
     */
    public static function run_all_tests() {
        $tests = array(
            'test_london_to_manchester',
            'test_short_distance_same_city',
            'test_birmingham_to_bristol',
            'test_very_short_distance',
            'test_london_to_edinburgh',
            'test_liverpool_to_leeds',
            'test_cardiff_to_swansea',
            'test_glasgow_to_aberdeen',
            'test_zero_distance',
            'test_invalid_coordinates'
        );

        $results = array();
        $passed = 0;
        $failed = 0;

        echo "\n========================================\n";
        echo "GPS Distance Calculation Accuracy Tests\n";
        echo "========================================\n\n";

        foreach ($tests as $test) {
            $result = call_user_func(array(self::class, $test));
            $results[] = $result;
            
            if ($result['passed']) {
                $passed++;
                echo "✅ PASS: {$result['name']}\n";
            } else {
                $failed++;
                echo "❌ FAIL: {$result['name']}\n";
            }
            
            echo "   Expected: {$result['expected']} km\n";
            echo "   Got: {$result['actual']} km\n";
            echo "   Accuracy: {$result['accuracy']}%\n";
            if (!empty($result['message'])) {
                echo "   Message: {$result['message']}\n";
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "Summary: $passed passed, $failed failed\n";
        echo "========================================\n\n";

        return array(
            'total' => count($tests),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results
        );
    }

    /**
     * Test: London to Manchester
     */
    private static function test_london_to_manchester() {
        $london = array('lat' => 51.5074, 'lng' => -0.1278);
        $manchester = array('lat' => 53.4808, 'lng' => -2.2426);
        
        $expected = 262.7; // km (approximate)
        $actual = self::calculate_distance($london, $manchester);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'London to Manchester',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Short distance in same city (London)
     */
    private static function test_short_distance_same_city() {
        $location1 = array('lat' => 51.5074, 'lng' => -0.1278); // Central London
        $location2 = array('lat' => 51.5155, 'lng' => -0.0922); // East London
        
        $expected = 3.2; // km (approximate)
        $actual = self::calculate_distance($location1, $location2);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Short distance (same city)',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 95.0
        );
    }

    /**
     * Test: Birmingham to Bristol
     */
    private static function test_birmingham_to_bristol() {
        $birmingham = array('lat' => 52.4862, 'lng' => -1.8904);
        $bristol = array('lat' => 51.4545, 'lng' => -2.5879);
        
        $expected = 143.2; // km
        $actual = self::calculate_distance($birmingham, $bristol);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Birmingham to Bristol',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Very short distance (500m)
     */
    private static function test_very_short_distance() {
        $location1 = array('lat' => 51.5074, 'lng' => -0.1278);
        $location2 = array('lat' => 51.5119, 'lng' => -0.1278); // 500m north
        
        $expected = 0.5; // km
        $actual = self::calculate_distance($location1, $location2);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Very short distance (500m)',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 90.0
        );
    }

    /**
     * Test: London to Edinburgh
     */
    private static function test_london_to_edinburgh() {
        $london = array('lat' => 51.5074, 'lng' => -0.1278);
        $edinburgh = array('lat' => 55.9533, 'lng' => -3.1883);
        
        $expected = 534.0; // km
        $actual = self::calculate_distance($london, $edinburgh);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'London to Edinburgh',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Liverpool to Leeds
     */
    private static function test_liverpool_to_leeds() {
        $liverpool = array('lat' => 53.4084, 'lng' => -2.9916);
        $leeds = array('lat' => 53.8008, 'lng' => -1.5491);
        
        $expected = 113.0; // km
        $actual = self::calculate_distance($liverpool, $leeds);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Liverpool to Leeds',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Cardiff to Swansea
     */
    private static function test_cardiff_to_swansea() {
        $cardiff = array('lat' => 51.4816, 'lng' => -3.1791);
        $swansea = array('lat' => 51.6214, 'lng' => -3.9436);
        
        $expected = 61.0; // km
        $actual = self::calculate_distance($cardiff, $swansea);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Cardiff to Swansea',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Glasgow to Aberdeen
     */
    private static function test_glasgow_to_aberdeen() {
        $glasgow = array('lat' => 55.8642, 'lng' => -4.2518);
        $aberdeen = array('lat' => 57.1497, 'lng' => -2.0943);
        
        $expected = 190.0; // km
        $actual = self::calculate_distance($glasgow, $aberdeen);
        $accuracy = 100 - abs(($actual - $expected) / $expected * 100);

        return array(
            'name' => 'Glasgow to Aberdeen',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $accuracy >= 99.0
        );
    }

    /**
     * Test: Zero distance (same location)
     */
    private static function test_zero_distance() {
        $location = array('lat' => 51.5074, 'lng' => -0.1278);
        
        $expected = 0.0; // km
        $actual = self::calculate_distance($location, $location);
        $accuracy = 100;

        return array(
            'name' => 'Zero distance (same location)',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $actual < 0.1,
            'message' => 'Distance should be 0 for same coordinates'
        );
    }

    /**
     * Test: Invalid coordinates (missing lat/lng)
     */
    private static function test_invalid_coordinates() {
        $location1 = array('lat' => 51.5074, 'lng' => -0.1278);
        $location2 = array(); // Empty location
        
        $expected = 999; // Fallback value
        $actual = self::calculate_distance($location1, $location2);
        $accuracy = 100;

        return array(
            'name' => 'Invalid coordinates',
            'expected' => $expected,
            'actual' => round($actual, 1),
            'accuracy' => round($accuracy, 1),
            'passed' => $actual === 999,
            'message' => 'Should return 999 for invalid coordinates'
        );
    }

    /**
     * Run performance benchmark.
     */
    public static function run_performance_test() {
        $iterations = 1000;
        $location1 = array('lat' => 51.5074, 'lng' => -0.1278);
        $location2 = array('lat' => 53.4808, 'lng' => -2.2426);

        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            self::calculate_distance($location1, $location2);
        }
        
        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds
        $avg_per_calculation = $duration / $iterations;

        echo "\n========================================\n";
        echo "GPS Performance Benchmark\n";
        echo "========================================\n\n";
        echo "Iterations: $iterations\n";
        echo "Total time: " . round($duration, 2) . " ms\n";
        echo "Average per calculation: " . round($avg_per_calculation, 4) . " ms\n";
        echo "Calculations per second: " . round(1000 / $avg_per_calculation, 0) . "\n";
        echo "\n========================================\n\n";

        return array(
            'iterations' => $iterations,
            'total_time_ms' => round($duration, 2),
            'avg_time_ms' => round($avg_per_calculation, 4),
            'per_second' => round(1000 / $avg_per_calculation, 0)
        );
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $results = GPS_Accuracy_Tests::run_all_tests();
    GPS_Accuracy_Tests::run_performance_test();
    
    exit($results['failed'] > 0 ? 1 : 0);
}
