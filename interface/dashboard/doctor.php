<?php
require_once("../globals.php");
// if (strpos($_SERVER['HTTP_REFERER'] ?? '', 'tabs/main.php') !== false) {
//     header("Location: /interface/dashboard/doctor.php");
//     exit;
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dr. Azmat Dashboard</title>

    <!-- Tailwind CDN (for quick start) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Your React App Bundle -->
    <script defer src="../../modern-ui/dist/assets/index.js"></script>
    <link rel="stylesheet" href="../../modern-ui/dist/assets/style.css" />
</head>

<body>
    <div id="root"></div>

    <script>
        window.__MODERN_UI_DATA__ = <?= json_encode([
            'doctorName' => $_SESSION['authUser'],
            'tasks' => [
                ['title' => 'Patient Rounds', 'time' => '04:00 PM'],
                ['title' => 'Lab Test Review', 'time' => '04:30 PM'],
            ]
        ]) ?>;
    </script>
</body>

</html>