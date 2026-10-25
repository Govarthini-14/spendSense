<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) : 'SpendSense' ?> | SpendSense</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php
$flashMessages = getFlashMessages();
foreach ($flashMessages as $type => $message):
    echo '<div class="toast show ' . e($type) . '" role="alert">' . e($message) . '</div>';
endforeach;
?>
