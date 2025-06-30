<?php
// Include authentication and authorization functions
require_once(__DIR__ . '/../core/auth.php'); // This includes paths.php and starts session

require_login(); // Ensure the user is logged in

// --- Calculate relative path to root ---
// This calculation assumes your project folder IS the web root.
// If your project is in a subdirectory (e.g. http://localhost/ANC/),
// you need to ensure PROJECT_SUBDIRECTORY is set correctly in config/paths.php
$script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /views
$depth = count(explode('/', trim($script_dir, '/')));
$base_url = str_repeat('../', $depth);
// --- End calculation ---


// Access user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];

// Include database connection ONLY if needed for the role's dashboard content
// For Admin, we need it to fetch stats
if (has_role('Administrator')) {
    require_once(__DIR__ . '/../config/db.php');

    // --- Fetch Statistics (Admin Functionality) ---
    $total_users = 0;
    $total_mothers = 0;
    $total_anc_records = 0;

    // Query to get total users
    $sql_users = "SELECT COUNT(*) AS total FROM users";
    if ($result_users = mysqli_query($link, $sql_users)) {
        $row_users = mysqli_fetch_assoc($result_users);
        $total_users = $row_users['total'];
        mysqli_free_result($result_users);
    } else {
        error_log("Error fetching total users: " . mysqli_error($link));
    }

    // Query to get total mothers
    $sql_mothers = "SELECT COUNT(*) AS total FROM mothers";
    if ($result_mothers = mysqli_query($link, $sql_mothers)) {
        $row_mothers = mysqli_fetch_assoc($result_mothers);
        $total_mothers = $row_mothers['total'];
        mysqli_free_result($result_mothers);
    } else {
         error_log("Error fetching total mothers: " . mysqli_error($link));
    }

    // Query to get total ANC records
    $sql_anc_records = "SELECT COUNT(*) AS total FROM anc_records";
    if ($result_anc_records = mysqli_query($link, $sql_anc_records)) {
        $row_anc_records = mysqli_fetch_assoc($result_anc_records);
        $total_anc_records = $row_anc_records['total'];
        mysqli_free_result($result_anc_records);
    } else {
        error_log("Error fetching total ANC records: " . mysqli_error($link));
    }

    // Close database connection after fetching stats (important!)
    mysqli_close($link);
}


// Set the page title BEFORE including the header
$pageTitle = "Dashboard";

// Include the header for logged-in users
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally

?>

    <!-- Content specific to the dashboard goes here -->

    <h2 class="welcome-heading text-center">Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
    <p class="text-center text-muted mb-4">Logged in as: <?php echo htmlspecialchars($role_name); ?></p>

    <?php
    // Display error/success messages from session
    if (isset($_SESSION['message'])) {
        $msg_class = $_SESSION['message_type'] ?? 'info'; // default to info if type not set
        echo '<div class="alert alert-' . $msg_class . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
        unset($_SESSION['message_type']);
    }
     // Display authorization error message if redirected
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']); // Clear the message after displaying
    }
    ?>


    <?php if (has_role('Administrator')): ?>

        <!-- Quick Statistics Section (Admin Only) -->
        <div class="row card-stats">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                         <i class="fas fa-users stats-icon"></i>
                        <div class="stats-value"><?php echo htmlspecialchars($total_users); ?></div>
                        <div class="stats-label">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-female stats-icon" style="color: #e83e8c;"></i> <!-- Pink icon for mothers -->
                        <div class="stats-value"><?php echo htmlspecialchars($total_mothers); ?></div>
                        <div class="stats-label">Registered Mothers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                 <div class="card">
                    <div class="card-body">
                         <i class="fas fa-notes-medical stats-icon" style="color: #20c997;"></i> <!-- Teal icon for records -->
                        <div class="stats-value"><?php echo htmlspecialchars($total_anc_records); ?></div>
                        <div class="stats-label">ANC Visit Records</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Actions Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card card-action">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">User Management</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <!-- Use $base_url for internal links -->
                        <a href="<?php echo $base_url; ?>admin/manage_accounts.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users action-icon"></i> Manage All Accounts
                        </a>
                         <a href="<?php echo $base_url; ?>admin/create_account.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-plus action-icon"></i> Create New Account
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                 <div class="card card-action">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">System Settings</h5>
                    </div>
                    <div class="list-group list-group-flush">
                         <!-- Use $base_url for internal links -->
                        <a href="<?php echo $base_url; ?>admin/configure_system.php" class="list-group-item list-group-item-action disabled" aria-disabled="true">
                            <i class="fas fa-cogs action-icon"></i> Configure System Settings (Placeholder)
                        </a>
                         <a href="<?php echo $base_url; ?>admin/manage_roles.php" class="list-group-item list-group-item-action disabled" aria-disabled="true">
                            <i class="fas fa-user-tag action-icon"></i> Manage Roles (Placeholder)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section (Example) -->
         <div class="row mt-4">
            <div class="col-md-12">
                 <div class="card card-action">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Reports & Analytics</h5>
                    </div>
                    <div class="list-group list-group-flush">
                         <!-- Use $base_url for internal links -->
                        <a href="<?php echo $base_url; ?>admin/reports.php?type=mothers" class="list-group-item list-group-item-action disabled" aria-disabled="true">
                            <i class="fas fa-chart-bar action-icon"></i> View Mother Registration Reports (Placeholder)
                        </a>
                         <a href="<?php echo $base_url; ?>admin/reports.php?type=visits" class="list-group-item list-group-item-action disabled" aria-disabled="true">
                            <i class="fas fa-chart-line action-icon"></i> View ANC Visit Reports (Placeholder)
                        </a>
                    </div>
                </div>
            </div>
        </div>


    <?php elseif (has_role('Data Clerk')): ?>
        <!-- Data Clerk Dashboard Content -->
        <div class="card card-action">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Data Clerk Actions</h5>
            </div>
            <div class="list-group list-group-flush">
                <!-- Use $base_url for internal links -->
                <a href="<?php echo $base_url; ?>dataclerk/register_mother.php" class="list-group-item list-group-item-action"><i class="fas fa-user-plus mr-2 action-icon"></i> Register New Mother</a>
                <a href="<?php echo $base_url; ?>dataclerk/view_mothers.php" class="list-group-item list-group-item-action"><i class="fas fa-female mr-2 action-icon"></i> View Registered Mothers</a>
                <!-- Add other Data Clerk links -->
            </div>
        </div>

    <?php elseif (has_role('Midwife')): ?>
         <!-- Midwife Dashboard Content -->
         <div class="card card-action">
             <div class="card-header bg-primary text-white">
                 <h5 class="mb-0">Midwife Actions</h5>
             </div>
             <div class="list-group list-group-flush">
                 <!-- Use $base_url for internal links -->
                 <a href="<?php echo $base_url; ?>midwife/view_mothers.php" class="list-group-item list-group-item-action"><i class="fas fa-female mr-2 action-icon"></i> View / Manage Mothers & Records</a>
                 <!-- Add other Midwife links if they are top-level actions not linked from mother_details -->
             </div>
         </div>

    <?php elseif (has_role('Laboratorist')): ?>
        <!-- Laboratorist Dashboard Content -->
        <div class="card card-action">
             <div class="card-header bg-primary text-white">
                 <h5 class="mb-0">Laboratorist Actions</h5>
             </div>
             <div class="list-group list-group-flush">
                  <!-- Use $base_url for internal links -->
                 <a href="<?php echo $base_url; ?>laboratorist/view_lab_requests.php" class="list-group-item list-group-item-action disabled" aria-disabled="true"><i class="fas fa-vials mr-2 action-icon"></i> View Lab Requests (Placeholder)</a>
                  <!-- Add other Laboratorist links -->
             </div>
         </div>

    <?php elseif (has_role('Radiologist')): ?>
        <!-- Radiologist Dashboard Content -->
         <div class="card card-action">
             <div class="card-header bg-primary text-white">
                 <h5 class="mb-0">Radiologist Actions</h5>
             </div>
             <div class="list-group list-group-flush">
                  <!-- Use $base_url for internal links -->
                 <a href="<?php echo $base_url; ?>radiologist/view_ultrasound_requests.php" class="list-group-item list-group-item-action disabled" aria-disabled="true"><i class="fas fa-x-ray mr-2 action-icon"></i> View Ultrasound Requests (Placeholder)</a>
                 <!-- Add other Radiologist links -->
             </div>
         </div>

    <?php else: ?>
        <!-- Default content for any other roles -->
        <div class="alert alert-info text-center">
            <p>Welcome to your dashboard.</p>
            <p>Content for your role is being developed.</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <!-- Use $base_url for the logout link -->
        <a href="<?php echo $base_url; ?>views/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt mr-1"></i> Sign Out</a>
    </div>


<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>