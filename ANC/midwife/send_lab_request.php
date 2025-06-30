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
    $_SESSION['error_message'] = "Invalid mother ID for sending lab request.";
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

// Define variables and initialize with empty values for the form
$requested_tests = $_POST['requested_tests'] ?? "";

$requested_tests_err = $general_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate Requested Tests
    if (empty(trim($requested_tests))) {
        $requested_tests_err = "Please specify the tests required.";
    } else {
        $requested_tests = trim($requested_tests);
    }

    // Get anc_record_id from POST if available (copied from URL into a hidden field)
    $posted_anc_record_id = $_POST['anc_record_id'] ?? null;
    if ($posted_anc_record_id !== null && is_numeric($posted_anc_record_id)) {
        $anc_record_id = intval($posted_anc_record_id);
    } else {
        $anc_record_id = NULL;
    }


    // Check input errors before inserting in database
    if (empty($requested_tests_err) && empty($general_err)) {

        // Prepare an insert statement
        // request_date will use default CURRENT_TIMESTAMP
        // request_status will use default 'Pending'
        // requested_by is the current user ID
        $sql = "INSERT INTO lab_requests (mother_id, anc_record_id, requested_tests, requested_by) VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters - types: i, i, s, i
             $param_anc_record_id = $anc_record_id !== NULL ? $anc_record_id : NULL;
             $param_requested_tests = $requested_tests;
             $param_requested_by = $_SESSION['user_id'];


            mysqli_stmt_bind_param($stmt, "iisi",
                $mother_id,
                $param_anc_record_id,
                $param_requested_tests,
                $param_requested_by
            );

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Lab request sent successfully. Redirect back to mother details.
                $_SESSION['message'] = "Lab request for " . htmlspecialchars($mother_name['first_name']) . " sent successfully.";
                $_SESSION['message_type'] = "success";
                header("location: " . PROJECT_SUBDIRECTORY . "/midwife/mother_details.php?id=" . htmlspecialchars($mother_id)); // Use PROJECT_SUBDIRECTORY for redirect
                exit();
            } else {
                $general_err = "Error sending lab request: " . mysqli_stmt_error($stmt);
                 error_log("Error sending lab request: " . mysqli_stmt_error($stmt));
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
$pageTitle = "Send Lab Request for " . htmlspecialchars($mother_name['first_name']);

// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally
?>

<h2>Send Lab Request for <?php echo htmlspecialchars($mother_name['first_name'] . ' ' . $mother_name['last_name']); ?></h2>
<p>Specify the lab tests required.</p>

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
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_lab_request.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" method="post">
     <?php if ($anc_record_id !== NULL): ?>
        <!-- Pass anc_record_id back through the form if it was in the URL -->
        <input type="hidden" name="anc_record_id" value="<?php echo htmlspecialchars($anc_record_id); ?>">
        <p class="alert alert-info">Sending request linked to ANC Record #<?php echo htmlspecialchars($anc_record_id); ?></p>
    <?php endif; ?>

    <div class="form-group">
        <label>Requested Tests <span class="text-danger">*</span></label>
        <textarea name="requested_tests" class="form-control <?php echo (!empty($requested_tests_err)) ? 'is-invalid' : ''; ?>" rows="4" required><?php echo htmlspecialchars($requested_tests); ?></textarea>
        <span class="invalid-feedback"><?php echo htmlspecialchars($requested_tests_err); ?></span>
        <small class="form-text text-muted">e.g., CBC, Urinalysis, Blood Group, RPR</small>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send Lab Request</button>
        <!-- Use $base_url for the cancel link -->
        <a href="<?php echo $base_url; ?>midwife/mother_details.php?id=<?php echo htmlspecialchars($mother_id); ?>" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>

<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>