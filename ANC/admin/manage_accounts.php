<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php (defines PROJECT_SUBDIRECTORY), config/config.php,
// connects DB, AND INCLUDES core/language.php (making __() function available).
require_once(__DIR__ . '/../core/auth.php');

// Since auth.php includes language.php, the __() function should be available globally.
// No need to explicitly include language.php again here.

// Ensure PROJECT_SUBDIRECTORY is defined. auth.php should handle this via paths.php
if (!defined('PROJECT_SUBDIRECTORY')) {
     // Fallback define, ideally paths.php included by auth.php handles this
     define('PROJECT_SUBDIRECTORY', '');
}


require_login(); // Ensure user is logged in
// require_role will redirect to dashboard if not Admin, using PROJECT_SUBDIRECTORY
// The message set by require_role is already translated in auth.php using __()
require_role('Administrator', 'views/dashboard.php');

// $link is available globally from core/auth.php
global $link;
// We also need the global $lang array and other language variables if accessing them directly,
// but generally using __() is sufficient after auth.php is included.
// global $lang, $supported_languages, $current_language, $current_language_name;


// Set the page title BEFORE including the header (using __())
// Using the key 'manage_accounts' for the menu item, and 'manage_user_accounts' for the heading.
$pageTitle = __("manage_user_accounts");

// Include the header (adjust path to includes)
// The header.php now handles its own translations for static elements
require_once(__DIR__ . '/../includes/header.php');


// Fetch users from the database (keep this logic)
$sql = "SELECT u.user_id, u.username, u.full_name, r.role_name, u.is_active, u.created_at
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.created_at DESC"; // Order by creation date

$result = mysqli_query($link, $sql);

$users = [];
$fetch_error_message = ""; // Use a separate variable for fetch error

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
} else {
    // Translate the error message here as it's user-facing
    $fetch_error_message = __("error_fetching_users") . " " . mysqli_error($link); // Translate the first part, append DB error
    error_log("Error fetching users for manage_accounts: " . mysqli_error($link));
}

// Do NOT close connection here, it's closed in footer.php (as per your create_account.php structure)

?>

    <!-- Content specific to manage accounts goes here -->

    <h2><?php echo __("manage_user_accounts"); ?></h2> <!-- Translate heading -->
    <p>
        <!-- Use PROJECT_SUBDIRECTORY/admin/create_account.php -->
        <!-- Translate link text -->
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/create_account.php" class="btn btn-success"><?php echo __("add_new_user"); ?></a>
         <!-- Back to Dashboard link is now in the header -->
    </p>

    <?php if (!empty($fetch_error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error_message); ?></div>
    <?php endif; ?>

    <?php
    // Display error/success messages from session (if redirected here from action pages)
    // These messages should have already been translated using __() when they were set
     if (isset($_SESSION['message'])) {
         $msg_class = $_SESSION['message_type'] ?? 'info'; // Default to info if type not set
         echo '<div class="alert alert-' . htmlspecialchars($msg_class) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
         unset($_SESSION['message']);
         unset($_SESSION['message_type']);
     }
     if (isset($_SESSION['error_message'])) {
         echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; // Error message is already translated/set
         unset($_SESSION['error_message']);
     }
    ?>


    <?php if (empty($users)): ?>
        <!-- Translate message -->
        <div class="alert alert-info"><?php echo __("no_users_found"); ?></div>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?php echo __("id"); ?></th> <!-- Translate table headers -->
                    <th><?php echo __("username"); ?></th>
                    <th><?php echo __("full_name"); ?></th>
                    <th><?php echo __("role"); ?></th>
                    <th><?php echo __("active"); ?></th> <!-- Translate 'Active' header -->
                    <th><?php echo __("created_at"); ?></th> <!-- Translate header -->
                    <th><?php echo __("actions"); ?></th> <!-- Translate header -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <!-- Translate fetched role name for display -->
                    <td><?php echo __($user['role_name']); ?></td>
                    <!-- Translate Yes/No -->
                    <td><?php echo $user['is_active'] ? __("yes") : __("no"); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                    <td>
                        <!-- Link text translated -->
                        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/edit_account.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-info"><?php echo __("edit"); ?></a>
                        <?php if ($user['is_active']): ?>
                            <!-- Link text translated -->
                            <!-- Confirmation message text translated (use sprintf for placeholder) -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/disable_account.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo htmlspecialchars(addslashes(sprintf(__("are_you_sure_disable_account"), $user['username']))); ?>');"><?php echo __("disable"); ?></a>
                        <?php else: ?>
                             <!-- Link text translated -->
                             <!-- Confirmation message text translated (use sprintf for placeholder) -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/enable_account.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-success" onclick="return confirm('<?php echo htmlspecialchars(addslashes(sprintf(__("are_you_sure_enable_account"), $user['username']))); ?>');"><?php echo __("enable"); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php
// Include the footer (adjust path) - Assumes footer.php closes the DB connection
require_once(__DIR__ . '/../includes/footer.php');
?>