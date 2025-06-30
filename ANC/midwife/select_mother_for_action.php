<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (midwife is 2 levels deep)

// IMPORTANT: Detect if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$is_ajax = $is_ajax || (isset($_GET['ajax_request']) && $_GET['ajax_request'] === 'true');

// If it's NOT an AJAX request (direct full page load), redirect to dashboard
if (!$is_ajax) {
    // This page is intended ONLY for AJAX loading from the sidebar
    require_login(); // Basic auth check
    require_role('Midwife', 'views/dashboard.php'); // Role check
    header("location: " . PROJECT_SUBDIRECTORY . "/views/dashboard.php"); // Redirect if accessed directly
    exit();
}

// If it IS an AJAX request, perform lightweight checks
if (!is_loggedin() || !has_role('Midwife')) {
    http_response_code(403); // Forbidden
    echo '<div class="alert alert-danger">Access denied. Please refresh the dashboard.</div>';
    exit();
}

// Ensure database connection is available (core/auth.php provides $link globally)
global $link;

// Get the intended action from the URL parameter
$intended_action = $_GET['action'] ?? null;

// Validate the intended action
$valid_actions = ['record_anc', 'take_vitals', 'send_lab', 'send_ultrasound', 'schedule_appt'];
if (!in_array($intended_action, $valid_actions)) {
    echo '<div class="alert alert-danger">Invalid action specified.</div>';
    exit();
}

// Define variables for the form and search results
$search_term = $_POST['search_term'] ?? '';
$search_results = [];
$search_error = null;

// Handle search submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $intended_action !== null) {
    // Ensure POST is coming from this same page and action (basic check)
    // You might want to add CSRF tokens for better security on forms
    if (empty(trim($search_term))) {
        $search_error = "Please enter a name or ID to search.";
    } else {
        $search_term_param = '%' . trim($search_term) . '%'; // Add wildcards for partial matching

        // Query to search mothers by first name, last name, or national ID
        $sql_search = "SELECT mother_id, first_name, last_name, date_of_birth, phone_number, national_id
                       FROM mothers
                       WHERE first_name LIKE ? OR last_name LIKE ? OR national_id LIKE ?
                       LIMIT 10"; // Limit results to avoid large lists

        if ($stmt_search = mysqli_prepare($link, $sql_search)) {
            mysqli_stmt_bind_param($stmt_search, "sss", $search_term_param, $search_term_param, $search_term_param);
            if (mysqli_stmt_execute($stmt_search)) {
                $result_search = mysqli_stmt_get_result($stmt_search);
                while ($row = mysqli_fetch_assoc($result_search)) {
                    $search_results[] = $row;
                }
                mysqli_free_result($result_search);
            } else {
                $search_error = "Database error during search.";
                error_log("DB Error searching mothers: " . mysqli_stmt_error($stmt_search));
            }
            mysqli_stmt_close($stmt_search);
        } else {
             $search_error = "Database error preparing search query.";
              error_log("DB Error preparing search query: " . mysqli_error($link));
        }
    }
}


// --- HTML CONTENT FOR THE MAIN AREA ---
// This is the content fragment displayed by AJAX

?>

<h2>Select Mother for Action</h2>
<p>Choose the mother you want to perform the "<?php echo htmlspecialchars(str_replace('_', ' ', $intended_action)); ?>" action for.</p>

<?php if (!empty($search_error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($search_error); ?></div>
<?php endif; ?>

<!-- Search Form -->
<form action="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/select_mother_for_action.php?action=<?php echo htmlspecialchars($intended_action); ?>" method="post" id="mother-search-form">
    <div class form-row">
        <div class="col-md-8">
             <div class="form-group">
                 <label for="search_term">Search by Name or ID</label>
                 <input type="text" name="search_term" id="search_term" class="form-control" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter first name, last name, or ID">
             </div>
        </div>
         <div class="col-md-4">
             <div class="form-group" style="margin-top: 32px;"> <?php // Align button baseline ?>
                 <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Search</button>
             </div>
        </div>
    </div>
</form>

<!-- Search Results -->
<?php if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($search_error)): ?>
    <?php if (empty($search_results)): ?>
        <div class="alert alert-info mt-4">No mothers found matching "<?php echo htmlspecialchars($search_term); ?>".</div>
    <?php else: ?>
        <h4 class="mt-4">Search Results:</h4>
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Date of Birth</th>
                    <th>Phone</th>
                    <th>National ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($search_results as $mother): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mother['mother_id']); ?></td>
                    <td><?php echo htmlspecialchars($mother['first_name'] . ' ' . $mother['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($mother['date_of_birth'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($mother['phone_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($mother['national_id'] ?? 'N/A'); ?></td>
                    <td>
                        <?php
                        // Determine the correct URL for the specific action page
                        $action_page_url = '';
                        switch ($intended_action) {
                            case 'record_anc': $action_page_url = PROJECT_SUBDIRECTORY . "/midwife/record_anc.php"; break;
                            case 'take_vitals': $action_page_url = PROJECT_SUBDIRECTORY . "/midwife/take_vital_sign.php"; break;
                            case 'send_lab': $action_page_url = PROJECT_SUBDIRECTORY . "/midwife/send_lab_request.php"; break;
                            case 'send_ultrasound': $action_page_url = PROJECT_SUBDIRECTORY . "/midwife send_ultrasound_request.php"; break;
                            case 'schedule_appt': $action_page_url = PROJECT_SUBDIRECTORY . "/midwife/schedule_appointment.php"; break;
                        }
                        ?>
                        <!-- Link to the actual action page, passing mother_id -->
                        <!-- NOTE: This link will trigger a FULL PAGE LOAD by default -->
                        <a href="<?php echo htmlspecialchars($action_page_url); ?>?mother_id=<?php echo htmlspecialchars($mother['mother_id']); ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-<?php echo ($intended_action == 'record_anc' ? 'notes-medical' : ($intended_action == 'take_vitals' ? 'heartbeat' : ($intended_action == 'send_lab' ? 'vials' : ($intended_action == 'send_ultrasound' ? 'x-ray' : ($intended_action == 'schedule_appt' ? 'calendar-plus' : 'info-circle'))))); ?> mr-1"></i>
                            Select Mother
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>


<?php
// --- END OF HTML CONTENT ---
// Footer is not included for AJAX requests
?>