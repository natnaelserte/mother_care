<?php
// Include authentication and authorization functions.
require_once(__DIR__ . '/../core/auth.php');

// Since auth.php includes language.php, the __() function is globally available.

// Ensure PROJECT_SUBDIRECTORY is defined. auth.php should handle this via paths.php
if (!defined('PROJECT_SUBDIRECTORY')) { define('PROJECT_SUBDIRECTORY', ''); }

require_login();
require_role('Administrator', 'views/dashboard.php');

global $link;

$user_id = $_GET['id'] ?? null;

if ($user_id && filter_var($user_id, FILTER_VALIDATE_INT)) {

    $sql = "UPDATE users SET is_active = 1 WHERE user_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_user_id);
        $param_user_id = $user_id;

        if (mysqli_stmt_execute($stmt)) {
             if (mysqli_stmt_affected_rows($stmt) > 0) {

                // --- AUDIT LOGGING: User enabled successfully ---
                $log_action_type = 'user_enabled';
                $log_target_id = $user_id;
                $log_details = null;
                $log_user_id = $_SESSION['user_id'];

                $log_sql = "INSERT INTO audit_log (user_id, action_type, target_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())";
                if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                     mysqli_stmt_bind_param($log_stmt, "isis", $log_user_id, $log_action_type, $log_target_id, $log_details);
                     if (!mysqli_stmt_execute($log_stmt)) { error_log("Audit Log Error: Failed to execute user enable log insertion (user_id: " . $log_user_id . ", target_id: " . $log_target_id . "): " . mysqli_stmt_error($log_stmt)); }
                     mysqli_stmt_close($log_stmt);
                } else { error_log("Audit Log Error: Failed to prepare user enable log statement (user_id: " . $log_user_id . "): " . mysqli_error($link)); }
                // --- END AUDIT LOGGING ---

                $_SESSION['message'] = __("user_account_enabled_success");
                $_SESSION['message_type'] = "success";
             } else {
                 $_SESSION['error_message'] = __("user_not_found_or_already_enabled");
                 $_SESSION['message_type'] = "warning";
                 error_log("Enable user failed: User ID " . $user_id . " not found or already active.");
             }
        } else {
            $_SESSION['error_message'] = __("error_enabling_user_account:") . " " . mysqli_stmt_error($stmt);
            $_SESSION['message_type'] = "danger";
            error_log("Database error enabling user ID " . $user_id . ": " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

    } else { $_SESSION['error_message'] = __("database_error_preparing_statement"); $_SESSION['message_type'] = "danger"; error_log("Database error preparing enable statement: " . mysqli_error($link)); }

} else { $_SESSION['error_message'] = __("invalid_or_missing_user_id"); $_SESSION['message_type'] = "warning"; error_log("Enable user failed: Invalid or missing user ID: " . $user_id); }

header("Location: " . PROJECT_SUBDIRECTORY . "/admin/manage_accounts.php");
exit();
?>