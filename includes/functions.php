<?php

/**
 * Calculates a fair fine by checking the historical rates and rules 
 * active on each specific day the book was overdue.
 */
function getFairFine($dueDate, $returnDate, $connection, $getBreakdown = false) {
    $totalFine = 0;
    $breakdown = [];
    
    $start = new DateTime($dueDate);
    $end = new DateTime($returnDate);
    
    // 1. If returned on time or early, fine is 0
    if ($start >= $end) {
        return $getBreakdown ? ['total' => 0, 'list' => []] : 0;
    }

    // 2. Cache to prevent redundant DB queries
    $rateCache = [];

    // 3. Loop through every day the book was overdue
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    $dayCount = 1;
    foreach ($period as $date) {
        $currentDayTimestamp = $date->format('Y-m-d 23:59:59');
        
        // Fetch rules active for THIS specific day
        if (!isset($rateCache[$currentDayTimestamp])) {
            $query = "SELECT standard_fine, late_fine, default_loan_period 
                      FROM fine_settings_history 
                      WHERE changed_at <= '$currentDayTimestamp' 
                      ORDER BY changed_at DESC LIMIT 1";
            
            $res = mysqli_query($connection, $query);
            $row = mysqli_fetch_assoc($res);

            if ($row) {
                $rateCache[$currentDayTimestamp] = $row;
            } else {
                // Absolute Fallback if history table is empty
                $rateCache[$currentDayTimestamp] = [
                    'standard_fine' => 2,
                    'late_fine' => 5,
                    'default_loan_period' => 7 // Using your new 7-day rule
                ];
            }
        }

        $activeRules = $rateCache[$currentDayTimestamp];
        $threshold = $activeRules['default_loan_period'];
        
        // Dynamic Logic: Check if current day exceeds the loan period threshold active at that time
        if ($dayCount <= $threshold) {
            $dailyRate = $activeRules['standard_fine'];
            $type = "Standard";
        } else {
            $dailyRate = $activeRules['late_fine'];
            $type = "Late";
        }

        $totalFine += $dailyRate;

        if ($getBreakdown) {
            $breakdown[] = [
                'date' => $date->format('Y-m-d'),
                'day_number' => $dayCount,
                'rate_applied' => $dailyRate,
                'type' => $type
            ];
        }
        $dayCount++;
    }

    return $getBreakdown ? ['total' => $totalFine, 'list' => $breakdown] : $totalFine;
}