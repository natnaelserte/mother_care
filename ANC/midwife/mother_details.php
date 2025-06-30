<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (midwife is 2 levels deep)

require_login(); // Ensure user is logged in
require_role('Midwife', 'views/dashboard.php'); // Ensure user is a Midwife, redirect to dashboard if not

// $link database connection is available globally after including core/auth.php
global $link; // Access the global database connection

// Remove the old $base_url calculation
// $script_dir = dirname($_SERVER['SCRIPT_NAME']);
// $depth = count(explode('/', trim($script_dir, '/')));
// $base_url = str_repeat('../', $depth);


// Get mother ID from URL
$mother_id = $_GET['id'] ?? null; // Use null coalescing for safety

// Check if mother ID is present and valid
if (!$mother_id || !is_numeric($mother_id)) {
    $_SESSION['error_message'] = "Invalid or missing mother ID.";
    // Use PROJECT_SUBDIRECTORY for redirect back to the mothers list
    header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php");
    exit();
}

$mother_id = intval($mother_id); // Ensure it's an integer

// Fetch mother's details
$sql_mother = "SELECT mother_id, first_name, last_name, date_of_birth, address, phone_number, national_id, registration_date
               FROM mothers
               WHERE mother_id = ?";

$mother_details = null;
if ($stmt_mother = mysqli_prepare($link, $sql_mother)) {
    mysqli_stmt_bind_param($stmt_mother, "i", $mother_id);
    if (mysqli_stmt_execute($stmt_mother)) {
        $result_mother = mysqli_stmt_get_result($stmt_mother);
        $mother_details = mysqli_fetch_assoc($result_mother);
        mysqli_free_result($result_mother);
    } else {
        // Handle query error
        $error_message = "Database error fetching mother details: " . mysqli_stmt_error($stmt_mother);
        error_log($error_message); // Log the actual DB error
         $_SESSION['error_message'] = "Error retrieving mother details."; // User-friendly message
         // Use PROJECT_SUBDIRECTORY for redirect back to the mothers list
         header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php");
         exit();
    }
    mysqli_stmt_close($stmt_mother);
} else {
    // Handle prepare error
    $error_message = "Database error preparing mother details query: " . mysqli_error($link);
     error_log($error_message); // Log the actual DB error
     $_SESSION['error_message'] = "System error retrieving mother details."; // User-friendly message
     // Use PROJECT_SUBDIRECTORY for redirect back to the mothers list
     header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php");
     exit();
}

// Check if mother was actually found with the given ID
if (!$mother_details) {
    $_SESSION['error_message'] = "Mother with ID " . htmlspecialchars($mother_id) . " not found.";
     // Use PROJECT_SUBDIRECTORY for redirect back to the mothers list
     header("location: " . PROJECT_SUBDIRECTORY . "/midwife/view_mothers.php");
     exit();
}

// Set the page title (must be done BEFORE including the header)
$pageTitle = htmlspecialchars($mother_details['first_name'] . ' ' . $mother_details['last_name']) . " Details";


// --- Fetch associated data (ANC Records, Vitals, Requests, Appointments) ---
// Use prepared statements for safety

// Fetch ANC Records
$sql_anc = "SELECT * FROM anc_records WHERE mother_id = ? ORDER BY visit_date DESC, record_id DESC";
$anc_records = [];
if ($stmt_anc = mysqli_prepare($link, $sql_anc)) {
    mysqli_stmt_bind_param($stmt_anc, "i", $mother_id);
    if (mysqli_stmt_execute($stmt_anc)) {
        $result_anc = mysqli_stmt_get_result($stmt_anc);
        while($row = mysqli_fetch_assoc($result_anc)) { $anc_records[] = $row; }
        mysqli_free_result($result_anc);
    } else { error_log("Error fetching ANC records: " . mysqli_stmt_error($stmt_anc)); }
    mysqli_stmt_close($stmt_anc);
}

// Fetch Vital Signs
$sql_vitals = "SELECT * FROM vital_signs WHERE mother_id = ? ORDER BY check_date_time DESC";
$vital_signs = [];
if ($stmt_vitals = mysqli_prepare($link, $sql_vitals)) {
    mysqli_stmt_bind_param($stmt_vitals, "i", $mother_id);
    if (mysqli_stmt_execute($stmt_vitals)) {
        $result_vitals = mysqli_stmt_get_result($stmt_vitals);
        while($row = mysqli_fetch_assoc($result_vitals)) { $vital_signs[] = $row; }
        mysqli_free_result($result_vitals);
    } else { error_log("Error fetching vital signs: " . mysqli_stmt_error($stmt_vitals)); }
     mysqli_stmt_close($stmt_vitals);
}

// Fetch Lab Requests
$sql_lab_req = "SELECT lr.*, lres.result_id FROM lab_requests lr LEFT JOIN lab_results lres ON lr.request_id = lres.request_id WHERE lr.mother_id = ? ORDER BY lr.request_date DESC";
$lab_requests = [];
if ($stmt_lab_req = mysqli_prepare($link, $sql_lab_req)) {
    mysqli_stmt_bind_param($stmt_lab_req, "i", $mother_id);
     if (mysqli_stmt_execute($stmt_lab_req)) {
        $result_lab_req = mysqli_stmt_get_result($stmt_lab_req);
        while($row = mysqli_fetch_assoc($result_lab_req)) { $lab_requests[] = $row; }
        mysqli_free_result($result_lab_req);
     } else { error_log("Error fetching lab requests: " . mysqli_stmt_error($stmt_lab_req)); }
    mysqli_stmt_close($stmt_lab_req);
}

// Fetch Ultrasound Requests
$sql_ultra_req = "SELECT ur.*, ures.result_id FROM ultrasound_requests ur LEFT JOIN ultrasound_results ures ON ur.request_id = ures.request_id WHERE ur.mother_id = ? ORDER BY ur.request_date DESC";
$ultrasound_requests = [];
if ($stmt_ultra_req = mysqli_prepare($link, $sql_ultra_req)) {
    mysqli_stmt_bind_param($stmt_ultra_req, "i", $mother_id);
     if (mysqli_stmt_execute($stmt_ultra_req)) {
        $result_ultra_req = mysqli_stmt_get_result($stmt_ultra_req);
        while($row = mysqli_fetch_assoc($result_ultra_req)) { $ultrasound_requests[] = $row; }
         mysqli_free_result($result_ultra_req);
     } else { error_log("Error fetching ultrasound requests: " . mysqli_stmt_error($stmt_ultra_req)); }
    mysqli_stmt_close($stmt_ultra_req);
}

// Fetch Appointments
$sql_appointments = "SELECT * FROM appointments WHERE mother_id = ? ORDER BY appointment_date_time DESC";
$appointments = [];
if ($stmt_appointments = mysqli_prepare($link, $sql_appointments)) {
    mysqli_stmt_bind_param($stmt_appointments, "i", $mother_id);
    if (mysqli_stmt_execute($stmt_appointments)) {
        $result_appointments = mysqli_stmt_get_result($stmt_appointments);
        while($row = mysqli_fetch_assoc($result_appointments)) { $appointments[] = $row; }
        mysqli_free_result($result_appointments);
    } else { error_log("Error fetching appointments: " . mysqli_stmt_error($stmt_appointments)); }
     mysqli_stmt_close($stmt_appointments);
}


// Close database connection (handled globally by auth.php / footer.php)
// mysqli_close($link); // Remove explicit close here


// Include the header (provides fixed layout and opens main content area)
require_once(__DIR__ . '/../includes/header.php'); // Adjust path based on depth

?>

<!-- The content below will be placed inside the main-content-area div opened in header.php -->

<h2>Details for <?php echo htmlspecialchars($mother_details['first_name'] . ' ' . $mother_details['last_name']); ?></h2>

<?php
 // Display error/success messages from session (set by redirects *to* this page)
 // Messages set during POST form submissions will be displayed on the redirected page (this page)
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
?>

<!-- Mother's Personal Information -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        Personal Information
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($mother_details['first_name'] . ' ' . $mother_details['last_name']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($mother_details['date_of_birth'] ?? 'N/A'); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($mother_details['phone_number'] ?? 'N/A'); ?></p>
                 <p><strong>National ID:</strong> <?php echo htmlspecialchars($mother_details['national_id'] ?? 'N/A'); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($mother_details['address'] ?? 'N/A')); ?></p>
                 <p><strong>Registration Date:</strong> <?php echo htmlspecialchars($mother_details['registration_date']); ?></p>
            </div>
        </div>
        <!-- Use PROJECT_SUBDIRECTORY for the link -->
         <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/update_mother.php?id=<?php echo htmlspecialchars($mother_id); ?>" class="btn btn-secondary btn-sm mt-3 disabled" aria-disabled="true"><i class="fas fa-edit mr-1"></i> Edit Mother Info (Placeholder)</a>
    </div>
</div>

<!-- Midwife Actions for This Mother -->
<div class="card mb-4 card-action">
    <div class="card-header bg-primary text-white">
        Midwife Actions for This Mother
    </div>
    <div class="list-group list-group-flush">
        <!-- Links to forms - Use PROJECT_SUBDIRECTORY and pass mother_id -->
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/record_anc.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" class="list-group-item list-group-item-action"><i class="fas fa-notes-medical mr-2 text-primary"></i> Record New ANC Visit</a>
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/take_vital_sign.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" class="list-group-item list-group-item-action"><i class="fas fa-heartbeat mr-2 text-success"></i> Take Vital Signs</a>
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_lab_request.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" class="list-group-item list-group-item-action"><i class="fas fa-vials mr-2 text-info"></i> Send Lab Request</a>
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_ultrasound_request.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" class="list-group-item list-group-item-action"><i class="fas fa-x-ray mr-2 text-warning"></i> Send Ultrasound Request</a>
        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/schedule_appointment.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>" class="list-group-item list-group-item-action"><i class="fas fa-calendar-plus mr-2 text-danger"></i> Schedule Appointment</a>
    </div>
</div>

<!-- ANC History -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        ANC Visit History
    </div>
    <div class="card-body">
        <?php if (empty($anc_records)): ?>
            <p>No ANC records found for this mother.</p>
        <?php else: ?>
            <div class="accordion" id="ancHistoryAccordion">
                <?php foreach ($anc_records as $index => $record): ?>
                    <div class="card">
                        <div class="card-header" id="headingANC<?php echo $index; ?>">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseANC<?php echo $index; ?>" aria-expanded="false" aria-controls="collapseANC<?php echo $index; ?>">
                                    ANC Visit on <?php echo htmlspecialchars($record['visit_date']); ?> (G.A. <?php echo htmlspecialchars($record['gestational_age_weeks'] ?? '?'); ?> weeks)
                                </button>
                            </h2>
                        </div>
                        <div id="collapseANC<?php echo $index; ?>" class="collapse" aria-labelledby="headingANC<?php echo $index; ?>" data-parent="#ancHistoryAccordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Weight:</strong> <?php echo htmlspecialchars($record['weight'] ?? 'N/A'); ?> kg</p>
                                        <p><strong>Blood Pressure:</strong> <?php echo htmlspecialchars($record['blood_pressure'] ?? 'N/A'); ?></p>
                                        <p><strong>Fundal Height:</strong> <?php echo htmlspecialchars($record['fundal_height'] ?? 'N/A'); ?> cm</p>
                                        <p><strong>Fetal Heart Rate:</strong> <?php echo htmlspecialchars($record['fetal_heart_rate'] ?? 'N/A'); ?> bpm</p>
                                    </div>
                                     <div class="col-md-6">
                                         <p><strong>Presentation:</strong> <?php echo htmlspecialchars($record['presentation'] ?? 'N/A'); ?></p>
                                         <p><strong>Oedema:</strong> <?php echo $record['oedema'] ? 'Yes' : 'No'; ?></p>
                                         <p><strong>Urine Protein Test:</strong> <?php echo htmlspecialchars($record['urine_protein_test'] ?? 'N/A'); ?></p>
                                         <p><strong>Hemoglobin:</strong> <?php echo htmlspecialchars($record['hemoglobin'] ?? 'N/A'); ?> g/dL</p>
                                     </div>
                                </div>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($record['notes'] ?? 'N/A')); ?></p>
                                <p class="text-muted"><small>Recorded on: <?php echo htmlspecialchars($record['created_at']); ?> by User ID <?php echo htmlspecialchars($record['taken_by'] ?? 'N/A'); ?></small></p>
                                <!-- Link to perform actions related to this specific visit if needed -->
                                <div class="mt-3">
                                     <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/take_vital_sign.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>&anc_record_id=<?php echo htmlspecialchars($record['record_id']); ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-heartbeat mr-1"></i> Add Vitals for this Visit</a>
                                     <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_lab_request.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>&anc_record_id=<?php echo htmlspecialchars($record['record_id']); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-vials mr-1"></i> Add Lab Request for this Visit</a>
                                     <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_ultrasound_request.php?mother_id=<?php echo htmlspecialchars($mother_id); ?>&anc_record_id=<?php echo htmlspecialchars($record['record_id']); ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-x-ray mr-1"></i> Add Ultrasound Request for this Visit</a>
                                     <!-- Add more actions specific to a visit -->
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Vital Signs History -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        Vital Signs History
    </div>
    <div class="card-body">
        <?php if (empty($vital_signs)): ?>
            <p>No Vital Sign records found for this mother.</p>
        <?php else: ?>
            <table class="table table-sm table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Temp (°C)</th>
                        <th>Pulse</th>
                        <th>Resp Rate</th>
                        <th>BP</th>
                        <th>Recorded By (ID)</th>
                         <th>ANC Record ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vital_signs as $vs): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vs['check_date_time']); ?></td>
                        <td><?php echo htmlspecialchars($vs['temperature'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($vs['pulse_rate'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($vs['respiratory_rate'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($vs['blood_pressure'] ?? 'N/A'); ?></td>
                         <td><?php echo htmlspecialchars($vs['taken_by'] ?? 'N/A'); ?></td>
                         <td>
                             <?php if ($vs['anc_record_id']): ?>
                                <?php echo htmlspecialchars($vs['anc_record_id']); ?>
                                 <!-- Optionally add a link to expand the corresponding ANC record -->
                             <?php else: ?>
                                Standalone
                             <?php endif; ?>
                         </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>


<!-- Lab Requests & Results History -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        Lab Requests & Results
    </div>
    <div class="card-body">
        <?php if (empty($lab_requests)): ?>
            <p>No Lab requests found for this mother.</p>
        <?php else: ?>
             <table class="table table-sm table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Requested Tests</th>
                        <th>Status</th>
                        <th>Requested By (ID)</th>
                         <th>Result Available</th>
                         <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lab_requests as $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['request_date']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($req['requested_tests'])); ?></td>
                        <td><span class="badge badge-<?php echo ($req['request_status'] == 'Completed' ? 'success' : ($req['request_status'] == 'Cancelled' ? 'danger' : 'warning')); ?>"><?php echo htmlspecialchars($req['request_status']); ?></span></td>
                         <td><?php echo htmlspecialchars($req['requested_by'] ?? 'N/A'); ?></td>
                        <td><?php echo $req['result_id'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>'; ?></td>
                        <td>
                            <?php if ($req['result_id']): ?>
                                 <!-- Link to view result (create this page later) -->
                                <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_result.php?id=<?php echo htmlspecialchars($req['result_id']); ?>" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true"><i class="fas fa-file-alt mr-1"></i> View Result</a>
                            <?php else: ?>
                                <span class="text-muted">Result Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Ultrasound Requests & Results History -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        Ultrasound Requests & Results
    </div>
    <div class="card-body">
        <?php if (empty($ultrasound_requests)): ?>
            <p>No Ultrasound requests found for this mother.</p>
        <?php else: ?>
             <table class="table table-sm table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested By (ID)</th>
                         <th>Result Available</th>
                         <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultrasound_requests as $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['request_date']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($req['reason'])); ?></td>
                        <td><span class="badge badge-<?php echo ($req['request_status'] == 'Completed' ? 'success' : ($req['request_status'] == 'Cancelled' ? 'danger' : 'warning')); ?>"><?php echo htmlspecialchars($req['request_status']); ?></span></td>
                         <td><?php echo htmlspecialchars($req['requested_by'] ?? 'N/A'); ?></td>
                        <td><?php echo $req['result_id'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>'; ?></td>
                        <td>
                             <?php if ($req['result_id']): ?>
                                 <!-- Link to view result (create this page later) -->
                                <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/radiologist/view_ultrasound_result.php?id=<?php echo htmlspecialchars($req['result_id']); ?>" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true"><i class="fas fa-file-alt mr-1"></i> View Result</a>
                            <?php else: ?>
                                 <span class="text-muted">Result Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Appointments History -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        Appointments
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <p>No appointments scheduled for this mother.</p>
        <?php else: ?>
             <table class="table table-sm table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Purpose</th>
                        <th>Notes</th>
                        <th>Scheduled By (ID)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appt['appointment_date_time']); ?></td>
                        <td><?php echo htmlspecialchars($appt['purpose'] ?? 'N/A'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($appt['notes'] ?? 'N/A')); ?></td>
                         <td><?php echo htmlspecialchars($appt['scheduled_by'] ?? 'N/A'); ?></td>
                        <td>
                             <!-- Link to edit/cancel appointment (create this page later) -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/edit_appointment.php?id=<?php echo htmlspecialchars($appt['appointment_id']); ?>" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true"><i class="fas fa-edit mr-1"></i> Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>


<p class="mt-4 text-center">
    <!-- Use PROJECT_SUBDIRECTORY for the link -->
    <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/view_mothers.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to Mothers List</a>
</p>

<?php
// Include the footer (closes layout divs and DB connection)
require_once(__DIR__ . '/../includes/footer.php'); // Adjust path based on depth
?>