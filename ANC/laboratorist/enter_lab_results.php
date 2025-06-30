<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (laboratorist is 2 levels deep)

require_login(); // Ensure user is logged in
require_role('Laboratorist', 'views/dashboard.php'); // Ensure user is a Laboratorist, redirect to dashboard if not

// Ensure database connection is available (core/auth.php provides $link globally)
global $link; // Access the global database connection

// --- Page specific logic ---

// This page REQUIRES a request_id
$is_request_id_required = true;
$request_id = $_GET['request_id'] ?? null; // Get ID from URL parameter
$request_details = null; // Variable to hold request details
$existing_result = null; // Variable to hold existing result if found
$content_error = null; // Variable to hold errors displayed in content area

// Define variables for the form
$result_details = $_POST['result_details'] ?? ''; // Pre-fill from POST on error
$result_details_err = '';
$general_err = '';


// Check if request ID is present and valid *and* fetch request details
if ($is_request_id_required) {
    if ($request_id !== null && is_numeric($request_id)) {
        $request_id = intval($request_id);

        // Fetch request details (join with mothers for name)
        $sql_request = "SELECT lr.request_id, lr.mother_id, m.first_name, m.last_name, lr.request_date, lr.requested_tests, lr.request_status
                        FROM lab_requests lr
                        JOIN mothers m ON lr.mother_id = m.mother_id
                        WHERE lr.request_id = ?";
        if ($stmt_request = mysqli_prepare($link, $sql_request)) {
            mysqli_stmt_bind_param($stmt_request, "i", $request_id);
            if (mysqli_stmt_execute($stmt_request)) {
                $result_request = mysqli_stmt_get_result($stmt_request);
                $request_details = mysqli_fetch_assoc($result_request);
                mysqli_free_result($result_request);
            }
            mysqli_stmt_close($stmt_request);
        } else {
             error_log("DB Error preparing request fetch: " . mysqli_error($link));
        }

        // If request details not found or request is cancelled/already completed, show error
        if (!$request_details) {
             $content_error = "Lab Request with ID " . htmlspecialchars($request_id) . " not found.";
        } elseif ($request_details['request_status'] !== 'Pending') {
             // If request is not pending, they should view the result instead
             if ($request_details['request_status'] === 'Completed') {
                 // Find the result_id to link to view_lab_result.php
                 $sql_find_result = "SELECT result_id FROM lab_results WHERE request_id = ?";
                 if ($stmt_find_result = mysqli_prepare($link, $sql_find_result)) {
                     mysqli_stmt_bind_param($stmt_find_result, "i", $request_id);
                     if(mysqli_stmt_execute($stmt_find_result)) {
                         mysqli_stmt_bind_result($stmt_find_result, $found_result_id);
                         if(mysqli_stmt_fetch($stmt_find_result)) {
                             // Redirect to view result page
                             header("location: " . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_result.php?id=" . htmlspecialchars($found_result_id));
                             exit();
                         }
                     }
                     mysqli_stmt_close($stmt_find_result);
                 }
                 // If redirect didn't happen (result not found somehow)
                 $content_error = "Lab Request with ID " . htmlspecialchars($request_id) . " is completed, but result was not found.";

             } else { // e.g., Cancelled
                 $content_error = "Lab Request with ID " . htmlspecialchars($request_id) . " has status '" . htmlspecialchars($request_details['request_status']) . "' and cannot be edited.";
             }
        } else {
             // Request is pending and found, now check if a result already exists (e.g. partial entry or draft)
             $sql_existing_result = "SELECT result_details FROM lab_results WHERE request_id = ?";
             if ($stmt_existing_result = mysqli_prepare($link, $sql_existing_result)) {
                 mysqli_stmt_bind_param($stmt_existing_result, "i", $request_id);
                 if (mysqli_stmt_execute($stmt_existing_result)) {
                     $result_existing = mysqli_stmt_get_result($stmt_existing_result);
                     $existing_result = mysqli_fetch_assoc($result_existing);
                     mysqli_free_result($result_existing);
                 }
                 mysqli_stmt_close($stmt_existing_result);
             } else {
                 error_log("DB Error preparing existing result fetch: " . mysqli_error($link));
                 // This is a non-fatal error for displaying the page, log it.
             }
        }

    } else {
        // Request ID is missing or not numeric
        $content_error = "Lab Request ID is required for this action.";
    }

    // --- Handle Content Error Based on Load Type ---
    if ($content_error !== null) {
        // If there's a content error (missing/invalid ID, request not found/pending)
        // If full page load, redirect to view requests
        if (!$is_ajax) {
             $_SESSION['error_message'] = $content_error;
             header("location: " . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_requests.php");
             exit();
        }
        // If AJAX load, the error will be displayed in the content area below.
    }
} else {
    // This page *should* always be loaded with a request_id
    $content_error = "Internal error: Request ID is missing."; // Should not happen if $is_request_id_required is true
}


// --- Rest of your page's PHP logic (handle POST submissions etc.) ---
// This part runs if no $content_error occurred (request ID is valid/found/pending)

// Handle POST submission ONLY if no content error exists from the initial GET/validation
if ($_SERVER["REQUEST_METHOD"] == "POST" && $content_error === null) {

    // Get data from POST
    $result_details = trim($_POST['result_details']);

    // Validate Result Details
    if (empty($result_details)) {
        $result_details_err = "Please enter the lab results.";
    }

    // Check input errors before inserting/updating in database
    if (empty($result_details_err) && empty($general_err)) {

        // Check again if a result already exists for this request ID
        $sql_check_exist = "SELECT result_id FROM lab_results WHERE request_id = ?";
         $result_id_exists = false;
         if ($stmt_check_exist = mysqli_prepare($link, $sql_check_exist)) {
             mysqli_stmt_bind_param($stmt_check_exist, "i", $request_id);
             if (mysqli_stmt_execute($stmt_check_exist)) {
                 mysqli_stmt_store_result($stmt_check_exist);
                 if (mysqli_stmt_num_rows($stmt_check_exist) > 0) {
                     $result_id_exists = true;
                 }
             } else { error_log("DB Error checking existing result on POST: " . mysqli_stmt_error($stmt_check_exist)); }
             mysqli_stmt_close($stmt_check_exist);
         } else { error_log("DB Error preparing check existing result on POST: " . mysqli_error($link)); }


        // Prepare INSERT or UPDATE statement
        if ($result_id_exists) {
             // Update existing result and update request status
             $sql_save = "UPDATE lab_results SET result_details = ?, report_date = CURRENT_TIMESTAMP, reported_by = ? WHERE request_id = ?";
             $sql_update_status = "UPDATE lab_requests SET request_status = 'Completed' WHERE request_id = ?"; // Assuming entering result marks it completed
             $success_msg = "Lab result for Request ID " . htmlspecialchars($request_id) . " updated and marked as Completed.";

        } else {
             // Insert new result and update request status
             $sql_save = "INSERT INTO lab_results (request_id, result_details, report_date, reported_by) VALUES (?, ?, CURRENT_TIMESTAMP, ?)";
             $sql_update_status = "UPDATE lab_requests SET request_status = 'Completed' WHERE request_id = ?"; // Assuming entering result marks it completed
             $success_msg = "Lab result for Request ID " . htmlspecialchars($request_id) . " saved and request marked as Completed.";
        }

        // Begin Transaction (optional but good practice for linked updates)
        mysqli_begin_transaction($link);
        $save_success = false;
        $status_success = false;


        // Attempt to execute the save statement
        if ($stmt_save = mysqli_prepare($link, $sql_save)) {
             if ($result_id_exists) {
                 mysqli_stmt_bind_param($stmt_save, "sii", $result_details, $_SESSION['user_id'], $request_id);
             } else {
                  mysqli_stmt_bind_param($stmt_save, "isi", $request_id, $result_details, $_SESSION['user_id']);
             }

            if (mysqli_stmt_execute($stmt_save)) {
                 $save_success = true;
            } else {
                 $general_err = "Error saving result: " . mysqli_stmt_error($stmt_save);
                  error_log("DB Error saving result: " . mysqli_stmt_error($stmt_save));
            }
            mysqli_stmt_close($stmt_save);
        } else {
             $general_err = "Database error preparing save statement.";
              error_log("DB Error preparing save statement: " . mysqli_error($link));
        }

        // Attempt to execute the status update statement (only if save was successful)
        if ($save_success) {
             if ($stmt_status = mysqli_prepare($link, $sql_update_status)) {
                 mysqli_stmt_bind_param($stmt_status, "i", $request_id);
                 if (mysqli_stmt_execute($stmt_status)) {
                      $status_success = true;
                 } else {
                      $general_err = "Error updating request status: " . mysqli_stmt_error($stmt_status);
                       error_log("DB Error updating status: " . mysqli_stmt_error($stmt_status));
                 }
                 mysqli_stmt_close($stmt_status);
             } else {
                  $general_err = "Database error preparing status update.";
                   error_log("DB Error preparing status update: " . mysqli_error($link));
             }
        }


        // Commit or Rollback transaction
        if ($save_success && $status_success) {
            mysqli_commit($link);
            // Success! Redirect back to the view requests page (FULL PAGE REDIRECT)
            $_SESSION['message'] = $success_msg;
            $_SESSION['message_type'] = "success";
            header("location: " . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_requests.php");
            exit(); // Stop execution after redirect
        } else {
            mysqli_rollback($link);
            // Transaction failed, general_err should be set
        }

    } else {
         // Validation errors on POST or general_err set
         $general_err = $general_err ?: "Please fix the errors in the form."; // Set generic error if none specific
         // Form variables are preserved from $_POST, so the form will display errors below
         // Individual *_err variables should also be set by your validation logic
    }
} // End if $_SERVER["REQUEST_METHOD"] == "POST"


// Close database connection (handled globally by auth.php / footer.php)
// mysqli_close($link); // Remove explicit close here


// If NOT AJAX, the header has already opened the main content area.
// If AJAX, we just output the content fragment directly.


// --- HTML CONTENT FOR THE MAIN AREA ---

// Display the error message if set (happens for missing ID, request not found/pending, failed POST)
if ($content_error !== null) {
    echo '<div class="alert alert-warning">' . htmlspecialchars($content_error) . '</div>';
     // Add a back link if it's an AJAX error state
     if ($is_ajax && $content_error !== null) {
         echo '<p class="text-center mt-4"><a href="' . PROJECT_SUBDIRECTORY . "/laboratorist/view_lab_requests.php" . '" class="btn btn-primary ajax-load-link"><i class="fas fa-arrow-left mr-1"></i> Back to Requests List</a></p>';
     }

} else {
     // ONLY display the normal page content (form) if there's no content error

     // H2 heading
     ?>
     <h2>Enter Lab Results for Request #<?php echo htmlspecialchars($request_details['request_id']); ?></h2>
     <p class="text-muted mb-4">For Mother: <?php echo htmlspecialchars($request_details['first_name'] . ' ' . $request_details['last_name']); ?> (Requested on: <?php echo htmlspecialchars($request_details['request_date']); ?>)</p>
     <p>Requested Tests: <?php echo nl2br(htmlspecialchars($request_details['requested_tests'])); ?></p>

     <?php
     // Display session messages (set by redirects - not expected here) and general errors from THIS page logic (failed POST)
     if (!empty($general_err)) {
         echo '<div class="alert alert-danger">' . htmlspecialchars($general_err) . '</div>';
     }
     // Individual validation errors are displayed next to form fields below
     ?>

     <?php
     // === INSERT THE SPECIFIC HTML CONTENT FOR THIS PAGE HERE ===
     // The lab results form
     ?>

     <!-- Use PROJECT_SUBDIRECTORY for form action -->
     <!-- Submitting this form will trigger a FULL PAGE POST to this same URL -->
     <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/enter_lab_results.php?request_id=<?php echo htmlspecialchars($request_id); ?>" method="post">
         <div class="form-group">
             <label>Lab Results <span class="text-danger">*</span></label>
             <textarea name="result_details" class="form-control <?php echo (!empty($result_details_err)) ? 'is-invalid' : ''; ?>" rows="6" required><?php echo htmlspecialchars($result_details); ?></textarea>
             <span class="invalid-feedback"><?php echo htmlspecialchars($result_details_err); ?></span>
         </div>

         <div class="form-group">
             <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Results</button>
              <!-- Link back to view requests list - Use PROJECT_SUBDIRECTORY -->
             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_requests.php" class="btn btn-secondary ml-2">Cancel</a>
         </div>
     </form>


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