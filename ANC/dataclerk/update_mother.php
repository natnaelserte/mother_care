<?php
// Include authentication and authorization
require_once(__DIR__ . '/../core/auth.php'); // This includes paths.php and starts session

require_login(); // Ensure user is logged in
require_role('Data Clerk', 'dashboard.php'); // Ensure user is a Data Clerk

// --- Calculate relative path to root ---
$script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /dataclerk
$depth = count(explode('/', trim($script_dir, '/')));
$base_url = str_repeat('../', $depth);
// --- End calculation ---


// Include database connection
require_once(__DIR__ . '/../config/db.php');

// Get mother ID from URL
$mother_id = $_GET['id'] ?? null;

if (!$mother_id || !is_numeric($mother_id)) {
    $_SESSION['error_message'] = "Invalid or missing mother ID.";
    header("location: " . PROJECT_SUBDIRECTORY . "/dataclerk/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
    exit();
}

$mother_id = intval($mother_id); // Ensure it's an integer

// Define variables and initialize with empty values or fetch existing data
$first_name = $last_name = $date_of_birth = $address = $phone_number = $national_id = "";
$first_name_err = $last_name_err = $date_of_birth_err = $phone_number_err = $national_id_err = $general_err = "";
$mother_details = null; // To hold fetched data

// Flag to indicate if we are loading existing data or processing a form submission error
$is_post_request = ($_SERVER["REQUEST_METHOD"] == "POST");


// If it's a GET request or a POST request with errors, fetch existing data
if (!$is_post_request || !empty($_POST)) { // If POST, $_POST might be empty if there are errors before parsing
     // Fetch existing mother details
    $sql_fetch = "SELECT mother_id, first_name, last_name, date_of_birth, address, phone_number, national_id FROM mothers WHERE mother_id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $mother_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            $mother_details = mysqli_fetch_assoc($result_fetch);
            mysqli_free_result($result_fetch);
        } else {
             // Handle query error
            $general_err = "Database error fetching mother details for update: " . mysqli_stmt_error($stmt_fetch);
            error_log($general_err);
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
         // Handle prepare error
        $general_err = "Database error preparing fetch query for update: " . mysqli_error($link);
         error_log($general_err);
    }

    if (!$mother_details) {
        // Mother not found
        $_SESSION['error_message'] = "Mother with ID " . htmlspecialchars($mother_id) . " not found.";
        header("location: " . PROJECT_SUBDIRECTORY . "/dataclerk/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
        exit();
    }

    // Populate form variables with fetched data if it's a GET request
    if (!$is_post_request || !empty($general_err) || !empty($_POST)) { // Also re-populate if there was a general DB error on POST
         $first_name = $_POST['first_name'] ?? ($mother_details['first_name'] ?? '');
         $last_name = $_POST['last_name'] ?? ($mother_details['last_name'] ?? '');
         $date_of_birth = $_POST['date_of_birth'] ?? ($mother_details['date_of_birth'] ?? '');
         $address = $_POST['address'] ?? ($mother_details['address'] ?? '');
         $phone_number = $_POST['phone_number'] ?? ($mother_details['phone_number'] ?? '');
         $national_id = $_POST['national_id'] ?? ($mother_details['national_id'] ?? '');
    }
     // Note: If POST validation failed (and general_err is empty), variables are already populated from $_POST
}


// Processing form data when form is submitted (POST request)
if ($is_post_request && empty($general_err)) { // Only process if it's a POST and no general fetch error

    // Re-fetch original details to compare National ID if needed later
    // Or assume $mother_details still holds the original data if loaded above

    // Get and validate First Name
    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter the first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // Get and validate Last Name
    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter the last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // Get and validate Date of Birth (Optional but good to validate format)
    if (!empty(trim($_POST["date_of_birth"]))) {
        $date_of_birth = trim($_POST["date_of_birth"]);
         if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_of_birth)) {
             $date_of_birth_err = "Invalid date format. Use YYYY-MM-DD.";
         }
    } else {
         $date_of_birth = NULL; // Store as NULL if empty
    }

    // Get Address (Optional)
    $address = trim($_POST["address"]);

    // Get Phone Number (Optional but good to validate format)
    $phone_number = trim($_POST["phone_number"]);
     // Add regex validation for phone number format if needed

    // Get and validate National ID (Optional, but check uniqueness if provided AND changed)
    $national_id = trim($_POST["national_id"]);
    if (!empty($national_id)) {
         // Only check for uniqueness if the National ID has been changed
         if ($national_id !== ($mother_details['national_id'] ?? '')) {
             // Check if National ID already exists for *another* mother
            $sql_check_id = "SELECT mother_id FROM mothers WHERE national_id = ? AND mother_id != ?";
            if ($stmt_check_id = mysqli_prepare($link, $sql_check_id)) {
                mysqli_stmt_bind_param($stmt_check_id, "si", $param_national_id, $mother_id);
                $param_national_id = $national_id;
                if (mysqli_stmt_execute($stmt_check_id)) {
                    mysqli_stmt_store_result($stmt_check_id);
                    if (mysqli_stmt_num_rows($stmt_check_id) > 0) {
                        $national_id_err = "This National ID is already registered for another mother.";
                    }
                } else {
                    $general_err = "Oops! Something went wrong with the National ID uniqueness check.";
                     error_log("Error checking National ID uniqueness on update: " . mysqli_stmt_error($stmt_check_id));
                }
                mysqli_stmt_close($stmt_check_id);
            } else {
                 $general_err = "Database error preparing National ID check.";
                 error_log("Database error preparing National ID check on update: " . mysqli_error($link));
            }
         }
    } else {
        $national_id = NULL; // Store as NULL if empty
    }


    // Check input errors before updating in database
    if (empty($first_name_err) && empty($last_name_err) && empty($date_of_birth_err) && empty($phone_number_err) && empty($national_id_err) && empty($general_err)) {

        // Prepare an update statement
        $sql = "UPDATE mothers SET first_name = ?, last_name = ?, date_of_birth = ?, address = ?, phone_number = ?, national_id = ? WHERE mother_id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters - types: s, s, s, s, s, s, i
            $param_date_of_birth = $date_of_birth !== NULL ? $date_of_birth : NULL;
            $param_address = empty($address) ? NULL : $address;
            $param_phone_number = empty($phone_number) ? NULL : $phone_number;
            $param_national_id = $national_id !== NULL ? $national_id : NULL;


            mysqli_stmt_bind_param($stmt, "ssssssi",
                $first_name,
                $last_name,
                $param_date_of_birth,
                $param_address,
                $param_phone_number,
                $param_national_id,
                $mother_id // Use the mother_id from the URL
            );

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Check if any rows were affected (optional, but good practice)
                 if (mysqli_stmt_affected_rows($stmt) > 0) {
                     $_SESSION['message'] = "Mother details updated successfully.";
                     $_SESSION['message_type'] = "success";
                 } else {
                      // No rows affected, could be no changes made, or ID not found (though we checked that)
                      $_SESSION['message'] = "Mother details updated successfully (no changes detected).";
                      $_SESSION['message_type'] = "info";
                 }

                // Redirect back to view mothers page.
                header("location: " . PROJECT_SUBDIRECTORY . "/dataclerk/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
                exit();
            } else {
                $general_err = "Error updating mother: " . mysqli_stmt_error($stmt);
                 error_log("Error updating mother: " . mysqli_stmt_error($stmt));
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
             $general_err = "Database error preparing update statement: " . mysqli_error($link);
             error_log("Database error preparing update statement: " . mysqli_error($link));
        }
    } else {
         // If there were input errors, re-populate form fields from $_POST (already done above)
         $general_err = "Please fix the errors in the form.";
    }
}

// Close database connection (only if not already closed by a redirect/exit)
// In the POST block, the redirect/exit happens before this.
// In the GET block, it might be closed after fetching, or here if there was a fetch error.
if (isset($link) && $link) {
     mysqli_close($link);
}


// Set the page title
$pageTitle = "Update Mother Details"; // Could add name: "Update Mother: " . htmlspecialchars($first_name)


// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally

?>

<h2>Update Mother Details</h2>
<p>Edit the information for this mother.</p>

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
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/update_mother.php?id=<?php echo htmlspecialchars($mother_id); ?>" method="post">
    <input type="hidden" name="mother_id" value="<?php echo htmlspecialchars($mother_id); ?>"> <!-- Pass ID in hidden field too -->
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name); ?>" required>
                <span class="invalid-feedback"><?php echo htmlspecialchars($first_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($last_name); ?>" required>
                <span class="invalid-feedback"><?php echo htmlspecialchars($last_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date_of_birth); ?>">
                 <span class="invalid-feedback"><?php echo htmlspecialchars($date_of_birth_err); ?></span>
            </div>
             <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone_number); ?>">
                <span class="invalid-feedback"><?php echo htmlspecialchars($phone_number_err); ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>National ID</label>
                <input type="text" name="national_id" class="form-control <?php echo (!empty($national_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($national_id); ?>">
                <span class="invalid-feedback"><?php echo htmlspecialchars($national_id_err); ?></span>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="4"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Changes</button>
        <!-- Use $base_url for the cancel link -->
        <a href="<?php echo $base_url; ?>dataclerk/view_mothers.php" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>


<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>