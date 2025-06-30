<?php
// Include authentication and authorization functions.
require_once(__DIR__ . '/../core/auth.php');

// Since auth.php includes language.php, the __() function is globally available.

// Ensure PROJECT_SUBDIRECTORY is defined. auth.php should handle this via paths.php
if (!defined('PROJECT_SUBDIRECTORY')) { define('PROJECT_SUBDIRECTORY', ''); }


require_login();
require_role('Administrator', 'views/dashboard.php');

global $link;


$pageTitle = __("admin_reports_audit_log");

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$start_date = htmlspecialchars($start_date);
$end_date = htmlspecialchars($end_date);


$sql = "SELECT al.log_id, u.username AS performed_by_username, al.action_type, al.target_id, al.details, al.timestamp
        FROM audit_log al
        JOIN users u ON al.user_id = u.user_id";

$where_clauses = [];
$bind_types = "";
$bind_params = [];

if (!empty($start_date) && !empty($end_date)) {
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
        $where_clauses[] = "al.timestamp BETWEEN ? AND ?";
        $bind_types .= "ss";
        $param_end_date = $end_date . ' 23:59:59';
        $bind_params[] = &$start_date;
        $bind_params[] = &$param_end_date;
    } else {
        $_SESSION['error_message'] = (__($_SESSION['error_message'] ?? '') ? __($_SESSION['error_message'] ?? '') . " " : "") . __("invalid_date_format");
        $_SESSION['message_type'] = "warning";
        $start_date = $end_date = "";
    }
} elseif (!empty($start_date)) {
     if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date)) {
        $where_clauses[] = "al.timestamp >= ?";
        $bind_types .= "s";
        $bind_params[] = &$start_date;
     } else { $_SESSION['error_message'] = (__($_SESSION['error_message'] ?? '') ? __($_SESSION['error_message'] ?? '') . " " : "") . __("invalid_date_format"); $_SESSION['message_type'] = "warning"; $start_date = ""; }
} elseif (!empty($end_date)) {
     if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
        $where_clauses[] = "al.timestamp <= ?";
        $bind_types .= "s";
        $param_end_date = $end_date . ' 23:59:59';
        $bind_params[] = &$param_end_date;
     } else { $_SESSION['error_message'] = (__($_SESSION['error_message'] ?? '') ? __($_SESSION['error_message'] ?? '') . " " : "") . __("invalid_date_format"); $_SESSION['message_type'] = "warning"; $end_date = ""; }
}


if (!empty($where_clauses)) { $sql .= " WHERE " . implode(" AND ", $where_clauses); }

$sql .= " ORDER BY al.timestamp DESC";

$sql .= " LIMIT 100";


$audit_logs = [];
$fetch_error_message = "";

if ($stmt = mysqli_prepare($link, $sql)) {
    if (!empty($bind_types)) { array_unshift($bind_params, $stmt, $bind_types); call_user_func_array('mysqli_stmt_bind_param', $bind_params); }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) { $audit_logs[] = $row; }
        mysqli_free_result($result);
    } else { $fetch_error_message = __("error_fetching_audit_logs") . " " . mysqli_stmt_error($stmt); error_log("Error fetching audit logs for reports: " . mysqli_stmt_error($stmt)); }
    mysqli_stmt_close($stmt);

} else { $fetch_error_message = __("database_error_preparing_query") . " " . mysqli_error($link); error_log("Database error preparing audit log query: " . mysqli_error($link)); }


require_once(__DIR__ . '/../includes/header.php');
?>

    <h2><?php echo __("admin_reports_audit_log"); ?></h2>
    <p><?php echo __("displays_recent_admin_actions"); ?></p>

    <?php
     if (isset($_SESSION['message'])) { $msg_class = $_SESSION['message_type'] ?? 'info'; echo '<div class="alert alert-' . htmlspecialchars($msg_class) . '">' . htmlspecialchars($_SESSION['message']) . '</div>'; unset($_SESSION['message']); unset($_SESSION['message_type']); }
     if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
     if (isset($_SESSION['log_warning'])) { echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['log_warning']) . '</div>'; unset($_SESSION['log_warning']); }
    ?>

     <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/reports.php" method="GET" class="form-inline mb-3">
         <div class="form-group mr-2">
             <label for="start_date" class="mr-1"><?php echo __("from"); ?>:</label>
             <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
         </div>
         <div class="form-group mr-2">
             <label for="end_date" class="mr-1"><?php echo __("to"); ?>:</label>
             <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
         </div>
         <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> <?php echo __("filter"); ?></button>
          <?php if (!empty($start_date) || !empty($end_date)): ?>
             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/reports.php" class="btn btn-secondary ml-2"><?php echo __("reset_filter"); ?></a>
         <?php endif; ?>
     </form>


    <?php if (!empty($fetch_error_message)): ?> <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error_message); ?></div> <?php endif; ?>

    <?php if (empty($audit_logs)): ?> <div class="alert alert-info"><?php echo __("no_audit_logs_found_criteria"); ?></div>
    <?php else: ?>
        <p class="mt-3"><?php echo sprintf(__("showing_last_entries"), count($audit_logs)); ?></p>
        <table class="table table-striped table-bordered table-sm">
            <thead>
                <tr>
                    <th><?php echo __("timestamp"); ?></th>
                    <th><?php echo __("action_by"); ?></th>
                    <th><?php echo __("action_type"); ?></th>
                    <th><?php echo __("target_id"); ?></th>
                    <th><?php echo __("details"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                    <td><?php echo htmlspecialchars($log['performed_by_username']); ?></td>
                    <td><?php
                         $action_key = $log['action_type'];
                         echo htmlspecialchars(__(str_replace('_', ' ', $action_key)));
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($log['target_id'] ?? __("n_a")); ?></td>
                    <td><?php
                         $details = $log['details'] ?? '';
                         if (!empty($details)) {
                             $json_details = json_decode($details, true);
                             if ($json_details !== null) { echo '<pre style="max-height: 100px; overflow-y: auto; margin: 0; padding: 5px; border: 1px solid #eee; background-color: #f9f9f9;"><small>' . htmlspecialchars(json_encode($json_details, JSON_PRETTY_PRINT)) . '</small></pre>'; }
                             else { echo '<small>' . htmlspecialchars($details) . '</small>'; }
                         } else { echo __("n_a"); }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-3">
             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/export_audit_log.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success mr-2"><i class="fas fa-download mr-1"></i> <?php echo __("export_to_csv"); ?></a>
             <p class="mt-3 text-muted"><?php echo __("more_reporting_features_soon"); ?></p>
        </div>
    <?php endif; ?>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>