<?php
// Include authentication and authorization functions.
// This file handles:
// - session_start()
// - Including config/paths.php (defines PROJECT_SUBDIRECTORY)
// - Including config/config.php (defines DB constants like DB_SERVER etc.)
// - Establishing the $link database connection globally
require_once(__DIR__ . '/../core/auth.php');

// Ensure the user is logged in.
require_login();

// Access user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];

// $link database connection is available globally after including core/auth.php
global $link; // Make it explicit


// --- Fetch Data for Charts and Stats based on Role ---

$chart_data = []; // Array to hold data for different charts

if (has_role('Administrator')) {
    // Admin Charts & Stats

    // 1. User Distribution by Role (Pie Chart) - Keep existing query
    $sql_users_by_role = "SELECT r.role_name, COUNT(u.user_id) AS user_count
                          FROM roles r
                          LEFT JOIN users u ON r.role_id = u.role_id
                          GROUP BY r.role_name
                          ORDER BY r.role_name";
    $result_users_by_role = mysqli_query($link, $sql_users_by_role);
    $chart_data['users_by_role'] = [];
    if ($result_users_by_role) {
        while ($row = mysqli_fetch_assoc($result_users_by_role)) {
            $chart_data['users_by_role'][] = $row;
        }
        mysqli_free_result($result_users_by_role);
    } else {
        error_log("Error fetching user distribution: " . mysqli_error($link));
    }

    // 2. ANC Visits per Month (Bar Chart) - Keep existing query
    $sql_visits_per_month = "SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as visit_count
                             FROM anc_records
                             WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                             GROUP BY month
                             ORDER BY month";
     $result_visits_per_month = mysqli_query($link, $sql_visits_per_month);
    $chart_data['visits_per_month'] = [];
    if ($result_visits_per_month) {
        while ($row = mysqli_fetch_assoc($result_visits_per_month)) {
            $chart_data['visits_per_month'][] = $row;
        }
        mysqli_free_result($result_visits_per_month);
    } else {
         error_log("Error fetching visits per month: " . mysqli_error($link));
    }

    // 3. Recent Activity (Total records created per day) - Keep existing query
    $sql_recent_activity = "SELECT activity_date, SUM(daily_count) as total_daily_activity
                            FROM (
                                -- Users created
                                SELECT DATE(created_at) as activity_date, COUNT(*) as daily_count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- Mothers registered
                                SELECT DATE(created_at) as activity_date, COUNT(*) as daily_count FROM mothers WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- ANC Records created
                                SELECT DATE(created_at) as activity_date, COUNT(*) as daily_count FROM anc_records WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- Vital Signs recorded
                                SELECT DATE(created_at) as activity_date, COUNT(*) as daily_count FROM vital_signs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- Lab Requests sent
                                SELECT DATE(request_date) as activity_date, COUNT(*) as daily_count FROM lab_requests WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- Ultrasound Requests sent
                                SELECT DATE(request_date) as activity_date, COUNT(*) as daily_count FROM ultrasound_requests WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                UNION ALL
                                -- Appointments scheduled
                                SELECT DATE(created_at) as activity_date, COUNT(*) as daily_count FROM appointments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY activity_date
                                -- Add other tables with relevant timestamps if needed
                            ) as combined_activities
                            GROUP BY activity_date
                            ORDER BY activity_date;";

    $result_recent_activity = mysqli_query($link, $sql_recent_activity);
    $chart_data['recent_activity'] = [];
    if ($result_recent_activity) {
        while ($row = mysqli_fetch_assoc($result_recent_activity)) {
            $chart_data['recent_activity'][] = $row;
        }
        mysqli_free_result($result_recent_activity);
    } else {
        error_log("Error fetching recent activity: " . mysqli_error($link));
    }


    // --- Fetch Admin Stats (already handled, keep here) ---
    $total_users = 0;
    $total_mothers = 0;
    $total_anc_records = 0;

    $sql_total_users = "SELECT COUNT(*) AS total FROM users";
    if ($result = mysqli_query($link, $sql_total_users)) { $row = mysqli_fetch_assoc($result); $total_users = $row['total']; mysqli_free_result($result); } else { error_log("Error fetching total users stat: " . mysqli_error($link)); $total_users = 'Error'; }

    $sql_total_mothers = "SELECT COUNT(*) AS total FROM mothers";
    if ($result = mysqli_query($link, $sql_total_mothers)) { $row = mysqli_fetch_assoc($result); $total_mothers = $row['total']; mysqli_free_result($result); } else { error_log("Error fetching total mothers stat: " . mysqli_error($link)); $total_mothers = 'Error'; }

    $sql_total_anc_records = "SELECT COUNT(*) AS total FROM anc_records";
    if ($result = mysqli_query($link, $sql_total_anc_records)) { $row = mysqli_fetch_assoc($result); $total_anc_records = $row['total']; mysqli_free_result($result); } else { error_log("Error fetching total ANC records stat: " . mysqli_error($link)); $total_anc_records = 'Error'; }


} elseif (has_role('Data Clerk')) {
     // Data Clerk Charts
     // ... (Keep existing Data Clerk chart queries) ...
     $sql_registrations_per_month = "SELECT DATE_FORMAT(registration_date, '%Y-%m') as month, COUNT(*) as registration_count
                                     FROM mothers
                                     WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                     GROUP BY month
                                     ORDER BY month";
      $result_registrations_per_month = mysqli_query($link, $sql_registrations_per_month);
     $chart_data['registrations_per_month'] = [];
     if ($result_registrations_per_month) {
         while ($row = mysqli_fetch_assoc($result_registrations_per_month)) {
             $chart_data['registrations_per_month'][] = $row;
         }
         mysqli_free_result($result_registrations_per_month);
     } else {
          error_log("Error fetching registrations per month: " . mysqli_error($link));
     }

     // 2. Example: Mothers by Age Group (Placeholder Data)
     // You would query date_of_birth and calculate age groups
      $chart_data['mothers_by_age_group'] = [
          ['age_group' => 'Under 20', 'count' => rand(10, 50)],
          ['age_group' => '20-24', 'count' => rand(50, 150)],
          ['age_group' => '25-29', 'count' => rand(80, 200)],
          ['age_group' => '30-34', 'count' => rand(40, 120)],
          ['age_group' => '35+', 'count' => rand(10, 60)],
     ];


} elseif (has_role('Midwife')) {
     // Midwife Charts

     // 1. Upcoming Appointments (Pie Chart) - Keep existing query
      $sql_appointments_summary = "SELECT
                                    SUM(CASE WHEN appointment_date_time BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY) - INTERVAL 1 SECOND THEN 1 ELSE 0 END) as today,
                                    SUM(CASE WHEN appointment_date_time BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) - INTERVAL 1 SECOND THEN 1 ELSE 0 END) as tomorrow,
                                    SUM(CASE WHEN appointment_date_time BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) - INTERVAL 1 SECOND THEN 1 ELSE 0 END) as this_week,
                                    SUM(CASE WHEN appointment_date_time > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as later
                                FROM appointments";
      $result_appointments_summary = mysqli_query($link, $sql_appointments_summary);
      $chart_data['appointments_summary'] = [];
      if ($result_appointments_summary) {
          $chart_data['appointments_summary'] = mysqli_fetch_assoc($result_appointments_summary);
          mysqli_free_result($result_appointments_summary);
      } else {
          error_log("Error fetching appointment summary: " . mysqli_error($link));
      }


     // 2. Visits Recorded per Month (Bar Chart) - Keep existing query
       $sql_midwife_visits_per_month = "SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as visit_count
                                      FROM anc_records
                                      WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                      GROUP BY month
                                      ORDER BY month";
       $result_midwife_visits_per_month = mysqli_query($link, $sql_midwife_visits_per_month);
      $chart_data['midwife_visits_per_month'] = [];
      if ($result_midwife_visits_per_month) {
          while ($row = mysqli_fetch_assoc($result_midwife_visits_per_month)) {
              $chart_data['midwife_visits_per_month'][] = $row;
          }
          mysqli_free_result($result_midwife_visits_per_month);
      } else {
           error_log("Error fetching midwife visits per month: " . mysqli_error($link));
      }

    // 3. Recent ANC Records Created per Day (Bar Chart) - NEW QUERY for Midwife
     $sql_midwife_recent_anc = "SELECT DATE(created_at) as activity_date, COUNT(*) as record_count
                                 FROM anc_records
                                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                 GROUP BY activity_date
                                 ORDER BY activity_date;";

    $result_midwife_recent_anc = mysqli_query($link, $sql_midwife_recent_anc);
    $chart_data['midwife_recent_anc'] = [];
    if ($result_midwife_recent_anc) {
        while ($row = mysqli_fetch_assoc($result_midwife_recent_anc)) {
            $chart_data['midwife_recent_anc'][] = $row;
        }
        mysqli_free_result($result_midwife_recent_anc);
    } else {
        error_log("Error fetching midwife recent ANC activity: " . mysqli_error($link));
    }


} elseif (has_role('Laboratorist')) {
    // Laboratorist Charts
     // ... (Keep existing Laboratorist chart queries) ...
      $sql_lab_status = "SELECT request_status, COUNT(*) as count FROM lab_requests GROUP BY request_status";
     $result_lab_status = mysqli_query($link, $sql_lab_status); $chart_data['lab_status'] = []; if ($result_lab_status) { while ($row = mysqli_fetch_assoc($result_lab_status)) { $chart_data['lab_status'][] = $row; } mysqli_free_result($result_lab_status); } else { error_log("Error fetching lab status: " . mysqli_error($link)); }

      $sql_lab_completed_month = "SELECT DATE_FORMAT(report_date, '%Y-%m') as month, COUNT(*) as completed_count FROM lab_results WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month";
      $result_lab_completed_month = mysqli_query($link, $sql_lab_completed_month); $chart_data['lab_completed_month'] = []; if ($result_lab_completed_month) { while ($row = mysqli_fetch_assoc($result_lab_completed_month)) { $chart_data['lab_completed_month'][] = $row; } mysqli_free_result($result_lab_completed_month); } else { error_log("Error fetching lab completed per month: " . mysqli_error($link)); }


} elseif (has_role('Radiologist')) {
    // Radiologist Charts
    // ... (Keep existing Radiologist chart queries) ...
    $sql_ultra_status = "SELECT request_status, COUNT(*) as count FROM ultrasound_requests GROUP BY request_status";
    $result_ultra_status = mysqli_query($link, $sql_ultra_status); $chart_data['ultra_status'] = []; if ($result_ultra_status) { while ($row = mysqli_fetch_assoc($result_ultra_status)) { $chart_data['ultra_status'][] = $row; } mysqli_free_result($result_ultra_status); } else { error_log("Error fetching ultrasound status: " . mysqli_error($link)); }

    $sql_ultra_completed_month = "SELECT DATE_FORMAT(report_date, '%Y-%m') as month, COUNT(*) as completed_count FROM ultrasound_results WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month";
    $result_ultra_completed_month = mysqli_query($link, $sql_ultra_completed_month); $chart_data['ultra_completed_month'] = []; if ($result_ultra_completed_month) { while ($row = mysqli_fetch_assoc($result_ultra_completed_month)) { $chart_data['ultra_completed_month'][] = $row; } mysqli_free_result($result_ultra_completed_month); } else { error_log("Error fetching ultrasound completed per month: " . mysqli_error($link)); }

}

// Database connection should NOT be closed here. It should remain open
// until the script finishes or the footer is included (if the footer handles closing).


// Set the page title BEFORE including the header
$pageTitle = "Dashboard";

// Include the header for logged-in users.
// The header includes config/paths.php and uses PROJECT_SUBDIRECTORY for links/assets.
// It also opens the main content column div.
require_once(__DIR__ . '/../includes/header.php');

?>

    <!-- The content below will be placed inside the main-content-column div opened in header.php -->

    <h2 class="welcome-heading">Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
    <p class="text-muted mb-4">You are logged in as: <?php echo htmlspecialchars($role_name); ?></p>

    <?php
    // Display error/success messages from session
    if (isset($_SESSION['message'])) {
        $msg_class = $_SESSION['message_type'] ?? 'info'; // default to info if type not set
        echo '<div class="alert alert-' . $msg_class . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
        unset($_SESSION['message_type']);
    }
     // Display authorization error message if redirected (from require_role)
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']); // Clear the message after displaying
    }
    ?>


    <?php if (has_role('Administrator')): ?>
        <h3>Admin Dashboard Overview</h3>

        <!-- Quick Statistics Section (Admin Only) -->
        <div class="row stats-widget-row">
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                         <i class="fas fa-users stats-icon"></i>
                        <div class="stats-value"><?php echo htmlspecialchars($total_users); ?></div>
                        <div class="stats-label">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-female stats-icon" style="color: #e83e8c;"></i>
                        <div class="stats-value"><?php echo htmlspecialchars($total_mothers); ?></div>
                        <div class="stats-label">Registered Mothers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                 <div class="card">
                    <div class="card-body">
                         <i class="fas fa-notes-medical stats-icon" style="color: #20c997;"></i>
                        <div class="stats-value"><?php echo htmlspecialchars($total_anc_records); ?></div>
                        <div class="stats-label">ANC Visit Records</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                 <div class="card">
                    <div class="card-body">
                         <i class="fas fa-chart-line stats-icon" style="color: #ffc107;"></i> <!-- Example icon -->
                        <div class="stats-value">--</div> <?php // Placeholder value ?>
                        <div class="stats-label">Avg Visits per Mother</div> <!-- Example label -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Widgets -->
        <div class="row mt-4">
             <div class="col-md-6">
                 <div class="card main-content-widget">
                     <div class="card-header">User Distribution by Role</div>
                     <div class="card-body">
                          <canvas id="adminUserRoleChart" width="400" height="300"></canvas>
                     </div>
                 </div>
             </div>
              <div class="col-md-6">
                 <div class="card main-content-widget">
                     <div class="card-header">ANC Visits per Month (Last 12 Months)</div>
                     <div class="card-body">
                          <canvas id="adminVisitsPerMonthChart" width="400" height="300"></canvas>
                     </div>
                 </div>
             </div>
        </div>
         <!-- Recent Activity Chart -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card main-content-widget">
                    <div class="card-header">Total Recent Activity (Last 30 Days)</div>
                    <div class="card-body">
                         <canvas id="adminRecentActivityChart" width="800" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>


    <?php elseif (has_role('Data Clerk')): ?>
         <h3>Data Clerk Dashboard Overview</h3>
         <!-- Data Clerk specific stats (if any) - Use actual stats if possible -->
         <div class="row stats-widget-row">
             <div class="col-md-6 col-lg-6">
                 <div class="card">
                     <div class="card-body">
                          <i class="fas fa-female stats-icon" style="color: #e83e8c;"></i>
                         <?php /* Query the DB for this stat */ ?>
                         <div class="stats-value">--</div>
                         <div class="stats-label">Mothers Registered Today</div>
                     </div>
                 </div>
             </div>
              <div class="col-md-6 col-lg-6">
                 <div class="card">
                     <div class="card-body">
                          <i class="fas fa-female stats-icon" style="color: #17a2b8;"></i>
                           <?php /* Query the DB for this stat */ ?>
                         <div class="stats-value">--</div>
                         <div class="stats-label">Mothers Registered This Week</div>
                     </div>
                 </div>
             </div>
             </div>
             <div class="row mt-4">
                  <div class="col-md-12">
                     <div class="card main-content-widget">
                          <div class="card-header">Mothers Registered per Month (Last 12 Months)</div>
                          <div class="card-body">
                               <canvas id="dataClerkRegistrationsPerMonthChart" width="400" height="200"></canvas>
                          </div>
                      </div>
                  </div>
             </div>
             <div class="row mt-4">
                 <div class="col-md-6">
                      <div class="card main-content-widget">
                           <div class="card-header">Mothers by Age Group (Placeholder Data)</div>
                           <div class="card-body">
                                <canvas id="dataClerkMothersByAgeGroupChart" width="400" height="250"></canvas>
                           </div>
                       </div>
                  </div>
                  <div class="col-md-6">
                     <div class="card main-content-widget">
                          <div class="card-header">Recent Registrations (Placeholder)</div>
                          <div class="card-body">
                              <p class="text-muted">Area for displaying a list of recently registered mothers.</p>
                               <div style="height: 150px; background-color: #f8f9fa; border: 1px dashed #ccc; text-align: center; padding-top: 50px;">Recent List Placeholder</div>
                          </div>
                      </div>
                  </div>
             </div>


                <?php elseif (has_role('Midwife')): ?>
                     <h3>Midwife Dashboard Overview</h3>
                     <!-- Midwife specific stats -->
                     <div class="row stats-widget-row">
                          <div class="col-md-6 col-lg-6">
                              <div class="card">
                                  <div class="card-body">
                                       <i class="fas fa-calendar-check stats-icon" style="color: #28a745;"></i>
                                      <?php /* Query the DB for this stat */ ?>
                                      <div class="stats-value">--</div>
                                      <div class="stats-label">Appointments Today</div>
                                  </div>
                              </div>
                          </div>
                           <div class="col-md-6 col-lg-6">
                              <div class="card">
                                  <div class="card-body">
                                       <i class="fas fa-notes-medical stats-icon" style="color: #20c997;"></i>
                                       <?php /* Query the DB for this stat */ ?>
                                      <div class="stats-value">--</div>
                                      <div class="stats-label">Visits Recorded Today</div>
                                  </div>
                              </div>
                          </div>
                     </div>
                      <div class="row mt-4">
                           <div class="col-md-6">
                              <div class="card main-content-widget">
                                   <div class="card-header">Upcoming Appointment Summary</div>
                                   <div class="card-body">
                                        <canvas id="midwifeAppointmentsSummaryChart" width="400" height="300"></canvas>
                                   </div>
                               </div>
                           </div>
                            <div class="col-md-6">
                               <div class="card main-content-widget">
                                    <div class="card-header">Visits Recorded per Month (Last 6 Months)</div>
                                    <div class="card-body">
                                         <canvas id="midwifeVisitsPerMonthChart" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                       </div>
                       <!-- Recent ANC Activity Chart - Replaces placeholder -->
                       <div class="row mt-4">
                           <div class="col-md-12">
                              <div class="card main-content-widget">
                                   <div class="card-header">Recent ANC Records Created (Last 30 Days)</div>
                                   <div class="card-body">
                                       <canvas id="midwifeRecentANCChart" width="800" height="300"></canvas> <?php // Adjust height as needed ?>
                                   </div>
                               </div>
                           </div>
                       </div>


                <?php elseif (has_role('Laboratorist')): ?>
                     <h3>Laboratorist Dashboard Overview</h3>
                     <div class="row stats-widget-row">
                         <div class="col-md-6 col-lg-6">
                             <div class="card">
                                 <div class="card-body">
                                      <i class="fas fa-vials stats-icon" style="color: #17a2b8;"></i>
                                      <?php /* Query the DB for this stat */ ?>
                                     <div class="stats-value">--</div>
                                     <div class="stats-label">Pending Lab Requests</div>
                                 </div>
                             </div>
                         </div>
                          <div class="col-md-6 col-lg-6">
                             <div class="card">
                                 <div class="card-body">
                                      <i class="fas fa-check-double stats-icon" style="color: #28a745;"></i>
                                       <?php /* Query the DB for this stat */ ?>
                                     <div class="stats-value">--</div>
                                     <div class="stats-label">Results Reported Today</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="row mt-4">
                          <div class="col-md-6">
                             <div class="card main-content-widget">
                                  <div class="card-header">Lab Request Status</div>
                                  <div class="card-body">
                                       <canvas id="laboratoristLabStatusChart" width="400" height="300"></canvas>
                                  </div>
                              </div>
                          </div>
                           <div class="col-md-6">
                              <div class="card main-content-widget">
                                   <div class="card-header">Completed Results per Month (Last 6 Months)</div>
                                   <div class="card-body">
                                        <canvas id="laboratoristCompletedResultsChart" width="400" height="300"></canvas>
                                   </div>
                               </div>
                           </div>
                       </div>
                       <div class="row mt-4">
                            <div class="col-md-12">
                               <div class="card main-content-widget">
                                    <div class="card-header">Recent Completed Results (Placeholder)</div>
                                    <div class="card-body">
                                        <p class="text-muted">Area for displaying a list of recently completed lab results.</p>
                                         <div style="height: 150px; background-color: #f8f9fa; border: 1px dashed #ccc; text-align: center; padding-top: 50px;">Recent List Placeholder</div>
                                    </div>
                                </div>
                            </div>
                       </div>


                <?php elseif (has_role('Radiologist')): ?>
                     <h3>Radiologist Dashboard Overview</h3>
                     <div class="row stats-widget-row">
                         <div class="col-md-6 col-lg-6">
                             <div class="card">
                                 <div class="card-body">
                                      <i class="fas fa-x-ray stats-icon" style="color: #6f42c1;"></i>
                                       <?php /* Query the DB for this stat */ ?>
                                     <div class="stats-value">--</div>
                                     <div class="stats-label">Pending Ultrasound Requests</div>
                                 </div>
                             </div>
                         </div>
                          <div class="col-md-6 col-lg-6">
                             <div class="card">
                                 <div class="card-body">
                                      <i class="fas fa-check-double stats-icon" style="color: #28a745;"></i>
                                       <?php /* Query the DB for this stat */ ?>
                                     <div class="stats-value">--</div>
                                     <div class="stats-label">Results Reported Today</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                      <div class="row mt-4">
                          <div class="col-md-6">
                             <div class="card main-content-widget">
                                  <div class="card-header">Ultrasound Request Status</div>
                                  <div class="card-body">
                                       <canvas id="radiologistUltraStatusChart" width="400" height="300"></canvas>
                                  </div>
                              </div>
                          </div>
                           <div class="col-md-6">
                              <div class="card main-content-widget">
                                   <div class="card-header">Completed Results per Month (Last 6 Months)</div>
                                   <div class="card-body">
                                        <canvas id="radiologistCompletedResultsChart" width="400" height="300"></canvas>
                                   </div>
                               </div>
                           </div>
                       </div>
                       <div class="row mt-4">
                            <div class="col-md-12">
                               <div class="card main-content-widget">
                                    <div class="card-header">Recent Completed Results (Placeholder)</div>
                                    <div class="card-body">
                                        <p class="text-muted">Area for displaying a list of recently completed ultrasound results.</p>
                                         <div style="height: 150px; background-color: #f8f9fa; border: 1px dashed #ccc; text-align: center; padding-top: 50px;">Recent List Placeholder</div>
                                    </div>
                                </div>
                            </div>
                       </div>


                <?php else: ?>
                    <!-- Default content for any other roles -->
                    <h3>Dashboard Overview</h3>
                    <div class="card main-content-widget">
                         <div class="card-header bg-secondary text-white">
                             <h5 class="mb-0">Available Actions</h5>
                         </div>
                          <div class="card-body">
                             <p>Content for your role is being developed.</p>
                          </div>
                     </div>
                <?php endif; ?>


            <!-- The closing divs for main-content-column, row, and container-fluid are in footer.php -->

<?php
// Include the footer.
// The footer should handle closing the database connection $link
// if it's the last script executed in the request.
require_once(__DIR__ . '/../includes/footer.php');
?>

<!-- === JavaScript for Charts === -->
<script>
$(document).ready(function() {

    // Data embedded from PHP
    var chartData = <?php echo json_encode($chart_data); ?>;

    // Function to render a Bar Chart
    function renderBarChart(ctxId, chartLabel, dataArray, labelKey, dataKey, backgroundColor) {
        var ctx = $('#' + ctxId);
        if (ctx.length && dataArray && dataArray.length > 0) {
            var labels = dataArray.map(function(item) { return item[labelKey]; });
            var data = dataArray.map(function(item) { return item[dataKey]; });

             new Chart(ctx, {
                 type: 'bar',
                 data: {
                     labels: labels,
                     datasets: [{
                         label: chartLabel,
                         data: data,
                         backgroundColor: backgroundColor,
                         borderColor: backgroundColor,
                         borderWidth: 1
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     legend: { display: false },
                     title: { display: false },
                     scales: {
                         yAxes: [{
                             ticks: {
                                 beginAtZero: true,
                                 // stepSize: 1 // Removed step size to auto-adjust for larger numbers
                                 // suggestedMax: Math.max(...data) + 1 // Optional: ensure max tick is slightly above max data
                             }
                         }],
                          xAxes: [{
                            // display: false // Hide x-axis labels if too many
                          }]
                     }
                 }
             });
         } else if (ctx.length) {
              // Display a message if no data
             ctx.parent().html('<p class="text-muted text-center">No data available for this chart.</p>');
         }
    }

    // Function to render a Pie Chart
     function renderPieChart(ctxId, dataArray, labelKey, dataKey, colors) {
         var ctx = $('#' + ctxId);
         if (ctx.length && dataArray && dataArray.length > 0) {
             var labels = dataArray.map(function(item) { return item[labelKey]; });
             var data = dataArray.map(function(item) { return item[dataKey]; });

              new Chart(ctx, {
                  type: 'pie',
                  data: {
                      labels: labels,
                      datasets: [{
                          data: data,
                          backgroundColor: colors,
                          borderColor: '#fff',
                          borderWidth: 1
                      }]
                  },
                  options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      legend: { position: 'right' },
                      title: { display: false }
                  }
              });
          } else if (ctx.length) {
              // Display a message if no data
             ctx.parent().html('<p class="text-muted text-center">No data available for this chart.</p>');
          }
     }


    // --- Admin Charts ---
    if (chartData.users_by_role) {
        var colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1', '#fd7e14'];
        renderPieChart('adminUserRoleChart', chartData.users_by_role, 'role_name', 'user_count', colors);
    }

     if (chartData.visits_per_month) {
         renderBarChart('adminVisitsPerMonthChart', 'ANC Visits', chartData.visits_per_month, 'month', 'visit_count', '#007bff'); // Primary color
     }

     // Admin Recent Activity Chart
     if (chartData.recent_activity) {
          renderBarChart('adminRecentActivityChart', 'Total Daily Activity', chartData.recent_activity, 'activity_date', 'total_daily_activity', '#20c997'); // Teal color
      }


    // --- Data Clerk Charts ---
     if (chartData.registrations_per_month) {
         renderBarChart('dataClerkRegistrationsPerMonthChart', 'Registrations', chartData.registrations_per_month, 'month', 'registration_count', '#28a745'); // Success color
     }

     if (chartData.mothers_by_age_group) {
          var ctx = $('#dataClerkMothersByAgeGroupChart');
          if (ctx.length && chartData.mothers_by_age_group.length > 0) {
             var labels = chartData.mothers_by_age_group.map(function(item) { return item.age_group; });
             var data = chartData.mothers_by_age_group.map(function(item) { return item.count; });
             var colors = ['#e83e8c', '#fd7e14', '#ffc107', '#20c997', '#17a2b8']; // Example colors

             new Chart(ctx, {
                 type: 'pie',
                 data: {
                     labels: labels,
                     datasets: [{
                         data: data,
                         backgroundColor: colors,
                         borderColor: '#fff',
                         borderWidth: 1
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     legend: { position: 'right' },
                     title: { display: false }
                 }
             });
         } else if (ctx.length) {
              ctx.parent().html('<p class="text-muted text-center">No data available for this chart.</p>');
         }
     }


    // --- Midwife Charts ---
    if (chartData.appointments_summary && Object.keys(chartData.appointments_summary).length > 0) {
        var labels = ['Today', 'Tomorrow', 'This Week (Upcoming)', 'Later'];
        var thisWeekExcludingTodayTomorrow = (chartData.appointments_summary.this_week || 0) - (chartData.appointments_summary.today || 0) - (chartData.appointments_summary.tomorrow || 0);
        if (thisWeekExcludingTodayTomorrow < 0) thisWeekExcludingTodayTomorrow = 0;

        var data = [
             chartData.appointments_summary.today || 0,
             chartData.appointments_summary.tomorrow || 0,
             thisWeekExcludingTodayTomorrow,
             chartData.appointments_summary.later || 0
        ];
        var colors = ['#28a745', '#ffc107', '#17a2b8', '#6c757d'];
        var ctx = $('#midwifeAppointmentsSummaryChart');
        if (ctx.length && data.reduce((sum, val) => sum + val, 0) > 0) { // Only render if total is > 0
            new Chart(ctx, {
                 type: 'pie',
                 data: {
                     labels: labels,
                     datasets: [{
                         data: data,
                         backgroundColor: colors,
                         borderColor: '#fff',
                         borderWidth: 1
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     legend: { position: 'right' },
                     title: { display: false }
                 }
             });
        } else if (ctx.length) {
             ctx.parent().html('<p class="text-muted text-center">No data available for this chart.</p>');
        }
    }

     if (chartData.midwife_visits_per_month) {
          renderBarChart('midwifeVisitsPerMonthChart', 'ANC Visits', chartData.midwife_visits_per_month, 'month', 'visit_count', '#20c997'); // Teal color
     }

    // NEW: Midwife Recent ANC Activity Chart
     if (chartData.midwife_recent_anc) {
          renderBarChart('midwifeRecentANCChart', 'ANC Records Created', chartData.midwife_recent_anc, 'activity_date', 'record_count', '#e83e8c'); // Pink color
      }


    // --- Laboratorist Charts ---
     if (chartData.lab_status) {
         var colors = ['#ffc107', '#28a745', '#dc3545'];
         renderPieChart('laboratoristLabStatusChart', chartData.lab_status, 'request_status', 'count', colors);
     }

     if (chartData.lab_completed_month) {
          renderBarChart('laboratoristCompletedResultsChart', 'Completed Results', chartData.lab_completed_month, 'month', 'completed_count', '#17a2b8'); // Info color
     }


    // --- Radiologist Charts ---
     if (chartData.ultra_status) {
         var colors = ['#ffc107', '#28a745', '#dc3545'];
         renderPieChart('radiologistUltraStatusChart', chartData.ultra_status, 'request_status', 'count', colors);
     }

     if (chartData.ultra_completed_month) {
          renderBarChart('radiologistCompletedResultsChart', 'Completed Results', chartData.ultra_completed_month, 'month', 'completed_count', '#6f42c1'); // Purple color
     }


}); // End $(document).ready
</script>
<!-- === End JavaScript for Charts === -->