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

    // Set the page title for the full page header (will be set later based on mother name)
    $pageTitle = "Midwife - Record ANC Visit";


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
    // Perform lightweight checks
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

// This page REQUIRES a mother_id
$is_mother_id_required = true;
$mother_id = $_GET['mother_id'] ?? null; // Get ID from 'mother_id' URL parameter
$mother_details = null; // Variable to hold mother details if found
$content_error = null; // Variable to hold errors displayed in content area


if ($is_mother_id_required) {
    if ($mother_id !== null && is_numeric($mother_id)) {
        $mother_id = intval($mother_id);
        // Fetch mother details to confirm existence and get name
        $sql_mother_details = "SELECT mother_id, first_name, last_name FROM mothers WHERE mother_id = ?";
        if ($stmt_details = mysqli_prepare($link, $sql_mother_details)) {
            mysqli_stmt_bind_param($stmt_details, "i", $mother_id);
            if (mysqli_stmt_execute($stmt_details)) {
                $result_details = mysqli_stmt_get_result($stmt_details);
                $mother_details = mysqli_fetch_assoc($result_details);
                mysqli_free_result($result_details);
            }
            mysqli_stmt_close($stmt_details);
        }
        // If mother_details is still null here, it means the ID was valid format but mother not found
        if (!$mother_details) {
             $content_error = "Mother with ID " . htmlspecialchars($mother_id) . " not found.";
        }

    } else {
        // Mother ID is missing or not numeric
        $content_error = "Mother ID is required for this action.";
    }

    // If there's a content error AND it's a full page load, redirect
    // This handles direct access like /midwife/record_anc.php without ?mother_id=123
    if ($content_error !== null && !$is_ajax) {
         $_SESSION['error_message'] = $content_error; // Store error in session for redirect target
         header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php"); // Redirect to mothers list
         exit(); // Stop execution
    }
}


// Define variables and initialize with empty values for the form or previous POST data
// Initialize only if no content error from missing mother ID
if ($content_error === null) {
     $visit_date = $_POST['visit_date'] ?? date('Y-m-d'); // Default to today
     $gestational_age_weeks = $_POST['gestational_age_weeks'] ?? "";
     $weight = $_POST['weight'] ?? "";
     $blood_pressure = $_POST['blood_pressure'] ?? "";
     $fundal_height = $_POST['fundal_height'] ?? "";
     $fetal_heart_rate = $_POST['fetal_heart_rate'] ?? "";
     $presentation = $_POST['presentation'] ?? "";
     $oedema = $_POST['oedema'] ?? 0; // Default to 0 (false) if checkbox is not checked
     $urine_protein_test = $_POST['urine_protein_test'] ?? "";
     $hemoglobin = $_POST['hemoglobin'] ?? "";
     $notes = $_POST['notes'] ?? "";

     $visit_date_err = $gestational_age_weeks_err = $weight_err = $blood_pressure_err = $fundal_height_err = $fetal_heart_rate_err = $presentation_err = $urine_protein_test_err = $hemoglobin_err = $general_err = "";

     // Optional: Get anc_record_id from POST if available (passed from mother_details if adding vitals/requests for a specific visit)
     $anc_record_id = $_POST['anc_record_id'] ?? null; // This form doesn't use anc_record_id
     if ($anc_record_id !== null && is_numeric($anc_record_id)) {
         $anc_record_id = intval($anc_record_id);
          // Optional: Verify this anc_record_id exists and belongs to this mother if security is critical
     } else {
         $anc_record_id = NULL; // Store as NULL if not provided or invalid
     }

     // Processing form data when form is submitted
     // Forms within AJAX loaded content typically trigger FULL page POST requests
     if ($_SERVER["REQUEST_METHOD"] == "POST") {
         // ... (Your POST validation logic for the specific form) ...

         // Validate Visit Date
         if (empty(trim($visit_date))) { $visit_date_err = "Please enter the visit date."; } else { if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $visit_date)) { $visit_date_err = "Invalid date format. Use YYYY-MM-DD."; } }
         // ... (Validation for other fields) ...

         // Check input errors before inserting in database
         if (empty($visit_date_err) && empty($gestational_age_weeks_err) && empty($weight_err) && empty($blood_pressure_err) && empty($fundal_height_err) && empty($fetal_heart_rate_err) && empty($presentation_err) && empty($urine_protein_test_err) && empty($hemoglobin_err) && empty($general_err) && $content_error === null) { // Ensure no content error prevents save

             // Prepare an insert statement
             $sql = "INSERT INTO anc_records (mother_id, visit_date, gestational_age_weeks, weight, blood_pressure, fundal_height, fetal_heart_rate, presentation, oedema, urine_protein_test, hemoglobin, notes, taken_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

             if ($stmt = mysqli_prepare($link, $sql)) {
                 // Bind parameters - check types: i, s, i, d, s, d, i, s, i, s, d, s, i
                 // Use NULL for optional fields if they are empty, otherwise use their type
                 $param_gestational_age = $gestational_age_weeks !== "" ? $gestational_age_weeks : NULL;
                 $param_weight = $weight !== "" ? $weight : NULL;
                 $param_blood_pressure = empty($blood_pressure) ? NULL : $blood_pressure;
                 $param_fundal_height = $fundal_height !== "" ? $fundal_height : NULL;
                 $param_fetal_heart_rate = $fetal_heart_rate !== "" ? $fetal_heart_rate : NULL;
                 $param_presentation = empty($presentation) ? NULL : $presentation;
                 $param_oedema = $oedema;
                 $param_urine_protein_test = empty($urine_protein_test) ? NULL : $urine_protein_test;
                 $param_hemoglobin = $hemoglobin !== "" ? $hemoglobin : NULL;
                 $param_notes = empty($notes) ? NULL : $notes;
                 $param_taken_by = $_SESSION['user_id'];

                  mysqli_stmt_bind_param($stmt, "isiddisiisssd",
                     $mother_id,
                     $visit_date,
                     $param_gestational_age,
                     $param_weight,
                     $param_blood_pressure,
                     $param_fundal_height,
                     $param_fetal_heart_rate,
                     $param_presentation,
                     $param_oedema,
                     $param_urine_protein_test,
                     $param_hemoglobin,
                     $param_notes,
                     $param_taken_by
                 );


                 // Attempt to execute the prepared statement
                 if (mysqli_stmt_execute($stmt)) {
                     // ANC record created successfully. Redirect back to mother details.
                     // This will be a FULL PAGE REDIRECT.
                     $_SESSION['message'] = "ANC record for " . htmlspecialchars($mother_details['first_name']) . " created successfully.";
                     $_SESSION['message_type'] = "success";
                     header("location: " . PROJECT_SUBDIRECTORY . "/midwife/mother_details.php?id=" . htmlspecialchars($mother_id));
                     exit(); // Stop execution after redirect
                 } else {
                     // Database error on POST
                     $general_err = "Error saving ANC record: " . mysqli_stmt_error($stmt);
                      error_log("DB Error: " . mysqli_stmt_error($stmt));
                     // Form variables are preserved from $_POST implicitly
                 }
                 mysqli_stmt_close($stmt);
             } else {
                  $general_err = "Database error preparing insert statement: " . mysqli_error($link);
                  error_log("DB Error: " . mysqli_error($link));
             }
         } else {
              // Validation errors on POST or general_err set
              $general_err = $general_err ?: "Please fix the errors in the form."; // Set generic error if none specific
              // Form variables are preserved from $_POST, so the form will display errors below
         }
     } // End if $_SERVER["REQUEST_METHOD"] == "POST"

} // End if content_error === null


// --- HTML CONTENT FOR THE MAIN AREA ---

// Display the error message if set (happens for AJAX requests with missing ID or failed POST validation)
if ($content_error !== null) {
    echo '<div class="alert alert-warning">' . htmlspecialchars($content_error) . '</div>';
     if ($is_mother_id_required && $content_error !== null && $is_ajax) {
         // For required-ID pages loaded via AJAX without ID, prompt to go back
          echo '<p class="text-center mt-4"><a href="' . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php" . '" class="btn btn-primary"><i class="fas fa-arrow-left mr-1"></i> Go to Mothers List</a></p>';
     }

} else {
     // ONLY display the normal page content (form) if there's no content error

     // H2 heading and description for this page
     ?>
     <h2>Record New ANC Visit for <?php echo htmlspecialchars($mother_details['first_name'] . ' ' . $mother_details['last_name']); ?></h2>
     <?php if (!$is_ajax): // Adjust description for AJAX context if needed ?>
          <p class="text-muted mb-4">Fill in the details for the ANC visit.</p>
     <?php else: ?>
          <p class="text-muted mb-4">Fill in the details for the ANC visit for Mother: <?php echo htmlspecialchars($mother_details['first_name'] . ' ' . $mother_details['last_name']); ?></p>
     <?php endif; ?>


     <?php
     // Display session messages (set by redirects - not expected here often for AJAX) and general errors from THIS page logic (failed POST)
     if (!empty($general_err)) {
         echo '<div class="alert alert-danger">' . htmlspecialchars($general_err) . '</div>';
     }
     // Individual validation errors are displayed next to form fields below
     ?>


     <?php
     // === INSERT THE SPECIFIC HTML CONTENT FOR THIS PAGE HERE ===
     // The ANC record form
     ?>

     <!-- Use PROJECT_SUBDIRECTORY for form action -->
     <!-- Submitting this form will trigger a FULL PAGE POST to this same URL -->
     <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/record_anc.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" method="post">
         <?php if ($anc_record_id !== NULL): ?>
             <!-- Pass anc_record_id back through the form if it was in the URL -->
             <input type="hidden" name="anc_record_id" value="<?php echo htmlspecialchars($anc_record_id); ?>">
             <p class="alert alert-info">Adding details linked to ANC Record #<?php echo htmlspecialchars($anc_record_id); ?></p>
         <?php endif; ?>
         <div class="row">
             <div class="col-md-6">
                 <div class="form-group">
                     <label>Visit Date <span class="text-danger">*</span></label>
                     <input type="date" name="visit_date" class="form-control <?php echo (!empty($visit_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($visit_date); ?>" required>
                     <span class="invalid-feedback"><?php echo htmlspecialchars($visit_date_err); ?></span>
                 </div>
                 <div class="form-group">
                     <label>Gestational Age (weeks)</label>
                     <input type="number" name="gestational_age_weeks" class="form-control <?php echo (!empty($gestational_age_weeks_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($gestational_age_weeks); ?>" min="1" max="45">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($gestational_age_weeks_err); ?></span>
                 </div>
                  <div class="form-group">
                     <label>Weight (kg)</label>
                     <input type="number" name="weight" class="form-control <?php echo (!empty($weight_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($weight); ?>" step="0.1" min="0.1">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($weight_err); ?></span>
                 </div>
                 <div class="form-group">
                     <label>Blood Pressure (e.g., 120/80)</label>
                     <input type="text" name="blood_pressure" class="form-control <?php echo (!empty($blood_pressure_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($blood_pressure); ?>">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($blood_pressure_err); ?></span>
                 </div>
                  <div class="form-group">
                     <label>Fundal Height (cm)</label>
                     <input type="number" name="fundal_height" class="form-control <?php echo (!empty($fundal_height_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fundal_height); ?>" step="0.1" min="0.1">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($fundal_height_err); ?></span>
                 </div>
             </div>
             <div class="col-md-6">
                  <div class="form-group">
                     <label>Fetal Heart Rate (bpm)</label>
                     <input type="number" name="fetal_heart_rate" class="form-control <?php echo (!empty($fetal_heart_rate_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fetal_heart_rate); ?>" min="50" max="200">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($fetal_heart_rate_err); ?></span>
                 </div>
                  <div class="form-group">
                     <label>Presentation</label>
                     <input type="text" name="presentation" class="form-control <?php echo (!empty($presentation_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($presentation); ?>">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($presentation_err); ?></span>
                 </div>
                 <div class="form-group form-check">
                      <input type="checkbox" class="form-check-input" id="oedemaCheck" name="oedema" value="1" <?php echo ($oedema == 1) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="oedemaCheck">Oedema present</label>
                  </div>
                 <div class="form-group">
                     <label>Urine Protein Test Result</label>
                     <input type="text" name="urine_protein_test" class="form-control <?php echo (!empty($urine_protein_test_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($urine_protein_test); ?>">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($urine_protein_test_err); ?></span>
                 </div>
                 <div class="form-group">
                     <label>Hemoglobin (g/dL)</label>
                     <input type="number" name="hemoglobin" class="form-control <?php echo (!empty($hemoglobin_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($hemoglobin); ?>" step="0.1" min="0.1">
                     <span class="invalid-feedback"><?php echo htmlspecialchars($hemoglobin_err); ?></span>
                 </div>
             </div>
         </div>
         <div class="form-group">
             <label>Notes</label>
             <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
         </div>

         <div class="form-group">
             <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save ANC Record</button>
             <!-- Cancel link - Clicking this will trigger a FULL page load to mother_details -->
             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/mother_details.php?id=<?php echo htmlspecialchars($mother_id); ?>" class="btn btn-secondary ml-2">Cancel</a>
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