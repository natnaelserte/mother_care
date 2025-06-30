<?php
// Start the session (must be at the very top of any file using sessions)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include application paths configuration
require_once(__DIR__ . '/../config/paths.php');

// --- INCLUDE LANGUAGE LOGIC HERE ---
require_once(__DIR__ . '/language.php');
// --- END INCLUDE LANGUAGE LOGIC ---


// Include database connection
require_once(__DIR__ . '/../config/db.php');

// Access global variables
global $link;
global $lang;
global $supported_languages;


/**
 * Authenticates a user.
 * @param string $username
 * @param string $password
 * @return bool True on successful login, false otherwise.
 */
function authenticate_user($username, $password) {
    global $link;

    $sql = "SELECT user_id, username, password_hash, full_name, role_id, is_active FROM users WHERE username = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_username);
        $param_username = $username;

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $user_id, $username, $hashed_password, $full_name, $role_id, $is_active);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                         if ($is_active) {
                             session_regenerate_id(true);

                             $_SESSION["loggedin"] = true;
                             $_SESSION["user_id"] = $user_id;
                             $_SESSION["username"] = $username;
                             $_SESSION["full_name"] = $full_name;
                             $_SESSION["role_id"] = $role_id;

                             $role_sql = "SELECT role_name FROM roles WHERE role_id = ?";
                             if ($role_stmt = mysqli_prepare($link, $role_sql)) {
                                 mysqli_stmt_bind_param($role_stmt, "i", $role_id);
                                 if (mysqli_stmt_execute($role_stmt)) {
                                     mysqli_stmt_store_result($role_stmt);
                                     if (mysqli_stmt_num_rows($role_stmt) == 1) {
                                         mysqli_stmt_bind_result($role_stmt, $role_name);
                                         if (mysqli_stmt_fetch($role_stmt)) {
                                             $_SESSION["role_name"] = $role_name; // Store English name
                                         } else { error_log("Auth Error: Could not fetch role name result for ID " . $role_id . "."); $_SESSION["role_name"] = "Unknown Role"; }
                                     } else { error_log("Auth Error: Role ID " . $role_id . " not found in roles table during login."); $_SESSION["role_name"] = "Unknown Role"; }
                                 } else { error_log("Auth Error: Could not execute role name query during login: " . mysqli_stmt_error($role_stmt)); $_SESSION["role_name"] = "Unknown Role"; }
                                 mysqli_stmt_close($role_stmt);
                             } else { error_log("Auth Error: Could not prepare role name query during login: " . mysqli_error($link)); $_SESSION["role_name"] = "Unknown Role"; }

                             mysqli_stmt_close($stmt);
                             return true;
                         } else {
                             mysqli_stmt_close($stmt);
                             return false; // Login failed (account inactive)
                         }
                    } else {
                         mysqli_stmt_close($stmt);
                        return false; // Login failed (wrong password)
                    }
                } else { mysqli_stmt_close($stmt); return false; }
            } else { mysqli_stmt_close($stmt); return false; }
        } else { error_log("Auth Error: Could not execute user select statement during login: " . mysqli_stmt_error($stmt)); return false; }

    } else { error_log("Auth Error: Could not prepare user select statement during login: " . mysqli_error($link)); return false; }


}


/**
 * Logs out the current user.
 */
function logout_user() {
    $_SESSION = array();
    session_destroy();
    header("location: " . PROJECT_SUBDIRECTORY . "/views/login.php");
    exit;
}

/**
 * Checks if the user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_loggedin() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

/**
 * Checks if the logged-in user has a specific role.
 * Compares against the *English* role name stored in session.
 * @param string $required_role_name The English name of the role to check against.
 * @return bool True if the user has the required role, false otherwise or if not logged in.
 */
function has_role($required_role_name) {
    if (is_loggedin() && isset($_SESSION["role_name"])) {
        return $_SESSION["role_name"] === $required_role_name;
    }
    return false;
}

/**
 * Redirects to the login page if the user is not logged in.
 */
function require_login() {
    if (!is_loggedin()) {
        header("location: " . PROJECT_SUBDIRECTORY . "/views/login.php");
        exit;
    }
}

/**
 * Redirects to a specified page if the user does not have the required role.
 * @param string $required_role_name The English name of the role required.
 * @param string $redirect_page The page to redirect to if authorization fails (e.g., 'dashboard.php').
 */
function require_role($required_role_name, $redirect_page = 'dashboard.php') {
    if (!has_role($required_role_name)) {
        // Translate the permission message using __()
        $_SESSION['error_message'] = __("you_do_not_have_permission"); // Use the key
        $_SESSION['message_type'] = "warning";
        header("location: " . PROJECT_SUBDIRECTORY . "/views/" . $redirect_page);
        exit;
    }
}
?>