<?php
// Include authentication and authorization functions.
require_once(__DIR__ . '/../core/auth.php');

// Since auth.php includes language.php, the __() function is globally available.

// Ensure PROJECT_SUBDIRECTORY is defined. auth.php should handle this via paths.php
if (!defined('PROJECT_SUBDIRECTORY')) { define('PROJECT_SUBDIRECTORY', ''); }

require_login();
require_role('Administrator', 'views/dashboard.php');

global $link;


$pageTitle = __("edit_user_account");

$user_id_param = $_GET['id'] ?? null;
$edit_user_id = null;

$username = "";
$full_name = "";
$role_id = "";
$is_active = 1;

$new_password = "";
$confirm_new_password = "";
$new_password_err = $confirm_new_password_err = "";


$username_err = $full_name_err = $role_id_err = $general_err = "";
$db_role_invalid_warning = "";


$user_data = null;

$roles_sql = "SELECT role_id, role_name FROM roles ORDER BY role_name";
$roles = [];
if ($roles_result = mysqli_query($link, $roles_sql)) {
    while($role_row = mysqli_fetch_assoc($roles_result)) { $roles[] = $role_row; }
    mysqli_free_result($roles_result);
} else { $general_err = __("Error fetching roles:") . " " . mysqli_error($link); error_log("Error fetching roles for edit_account: " . mysqli_error($link)); $roles = []; }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $edit_user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role_id = $_POST['role_id'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_new_password = trim($_POST['confirm_new_password'] ?? '');

    if (empty($edit_user_id) || !filter_var($edit_user_id, FILTER_VALIDATE_INT)) {
        $general_err = __("invalid_or_missing_user_id");
        error_log("Edit account POST failed: Invalid user_id: " . $edit_user_id);
        $_SESSION['error_message'] = $general_err; $_SESSION['message_type'] = "danger";
        header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit();
    }

    if (empty($username)) { $username_err = __("please_enter_username"); }
    if (empty($full_name)) { $full_name_err = __("please_enter_full_name"); }

    if (empty($roles)) {
         if (!empty($role_id)) { $role_id_err = __("roles_could_not_be_loaded"); }
         else { $role_id_err = __("select_role"); }
    } elseif (empty($role_id)) { $role_id_err = __("select_role"); }
    else {
         $role_id_int = intval($role_id);
         $role_exists = false;
         foreach ($roles as $role) { if ($role['role_id'] == $role_id_int) { $role_exists = true; break; } }
         if (!$role_exists) { $role_id_err = __("invalid_role_selected"); error_log("Edit account POST failed: Submitted role_id (" . $_POST['role_id'] . ") does not exist in fetched roles."); }
         else { $role_id = $role_id_int; }
    }

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) { $new_password_err = __("new_password_short"); }
        if (empty(trim($confirm_new_password))) { $confirm_new_password_err = __("please_confirm_new_password"); }
        else { if (empty($new_password_err) && ($new_password != $confirm_new_password)) { $confirm_new_password_err = __("passwords_do_not_match"); } }
    } else { $confirm_new_password_err = ""; }


    if (empty($username_err)) {
        $check_user_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        if ($stmt_check = mysqli_prepare($link, $check_user_sql)) {
            mysqli_stmt_bind_param($stmt_check, "si", $param_username, $param_user_id);
            $param_username = $username; $param_user_id = $edit_user_id;
            if (mysqli_stmt_execute($stmt_check)) {
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) > 0) { $username_err = __("this_username_is_already_taken"); }
            } else { $general_err = __("database_error_preparing_username_check"); error_log("Error checking username uniqueness on edit: " . mysqli_stmt_error($stmt_check)); }
            mysqli_stmt_close($stmt_check);
        } else { $general_err = __("database_error_preparing_username_check"); error_log("Database error preparing username check on edit: " . mysqli_error($link)); }
    }

    if (empty($username_err) && empty($full_name_err) && empty($role_id_err) && empty($new_password_err) && empty($confirm_new_password_err) && empty($general_err)) {
         if ($edit_user_id == $_SESSION['user_id'] && $is_active == 0) {
              $general_err = __("you_cannot_disable_own_account");
              $is_active = 1;
         } else {
            $sql_parts = ["username = ?", "full_name = ?", "role_id = ?", "is_active = ?"];
            $bind_types = "ssii";
            $bind_params = [&$username, &$full_name, &$role_id, &$is_active];

            $password_updated = !empty($new_password);

            if ($password_updated) {
                $sql_parts[] = "password_hash = ?";
                $bind_types .= "s";
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $bind_params[] = &$hashed_password;
            }

            $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE user_id = ?";
            $bind_types .= "i";
            $bind_params[] = &$edit_user_id;

            if ($stmt = mysqli_prepare($link, $sql)) {
                array_unshift($bind_params, $stmt, $bind_types);
                call_user_func_array('mysqli_stmt_bind_param', $bind_params);

                if (mysqli_stmt_execute($stmt)) {
                    // --- AUDIT LOGGING: User updated successfully ---
                    $log_action_type = 'user_updated';
                    $log_target_id = $edit_user_id;
                    $log_details = null; // Simple log, could be more detailed if needed
                    $log_user_id = $_SESSION['user_id'];

                    $log_sql = "INSERT INTO audit_log (user_id, action_type, target_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())";
                    if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                         mysqli_stmt_bind_param($log_stmt, "isis", $log_user_id, $log_action_type, $log_target_id, $log_details);
                         if (!mysqli_stmt_execute($log_stmt)) { error_log("Audit Log Error: Failed to execute user update log insertion (user_id: " . $log_user_id . ", target_id: " . $log_target_id . "): " . mysqli_stmt_error($log_stmt)); }
                         mysqli_stmt_close($log_stmt);
                    } else { error_log("Audit Log Error: Failed to prepare user update log statement (user_id: " . $log_user_id . "): " . mysqli_error($link)); }
                    // --- END AUDIT LOGGING ---

                    $_SESSION['message'] = sprintf(__("user_account_updated_success"), htmlspecialchars($full_name));
                    $_SESSION['message_type'] = "success";
                    mysqli_stmt_close($stmt);
                    header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit();
                } else { $general_err = __("error_updating_password") . " " . mysqli_stmt_error($stmt); error_log("Error updating user on edit: " . mysqli_stmt_error($stmt)); }
                mysqli_stmt_close($stmt);
            } else { $general_err = __("database_error_preparing_update_statement"); error_log("Database error preparing update on edit: " . mysqli_error($link)); }
         }
    } else {
         if (empty($general_err)) { $general_err = __("please_fix_errors_in_form"); }
         $user_id_param = $edit_user_id;
    }
} else {
    $edit_user_id = $user_id_param;
}


if ($edit_user_id && filter_var($edit_user_id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT user_id, username, full_name, role_id, is_active FROM users WHERE user_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_user_id);
        $param_user_id = $edit_user_id;

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if ($_SERVER["REQUEST_METHOD"] != "POST" || (!empty($general_err) || !empty($username_err) || !empty($full_name_err) || !empty($role_id_err) || !empty($new_password_err) || !empty($confirm_new_password_err))) {
                    $username = $user_data['username'];
                    $full_name = $user_data['full_name'];
                    $role_id = $user_data['role_id'];
                    $is_active = $user_data['is_active'];
                }

                 $fetched_role_valid = false;
                 $user_data_role_id_int = intval($user_data['role_id']);
                 if (!empty($roles)) {
                     foreach ($roles as $role) { if ($role['role_id'] == $user_data_role_id_int) { $fetched_role_valid = true; break; } }
                 } else { $fetched_role_valid = true; } // Assume valid if roles not fetched

                 if (!$fetched_role_valid) {
                      $db_role_invalid_warning = sprintf(__("warning_assigned_role_not_exists"), htmlspecialchars($user_data['role_id']));
                 }

            } else {
                $_SESSION['error_message'] = __("user_not_found"); $_SESSION['message_type'] = "warning";
                mysqli_stmt_close($stmt); header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit();
            }
        } else {
             $general_err = __("database_error_fetching_user_details") . " " . mysqli_stmt_error($stmt); error_log("Error fetching user details for edit: " . mysqli_stmt_error($stmt));
             $_SESSION['error_message'] = __("could_not_retrieve_user_details") . " " . htmlspecialchars($general_err); $_SESSION['message_type'] = "danger";
             mysqli_stmt_close($stmt); header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit();
        }
        mysqli_stmt_close($stmt);
    } else {
         if (empty($general_err)) { $_SESSION['error_message'] = __("database_error_preparing_fetch_statement"); $_SESSION['message_type'] = "danger"; header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit(); }
    }
} else {
     if (empty($general_err)) { $_SESSION['error_message'] = __("invalid_user_id_provided"); $_SESSION['message_type'] = "warning"; header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php"); exit(); }
}


require_once(__DIR__ . '/../includes/header.php');
?>

    <h2><?php echo __("edit_user_account"); ?></h2>

    <?php
     if (isset($_SESSION['message'])) { echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>'; unset($_SESSION['message']); }
     if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
    ?>

    <?php if (!empty($general_err)): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($general_err); ?></div> <?php endif; ?>
    <?php if (!empty($db_role_invalid_warning)): ?> <div class="alert alert-warning"><?php echo htmlspecialchars($db_role_invalid_warning); ?></div> <?php endif; ?>

    <?php if ($user_data): ?>
    <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/edit_account.php" method="post">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['user_id']); ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="username"><?php echo __("username"); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($username_err); ?></span>
                </div>
                <div class="form-group">
                    <label for="full_name"><?php echo __("full_name"); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($full_name_err); ?></span>
                </div>
                <div class="form-group">
                    <label for="role_id"><?php echo __("role"); ?> <span class="text-danger">*</span></label>
                    <select name="role_id" id="role_id" class="form-control <?php echo (!empty($role_id_err)) ? 'is-invalid' : ''; ?>" required <?php echo empty($roles) ? 'disabled' : ''; ?>>
                        <option value=""><?php echo __("select_role"); ?></option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo htmlspecialchars($role['role_id']); ?>" <?php echo (strval($role['role_id']) === strval($role_id)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <span class="invalid-feedback"><?php echo htmlspecialchars($role_id_err); ?></span>
                     <?php if (empty($roles) && empty($general_err)): ?>
                         <small class="form-text text-danger"><?php echo __("roles_could_not_be_loaded"); ?></small>
                     <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="form-group">
                    <label for="new_password"><?php echo __("new_password"); ?> (<?php echo __("optional"); ?>)</label>
                    <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($new_password); ?>">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($new_password_err); ?></span>
                     <small class="form-text text-muted"><?php echo __("leave_blank_no_password_change"); ?></small>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password"><?php echo __("confirm_new_password"); ?></label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control <?php echo (!empty($confirm_new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($confirm_new_password); ?>">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($confirm_new_password_err); ?></span>
                </div>
                 <div class="form-group form-check mt-4 pt-2">
                     <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?php echo $is_active ? 'checked' : ''; ?>
                            <?php if ($user_data['user_id'] == $_SESSION['user_id']) echo 'disabled'; ?> >
                     <label class="form-check-label" for="is_active"><?php echo __("account_is_active"); ?></label>
                     <?php if ($user_data['user_id'] == $_SESSION['user_id']): ?>
                         <small class="form-text text-muted"><?php echo __("you_cannot_disable_own_account"); ?></small>
                     <?php endif; ?>
                 </div>
            </div>
        </div>

        <div class="form-group text-center mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> <?php echo __("save_changes"); ?></button>
            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/manage_accounts.php" class="btn btn-secondary ml-2"><?php echo __("cancel"); ?></a>
        </div>
    </form>
    <?php endif; ?>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>