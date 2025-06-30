<?php
// Enable error reporting (for development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include authentication functions
// This handles session_start(), includes config/paths.php, config/config.php, and connects DB
require_once(__DIR__ . '/../core/auth.php');

// If user is already logged in, redirect to dashboard using PROJECT_SUBDIRECTORY
if (is_loggedin()) {
    header("location: " . PROJECT_SUBDIRECTORY . "/views/dashboard.php");
    exit;
}

// $link database connection is available globally after including core/auth.php
global $link; // Not strictly needed for login form itself, but good practice if DB access was needed here

// Define variables and initialize with empty values (or POST data on error)
$username = $_POST['username'] ?? "";
$password = $_POST['password'] ?? ""; // Don't pre-fill password field on error for security
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username
    if (empty(trim($username))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($username); // Use the trimmed value
    }

    // Validate password
    if (empty(trim($password))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($password); // Use the trimmed value
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Use the authenticate_user function from auth.php
        // authenticate_user function uses the global $link internally
        if (authenticate_user($username, $password)) {
            // authenticate_user sets session variables on success
            // Redirect user to dashboard using PROJECT_SUBDIRECTORY
             header("location: " . PROJECT_SUBDIRECTORY . "/views/dashboard.php");
             exit;
        } else {
            // Display an error message if login failed
            $login_err = "Invalid username or password.";
        }
    }
}

// Close database connection (it's globally managed by core/auth.php, ideally closed in footer)
// The $link connection is established by require_once(__DIR__ . '/../core/auth.php');
// For a script that doesn't use the header/footer structure, you might need to explicitly close it here.
// However, often PHP closes connections automatically at the end of script execution.
// If you encounter issues with connections staying open, add mysqli_close($link); here.
// global $link; // Need to access global link to close it
// if ($link) {
//     mysqli_close($link);
// }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DPH-ANC System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Your Custom CSS (for general styles like form controls, alerts etc.) -->
    <link rel="stylesheet" href="<?php echo PROJECT_SUBDIRECTORY; ?>/css/style.css">

    <style>
        /* Specific styles for the login page layout and animation */

        /* Override body styles for login page background/layout */
        body.login-page-body { /* Target specifically the body with this class */
            font-family: 'Roboto', sans-serif; /* Ensure font is applied */
            line-height: 1.6; /* Ensure line height */
            color: #333; /* Ensure base text color */
            margin: 0; /* Remove default body margin */
            padding: 0; /* Remove default body padding */
            background: linear-gradient(to right, #003366, #0056b3); /* Gradient background */
            display: flex; /* Use flexbox for centering */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            min-height: 100vh; /* Full viewport height */
            padding-top: 0; /* Ensure no padding-top from general body rules */
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box; /* Include padding in width calculation */
        }

        .login-card {
            background-color: #fff; /* White background for the card */
            border-radius: 1rem; /* More rounded corners */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            padding: 40px; /* Increased padding */
            max-width: 400px; /* Max width for the login form */
            width: 100%;
            text-align: center; /* Center text inside card */
            opacity: 0; /* Start invisible for animation */
            transform: translateY(20px); /* Start slightly below for animation */
            animation: fadeInSlideUp 0.8s ease-out forwards; /* Apply animation */
        }

        .login-card h2 {
            color: #003366; /* Dark blue heading */
            margin-bottom: 10px;
            font-weight: 500;
            border-bottom: none; /* Remove border */
        }

        .login-card p {
             color: #555;
             margin-bottom: 20px;
        }

        .login-logo {
            font-size: 3rem; /* Large logo icon */
            color: #0056b3; /* Primary color */
            margin-bottom: 20px;
        }

        .login-card .form-group { /* Target form groups inside login card */
            text-align: left; /* Align form elements left */
            margin-bottom: 1.5rem; /* Keep consistent spacing */
        }

        /* Target form controls inside login card - inherit most styles from external CSS */
        .login-card .form-control {
             /* Specific overrides if needed, but general form-control styles are usually fine */
             border-radius: 0.5rem; /* Rounded input fields */
             padding: 12px 15px; /* More padding */
        }

        .login-card .btn-primary { /* Target primary button inside login card */
            width: 100%; /* Full width button */
            padding: 12px;
             border-radius: 0.5rem; /* Rounded button */
             font-size: 1.1rem;
             margin-top: 10px; /* Space above button */
        }

         /* Animation Keyframes */
         @keyframes fadeInSlideUp {
             to {
                 opacity: 1;
                 transform: translateY(0);
             }
         }

          /* Ensure error messages are visible within the login card */
         .login-card .alert {
              margin-top: 0;
              margin-bottom: 15px;
              padding: 10px 15px;
              font-size: 0.9rem;
              text-align: left; /* Align alert text left */
         }

          /* Style for invalid input */
          .login-card .form-control.is-invalid {
              border-color: #dc3545; /* Bootstrap danger */
          }
         .login-card .invalid-feedback { /* Ensure invalid feedback is displayed */
             display: block;
             width: 100%;
             margin-top: 0.25rem;
             font-size: 0.875em;
             color: #dc3545;
         }

    </style>
</head>
<!-- Add a specific class to the body for login page styling -->
<body class="login-page-body">

    <div class="login-container">
        <div class="login-card">
            <div class="login-logo"><i class="fas fa-stethoscope"></i></div> <?php // Example medical icon ?>
            <h2>Login</h2>
            <p>Access your DPH-ANC System account.</p>

            <?php
            // Display error messages (check if specific or general login error exists)
            if (!empty($username_err) || !empty($password_err) || !empty($login_err)) {
                 echo '<div class="alert alert-danger">';
                 if (!empty($username_err)) echo htmlspecialchars($username_err) . '<br>';
                 if (!empty($password_err)) echo htmlspecialchars($password_err) . '<br>';
                 if (empty($username_err) && empty($password_err) && !empty($login_err)) echo htmlspecialchars($login_err); // Only show general if no specific errors
                 echo '</div>';
            }
            ?>

            <!-- Form uses Bootstrap classes -->
            <!-- Action attribute uses PROJECT_SUBDIRECTORY for correct path -->
            <form action="<?php echo PROJECT_SUBDIRECTORY; ?>/views/login.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <!-- Apply is-invalid class if *either* username_err *or* login_err is not empty -->
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err) || !empty($login_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                    <!-- Only display specific error here if it's the cause -->
                    <span class="invalid-feedback"><?php echo htmlspecialchars($username_err); ?></span>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <!-- Apply is-invalid class if *either* password_err *or* login_err is not empty -->
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err) || !empty($login_err)) ? 'is-invalid' : ''; ?>" required>
                     <!-- Only display specific error here if it's the cause -->
                    <span class="invalid-feedback"><?php echo htmlspecialchars($password_err); ?></span>
                </div>
                <div class="form-group mt-4"> <?php // Add top margin to button ?>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-sign-in-alt mr-1"></i> Login</button> <?php // Button full width ?>
                </div>
                <!-- Optional: Forgot password link -->
                <!-- <p class="text-center"><a href="<?php // echo PROJECT_SUBDIRECTORY; ?>/views/forgot_password.php">Forgot Password?</a></p> -->
            </form>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies (jQuery and Popper are required by Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- No custom script.js needed unless adding specific login page JS -->

</body>
</html>