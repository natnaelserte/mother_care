<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (laboratorist is 2 levels deep)

require_login(); // Ensure user is logged in
require_role('Laboratorist', 'views/dashboard.php'); // Ensure user is a Laboratorist, redirect to dashboard if not
// You might also allow Midwives to view results, adjust require_role if needed

// Ensure database connection is available (core/auth.php provides $link globally)
global $link; // Access the global database connection

// --- Page specific logic ---

// This page REQUIRES a result_id
$is_result_id_required = true;
$result_id = $_GET['id'] ?? null; // Get ID from URL parameter
$result_details_data = null; // Variable to hold result details and associated info
$content_error = null; // Variable to hold errors displayed in content area


// Check if result ID is present and valid *and* fetch result details
if ($is_result_id_required) {
    if ($result_id !== null && is_numeric($result_id)) {
        $result_id = intval($result_id);

        // Fetch result details and join with request, mother, and user (for reported_by name) tables
        $sql_result = "SELECT
                           lres.result_id,
                           lres.result_details,
                           lres.report_date,
                           lres.reported_by,
                           u.full_name AS reported_by_name,
                           lr.request_id,
                           lr.mother_id,
                           m.first_name,
                           m.last_name,
                           lr.request_date,
                           lr.requested_tests
                       FROM lab_results lres
                       JOIN lab_requests lr ON lres.request_id = lr.request_id
                       JOIN mothers m ON lr.mother_id = m.mother_id
                       LEFT JOIN users u ON lres.reported_by = u.user_id -- Join to get reported_by name
                       WHERE lres.result_id = ?";

        if ($stmt_result = mysqli_prepare($link, $sql_result)) {
            mysqli_stmt_bind_param($stmt_result, "i", $result_id);
            if (mysqli_stmt_execute($stmt_result)) {
                $result_fetch = mysqli_stmt_get_result($stmt_result);
                $result_details_data = mysqli_fetch_assoc($result_fetch);
                mysqli_free_result($result_fetch);
            }
            mysqli_stmt_close($stmt_result);
        } else {
             error_log("DB Error preparing result fetch: " . mysqli_error($link));
        }

        // If result details not found, show error
        if (!$result_details_data) {
             $content_error = "Lab Result with ID " . htmlspecialchars($result_id) . " not found.";
        }

    } else {
        // Result ID is missing or not numeric
        $content_error = "Lab Result ID is required.";
    }

    // --- Handle Content Error Based on Load Type ---
    if ($content_error !== null) {
        // If there's a content error (missing/invalid ID, result not found)
        // If full page load, redirect to view requests
        if (!$is_ajax) {
             $_SESSION['error_message'] = $content_error;
             header("location: " . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_requests.php");
             exit();
        }
        // If AJAX load, the error will be displayed in the content area below.
    }
} else {
    // This page *should* always be loaded with a result_id
    $content_error = "Internal error: Result ID is missing."; // Should not happen if $is_result_id_required is true
}


// Close database connection (handled globally by auth.php / footer.php)
// mysqli_close($link); // Remove explicit close here


// If NOT AJAX, the header has already opened the main content area.
// If AJAX, we just output the content fragment directly.


// Set the page title if successful (must be done BEFORE including the header)
if ($content_error === null && $result_details_data) {
    $pageTitle = "Lab Result for Request #" . htmlspecialchars($result_details_data['request_id']);
} else {
     $pageTitle = "View Lab Result";
}

// If NOT AJAX, include the header
if (!$is_ajax) {
    require_once(__DIR__ . '/../includes/header.php'); // Adjust path based on depth
}


// --- HTML CONTENT FOR THE MAIN AREA ---

// Display the error message if set
if ($content_error !== null) {
    echo '<div class="alert alert-warning">' . htmlspecialchars($content_error) . '</div>';
     // Add a back link if it's an AJAX error state
     // This page should ideally not be AJAX loaded from sidebar, but from requests list
     echo '<p class="text-center mt-4"><a href="' . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_requests.php" . '" class="btn btn-primary"><i class="fas fa-arrow-left mr-1"></i> Back to Requests List</a></p>'; // This link triggers full load
} else {
     // ONLY display the normal page content (result details) if there's no content error

     // H2 heading
     ?>
     <h2>Lab Result for Request #<?php echo htmlspecialchars($result_details_data['request_id']); ?></h2>
     <?php if (!$is_ajax): // Adjust description for AJAX context if needed ?>
          <p class="text-muted mb-4">Details of the completed lab result.</p>
     <?php endif; ?>


     <?php
     // Display session messages (set by redirects - not expected here)
     // No form on this page, so no validation errors handled here
     if (isset($_SESSION['message'])) { // Display messages if this page was redirected to
         $msg_class = $_SESSION['message_type'] ?? 'info';
         echo '<div class="alert alert-' . $msg_class . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
         unset($_SESSION['message']);
         unset($_SESSION['message_type']);
     }
     // error_message handled by general content_error display
     ?>

     <?php
     // === INSERT THE SPECIFIC HTML CONTENT FOR THIS PAGE HERE ===
     // Display the result details
     ?>

     <div class="card mb-4">
         <div class="card-header bg-info text-white">
             Result Details
         </div>
         <div class="card-body">
             <p><strong>Reported Date:</strong> <?php echo htmlspecialchars($result_details_data['report_date']); ?></p>
             <p><strong>Reported By:</strong> <?php echo htmlspecialchars($result_details_data['reported_by_name'] ?? 'User ID ' . $result_details_data['reported_by']); ?></p>
             <p><strong>Details:</strong></p>
             <div class="card p-3 bg-light">
                  <?php echo nl2br(htmlspecialchars($result_details_data['result_details'])); ?>
             </div>
         </div>
     </div>

     <div class="card mb-4">
         <div class="card-header bg-secondary text-white">
             Related Request Details
         </div>
         <div class="card-body">
              <p><strong>Request Date:</strong> <?php echo htmlspecialchars($result_details_data['request_date']); ?></p>
              <p><strong>Requested Tests:</strong> <?php echo nl2br(htmlspecialchars($result_details_data['requested_tests'])); ?></p>
              <p><strong>For Mother:</strong>
                  <!-- Link to mother details (Midwife page) - Use PROJECT_SUBDIRECTORY -->
                  <!-- Clicking this link will trigger a FULL page load -->
                  <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/mother_details.php?id=<?php echo htmlspecialchars($result_details_data['mother_id']); ?>">
                      <?php echo htmlspecialchars($result_details_data['first_name'] . ' ' . $result_details_data['last_name']); ?> (ID: <?php echo htmlspecialchars($result_details_data['mother_id']); ?>)
                  </a>
              </p>
         </div>
     </div>


     <p class="mt-4 text-center">
          <!-- Link back to view requests list - Use PROJECT_SUBDIRECTORY -->
         <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_requests.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to Requests List</a>
     </p>

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