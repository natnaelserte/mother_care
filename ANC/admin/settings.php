<?php
// Include authentication and authorization functions.
require_once(__DIR__ . '/../core/auth.php');

// Since auth.php includes language.php, the __() function is globally available.

// Ensure PROJECT_SUBDIRECTORY is defined. auth.php should handle this via paths.php
if (!defined('PROJECT_SUBDIRECTORY')) { define('PROJECT_SUBDIRECTORY', ''); }

require_login();
require_role('Administrator', 'views/dashboard.php');

global $link;


$pageTitle = __("admin_settings");

$current_password = "";
$new_password = "";
$confirm_new_password = "";

$current_password_err = $new_password_err = $confirm_new_password_err = $general_err = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST["current_password"] ?? "");
    $new_password = trim($_POST["new_password"] ?? "");
    $confirm_new_password = trim($_POST["confirm_new_password"] ?? "");

    if (empty($current_password)) { $current_password_err = __("please_enter_current_password"); }
    if (empty($new_password)) { $new_password_err = __("please_enter_new_password"); }
    elseif (strlen($new_password) < 6) { $new_password_err = __("new_password_short"); }

    if (empty(trim($confirm_new_password))) { $confirm_new_password_err = __("please_confirm_new_password"); }
    else { if (empty($new_password_err) && ($new_password != $confirm_new_password)) { $confirm_new_password_err = __("passwords_do_not_match"); } }


    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_new_password_err) && empty($general_err)) {

        $sql = "SELECT password_hash FROM users WHERE user_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $param_user_id);
            $param_user_id = $_SESSION["user_id"];

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $password_hash);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($current_password, $password_hash)) {
                            $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                            if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $param_user_id);

                                if (mysqli_stmt_execute($update_stmt)) {
                                    // --- AUDIT LOGGING: Admin password changed ---
                                    $log_action_type = 'password_changed';
                                    $log_target_id = $param_user_id;
                                    $log_details = null;
                                    $log_user_id = $_SESSION['user_id'];

                                    $log_sql = "INSERT INTO audit_log (user_id, action_type, target_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())";
                                    if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                                         mysqli_stmt_bind_param($log_stmt, "isis", $log_user_id, $log_action_type, $log_target_id, $log_details);
                                         if (!mysqli_stmt_execute($log_stmt)) { error_log("Audit Log Error: Failed to execute password change log insertion (user_id: " . $log_user_id . ", target_id: " . $log_target_id . "): " . mysqli_stmt_error($log_stmt)); }
                                         mysqli_stmt_close($log_stmt);
                                    } else { error_log("Audit Log Error: Failed to prepare password change log statement (user_id: " . $log_user_id . "): " . mysqli_error($link)); }
                                    // --- END AUDIT LOGGING ---

                                    $success_message = __("password_updated_success");
                                    $current_password = $new_password = $confirm_new_password = "";
                                } else { $general_err = __("error_updating_password") . " " . mysqli_stmt_error($update_stmt); error_log("Error updating admin password (user_id: " . $_SESSION['user_id'] . "): " . mysqli_stmt_error($update_stmt)); }
                                mysqli_stmt_close($update_stmt);
                            } else { $general_err = __("database_error_preparing_update_statement"); error_log("Database error preparing admin password update: " . mysqli_error($link)); }
                        } else { $current_password_err = __("current_password_incorrect"); }
                    } else { $general_err = __("could_not_process_user_info"); error_log("Admin password change error: Could not fetch result variables for user_id " . $_SESSION['user_id']); }
                } else { $general_err = __("could_not_retrieve_user_info"); error_log("Admin password change error: Could not find user_id " . $_SESSION['user_id']); }
            } else { $general_err = __("database_error_fetching_user_info"); error_log("Database error fetching admin password hash: " . mysqli_stmt_error($stmt)); }
            mysqli_stmt_close($stmt);
        } else { $general_err = __("database_error_preparing_fetch"); error_log("Database error preparing admin password hash fetch: " . mysqli_error($link)); }
    } else {
         if (empty($general_err)) { $general_err = __("please_fix_errors_in_form"); }
    }
}

require_once(__DIR__ . '/../includes/header.php');
?>

    <h2><?php echo __("admin_settings"); ?></h2>

    <?php
     if (isset($_SESSION['message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>'; unset($_SESSION['message']); }
     if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
    ?>

    <?php if (!empty($general_err)): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($general_err); ?></div> <?php endif; ?>
    <?php if (!empty($success_message)): ?> <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div> <?php endif; ?>


    <div class="card">
        <div class="card-header"><?php echo __("change_your_password"); ?></div>
        <div class="card-body">
            <p><?php echo __("fill_form_to_change_password"); ?></p>
            <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/settings.php" method="post">
                <div class="form-group">
                    <label><?php echo __("current_password"); ?> <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($current_password); ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($current_password_err); ?></span>
                </div>
                 <div class="form-group">
                    <label><?php echo __("new_password"); ?> <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_password); ?>" required>
                     <span class="invalid-feedback"><?php echo htmlspecialchars($new_password_err); ?></span>
                     <small class="form-text text-muted"><?php echo __("new_password_min_chars_hint"); ?></small>
                </div>
                <div class="form-group">
                    <label><?php echo __("confirm_new_password"); ?> <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control <?php echo (!empty($confirm_new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($confirm_new_password); ?>" required>
                     <span class="invalid-feedback"><?php echo htmlspecialchars($confirm_new_password_err); ?></span>
                </div>
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key mr-1"></i> <?php echo __("change_password_button"); ?></button>
                </div>
            </form>
        </div>
    </div>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>