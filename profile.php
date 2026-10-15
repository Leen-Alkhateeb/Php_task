<?php
// نحمّل الكلاس قبل session_start() عشان PHP يعرف يرجّع الكائن المخزّن
require_once 'Registration.php';
session_start();

// دالة تنظيف النص قبل الطباعة (حماية من XSS)
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// نجيب الكائن من الـ Session، وإذا مش موجود بيكون null
$reg = $_SESSION['registration'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <nav>
        <a href="register.php">Register</a>
        <a href="profile.php">My Profile (Session)</a>
        <a href="search.php">Search</a>
    </nav>

    <h1>My Registered Information</h1>

    <!-- instanceof بتتأكد إنه اللي بالـ Session فعلاً كائن من نوع Registration -->
    <?php if ($reg instanceof Registration): ?>
        <div class="box">
            <p><strong>Name:</strong> <?= e($reg->name) ?></p>
            <p><strong>Email:</strong> <?= e($reg->email) ?></p>
            <p><strong>Mobile:</strong> <?= e($reg->mobile) ?></p>
            <p><strong>Governorate:</strong> <?= e($reg->governorate) ?></p>
            <p><strong>Track:</strong> <?= e($reg->track) ?></p>
            <p><strong>Skills:</strong> <?= e($reg->skillsText()) ?></p>
            <p><strong>Message:</strong> <?= $reg->message !== '' ? nl2br(e($reg->message)) : '-' ?></p>
        </div>
    <?php else: ?>
        <!-- ما في بيانات بالـ Session، يعني المستخدم ما سجّل لسا -->
        <p class="error">No registration found in the session. <a href="register.php">Register first</a>.</p>
    <?php endif; ?>

    <!-- إذا في كوكي محفوظ بنعرض رسالة ترحيب منه -->
    <?php if (isset($_COOKIE['reg_name'])): ?>
        <div class="box">
            <p><strong>From cookie:</strong> Welcome back, <?= e($_COOKIE['reg_name']) ?>!</p>
        </div>
    <?php endif; ?>

</div>
</body>
</html>