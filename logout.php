<?php
    session_start();

    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page with a logout message
    header("location: loginTest.php?logout=success");
    exit();
?>