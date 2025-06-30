<?php
// Include authentication and authorization
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php'); // Adjust path based on depth (laboratorist is 2 levels deep)

require_login(); // Ensure user is logged in
require_role('Laboratorist', 'views/dashboard.php'); // Ensure user is a Laboratorist, redirect to dashboard if not

// Ensure database connection is available (core/auth.php provides $link globally)
global $link; // Access the global database connection

// --- Page specific logic ---
// Fetch lab requests from the database
// Join with mothers table to get mother's name
// Join with lab_results to check if a result exists
$sql = "SELECT
            lr.request_id,
            lr.mother_id,
            m.first_name,
            m.last_name,
            lr.request_date,
            lr.requested_tests,
            lr.request_status,
            lres.result_id AS has_result, -- Check if a result exists for this request
            lr.anc_record_id -- Optional: display linked ANC record ID
        FROM lab_requests lr
        JOIN mothers m ON lr.mother_id = m.mother_id
        LEFT JOIN lab_results lres ON lr.request_id = lres.request_id -- Use LEFT JOIN to include requests without results
        ORDER BY lr.request_date DESC"; // Order by request date

$result = mysqli_query($link, $sql);

$requests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    mysqli_free_result($result);
} else {
    $error_message = "Error fetching lab requests: " . mysqli_error($link);
     error_log("Error fetching lab requests: " . mysqli_error($link)); // Log the error
}

// Close database connection (handled globally by auth.php / footer.php)
// mysqli_close($link); // Remove explicit close here


// Set the page title (must be done BEFORE including the header)
$pageTitle = "Laboratorist - Lab Requests";

// Include the header (provides fixed layout and opens main content area)
require_once(__DIR__ . '/../includes/header.php'); // Adjust path based on depth

?>

<!-- The content below will be placed inside the main-content-area div opened in header.php -->

<h2>Lab Requests</h2>
<p>View pending and completed lab requests.</p>

<?php
 // Display error/success messages from session (set by redirects *to* this page)
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

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>


<?php if (empty($requests)): ?>
    <div class="alert alert-info">No lab requests found.</div>
<?php else: ?>
    <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Request Date</th>
                <th>Mother Name</th>
                <th>Requested Tests</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                <td>
                     <!-- Link to mother details (Midwife page) - Use PROJECT_SUBDIRECTORY -->
                     <!-- Clicking this link will trigger a FULL page load -->
                     <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/mother_details.php?id=<?php echo htmlspecialchars($request['mother_id']); ?>" title="View Mother Details">
                         <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                     </a>
                     <?php if($request['anc_record_id']): ?>
                         <small class="text-muted">(ANC #<?php echo htmlspecialchars($request['anc_record_id']); ?>)</small>
                     <?php endif; ?>
                </td>
                <td><?php echo nl2br(htmlspecialchars($request['requested_tests'])); ?></td>
                <td><span class="badge badge-<?php echo ($request['request_status'] == 'Completed' ? 'success' : ($request['request_status'] == 'Cancelled' ? 'danger' : 'warning')); ?>"><?php echo htmlspecialchars($request['request_status']); ?></span></td>
                <td>
                    <?php if ($request['has_result']): ?>
                         <!-- Link to view result - Use PROJECT_SUBDIRECTORY -->
                         <!-- Clicking this link will trigger a FULL page load -->
                        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_result.php?id=<?php echo htmlspecialchars($request['has_result']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt mr-1"></i> View Result</a>
                    <?php else: ?>
                         <!-- Link to enter result - Use PROJECT_SUBDIRECTORY -->
                         <!-- Clicking this link will trigger a FULL page load -->
                        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/enter_lab_results.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit mr-1"></i> Enter Result</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
// Include the footer (closes layout divs and DB connection)
require_once(__DIR__ . '/../includes/footer.php'); // Adjust path based on depth
?>