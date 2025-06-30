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

// Define variables and initialize with empty values for the form
$first_name = $last_name = $date_of_birth = $address = $phone_number = $national_id = "";
$first_name_err = $last_name_err = $date_of_birth_err = $phone_number_err = $national_id_err = $general_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

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

    // Get and validate National ID (Optional, but check uniqueness if provided)
    $national_id = trim($_POST["national_id"]);
    if (!empty($national_id)) {
        // Check if National ID already exists
        $sql_check_id = "SELECT mother_id FROM mothers WHERE national_id = ?";
        if ($stmt_check_id = mysqli_prepare($link, $sql_check_id)) {
            mysqli_stmt_bind_param($stmt_check_id, "s", $param_national_id);
            $param_national_id = $national_id;
            if (mysqli_stmt_execute($stmt_check_id)) {
                mysqli_stmt_store_result($stmt_check_id);
                if (mysqli_stmt_num_rows($stmt_check_id) > 0) {
                    $national_id_err = "This National ID is already registered.";
                }
            } else {
                $general_err = "Oops! Something went wrong with the National ID check.";
                 error_log("Error checking National ID uniqueness: " . mysqli_stmt_error($stmt_check_id));
            }
            mysqli_stmt_close($stmt_check_id);
        } else {
             $general_err = "Database error preparing National ID check.";
             error_log("Database error preparing National ID check: " . mysqli_error($link));
        }
    } else {
        $national_id = NULL; // Store as NULL if empty
    }


    // Check input errors before inserting in database
    if (empty($first_name_err) && empty($last_name_err) && empty($date_of_birth_err) && empty($phone_number_err) && empty($national_id_err) && empty($general_err)) {

        // Prepare an insert statement
        // registration_date will use default CURRENT_DATE (as defined in SQL schema)
        // registered_by is the current user ID
        $sql = "INSERT INTO mothers (first_name, last_name, date_of_birth, address, phone_number, national_id, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters - types: s, s, s, s, s, s, i
            $param_date_of_birth = $date_of_birth !== NULL ? $date_of_birth : NULL;
            $param_address = empty($address) ? NULL : $address;
            $param_phone_number = empty($phone_number) ? NULL : $phone_number;
            $param_national_id = $national_id !== NULL ? $national_id : NULL;
            $param_registered_by = $_SESSION['user_id'];


            mysqli_stmt_bind_param($stmt, "ssssssi",
                $first_name,
                $last_name,
                $param_date_of_birth,
                $param_address,
                $param_phone_number,
                $param_national_id,
                $param_registered_by
            );

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Mother registered successfully. Redirect to view mothers page.
                $_SESSION['message'] = "Mother " . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . " registered successfully.";
                $_SESSION['message_type'] = "success";
                header("location: " . PROJECT_SUBDIRECTORY . "/dataclerk/view_mothers.php"); // Use PROJECT_SUBDIRECTORY for redirect
                exit();
            } else {
                $general_err = "Error registering mother: " . mysqli_stmt_error($stmt);
                 error_log("Error registering mother: " . mysqli_stmt_error($stmt));
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


// Set the page title
$pageTitle = "Register New Mother";

// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally

?>

<h2>Register New Mother</h2>
<p>Fill in the details to register a new mother.</p>

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
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/register_mother.php" method="post">
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
            <!-- Registration Date and Registered By will be set automatically by the DB / PHP -->
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus mr-1"></i> Register Mother</button>
        <!-- Use $base_url for the cancel link -->
        <a href="<?php echo $base_url; ?>dataclerk/view_mothers.php" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>


<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>