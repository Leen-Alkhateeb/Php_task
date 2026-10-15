<?php
// بدء الجلسة (Session) عشان نقدر نقرأ البيانات المخزنة من صفحة التسجيل
// لازم تكون أول سطر قبل أي إخراج HTML
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile</title>
  <!-- ربط ملف التنسيق CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">

<?php 
// نتأكد إذا في بيانات مستخدم مخزنة بالجلسة (يعني المستخدم سجّل قبل)
if (isset($_SESSION["user"])) { ?>

  <h2>Your registration (from Session)</h2>

  <!-- كل سطر بيطبع قيمة من الجلسة -->
  <!-- htmlspecialchars بتحمي من هجمات XSS عن طريق تحويل الرموز الخاصة لنص عادي -->
  <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION["user"]["name"]); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION["user"]["email"]); ?></p>
  <p><strong>Mobile:</strong> <?php echo htmlspecialchars($_SESSION["user"]["mobile"]); ?></p>
  <p><strong>Governorate:</strong> <?php echo htmlspecialchars($_SESSION["user"]["governorate"]); ?></p>
  <p><strong>Track:</strong> <?php echo htmlspecialchars($_SESSION["user"]["track"]); ?></p>

  <!-- المهارات مخزنة كمصفوفة (Array)، فبنستخدم implode لدمجها بنص واحد مفصول بفاصلة -->
  <p><strong>Skills:</strong> <?php echo htmlspecialchars(implode(", ", $_SESSION["user"]["skills"])); ?></p>
  <p><strong>Message:</strong> <?php echo htmlspecialchars($_SESSION["user"]["message"]); ?></p>

<?php } else { 
// إذا ما في بيانات بالجلسة، يعني المستخدم ما عبّى نموذج التسجيل ?>

  <h2>No registration found</h2>
  <p>You have not registered yet.</p>

<?php } // نهاية شرط if ?>

  <!-- رابط للرجوع لصفحة نموذج التسجيل (بشكل زر) -->
  <a class="btn" href="register.php">Registration form</a>
</div>
</body>
</html>