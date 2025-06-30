<?php
// English Language File

$lang = array(
    // General
    'dph_anc_system' => 'DPH-ANC System',
    'dashboard' => 'Dashboard',
    'logout' => 'Logout',
    'settings' => 'Settings',
    'cancel' => 'Cancel',
    'save_changes' => 'Save Changes',
    'update' => 'Update',
    'edit' => 'Edit',
    'disable' => 'Disable',
    'enable' => 'Enable',
    'delete' => 'Delete',
    'yes' => 'Yes',
    'no' => 'No',
    'guest_user' => 'Guest User',
    'unknown_role' => 'Unknown Role',
    'id' => 'ID', // Table Header

    // Auth & Users
    'login' => 'Login',
    'username' => 'Username',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'full_name' => 'Full Name',
    'role' => 'Role',
    'account_is_active' => 'Account is Active',
    'you_cannot_disable_own_account' => 'You cannot disable your own account.',
    'manage_accounts' => 'Manage Accounts', // Menu item text
    'manage_user_accounts' => 'Manage User Accounts', // Heading/Page title text
    'add_new_user' => 'Add New User',
    'create_user' => 'Create User', // Menu item text
    'create_new_user_account' => 'Create New User Account', // Heading/Page title text
    'fill_form_to_create_user' => 'Fill in this form to create a new user account.',
    'edit_user_account' => 'Edit User Account', // Heading/Page title text
    'select_role' => '-- Select Role --',
    'please_enter_username' => 'Please enter a username.',
    'this_username_is_already_taken' => 'This username is already taken.',
    'oops_something_went_wrong_username_check' => 'Oops! Something went wrong with the username check.',
    'database_error_preparing_username_check' => 'Database error preparing username check.',
    'please_enter_full_name' => 'Please enter the full name.',
    'please_enter_password' => 'Please enter a password.',
    'password_min_chars' => 'Password must have at least 6 characters.', // Used in create form
    'please_confirm_password' => 'Please confirm password.',
    'password_did_not_match' => 'Password did not match.', // Used in create form
    'invalid_role_selected' => 'Invalid role selected.',
    'database_error_preparing_role_validation' => 'Database error preparing role validation.',
    'please_fix_errors_in_form' => 'Please fix the errors in the form.',
    'database_error_preparing_user_insertion' => 'Database error preparing user insertion.',
    'error_creating_user' => 'Error creating user:', // Should be followed by DB error
    'user_account_created_success' => 'User account for %s created successfully.', // %s = full name
    'user_account_updated_success' => 'User account for %s updated successfully.', // %s = full name
    'user_account_disabled_success' => 'User account disabled successfully.',
    'user_account_enabled_success' => 'User account enabled successfully.',
    'user_not_found_or_already_disabled' => 'User not found or already disabled.',
    'user_not_found_or_already_enabled' => 'User not found or already enabled.',
    'invalid_or_missing_user_id' => 'Invalid or missing user ID.',
    'user_not_found' => 'User not found.',
    'no_users_found' => 'No users found.',
    'warning_assigned_role_not_exists' => 'Warning: The user\'s currently assigned role (ID: %s) does not exist in the system\'s available roles. Please select a valid role.', // %s = role_id
    'error_fetching_users' => 'Error fetching users:', // Should be followed by DB error
    'roles_could_not_be_loaded' => 'Roles could not be loaded from the database.', // Error if roles fetch fails
    'are_you_sure_disable_account' => 'Are you sure you want to disable the account for %s?', // %s = username
    'are_you_sure_enable_account' => 'Are you sure you want to enable the account for %s?', // %s = username
    'active' => 'Active', // Table header/Label text
    'created_at' => 'Created At', // Table header
    'actions' => 'Actions', // Table header
    'database_error_fetching_user_details' => 'Database error fetching user details.', // Error
    'could_not_retrieve_user_details' => 'Could not retrieve user details.', // Error message
    'database_error_preparing_fetch_statement' => 'Database error preparing fetch statement.', // Error
    'invalid_user_id_provided' => 'Invalid user ID provided.', // Error message
     'user_account_already_exists' => 'User account already exists.', // Error message (if not handled by username check)
     'optional' => 'Optional', // Text for optional fields


    // Settings Page
    'admin_settings' => 'Admin Settings', // Heading/Page title text
    'change_your_password' => 'Change Your Password', // Card header
    'fill_form_to_change_password' => 'Fill in the form below to change your password.',
    'current_password' => 'Current Password', // Label
    'new_password' => 'New Password', // Label
    'confirm_new_password' => 'Confirm New Password', // Label
    'leave_blank_no_password_change' => 'Leave blank if you don\'t want to change the password.',
    'new_password_min_chars_hint' => 'Minimum 6 characters.', // Small text hint
    'change_password_button' => 'Change Password', // Button text
    'please_enter_current_password' => 'Please enter your current password.', // Error
    'please_enter_new_password' => 'Please enter a new password.', // Error
    'new_password_short' => 'New password must have at least 6 characters.', // Error
    'please_confirm_new_password' => 'Please confirm the new password.', // Error
    'passwords_do_not_match' => 'New password and confirmation do not match.', // Error
    'current_password_incorrect' => 'The current password you entered is incorrect.', // Error
    'password_updated_success' => 'Password updated successfully.', // Success message
    'error_updating_password' => 'Error updating password:', // Error + DB error
    'database_error_preparing_update_statement' => 'Database error preparing update statement.', // Error
    'could_not_process_user_info' => 'Could not process user account information.', // Error
    'could_not_retrieve_user_info' => 'Could not retrieve user account information.', // Error
    'database_error_fetching_user_info' => 'Database error fetching user information.', // Error
    'database_error_preparing_fetch' => 'Database error preparing fetch statement.', // Error


    // Reports Page
    'reports' => 'Reports', // Menu item text
    'admin_reports_audit_log' => 'Admin Reports - Audit Log', // Heading/Page title text
    'displays_recent_admin_actions' => 'Displays recent administrative actions.',
    'timestamp' => 'Timestamp', // Table Header
    'action_by' => 'Action By', // Table Header
    'action_type' => 'Action Type', // Table Header
    'target_id' => 'Target ID', // Table Header
    'details' => 'Details', // Table Header
    'filter' => 'Filter', // Button
    'from' => 'From', // Label
    'to' => 'To', // Label
    'reset_filter' => 'Reset Filter', // Button
    'no_audit_logs_found_criteria' => 'No audit log entries found matching the criteria. Make sure actions are being logged.',
    'showing_last_entries' => 'Showing last %s entries.', // %s = number of entries
    'export_to_csv' => 'Export to CSV', // Button
    'more_reporting_features_soon' => 'More reporting features coming soon...',
    'invalid_date_format' => 'Invalid date format provided.', // Error message
     'error_fetching_audit_logs' => 'Error fetching audit logs:', // Error message + DB error
     'database_error_preparing_query' => 'Database error preparing audit log query.', // Error message
    'n_a' => 'N/A', // Not Applicable for Target ID or Details


    // Audit Log Action Types (Translate these specifically)
    'user_created' => 'User Created',
    'user_updated' => 'User Updated',
    'user_disabled' => 'User Disabled',
    'user_enabled' => 'User Enabled',
    'password_changed' => 'Password Changed',
    // Add other action types from your log if any

    // Permissions Error (set in auth.php)
    'you_do_not_have_permission' => 'You do not have permission to access this page.',

    // Suspicious Redirect Blocked (set in set_language.php)
    'suspicious_redirect_attempt_blocked' => 'Suspicious redirect attempt blocked.',

    // Dashboard specific
    'welcome' => 'Welcome', // Heading part
    'you_are_logged_in_as' => 'You are logged in as:', // Paragraph part
    'admin_dashboard_overview' => 'Admin Dashboard Overview', // Heading
    'total_users' => 'Total Users', // Stat label
    'registered_mothers' => 'Registered Mothers', // Stat label
    'anc_visit_records' => 'ANC Visit Records', // Stat label
    'avg_visits_per_mother' => 'Avg Visits per Mother', // Stat label
    'user_distribution_by_role' => 'User Distribution by Role', // Chart header
    'anc_visits_per_month_last_12' => 'ANC Visits per Month (Last 12 Months)', // Chart header
    'total_recent_activity_last_30' => 'Total Recent Activity (Last 30 Days)', // Chart header
    'data_clerk_dashboard_overview' => 'Data Clerk Dashboard Overview', // Heading
    'mothers_registered_today' => 'Mothers Registered Today', // Stat label
    'mothers_registered_this_week' => 'Mothers Registered This Week', // Stat label
    'mothers_registered_per_month_last_12' => 'Mothers Registered per Month (Last 12 Months)', // Chart header
    'mothers_by_age_group' => 'Mothers by Age Group', // Chart header
    'recent_registrations_placeholder' => 'Recent Registrations (Placeholder)', // Placeholder header/text
    'area_displaying_recent_mothers_list' => 'Area for displaying a list of recently registered mothers.', // Placeholder text
    'recent_list_placeholder' => 'Recent List Placeholder', // Placeholder text
    'midwife_dashboard_overview' => 'Midwife Dashboard Overview', // Heading
    'appointments_today' => 'Appointments Today', // Stat label
    'visits_recorded_today' => 'Visits Recorded Today', // Stat label
    'upcoming_appointment_summary' => 'Upcoming Appointment Summary', // Chart header
    'visits_recorded_per_month_last_6' => 'Visits Recorded per Month (Last 6 Months)', // Chart header
    'recent_anc_records_last_30' => 'Recent ANC Records Created (Last 30 Days)', // Chart header
    'laboratorist_dashboard_overview' => 'Laboratorist Dashboard Overview', // Heading
    'pending_lab_requests' => 'Pending Lab Requests', // Stat label
    'results_reported_today' => 'Results Reported Today', // Stat label (used for both Lab & Radiology)
    'lab_request_status' => 'Lab Request Status', // Chart header
    'completed_results_per_month_last_6' => 'Completed Results per Month (Last 6 Months)', // Chart header
    'recent_completed_results_placeholder' => 'Recent Completed Results (Placeholder)', // Placeholder header/text
    'area_displaying_recent_results_list' => 'Area for displaying a list of recently completed results.', // Placeholder text
    'radiologist_dashboard_overview' => 'Radiologist Dashboard Overview', // Heading
    'pending_ultrasound_requests' => 'Pending Ultrasound Requests', // Stat label
    'ultrasound_request_status' => 'Ultrasound Request Status', // Chart header
    'recent_completed_ultrasound_placeholder' => 'Recent Completed Results (Placeholder)', // Placeholder header/text
    'default_dashboard_overview' => 'Dashboard Overview', // Heading for unknown roles
    'available_actions' => 'Available Actions', // Card header for unknown roles
    'content_for_role_being_developed' => 'Content for your role is being developed.', // Placeholder text for unknown roles
    'no_data_available_for_chart' => 'No data available for this chart.', // Chart placeholder message

    // Midwife Appointment Summary Pie Chart Labels (Keep these as keys for translation if needed)
    'today' => 'Today',
    'tomorrow' => 'Tomorrow',
    'this_week_upcoming' => 'This Week (Upcoming)',
    'later' => 'Later',


    // Add other strings as needed for other roles/pages
    'register_mother' => 'Register Mother',
    'view_mothers' => 'View Mothers',
    'record_anc' => 'Record ANC',
    'take_vitals' => 'Take Vitals',
    'send_lab_request' => 'Send Lab Request',
    'send_ultrasound' => 'Send Ultrasound',
    'schedule_appt' => 'Schedule Appt',
    'lab_requests' => 'Lab Requests',
    'enter_results' => 'Enter Results',
    'view_results' => 'View Results',
    'ultrasound_requests' => 'Ultrasound Requests',
);