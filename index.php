<?php
// Initialize variables for salary calculations
$dayValues = [];
$salaryPerHour = 0;
$totaldays = 0;
$totalhours = 0;
$basicSalary = 0;
$addition = 0;
$totalSalary = 0;
$startDay = 0;  // Default: Sunday (0 = Sun)
$flag = false;  // Flag to track if the calendar has been updated

// If the form is submitted (either Update or Calculate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve starting day and salary per hour from form
    if (isset($_POST['startDay'])) {
        $startDay = intval($_POST['startDay']);
    }
    if (isset($_POST['salaryPerHour']) && is_numeric($_POST['salaryPerHour'])) {
        $salaryPerHour = floatval($_POST['salaryPerHour']);
    }

    // Check if calendar was updated by flag
    if (isset($_POST['flag']) && $_POST['flag'] == "1") {
        $flag = true;  // The calendar has been updated
    }

    // If Update button is pressed, set flag to true
    if (isset($_POST['update'])) {
        $flag = true;
    }

    // If Calculate button is pressed, process salary calculation
    if (isset($_POST['calculate']) && $flag && isset($_POST['selectedOptions']) && is_array($_POST['selectedOptions'])) {
        $selectedOptions = $_POST['selectedOptions'];

        foreach ($selectedOptions as $day => $shiftOptions) {
            $dayWorked = false;
            $dayValue = $dayBasic = 0;
            $dayOfWeek = ($day + $startDay - 1) % 7;  // Adjust based on selected start day

            foreach ($shiftOptions as $shift => $selected) {
                if ($selected == 'on') {
                    if (!$dayWorked) {
                        $totaldays++;
                        $dayWorked = true;
                    }
                    // Salary calculations based on shift and day of week
                    list($shiftHours, $shiftMultiplier) = getShiftDetails($shift, $dayOfWeek);
                    $shiftSalary = $shiftHours * $salaryPerHour * $shiftMultiplier;
                    $dayValue += $shiftSalary;
                    $totalhours += $shiftHours;
                    $dayBasic += $shiftHours * $salaryPerHour;
                }
            }

            // Store the addition (bonus) and basic salary
            $addition += $dayValue - $dayBasic;
            $basicSalary += $dayBasic;
            $dayValues[$day] = $dayValue;
        }

        // Final total salary
        $totalSalary = $basicSalary + $addition;

        // After calculation, reset the flag to require updating the calendar again
        $flag = false;
    }
}

// Function to get shift details (hours and multiplier) based on day of the week
function getShiftDetails($shift, $dayOfWeek) {
    $shiftHours = 0;
    $shiftMultiplier = 1;

    if ($dayOfWeek >= 0 && $dayOfWeek <= 4) {  // Sunday to Thursday
        switch ($shift) {
            case 'morning': return [8, 1];
            case 'evening': return [8.5, 1.2];
            case 'night': return [9, 1.5];
        }
    }
    if ($dayOfWeek == 5) {  // Friday
        switch ($shift) {
            case 'morning': return [8, 1.4];
            case 'evening': return [8.5, 1.4];
            case 'night': return [9, 2];
        }
    }
    if ($dayOfWeek == 6) {  // Saturday
        switch ($shift) {
            case 'morning': return [8, 1.75];
            case 'evening': return [8.5, 1.75];
            case 'night': return [9, 2];
        }
    }

    return [$shiftHours, $shiftMultiplier];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Calendar for Salary Calculation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            font-size: 24px;  /* Increase font size */
        }
        form {
            margin-bottom: 20px;
            text-align: center;
        }
        input[type="number"], input[type="submit"], select {
            padding: 10px;
            margin: 10px 0;
            font-size: 16px;
            width: 100%;
            max-width: 300px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            text-align: center;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            font-size: 24px;
            width: 75px;
        }
        th {
            background-color: #34495e;
            color: white;
        }
        .empty {
            background-color: #ecf0f1;
        }
        input[type="checkbox"] {
            margin: 5px;
        }
        label {
            cursor: pointer;
            font-size: 24px;  /* Increase label font size */
        }
        .summary {
            text-align: center;
            margin-top: 20px;
            font-size: 24px;  /* Increase summary font size */
        }
        .summary h2 {
            margin: 5px 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            table, th, td {
                font-size: 10px;
            }
            th, td {
                padding: 8px;
                width: auto;
            }
        }

        @media (max-width: 600px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                display: inline-block;
                padding: 6px;
                width: 75px;
            }

            input[type="number"], input[type="submit"], select {
                width: 90%;
                max-width: 100%;
                font-size: 14px;
            }
        }

    </style>
    <script>
        // This function ensures only 2 checkboxes can be selected per day
        function limitCheckboxes(day) {
            const checkboxes = document.querySelectorAll(`input[name^="selectedOptions[${day}]"]`);
            let checkedCount = 0;
            checkboxes.forEach(box => {
                if (box.checked) checkedCount++;
            });
            checkboxes.forEach(box => {
                if (!box.checked) box.disabled = (checkedCount >= 2);
            });
        }

        function checkFlag() {
            var flag = "<?php echo $flag ? 'true' : 'false'; ?>";
            document.getElementById('calculateBtn').disabled = (flag == 'false');
        }
    </script>
</head>
<body onload="checkFlag()">

    <h2>Dynamic Calendar for Salary Calculation</h2>
    
    <form method="POST">
        <label for="startDay">Select the Starting Day of the Month:</label>
        <select id="startDay" name="startDay">
            <option value="0" <?php if($startDay == 0) echo 'selected'; ?>>Sunday</option>
            <option value="1" <?php if($startDay == 1) echo 'selected'; ?>>Monday</option>
            <option value="2" <?php if($startDay == 2) echo 'selected'; ?>>Tuesday</option>
            <option value="3" <?php if($startDay == 3) echo 'selected'; ?>>Wednesday</option>
            <option value="4" <?php if($startDay == 4) echo 'selected'; ?>>Thursday</option>
            <option value="5" <?php if($startDay == 5) echo 'selected'; ?>>Friday</option>
            <option value="6" <?php if($startDay == 6) echo 'selected'; ?>>Saturday</option>
        </select>
        <br><br>
        <input type="submit" name="update" value="Update Calendar">

        <br><br>
        <label for="salaryPerHour">Salary per Hour (₪):</label> <!-- Changed to shekel -->
        <input type="number" id="salaryPerHour" name="salaryPerHour" step="0.01" min="0" value="<?php echo $salaryPerHour; ?>" placeholder="Enter your hourly rate" require>
        <br><br>

        <!-- Hidden input to store the flag -->
        <input type="hidden" name="flag" value="<?php echo $flag ? 1 : 0; ?>">

        <table>
            <thead>
                <tr>
                    <th>SUN</th>
                    <th>MON</th>
                    <th>TUE</th>
                    <th>WED</th>
                    <th>THU</th>
                    <th>FRI</th>
                    <th>SAT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $daysInMonth = 31;  // Assuming August
                $currentDay = 1;

                for ($week = 0; $week < 6; $week++) {
                    echo "<tr>";
                    for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                        if ($week == 0 && $dayOfWeek < $startDay) {
                            echo "<td class='empty'></td>";
                        } elseif ($currentDay <= $daysInMonth) {
                            echo "<td>";
                            echo "<strong>" . $currentDay . "</strong><br>";
                            echo "<label><input type='checkbox' name='selectedOptions[$currentDay][morning]' onclick='limitCheckboxes($currentDay)'> Morning</label><br>";
                            echo "<label><input type='checkbox' name='selectedOptions[$currentDay][evening]' onclick='limitCheckboxes($currentDay)'> Evening</label><br>";
                            echo "<label><input type='checkbox' name='selectedOptions[$currentDay][night]' onclick='limitCheckboxes($currentDay)'> Night</label><br>";

                            if (isset($dayValues[$currentDay])) {
                                echo "<p>Total: ₪" . number_format($dayValues[$currentDay], 2) . "</p>";  // Changed to shekel
                            }

                            $currentDay++;
                        } else {
                            echo "<td class='empty'></td>";
                        }
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <input type="submit" name="calculate" id="calculateBtn" value="Calculate Salary" disabled>
    </form>

    <div class="summary">
        <h2>Hours Worked: <?php echo $totalhours; ?></h2>
        <h2>Days Worked: <?php echo $totaldays; ?></h2>
        <h2>Basic Salary: ₪<?php echo number_format($basicSalary, 2); ?></h2> <!-- Changed to shekel -->
        <h2>Addition (Overtime/Bonuses): ₪<?php echo number_format($addition, 2); ?></h2> <!-- Changed to shekel -->
        <h2>Total Salary: ₪<?php echo number_format($totalSalary, 2); ?></h2> <!-- Changed to shekel -->
    </div>

</body>
</html>
