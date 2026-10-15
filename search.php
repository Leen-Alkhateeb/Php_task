<?php
// دالة تنظيف النص قبل الطباعة (حماية من XSS)
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// نقرأ كلمة البحث من الرابط: search.php?q=javascript
// trim بتشيل المسافات الزايدة، و ?? '' بتعطي قيمة فاضية إذا ما في q
$q = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <nav>
        <a href="register.php">Register</a>
        <a href="profile.php">My Profile (Session)</a>
        <a href="search.php">Search</a>
    </nav>

    <h1>Search</h1>

    <!-- method="get" يعني الكلمة بتنكتب بالرابط، واسم الحقل q هو اللي بنقراه بـ $_GET -->
    <form method="get" action="search.php" class="search">
        <input type="text" name="q" placeholder="Search..." value="<?= e($q) ?>">
        <button type="submit">Search</button>
    </form>

    <?php if ($q !== ''): ?>
        <!-- في كلمة بحث: نعرضها -->
        <p class="box">Search result for: <strong><?= e($q) ?></strong></p>
    <?php elseif (isset($_GET['q'])): ?>
        <!-- المستخدم ضغط بحث بس ما كتب شي -->
        <p class="error">Please type something to search.</p>
    <?php endif; ?>

</div>
</body>
</html>