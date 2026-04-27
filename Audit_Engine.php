<?php
/**
 * 🕵️‍♂️ ADVANCED FORENSIC AUDIT SENTINEL
 * Developed by: Amit Sah, MBA
 * * This is like a "Digital Detective." It takes a pile of receipts (JSON),
 * checks them against a rulebook, and tells you if someone is cheating.
 */

class AuditEngine {
    private $data;             // Our pile of receipts
    private $fullReport = [];  // The results of our investigation
    private $cachedHolidays = []; // A memory bank to remember holidays
    private $systemErrors = [];   // A notebook to write down if our tools break

    // --- THE STARTING LINE ---
    // When we start the robot, it opens the file and turns the text into a list.
    public function __construct($filePath) {
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $this->data = json_decode($jsonContent, true);
            
            // If the file is garbled, write down an error.
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError("Failed to parse JSON: " . json_last_error_msg(), "critical");
                $this->data = [];
            }
        } else {
            $this->logError("Source file not found at: $filePath", "critical");
            $this->data = [];
        }
    }

    // --- THE ERROR NOTEBOOK ---
    // This lets us see all the things that went wrong with the robot itself.
    public function getSystemErrors() { return $this->systemErrors; }

    private function logError($message, $severity = 'warning') {
        $this->systemErrors[] = [
            'timestamp' => date('H:i:s'),
            'message' => $message,
            'severity' => $severity
        ];
    }

    // --- THE MAIN INVESTIGATION ---
    // This goes through every receipt one-by-one.
    public function runAudit() {
        if (empty($this->data)) {
            $this->logError("The provided JSON ledger is empty. Analysis aborted.", "critical");
            return [];
        }

        foreach ($this->data as $tx) {
            // If a receipt is missing a price or a date, it's garbage. Skip it.
            if (!isset($tx['amount']) || !isset($tx['date'])) {
                $this->logError("Transaction ID " . ($tx['id'] ?? 'Unknown') . " missing critical data. Skipping.", "warning");
                continue;
            }

            // Ask the 10 questions (Rules)
            $alerts = $this->getAlerts($tx);
            
            // --- THE SCORING SYSTEM ---
            // Some rules are scarier than others. We add up points for each broken rule.
            $score = 0;
            foreach ($alerts as $a) {
                if (strpos($a, 'Ghost') !== false)    $score += 40; // Scary! Fake company.
                if (strpos($a, 'Split') !== false)    $score += 30; // Hiding a big payment.
                if (strpos($a, 'Velocity') !== false) $score += 25; // Too many payments too fast.
                if (strpos($a, 'Cyclical') !== false) $score += 25; // Looks like a hidden salary.
                if (strpos($a, 'Match') !== false)    $score += 20; // Copy-pasted amounts.
                if (strpos($a, 'Holiday') !== false)  $score += 15; // Shady timing.
                if (strpos($a, 'Round') !== false)    $score += 10; // Guessed numbers.
                if (strpos($a, 'Weekend') !== false)  $score += 5;  // Odd timing.
            }

            // Save the receipt with its new "Risk Index"
            $this->fullReport[] = array_merge($tx, [
                'alerts' => $alerts,
                'risk_index' => min(100, $score) // Don't go over 100%
            ]);
        }
        return $this->fullReport;
    }

    // --- THE 10 QUESTIONS (THE RULES) ---
    private function getAlerts($tx) {
        $flags = [];

        // R1: Round Numbers (Humans love £1,000. Machines love £1,000.42)
        if ($tx['amount'] >= 1000 && $tx['amount'] == floor($tx['amount'])) {
            $flags[] = "Round Number Check";
        }

        // R2: Weekends (Business usually stops on Sundays)
        $dayOfWeek = date('w', strtotime($tx['date']));
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $flags[] = "Weekend Entry (" . date('l', strtotime($tx['date'])) . ")";
        }

        // R3: Threshold (Trying to stay just under a £10k limit)
        if ($tx['amount'] > 9500 && $tx['amount'] < 10000) {
            $flags[] = "Limit Proximity (<10k)";
        }

        // R4: Split-Invoices (Breaking a big bill into small pieces)
        $splitInfo = $this->analyzeVendorDailyActivity($tx);
        if (!empty($splitInfo)) $flags = array_merge($flags, $splitInfo);

        // R5: Ghost Vendor (Is "Apple" pretending to be "Appel"?)
        $ghost = $this->detectGhostVendor($tx['vendor']);
        if ($ghost) $flags[] = $ghost;

        // R7: Bank Holidays (Is someone working on Christmas?)
        if ($this->isPublicHoliday($tx['date'])) {
            $flags[] = "Bank Holiday Entry";
        }

        // R8: Copy-Paste (Same price to two different people in 3 days)
        $match = $this->checkDuplicateAmountPatterns($tx);
        if ($match) $flags[] = $match;

        // R9: Velocity (Is this vendor suddenly getting paid 5 times a week?)
        $velocity = $this->checkVelocitySpike($tx);
        if ($velocity) $flags[] = $velocity;

        // R10: Shadow Payroll (Paying the same amount on the same day every month)
        $cyclical = $this->checkCyclicalPattern($tx);
        if ($cyclical) $flags[] = $cyclical;

        return $flags;
    }

    // --- THE DETECTIVE'S TOOLS ---

    // Tool: Ask the Internet for a list of UK holidays
    private function isPublicHoliday($dateString) {
        $year = date('Y', strtotime($dateString));
        if (!isset($this->cachedHolidays[$year])) {
            $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/GB";
            $ctx = stream_context_create(['http' => ['timeout' => 2]]); 
            $response = @file_get_contents($url, false, $ctx);

            if ($response) {
                $holidays = json_decode($response, true);
                $this->cachedHolidays[$year] = array_column($holidays, 'date');
            } else {
                $this->logError("API Connection Failed for year $year. Switched to Fallback Method.", "critical");
                $this->cachedHolidays[$year] = ["$year-01-01", "$year-12-25", "$year-12-26"]; 
            }
        }
        return in_array($dateString, $this->cachedHolidays[$year]);
    }

    // Tool: Look for "Twin" amounts sent to different companies
    private function checkDuplicateAmountPatterns($tx) {
        foreach ($this->data as $other) {
            if ($other['id'] !== $tx['id'] && $other['amount'] === $tx['amount'] && $other['vendor'] !== $tx['vendor']) {
                $dateDiff = abs(strtotime($tx['date']) - strtotime($other['date'])) / 86400;
                if ($dateDiff <= 3) return "Cross-Vendor Amount Match";
            }
        }
        return null;
    }

    // Tool: Check if a vendor is suddenly "too busy"
    private function checkVelocitySpike($tx) {
        $windowStart = strtotime($tx['date'] . ' -7 days');
        $windowEnd = strtotime($tx['date']);
        $count = 0;
        foreach ($this->data as $other) {
            $t = strtotime($other['date']);
            if ($other['vendor'] === $tx['vendor'] && $t >= $windowStart && $t <= $windowEnd) $count++;
        }
        return ($count > 3) ? "Velocity Spike ($count tx/week)" : null;
    }

    // Tool: Look for hidden salaries (Shadow Payroll)
    private function checkCyclicalPattern($tx) {
        $day = date('d', strtotime($tx['date']));
        $matches = 0;
        foreach ($this->data as $other) {
            if ($other['vendor'] === $tx['vendor'] && date('d', strtotime($other['date'])) === $day && $other['id'] !== $tx['id']) $matches++;
        }
        return ($matches >= 2) ? "Cyclical Pattern (Shadow Payroll)" : null;
    }

    // Tool: Check the daily total for a company
    private function analyzeVendorDailyActivity($tx) {
        $alerts = []; $count = 0; $total = 0;
        foreach ($this->data as $o) {
            if ($o['vendor'] === $tx['vendor'] && $o['date'] === $tx['date']) { $count++; $total += $o['amount']; }
        }
        if ($count > 1) $alerts[] = "Split-Invoice: $count txns today";
        if ($total >= 10000 && $tx['amount'] < 10000) $alerts[] = "Daily Cumulative > £10k";
        return $alerts;
    }

    // Tool: Spot misspelled company names (Ghost Vendors)
    private function detectGhostVendor($currentVendor) {
        $uniqueVendors = array_unique(array_column($this->data, 'vendor'));
        foreach ($uniqueVendors as $existing) {
            if ($currentVendor !== $existing) {
                $dist = levenshtein(strtolower($currentVendor), strtolower($existing));
                // If the name is 1 or 2 letters different, it's a "Fuzzy Match"
                if ($dist > 0 && $dist <= 2 && strlen($currentVendor) > 4) return "Fuzzy Match: Similar to '$existing'";
            }
        }
        return null;
    }

    // --- THE SUMMARY BUILDERS ---

    // Give us a "Risk Profile" for every company
    public function getVendorRiskProfiles() {
        $profiles = [];
        foreach ($this->data as $tx) {
            $v = $tx['vendor'];
            if (!isset($profiles[$v])) $profiles[$v] = ['name'=>$v, 'tx_count'=>0, 'alert_count'=>0, 'total_value'=>0];
            $profiles[$v]['tx_count']++;
            $profiles[$v]['total_value'] += $tx['amount'];
            $alerts = $this->getAlerts($tx);
            if (!empty($alerts)) $profiles[$v]['alert_count']++;
        }
        foreach ($profiles as &$p) {
            $p['risk_score'] = round(($p['alert_count'] / $p['tx_count']) * 100, 1);
            $p['rating'] = $p['risk_score'] > 40 ? 'High Risk' : ($p['risk_score'] > 15 ? 'Medium Risk' : 'Verified');
        }
        uasort($profiles, function($a, $b) { return $b['risk_score'] <=> $a['risk_score']; });
        return $profiles;
    }

    // The Benford Magic Trick: Do these numbers look "Natural"?
    public function getBenfordAnalysis() {
        $counts = array_fill(1, 9, 0); $total = 0;
        if (empty($this->data)) return array_fill(1, 9, ['actual'=>0, 'expected'=>0, 'deviation'=>0]);
        foreach ($this->data as $tx) {
            $clean = ltrim(preg_replace('/[^0-9]/', '', (string)$tx['amount']), '0');
            if (!empty($clean)) {
                $first = (int)$clean[0];
                if ($first >= 1 && $first <= 9) { $counts[$first]++; $total++; }
            }
        }
        $expected = [1=>30.1, 2=>17.6, 3=>12.5, 4=>9.7, 5=>7.9, 6=>6.7, 7=>5.8, 8=>5.1, 9=>4.6];
        $dist = [];
        foreach ($counts as $digit => $count) {
            $actual = ($total > 0) ? round(($count / $total) * 100, 1) : 0;
            $dist[$digit] = ['actual'=>$actual, 'expected'=>$expected[$digit], 'deviation'=>abs($actual - $expected[$digit])];
        }
        return $dist;
    }

    // --- THE WISE OWL (AI INSIGHT) ---
    // The robot explains everything it found in plain English.
    public function generateAIInsight($totalCount, $totalAlerts, $trustScore, $categoryRisk, $vendorProfiles) {
        $topCategory = array_key_first($categoryRisk);
        $catAlerts = $categoryRisk[$topCategory]['alerts'];
        $topVendor = array_key_first($vendorProfiles);
        $vStats = $vendorProfiles[$topVendor];
        $alertDensity = ($totalAlerts / max(1, $totalCount)) * 100;

        $catConcentration = ($catAlerts / max(1, $totalAlerts)) * 100;
        
        $conclusion = "";
        $severity = ($alertDensity > 20 || $trustScore < 65) ? "CRITICAL" : (($alertDensity > 5) ? "ELEVATED" : "STABLE");

        $conclusion .= "### **System Audit Status: $severity** \n\n";

        // Explain the Trust Score
        if ($trustScore < 70) {
            $conclusion .= "🔍 **Data Integrity Alert:** The Trust Score ($trustScore%) indicates a 'Non-Natural' distribution. Someone likely manually guessed these numbers. \n\n";
        }

        // Explain the Clusters
        if ($catConcentration > 40) {
            $conclusion .= "🎯 **Risk Clustering Detected:** We found a lot of trouble in **$topCategory**. This suggests a specific department is breaking the rules. \n\n";
        }

        // Explain the Companies
        if ($vStats['risk_score'] > 40) {
            $conclusion .= "🚩 **Entity Priority:** **$topVendor** looks very suspicious. It has a $vStats[risk_score]% flag rate. \n\n";
        }

        // Tell us what to do next
        $conclusion .= "--- \n";
        $conclusion .= "💡 **Prescriptive Action:** \n";
        if ($severity === "CRITICAL") {
            $conclusion .= "1. Stop paying the $topCategory department immediately.\n";
            $conclusion .= "2. Go check the ID of $topVendor.\n";
            $conclusion .= "3. Check who was working on the weekends.";
        } else {
            $conclusion .= "Everything looks mostly okay. Just keep a close eye on the **$topCategory** department.";
        }

        return $conclusion;
    }
}