<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (midwife is 2 levels deep)

// IMPORTANT: Detect if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$is_ajax = $is_ajax || (isset($_GET['ajax_request']) && $_GET['ajax_request'] === 'true');


// If it's NOT an AJAX request (i.e., a direct full page load)
if (!$is_ajax) {
    // Perform full authentication and role check for direct access
    require_login();
    require_role('Midwife', 'views/dashboard.php'); // Redirects to dashboard if not Midwife

    // Ensure database connection is available (core/auth.php provides $link globally)
    global $link; // $link is available after require_once core/auth.php

    // Set the page title for the full page header
    $pageTitle = "Midwife - Registered Mothers";

    // Include the header (sets up the layout, includes sidebar, opens main content area)
    require_once(__DIR__ . '/../includes/header.php'); // Adjust path based on depth

    // Display session messages set by redirects *to* this page
    if (isset($_SESSION['message'])) {
        $msg_class = $_SESSION['message_type'] ?? 'info';
        echo '<div class="alert alert-' . $msg_class . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }

} else {
    // If it IS an AJAX request
    // Perform lightweight checks - assume user is logged in and has role from the calling page (dashboard)
     if (!is_loggedin() || !has_role('Midwife')) {
         http_response_code(403); // Forbidden
         echo '<div class="alert alert-danger">Access denied. Please refresh the dashboard.</div>';
         exit(); // Stop execution for unauthorized AJAX
     }

    // Ensure database connection is available (core/auth.php provides $link globally)
    global $link; // $link is available after require_once core/auth.php

    // No header/footer/title needed for AJAX fragments
}


// --- START OF PAGE-SPECIFIC PHP LOGIC AND HTML CONTENT ---
// This part is executed for BOTH full page loads and AJAX requests

// This page does NOT require a mother_id
$is_mother_id_required = false;
$content_error = null; // No content error expected for this page unless DB fetch fails

// Fetch all mothers from the database
$sql = "SELECT mother_id, first_name, last_name, date_of_birth, phone_number, registration_date
        FROM mothers
        ORDER BY registration_date DESC";

$result = mysqli_query($link, $sql);

$mothers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $mothers[] = $row;
    }
    mysqli_free_result($result);
} else {
    $content_error = "Error fetching mothers list: " . mysqli_error($link); // Set content error instead of session error
     error_log("Error fetching mothers list: " . mysqli_error($link)); // Log the error
}

// --- HTML CONTENT FOR THE MAIN AREA ---

// Display the error message if set (happens if DB fetch fails)
if ($content_error !== null) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($content_error) . '</div>';
} else {
     // ONLY display the normal page content (table) if there's no content error

     // Example: H2 heading and description for this page
     ?>
     <h2>Registered Mothers</h2>
     <p>List of mothers registered in the system.</p>

     <p>
         <!-- Link to register a new mother - Use PROJECT_SUBDIRECTORY -->
         <!-- Note: Clicking this link will trigger a FULL page load by default -->
         <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/register_mother.php" class="btn btn-success"><i class="fas fa-user-plus mr-1"></i> Register New Mother</a>
     </p>

     <?php if (empty($mothers)): ?>
         <div class="alert alert-info">No mothers registered yet.</div>
     <?php else: ?>
         <table class="table table-striped table-bordered table-hover">
             <thead>
                 <tr>
                     <th>ID</th>
                     <th>Name</th>
                     <th>Date of Birth</th>
                     <th>Phone</th>
                     <th>Registration Date</th>
                     <th>Action</th>
                 </tr>
             </thead>
             <tbody>
                 <?php foreach ($mothers as $mother): ?>
                 <tr>
                     <td><?php echo htmlspecialchars($mother['mother_id']); ?></td>
                     <td><?php echo htmlspecialchars($mother['first_name'] . ' ' . $mother['last_name']); ?></td>
                     <td><?php echo htmlspecialchars($mother['date_of_birth'] ?? 'N/A'); ?></td>
                     <td><?php echo htmlspecialchars($mother['phone_number'] ?? 'N/A'); ?></td>
                     <td><?php echo htmlspecialchars($mother['registration_date']); ?></td>
                     <td>
                         <!-- Link to view mother details - Use PROJECT_SUBDIRECTORY -->
                         <!-- Note: Clicking this link will trigger a FULL page load by default -->
                         <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/mother_details.php?id=<?php echo htmlspecialchars($mother['mother_id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye mr-1"></i> View Details</a>
                     </td>
                 </tr>
                 <?php endforeach; ?>
             </tbody>
         </table>
     <?php endif; ?>

     <?php
     // === END OF SPECIFIC HTML CONTENT ===
} // End of check for content_error

// --- END OF PAGE-SPECIFIC PHP LOGIC AND HTML CONTENT ---


// If it's NOT an AJAX request, include the footer and close the manual container div
if (!$is_ajax) {
    // The footer closes the main-content-area div and layout divs, and closes the DB connection
    require_once(__DIR__ . '/../includes/footer.php'); // Adjust path based on depth
}
?>