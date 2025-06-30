<?php
// This header is for pages AFTER login.
// It assumes session_start() has been called (e.g., by core/auth.php)
// user is logged in, AND core/language.php has been included via auth.php.

// Include application configuration (defines PROJECT_SUBDIRECTORY)
// Path from includes/ to config/paths.php
require_once(__DIR__ . '/../config/paths.php');

// --- Access language variables from core/language.php ---
// These should be available because core/auth.php (included in every protected page)
// includes core/language.php BEFORE including db.php.
global $supported_languages, $current_language, $current_language_name; // Variables set in core/language.php
global $lang; // The language array loaded by core/language.php
// The __() function is also globally available after including language.php
// -------------------------------------------------------


// Access user info stored in session by auth.php
$loggedInUsername = $_SESSION['username'] ?? 'Guest';
// Translate Guest User and Unknown Role using __()
$loggedInFullName = $_SESSION['full_name'] ?? __("guest_user"); // Use key from language file
$loggedInRoleName = $_SESSION['role_name'] ?? __("unknown_role"); // This should store the *English* name like 'Administrator'


// Determine current page URL and path for active state highlighting and redirect
$current_page_url = $_SERVER['REQUEST_URI']; // Keep original URL for redirect after language change
$current_page_path = parse_url($current_page_url, PHP_URL_PATH);
// Remove PROJECT_SUBDIRECTORY from the path for comparison
// Ensure PROJECT_SUBDIRECTORY is not empty to prevent unexpected results with str_replace
$current_page_relative = ($current_page_path && PROJECT_SUBDIRECTORY) ? str_replace(PROJECT_SUBDIRECTORY, '', $current_page_path) : $current_page_path;
// Handle potential empty PROJECT_SUBDIRECTORY resulting in leading slash removal issues
if (PROJECT_SUBDIRECTORY === '' && $current_page_path !== null) {
    // If no subdirectory, relative path is just the path
    $current_page_relative = $current_page_path;
}


?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_language); ?>"> <!-- Set HTML lang attribute -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? __("dph_anc_system")); ?></title> <!-- Translate default title -->
    <!-- Bootstrap CSS (CDN) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome CSS (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome -->

    <!-- Your Custom CSS (Using PROJECT_SUBDIRECTORY for path from web root) -->
    <link rel="stylesheet" href="<?php echo PROJECT_SUBDIRECTORY; ?>/css/style.css"> <!-- Link to your custom CSS -->

    <!-- Add any other head elements like favicons, etc. here -->

    <?php /*
    // Move specific styles for the layout to style.css as recommended earlier
    <style>
         .sidebar { ... }
         .main-content-area { ... }
         @media (max-width: 767.98px) { ... }
         .sidebar .list-group-item { ... }
         .sidebar .list-group-item:hover { ... }
         .sidebar .list-group-item.active { ... }
         .sidebar .list-group-item .action-icon { ... }
          .main-content-area { ... }
    </style>
    */ ?>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid"> <!-- Use fluid container for navbar -->
            <!-- Use PROJECT_SUBDIRECTORY for all hrefs -->
            <a class="navbar-brand" href="<?php echo PROJECT_SUBDIRECTORY; ?>/views/dashboard.php"><?php echo __("dph_anc_system"); ?></a> <!-- Translate Brand -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <!-- Dashboard link remains a primary link -->
                    <!-- Active class check using relative path -->
                    <li class="nav-item <?php echo ($current_page_relative === '/views/dashboard.php' || $current_page_relative === '/dashboard.php') ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo PROJECT_SUBDIRECTORY; ?>/views/dashboard.php"><?php echo __("Dashboard"); ?> <span class="sr-only">(current)</span></a> <!-- Translate Dashboard -->
                    </li>
                     <!-- Add global nav items here if any -->
                </ul>
                <ul class="navbar-nav ml-auto">

                    <!-- Language Switcher Dropdown -->
                     <li class="nav-item dropdown">
                         <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                             <i class="fas fa-language mr-1"></i> <?php echo htmlspecialchars($supported_languages[$current_language] ?? $current_language); ?> <!-- Display current language name -->
                         </a>
                         <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageDropdown">
                             <?php foreach ($supported_languages as $lang_code => $lang_name): ?>
                                 <a class="dropdown-item <?php echo ($lang_code === $current_language) ? 'active' : ''; ?>"
                                    href="<?php echo PROJECT_SUBDIRECTORY; ?>/core/set_language.php?lang=<?php echo htmlspecialchars($lang_code); ?>&redirect=<?php echo urlencode($current_page_url); ?>">
                                     <?php echo htmlspecialchars($lang_name); ?>
                                 </a>
                             <?php endforeach; ?>
                         </div>
                     </li>

                     <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($loggedInFullName); ?> (<?php echo __($loggedInRoleName); ?>) <!-- Translate Role Name for Display -->
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                             <!-- Add a link to the settings page here too -->
                             <!-- Active class check using relative path -->
                             <a class="dropdown-item <?php echo ($current_page_relative === '/admin/settings.php') ? 'active' : ''; ?>" href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/settings.php"><i class="fas fa-cog mr-1"></i> <?php echo __("Settings"); ?></a> <!-- Translate Settings -->
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo PROJECT_SUBDIRECTORY; ?>/views/logout.php"><i class="fas fa-sign-out-alt mr-1"></i> <?php echo __("Logout"); ?></a> <!-- Translate Logout -->
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard Layout -->
    <!-- Use container-fluid for full width, add top margin below navbar -->
    <div class="container-fluid dashboard-layout-row mt-3">
        <div class="row">

            <!-- Sidebar Column (Common to all pages after login) -->
            <div class="col-md-3 col-lg-2 sidebar-column">
                <div class="sidebar-user-profile">
                    <i class="fas fa-user-circle profile-icon"></i>
                    <div class="user-name"><?php echo htmlspecialchars($loggedInFullName); ?></div>
                    <div class="user-role"><?php echo __($loggedInRoleName); ?></div> <!-- Translate Role Name for Display -->
                     <!-- Add email or other info here if available -->
                     <!-- <div class="user-info"><small><?php // echo htmlspecialchars($username); ?></small></div> -->
                </div>

                <div class="sidebar-nav">
                    <div class="list-group list-group-flush">
                        <!-- === Sidebar Links based on Role === -->
                        <!-- Dashboard link -->
                         <!-- Active class check using relative path -->
                        <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/views/dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page_relative === '/views/dashboard.php' || $current_page_relative === '/dashboard.php') ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-2 action-icon"></i> <?php echo __("Dashboard"); ?> <!-- Translate Dashboard -->
                        </a>

                        <?php if (has_role('Administrator')): ?>
                            <!-- Active class check using relative path -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/manage_accounts.php" class="list-group-item list-group-item-action <?php echo ($current_page_relative === '/admin/manage_accounts.php') ? 'active' : ''; ?>">
                                <i class="fas fa-users mr-2 action-icon"></i> <?php echo __("manage_accounts"); ?> <!-- Translate Manage Accounts menu item -->
                            </a>
                            <!-- Active class check using relative path -->
                             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/create_account.php" class="list-group-item list-group-item-action <?php echo ($current_page_relative === '/admin/create_account.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user-plus mr-2 action-icon"></i> <?php echo __("create_user"); ?> <!-- Translate Create User menu item -->
                            </a>
                            <!-- Admin Links - Active class check using relative path -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/settings.php" class="list-group-item list-group-item-action <?php echo ($current_page_relative === '/admin/settings.php') ? 'active' : ''; ?>"><i class="fas fa-cogs mr-2 action-icon"></i> <?php echo __("Settings"); ?></a> <!-- Translate Settings -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/admin/reports.php" class="list-group-item list-group-item-action <?php echo ($current_page_relative === '/admin/reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-bar mr-2 action-icon"></i> <?php echo __("Reports"); ?></a> <!-- Translate Reports -->


                        <?php elseif (has_role('Data Clerk')): ?>
                            <!-- Active class check using strpos for potential sub-paths -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/register_mother.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/dataclerk/register_mother.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-user-plus mr-2 action-icon"></i> <?php echo __("register_mother"); ?>
                            </a>
                             <!-- Active class check using strpos for potential sub-paths -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/dataclerk/view_mothers.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/dataclerk/view_mothers.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-female mr-2 action-icon"></i> <?php echo __("view_mothers"); ?>
                            </a>

                        <?php elseif (has_role('Midwife')): ?>
                             <!-- Primary Link: View/Manage Mothers - Active check covers view_mothers and mother_details -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/view_mothers.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/view_mothers.php') !== false || strpos($current_page_relative, '/midwife/mother_details.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-female mr-2 action-icon"></i> <?php echo __("view_mothers"); ?>
                            </a>
                            <!-- Links for mother-specific actions - Active class check using strpos -->
                             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/record_anc.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/record_anc.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-notes-medical mr-2 action-icon"></i> <?php echo __("record_anc"); ?>
                            </a>
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/take_vital_sign.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/take_vital_sign.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-heartbeat mr-2 action-icon"></i> <?php echo __("take_vitals"); ?>
                            </a>
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_lab_request.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/send_lab_request.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-vials mr-2 action-icon"></i> <?php echo __("send_lab_request"); ?>
                            </a>
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/send_ultrasound_request.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/send_ultrasound_request.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-x-ray mr-2 action-icon"></i> <?php echo __("send_ultrasound"); ?>
                            </a>
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/schedule_appointment.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/midwife/schedule_appointment.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-plus mr-2 action-icon"></i> <?php echo __("schedule_appt"); ?>
                            </a>


                        <?php elseif (has_role('Laboratorist')): ?>
                            <!-- Active class check using strpos -->
                             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_requests.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/laboratorist/view_lab_requests.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-plus mr-2 action-icon"></i> <?php echo __("lab_requests"); ?>
                            </a>
                             <!-- Active class check using strpos -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/enter_lab_results.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/laboratorist/enter_lab_results.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-plus mr-2 action-icon"></i> <?php echo __("enter_results"); ?>
                            </a>
                             <!-- Active class check using strpos -->
                             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/laboratorist/view_lab_result.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/laboratorist/view_lab_result.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-plus mr-2 action-icon"></i> <?php echo __("view_results"); ?>
                            </a>


                        <?php elseif (has_role('Radiologist')): ?>
                            <!-- Active class check using strpos -->
                            <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/radiologist/view_ultrasound_requests.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/radiologist/view_ultrasound_requests.php') !== false) ? 'active' : ''; ?>"><i class="fas fa-x-ray mr-2 action-icon"></i> <?php echo __("ultrasound_requests"); ?></a>
                            <!-- Active class check using strpos -->
                             <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/radiologist/enter_ultrasound_results.php" class="list-group-item list-group-item-action <?php echo (strpos($current_page_relative, '/radiologist/enter_ultrasound_results.php') !== false) ? 'active' : ''; ?>"><i class="fas fa-edit mr-2 action-icon"></i> <?php echo __("enter_results"); ?></a>

                        <?php else: ?>
                            <!-- Default sidebar for unknown roles -->
                             <!-- No specific links -->
                        <?php endif; ?>

                         <!-- Add global sidebar links here if any -->
                         <!-- <a href="<?php // echo PROJECT_SUBDIRECTORY; ?>/views/profile.php" class="list-group-item list-group-item-action"><i class="fas fa-user mr-2 action-icon"></i> <?php // echo __("My Profile"); ?></a> --> <!-- Translate My Profile -->

                    </div>
                     <!-- Logout link separate at the bottom -->
                     <div class="list-group list-group-flush mt-auto"> <!-- mt-auto pushes this group to the bottom -->
                         <a href="<?php echo PROJECT_SUBDIRECTORY; ?>/views/logout.php" class="list-group-item list-group-item-action"><i class="fas fa-sign-out-alt mr-2 action-icon"></i> <?php echo __("Logout"); ?></a> <!-- Translate Logout -->
                     </div>

                </div>
            </div>

            <!-- Main Content Column - **This is opened here** -->
            <div class="col-md-9 col-lg-10 main-content-column">

                <!-- The content of the specific page (dashboard, form, list) goes here -->