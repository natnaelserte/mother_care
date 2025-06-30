<?php
// Include authentication and authorization
require_once(__DIR__ . '/../core/auth.php'); // This includes paths.php and starts session

require_login(); // Ensure user is logged in
require_role('Data Clerk', 'dashboard.php'); // Ensure user is a Data Clerk

// --- Calculate relative path to root ---
$script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /dataclerk
$depth = count(explode('/', trim($script_dir, '/')));
$base_url = str_repeat('../', $depth);
// --- End calculation ---


// Set the page title
$pageTitle = "View Registered Mothers";

// Include the header
require_once(__DIR__ . '/../includes/header.php'); // Header includes $base_url calculation internally

// Include database connection
require_once(__DIR__ . '/../config/db.php');

// Fetch all mothers from the database
$sql = "SELECT mother_id, first_name, last_name, date_of_birth, phone_number, registration_date, national_id
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
    $error_message = "Error fetching mothers: " . mysqli_error($link);
     error_log("Error fetching mothers: " . mysqli_error($link)); // Log the error
}

// Close connection
mysqli_close($link);
?>

<h2>Registered Mothers</h2>
<p>List of mothers registered in the system.</p>

<p>
    <!-- Link to register a new mother -->
    <a href="<?php echo $base_url; ?>dataclerk/register_mother.php" class="btn btn-success"><i class="fas fa-user-plus mr-1"></i> Register New Mother</a>
</p>


<?php
 // Display error/success messages from session
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
                <th>National ID</th>
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
                 <td><?php echo htmlspecialchars($mother['national_id'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($mother['registration_date']); ?></td>
                <td>
                    <!-- Link to update mother details using $base_url -->
                    <a href="<?php echo $base_url; ?>dataclerk/update_mother.php?id=<?php echo htmlspecialchars($mother['mother_id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit mr-1"></i> Edit Info</a>
                    <!-- Data Clerks might also view mother details like midwives, but let's keep it simple for now -->
                    <!-- <a href="<?php // echo $base_url; ?>midwife/mother_details.php?id=<?php // echo htmlspecialchars($mother['mother_id']); ?>" class="btn btn-sm btn-info">View Details</a> -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
// Include the footer
require_once(__DIR__ . '/../includes/footer.php'); // Footer includes $base_url calculation internally
?>