<?php
// نقرأ الكلمة من الرابط: search.php?q=javascript
$q = "";
if (isset($_GET["q"])) {
    $q = trim($_GET["q"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<form action="search.php" method="get">
  <label for="q">Search</label>
  <input type="text" id="q" name="q" placeholder="e.g. javascript">
  <button type="submit">Search</button>
</form>

<div class="card">
  <?php if ($q != "") { ?>
    <p>Search result for: <strong><?php echo htmlspecialchars($q); ?></strong></p>
  <?php } else { ?>
    <p>Type a keyword and press Search.</p>
  <?php } ?>
</div>

</body>
</html>