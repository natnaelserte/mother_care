<?php
// ... (previous code) ...

        // Example of role-based content display
        if (has_role('Administrator')) {
            echo "<p><a href='../admin/manage_accounts.php' class='btn btn-primary'>Manage Accounts</a></p>";
            echo "<p><a href='../admin/configure_system.php' class='btn btn-secondary'>Configure System Settings (Placeholder)</a></p>"; // Link to a future config page
        } elseif (has_role('Data Clerk')) {
             echo "<p><a href='../dataclerk/register_mother.php' class='btn btn-primary'>Register New Mother</a></p>";
             echo "<p><a href='../dataclerk/view_mothers.php' class='btn btn-secondary'>View Mothers (Placeholder)</a></p>";
             // ... links for other Data Clerk tasks
        } elseif (has_role('Midwife')) {
             echo "<p><a href='../midwife/record_anc.php' class='btn btn-primary'>Record ANC Visit</a></p>";
             echo "<p><a href='../midwife/take_vital_sign.php' class='btn btn-secondary'>Take Vital Signs</a></p>";
             // ... links for other midwife tasks
        } elseif (has_role('Laboratorist')) {
             echo "<p><a href='../laboratorist/view_lab_requests.php' class='btn btn-primary'>View Lab Requests (Placeholder)</a></p>";
             // ... links for other Laboratorist tasks
        } elseif (has_role('Radiologist')) {
             echo "<p><a href='../radiologist/view_ultrasound_requests.php' class='btn btn-primary'>View Ultrasound Requests (Placeholder)</a></p>";
             // ... links for other Radiologist tasks
        }
        // Add checks for other roles as needed

        // Display error/success messages from session (e.g., after creating a user)
        if (isset($_SESSION['message'])) {
            $msg_class = $_SESSION['message_type'] ?? 'info'; // default to info if type not set
            echo '<div class="alert alert-' . $msg_class . ' mt-3">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']); // Clear the message after displaying
            unset($_SESSION['message_type']);
        }


        ?>

        <p class="mt-4">
            <a href="logout.php" class="btn btn-danger">Sign Out</a>
        </p>
    </div>

    <?php
    // Include footer (optional)
    // require_once(__DIR__ . '/../includes/footer.php');
    ?>
</body>
</html>