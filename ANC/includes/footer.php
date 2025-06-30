
<?php
// This footer is for pages AFTER login.
// It closes the main content column and the layout divs opened in header.php.

// Include application configuration (defines PROJECT_SUBDIRECTORY)
// Path from includes/ to config/paths.php
require_once(__DIR__ . '/../config/paths.php');

?>
            </div> <!-- Closes the .main-content-area div -->

        </div> <!-- Closes the .row div (dashboard-layout-row is removed) -->
    </div> <!-- Closes the .main-layout-container div -->


    <footer class="footer bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <p>© <?php echo date('Y'); ?> DPH-ANC System. All rights reserved.</p>
        </div>
    </footer>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script> <!-- <-- Add this line -->
    <!-- Your Custom JavaScript File -->
     <script src="<?php echo PROJECT_SUBDIRECTORY; ?>/js/script.js"></script>

   
    <?php
    // Optional: Close DB connection at the end of the script execution
    // global $link;
    // if ($link) {
    //     mysqli_close($link);
    // }
    ?>
</body>
</html>
