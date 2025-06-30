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
    $_SESSION['error_message'] = "Invalid mother ID for scheduling appointment.";
    header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
    exit();
}

$mother_id = intval($mother_id);

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

// Define variables and initialize with empty values for the form
$appointment_date_time = $_POST['appointment_date_time'] ?? ''; // Use empty string as default for datetime-local, or populate from POST
$purpose = $_POST['purpose'] ?? "";
$notes = $_POST['notes'] ?? "";

$appointment_date_time_err = $purpose_err = $general_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate Appointment Date/Time
     if (empty(trim($appointment_date_time))) {
        $appointment_date_time_err = "Please select the appointment date and time.";
    } else {
        // Parse the HTML5 datetime-local format YYYY-MM-DDTHH:MM
         $date_time_obj = DateTime::createFromFormat('Y-m-d\TH:i', $appointment_date_time);
         if ($date_time_obj && $date_time_obj->format('Y-m-d\TH:i') === $appointment_date_time) {
              // Valid format, convert to MySQL format YYYY-MM-DD HH:MM:SS
             $appointment_date_time_mysql = $date_time_obj->format('Y-m-d H:i:s'); // Add seconds for DATETIME column
         } else {
              $appointment_date_time_err = "Invalid date/time format. Please use the picker.";
         }
    }


    // Validate Purpose
    if (empty(trim($purpose))) {
        $purpose_err = "Please enter the purpose of the appointment.";
    } else {
        $purpose = trim($purpose);
    }

    // Notes (Optional)
    $notes = trim($notes);


    // Check input errors before inserting in database
    if (empty($appointment_date_time_err) && empty($purpose_err) && empty($general_err)) {

        // Prepare an insert statement
        // scheduled_by is the current user ID
        $sql = "INSERT INTO appointments (mother_id, appointment_date_time, purpose, notes, scheduled_by) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters - types: i, s, s, s, i
             $param_notes = empty($notes) ? NULL : $notes;
             $param_scheduled_by = $_SESSION['user_id'];

            mysqli_stmt_bind_param($stmt, "isssi",
                $mother_id,
                $appointment_date_time_mysql, // Use MySQL formatted string
                $purpose,
                $param_notes,
                $param_scheduled_by
            );

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Appointment scheduled successfully. Redirect back to mother details.
                // Format date/time nicely for message
                $display_datetime = $date_time_obj ? $date_time_obj->format('Y-m-d H:i') : htmlspecialchars($appointment_date_time);
                $_SESSION['message'] = "Appointment for " . htmlspecialchars($mother_name['first_name']) . " scheduled successfully for " . htmlspecialchars($display_datetime) . ".";
                $_SESSION['message_type'] = "success";
                header("location: " . PROJECT_SUBDIRECTORY . "/midwife/mother_details.php?id=" . htmlspecialchars($mother_id)); // Use PROJECT_SUBDIRECTORY for redirect
                exit();
            } else {
                $general_err = "Error scheduling appointment: " . mysqli_stmt_error($stmt);
                 error_log("Error scheduling appointment: " . mysqli_stmt_error($stmt));
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
$pageTitle = "Schedule Appointment for " . htmlspecialchars($mother_name['first_name']);

// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally
?>

<h2>Schedule Appointment for <?php echo htmlspecialchars($mother_name['first_name'] . ' ' . $mother_name['last_name']); ?></h2>
<p>Fill in the appointment details.</p>

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
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/schedule_appointment.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" method="post">
    <div class="form-group">
        <label>Appointment Date and Time <span class="text-danger">*</span></label>
        <!-- HTML5 datetime-local input -->
        <!-- Value needs to be in YYYY-MM-DDTHH:MM format for picker to display default -->
        <input type="datetime-local" name="appointment_date_time" class="form-control <?php echo (!empty($appointment_date_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($appointment_date_time); ?>" required>
        <span class="invalid-feedback"><?php echo htmlspecialchars($appointment_date_time_err); ?></span>
    </div>

     <div class="form-group">
        <label>Purpose <span class="text-danger">*</span></label>
        <input type="text" name="purpose" class="form-control <?php echo (!empty($purpose_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($purpose); ?>" required>
        <span class="invalid-feedback"><?php echo htmlspecialchars($purpose_err); ?></span>
        <small class="form-text text-muted">e.g., Routine ANC Checkup, Lab Review, Ultrasound</small>
    </div>

     <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
    </div>


    <div class="form-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus mr-1"></i> Schedule Appointment</button>
        <!-- Use $base_url for the cancel link -->
        <a href="<?php echo $base_url; ?>midwife/mother_details.php?id=<?php echo htmlspecialchars($mother_id); ?>" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>


<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>