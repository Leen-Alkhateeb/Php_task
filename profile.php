<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">

<?php if (isset($_SESSION["user"])) { ?>
  <h2>Your registration (from Session)</h2>
  <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION["user"]["name"]); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION["user"]["email"]); ?></p>
  <p><strong>Mobile:</strong> <?php echo htmlspecialchars($_SESSION["user"]["mobile"]); ?></p>
  <p><strong>Governorate:</strong> <?php echo htmlspecialchars($_SESSION["user"]["governorate"]); ?></p>
  <p><strong>Track:</strong> <?php echo htmlspecialchars($_SESSION["user"]["track"]); ?></p>
  <p><strong>Skills:</strong> <?php echo htmlspecialchars(implode(", ", $_SESSION["user"]["skills"])); ?></p>
  <p><strong>Message:</strong> <?php echo htmlspecialchars($_SESSION["user"]["message"]); ?></p>
<?php } else { ?>
  <h2>No registration found</h2>
  <p>You have not registered yet.</p>
<?php } ?>

  <a class="btn" href="register.php">Registration form</a>
</div>
</body>
</html>
