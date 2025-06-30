<?php
// Include authentication and authorization
require_once(__DIR__ . '/../core/auth.php'); // This includes paths.php and starts session

require_login();
require_role('Midwife', 'dashboard.php');

// --- Calculate relative path to root ---
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$depth = count(explode('/', trim($script_dir, '/')));
$base_url = str_repeat('../', $depth);
// --- End calculation ---


// Include database connection
require_once(__DIR__ . '/../config/db.php');

// Get mother ID from URL
$mother_id = $_GET['mother_id'] ?? null;

if (!$mother_id || !is_numeric($mother_id)) {
    $_SESSION['error_message'] = "Invalid mother ID for recording vital signs.";
    header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
    exit();
}

$mother_id = intval($mother_id);

// Get optional ANC record ID from URL
$anc_record_id = $_GET['anc_record_id'] ?? null;
if ($anc_record_id !== null && !is_numeric($anc_record_id)) {
     $anc_record_id = null; // Ignore if invalid
}
if ($anc_record_id !== null) {
    $anc_record_id = intval($anc_record_id);
    // Optional: Verify this ANC record ID exists and belongs to this mother
    // For simplicity here, we'll just pass it along.
}


// Fetch mother's name for display
$sql_mother_name = "SELECT first_name, last_name FROM mothers WHERE mother_id = ?";
$mother_name = null;
if ($stmt_name = mysqli_prepare($link, $sql_mother_name)) {
    mysqli_stmt_bind_param($stmt_name, "i", $mother_id);
    if (mysqli_stmt_execute($stmt_name)) {
        $result_name = mysqli_stmt_get_result($stmt_name);
        $mother_name = mysqli_fetch_assoc($result_name);
        mysqli_free_result($result_name);
    } else {
         error_log("Error fetching mother name: " . mysqli_stmt_error($stmt_name));
         $mother_name = ['first_name' => 'Unknown', 'last_name' => 'Mother'];
    }
    mysqli_stmt_close($stmt_name);
} else {
    error_log("Error preparing mother name query: " . mysqli_error($link));
    $mother_name = ['first_name' => 'Unknown', 'last_name' => 'Mother'];
}

if (!$mother_name) {
      $_SESSION['error_message'] = "Mother not found.";
      header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
      exit();
}

// Define variables and initialize with empty values for the form, using POST data on error
// check_date_time should default to current time unless POSTed
$check_date_time = $_POST['check_date_time'] ?? date('Y-m-d\TH:i'); // HTML5 format default
$temperature = $_POST['temperature'] ?? "";
$pulse_rate = $_POST['pulse_rate'] ?? "";
$respiratory_rate = $_POST['respiratory_rate'] ?? "";
$blood_pressure = $_POST['blood_pressure'] ?? "";

$check_date_time_err = $temperature_err = $pulse_rate_err = $respiratory_rate_err = $blood_pressure_err = $general_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate Check Date/Time
     if (empty(trim($check_date_time))) {
        $check_date_time_err = "Please enter the date and time.";
    } else {
        // Parse the HTML5 datetime-local format YYYY-MM-DDTHH:MM
         $date_time_obj = DateTime::createFromFormat('Y-m-d\TH:i', $check_date_time);
         if ($date_time_obj && $date_time_obj->format('Y-m-d\TH:i') === $check_date_time) {
              // Valid format, convert to MySQL format YYYY-MM-DD HH:MM:SS
             $check_date_time_mysql = $date_time_obj->format('Y-m-d H:i:s');
         } else {
              $check_date_time_err = "Invalid date/time format. Please use the picker.";
         }
    }

    // Validate Temperature (Optional)
    if (!empty(trim($temperature))) {
        if (!filter_var(trim($temperature), FILTER_VALIDATE_FLOAT) || trim($temperature) <= 0 || trim($temperature) > 45) { // Example reasonable range
            $temperature_err = "Please enter a valid temperature in °C.";
        } else {
            $temperature = floatval(trim($temperature));
        }
    } else {
        $temperature = NULL;
    }

     // Validate Pulse Rate (Optional)
    if (!empty(trim($pulse_rate))) {
        if (!filter_var(trim($pulse_rate), FILTER_VALIDATE_INT, ["options" => ["min_range" => 20, "max_range" => 300]])) { // Example reasonable range
            $pulse_rate_err = "Please enter a valid pulse rate (20-300).";
        } else {
            $pulse_rate = intval(trim($pulse_rate));
        }
    } else {
        $pulse_rate = NULL;
    }

     // Validate Respiratory Rate (Optional)
    if (!empty(trim($respiratory_rate))) {
        if (!filter_var(trim($respiratory_rate), FILTER_VALIDATE_INT, ["options" => ["min_range" => 5, "max_range" => 60]])) { // Example reasonable range
            $respiratory_rate_err = "Please enter a valid respiratory rate (5-60).";
        } else {
            $respiratory_rate = intval(trim($respiratory_rate));
        }
    } else {
        $respiratory_rate = NULL;
    }

    // Validate Blood Pressure (Optional)
    $blood_pressure = trim($blood_pressure);
    // Add regex validation for BP format if needed, e.g., /^\d{2,3}\/\d{2,3}$/


    // Get anc_record_id from POST if available (copied from URL into a hidden field)
    $posted_anc_record_id = $_POST['anc_record_id'] ?? null;
    if ($posted_anc_record_id !== null && is_numeric($posted_anc_record_id)) {
        $anc_record_id = intval($posted_anc_record_id);
    } else {
        $anc_record_id = NULL;
    }


    // Check input errors before inserting in database
    if (empty($check_date_time_err) && empty($temperature_err) && empty($pulse_rate_err) && empty($respiratory_rate_err) && empty($blood_pressure_err) && empty($general_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO vital_signs (mother_id, anc_record_id, check_date_time, temperature, pulse_rate, respiratory_rate, blood_pressure, taken_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters - types: i, i, s, d, i, i, s, i
            $param_anc_record_id = $anc_record_id !== NULL ? $anc_record_id : NULL;
            $param_temperature = $temperature !== NULL ? $temperature : NULL;
            $param_pulse_rate = $pulse_rate !== NULL ? $pulse_rate : NULL;
            $param_respiratory_rate = $respiratory_rate !== NULL ? $respiratory_rate : NULL;
            $param_blood_pressure = empty($blood_pressure) ? NULL : $blood_pressure;
            $param_taken_by = $_SESSION['user_id'];

             mysqli_stmt_bind_param($stmt, "iisdiiis",
                $mother_id,
                $param_anc_record_id,
                $check_date_time_mysql, // Use the MySQL formatted datetime string
                $param_temperature,
                $param_pulse_rate,
                $param_respiratory_rate,
                $param_blood_pressure,
                $param_taken_by
            );


            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Vital signs recorded successfully. Redirect back to mother details.
                $_SESSION['message'] = "Vital signs for " . htmlspecialchars($mother_name['first_name']) . " recorded successfully.";
                $_SESSION['message_type'] = "success";
                header("location: " . PROJECT_SUBDIRECTORY . "/midwife/mother_details.php?id=" . htmlspecialchars($mother_id)); // Use PROJECT_SUBDIRECTORY for redirect
                exit();
            } else {
                $general_err = "Error saving vital signs: " . mysqli_stmt_error($stmt);
                 error_log("Error saving vital signs: " . mysqli_stmt_error($stmt));
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
             $general_err = "Database error preparing insert statement: " . mysqli_error($link);
             error_log("Database error preparing insert statement: " . mysqli_error($link));
        }
    } else {
         $general_err = "Please fix the errors in the form.";
    }
}

// Close database connection
mysqli_close($link);

// Set the page title (after fetching mother name and handling POST)
$pageTitle = "Record Vital Signs for " . htmlspecialchars($mother_name['first_name']);

// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally
?>

<h2>Record Vital Signs for <?php echo htmlspecialchars($mother_name['first_name'] . ' ' . $mother_name['last_name']); ?></h2>
<p>Fill in the vital sign details.</p>

<?php
// Display error/success messages
if (isset($_SESSION['message'])) {
    $msg_class = $_SESSION['message_type'] ?? 'info';
    echo '<div class="alert alert-' . $msg_class . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
if (!empty($general_err)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($general_err) . '</div>';
}
?>

<!-- Use PROJECT_SUBDIRECTORY for form action -->
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/take_vital_sign.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" method="post">
    <?php if ($anc_record_id !== NULL): ?>
        <!-- Pass anc_record_id back through the form if it was in the URL -->
        <input type="hidden" name="anc_record_id" value="<?php echo htmlspecialchars($anc_record_id); ?>">
        <p class="alert alert-info">Recording vital signs linked to ANC Record #<?php echo htmlspecialchars($anc_record_id); ?></p>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
             <div class="form-group">
                <label>Check Date and Time <span class="text-danger">*</span></label>
                 <!-- Note: HTML datetime-local input format is YYYY-MM-DDTHH:MM -->
                 <!-- Value needs to be in this format for picker to display default -->
                <input type="datetime-local" name="check_date_time" class="form-control <?php echo (!empty($check_date_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($check_date_time); ?>" required>
                <span class="invalid-feedback"><?php echo htmlspecialchars($check_date_time_err); ?></span>
            </div>
            <div class="form-group">
                <label>Temperature (°C)</label>
                <input type="number" name="temperature" class="form-control <?php echo (!empty($temperature_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($temperature); ?>" step="0.1" min="30" max="45">
                <span class="invalid-feedback"><?php echo htmlspecialchars($temperature_err); ?></span>
            </div>
            <div class="form-group">
                <label>Pulse Rate (bpm)</label>
                <input type="number" name="pulse_rate" class="form-control <?php echo (!empty($pulse_rate_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($pulse_rate); ?>" min="20" max="300">
                <span class="invalid-feedback"><?php echo htmlspecialchars($pulse_rate_err); ?></span>
            </div>
        </div>
         <div class="col-md-6">
            <div class="form-group">
                <label>Respiratory Rate (breaths/min)</label>
                <input type="number" name="respiratory_rate" class="form-control <?php echo (!empty($respiratory_rate_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($respiratory_rate); ?>" min="5" max="60">
                <span class="invalid-feedback"><?php echo htmlspecialchars($respiratory_rate_err); ?></span>
            </div>
            <div class="form-group">
                <label>Blood Pressure (e.g., 120/80)</label>
                <input type="text" name="blood_pressure" class="form-control <?php echo (!empty($blood_pressure_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($blood_pressure); ?>">
                <span class="invalid-feedback"><?php echo htmlspecialchars($blood_pressure_err); ?></span>
            </div>
         </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Vital Signs</button>
        <!-- Use $base_url for the cancel link -->
        <a href="<?php echo $base_url; ?>midwife/mother_details.php?id=<?php echo htmlspecialchars($mother_id); ?>" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>

<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>