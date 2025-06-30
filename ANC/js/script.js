// dph_ancis/js/script.js

$(document).ready(function () {
  // Function to load content via AJAX
  function loadContent(url) {
    var $contentArea = $(".main-content-area"); // Target the main content area div
    // Ensure the content area exists on the current page
    if ($contentArea.length === 0) {
      console.error(
        "Error: Main content area div (.main-content-area) not found on this page."
      );
      // Maybe redirect to dashboard as a fallback?
      // window.location.href = '<?php //echo PROJECT_SUBDIRECTORY; ?>/views/dashboard.php';
      return;
    }

    // Show loading indicator
    $contentArea.html(
      '<div class="loading-indicator text-center"><i class="fas fa-spinner fa-spin mr-2"></i> Loading...</div>'
    );

    // Add a parameter to the URL to tell the PHP script it's an AJAX request
    var ajaxUrl =
      url + (url.indexOf("?") > -1 ? "&" : "?") + "ajax_request=true";

    $.ajax({
      url: ajaxUrl,
      method: "GET",
      success: function (data) {
        $contentArea.html(data); // Load the received HTML fragment

        // Update browser URL without reloading (HTML5 History API)
        // This makes the back/forward buttons work with AJAX loaded content
        history.pushState({ path: url }, "", url);

        // Re-initialize any Bootstrap components (like accordions, tooltips, popovers)
        // within the newly loaded content if needed
        $contentArea.find(".accordion").collapse(); // Re-initialize Bootstrap Collapse
        // Example: $contentArea.find('[data-toggle="tooltip"]').tooltip();
        // Example: $contentArea.find('[data-toggle="popover"]').popover();

        // IMPORTANT: Re-run any inline or external scripts within the loaded HTML if they exist
        // This is a common gotcha with AJAX. Scripts inside the loaded data might not execute.
        // If your forms or other elements rely on inline <script> tags or external JS that
        // isn't included globally, you might need to manually find and execute them here.
        // A better practice is to put page-specific JS in separate files and call functions after loading.
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error: ", textStatus, errorThrown);
        $contentArea.html(
          '<div class="alert alert-danger">Error loading content. Please try again.<br>Details: ' +
            textStatus +
            " - " +
            errorThrown +
            "</div>"
        );
      },
    });
  }

  // Handle click events on the Midwife sidebar links (using event delegation)
  // This allows binding events even if the sidebar itself is static but the links change
  $(document).on(
    "click",
    ".sidebar-nav .list-group-item.ajax-load-link",
    function (e) {
      // Check if the link is NOT disabled and is NOT the logout link
      if (
        !$(this).hasClass("disabled") &&
        $(this).attr("href") !==
          "<?php echo PROJECT_SUBDIRECTORY; ?>/views/logout.php"
      ) {
        e.preventDefault(); // Prevent the default link behavior

        var targetUrl = $(this).attr("href"); // Get the URL from the href

        // Update active link styling in the sidebar
        // Find the currently active link and remove the class within the sidebar nav
        $(".sidebar-nav .list-group-item.active").removeClass("active");
        // Add the active class to the clicked link
        $(this).addClass("active");

        loadContent(targetUrl); // Load the content via AJAX

        // Optional: Scroll to the content area if needed, adjusted for fixed header
        var $contentArea = $(".main-content-area");
        if ($contentArea.length) {
          var navbarHeight = $(".navbar.fixed-top").outerHeight() || 56; // Get actual navbar height or use default
          $("html, body").animate(
            {
              scrollTop: $contentArea.offset().top - navbarHeight,
            },
            300
          ); // Smooth scroll duration
        }
      }
    }
  );

  // Handle browser back/forward buttons with history.pushState
  $(window).on("popstate", function () {
    // When back/forward is clicked, the URL changes, check if it's an internal page that should be AJAX loaded
    var path = window.location.pathname + window.location.search;

    // Check if the path corresponds to one of the AJAX loadable midwife pages
    // You could improve this check
    if (
      path.indexOf("<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/") !== -1 ||
      path.indexOf(
        "<?php echo PROJECT_SUBDIRECTORY; ?>/views/dashboard.php"
      ) !== -1
    ) {
      // Load the content for the history state
      // Need to strip the ?ajax_request=true parameter if it exists in history state URL
      var loadPath = path.replace(/&?ajax_request=true/, "");
      loadContent(loadPath);

      // Also update the active sidebar link based on the history path
      $(".sidebar-nav .list-group-item").removeClass("active");
      $('.sidebar-nav .list-group-item[href="' + loadPath + '"]').addClass(
        "active"
      );
      // Special case: If on mother_details, highlight view_mothers link
      if (
        loadPath.indexOf(
          "<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/mother_details.php"
        ) !== -1
      ) {
        $(
          '.sidebar-nav .list-group-item[href="<?php echo PROJECT_SUBDIRECTORY; ?>/midwife/view_mothers.php"]'
        ).addClass("active");
      }
    }
    // For non-AJAX paths, let the default browser behavior handle it
  });

  // Optional: Load default content on dashboard page load
  // This check should be on the dashboard page itself, or trigger a specific AJAX load
  // For now, we'll add a script block ONLY on the dashboard to trigger this.
}); // End $(document).ready
