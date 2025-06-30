<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php');

require_login(); // Ensure user is logged in
// require_role will redirect to dashboard if not Admin, using PROJECT_SUBDIRECTORY
require_role('Administrator', 'views/dashboard.php');

// $link is available globally from core/auth.php
global $link;

// Define variables and initialize with empty values or POST data on error
$username = $_POST['username'] ?? "";
$full_name = $_POST['full_name'] ?? "";
$password = $_POST['password'] ?? "";
$confirm_password = $_POST['confirm_password'] ?? "";
$role_id = $_POST['role_id'] ?? ""; // Keep as string/empty for validation check

$username_err = $full_name_err = $password_err = $confirm_password_err = $role_id_err = $general_err = "";


// Fetch roles for the dropdown (needed both for displaying the form initially and re-displaying on error)
$roles_sql = "SELECT role_id, role_name FROM roles ORDER BY role_name";
$roles = [];
if ($roles_result = mysqli_query($link, $roles_sql)) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $row;
    }
    mysqli_free_result($roles_result);
} else {
    $general_err = "Error fetching roles: " . mysqli_error($link);
    error_log("Error fetching roles: " . mysqli_error($link));
}


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username
    if (empty(trim($username))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement to check if username already exists
        $sql = "SELECT user_id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "This username is already taken.";
                } else {
                    // Username is unique, keep trimmed value
                    $username = trim($username);
                }
            } else {
                $general_err = "Oops! Something went wrong with the username check.";
                 error_log("Error checking username uniqueness: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
             $general_err = "Database error preparing username check.";
              error_log("Database error preparing username check: " . mysqli_error($link));
        }
    }

    // Validate full name
    if (empty(trim($full_name))) {
        $full_name_err = "Please enter the full name.";
    } else {
        $full_name = trim($full_name);
    }

    // Validate password
    if (empty(trim($password))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($password)) < 6) { // Example minimum length
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($password);
    }

    // Validate confirm password
    if (empty(trim($confirm_password))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($confirm_password);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate role
    if (empty($role_id)) { // role_id is a string '0' or empty if nothing selected
        $role_id_err = "Please select a role.";
    } else {
        $role_id = intval($role_id); // Convert to integer for DB
        // Validate that the role_id exists in the roles table
        $role_exists_sql = "SELECT role_id FROM roles WHERE role_id = ?";
         if ($stmt = mysqli_prepare($link, $role_exists_sql)) {
             mysqli_stmt_bind_param($stmt, "i", $role_id);
             mysqli_stmt_execute($stmt);
             mysqli_stmt_store_result($stmt);
             if (mysqli_stmt_num_rows($stmt) == 0) {
                 $role_id_err = "Invalid role selected."; // The ID from POST doesn't match a role
             }
             mysqli_stmt_close($stmt);
         } else {
             $general_err = "Database error preparing role validation.";
              error_log("Database error preparing role validation: " . mysqli_error($link));
         }
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($full_name_err) && empty($password_err) && empty($confirm_password_err) && empty($role_id_err) && empty($general_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password_hash, full_name, role_id, is_active) VALUES (?, ?, ?, ?, TRUE)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters (sssi means string, string, string, integer)
            $param_username = $username;
            $param_password_hash = password_hash($password, PASSWORD_DEFAULT); // Create a password hash
            $param_full_name = $full_name;
            $param_role_id = $role_id; // Use the integer role_id

            mysqli_stmt_bind_param($stmt, "sssi", $param_username, $param_password_hash, $param_full_name, $param_role_id);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // --- AUDIT LOGGING: User created successfully ---
                $new_user_id = mysqli_insert_id($link); // Get the ID of the newly created user
                $log_action_type = 'user_created';
                $log_target_id = $new_user_id;
                $log_details = json_encode([
                    'username' => $param_username,
                    'full_name' => $param_full_name,
                    'role_id' => $param_role_id,
                    'is_active' => TRUE
                ]);
                $log_user_id = $_SESSION['user_id']; // The admin who created the user

                $log_sql = "INSERT INTO audit_log (user_id, action_type, target_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())";
                if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                     mysqli_stmt_bind_param($log_stmt, "isis", $log_user_id, $log_action_type, $log_target_id, $log_details);
                     if (!mysqli_stmt_execute($log_stmt)) {
                         error_log("Audit Log Error: Failed to execute user creation log insertion (user_id: " . $log_user_id . ", target_id: " . $log_target_id . "): " . mysqli_stmt_error($log_stmt));
                     }
                     mysqli_stmt_close($log_stmt);
                } else {
                    error_log("Audit Log Error: Failed to prepare user creation log statement (user_id: " . $log_user_id . "): " . mysqli_error($link));
                }
                // --- END AUDIT LOGGING ---


                // User created successfully. Redirect to manage accounts page with success message.
                $_SESSION['message'] = "User account for " . htmlspecialchars($full_name) . " created successfully.";
                $_SESSION['message_type'] = "success";
                // Use PROJECT_SUBDIRECTORY for redirect
                header("location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php");
                exit();
            } else {
                $general_err = "Error creating user: " . mysqli_stmt_error($stmt);
                 error_log("Error creating user: " . mysqli_stmt_error($stmt));
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
             $general_err = "Database error preparing user insertion.";
             error_log("Database error preparing user insertion: " . mysqli_error($link));
        }
    } else {
         // If there were input errors, re-populate form variables from $_POST (already done at the top)
         $general_err = "Please fix the errors in the form."; // Set general error to indicate form issues
    }
}

// Set the page title
$pageTitle = "Create New User Account";

// Include the header (provides sidebar and opens main content column)
require_once(__DIR__ . '/../includes/header.php');

?>

    <!-- The content below will be placed inside the main-content-column div opened in header.php -->

    <h2>Create New User Account</h2>
    <p>Fill in this form to create a new user account.</p>

    <?php
    // Display session messages (e.g., from redirects) and general errors from THIS page logic
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
    <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/create_account.php" method="post">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($username_err); ?></span>
                </div>
                <div class="form-group">
                    <label>Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($full_name_err); ?></span>
                </div>
                 <div class="form-group">
                    <label>Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-control <?php echo (!empty($role_id_err)) ? 'is-invalid' : ''; ?>" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                             <option value="<?php echo htmlspecialchars($role['role_id']); ?>" <?php echo (strval($role_id) === strval($role['role_id'])) ? 'selected' : ''; ?>> <!-- Compare as strings for safety with '0' or empty -->
                                 <?php echo htmlspecialchars($role['role_name']); ?>
                             </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($role_id_err); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($password_err); ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($confirm_password_err); ?></span>
                </div>
                 <!-- Add space to align vertically -->
                 <div class="form-group" style="min-height: 38px;"></div>
            </div>
        </div>

        <div class="form-group text-center"> <!-- Center the buttons -->
            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus mr-1"></i> Create Account</button>
             <!-- Use PROJECT_SUBDIRECTORY for the cancel link -->
             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/manage_accounts.php" class="btn btn-secondary ml-2">Cancel</a>
        </div>
    </form>


<?php
// Include the footer (closes main content column and layout divs, closes DB connection)
require_once(__DIR__ . '/../includes/footer.php');
?>