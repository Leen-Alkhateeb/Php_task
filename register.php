<?php
session_start(); // لازم أول سطر عشان نستخدم الـ Session

// ---------- الكلاس (الـ Object) ----------
// كلاس بسيط بيمثل المستخدم، كل خاصية (property) بتخزن قيمة من النموذج
class User {
    public $name;
    public $email;
    public $mobile;
    public $governorate;
    public $track;
    public $skills;
    public $message;
}

// ---------- متغيرات البداية ----------
$errors = [];      // هون منخزن رسائل الأخطاء (المفتاح = اسم الحقل، القيمة = نص الخطأ)
$success = false;  // بصير true لما كل شي يكون صح

// القيم القديمة (فاضية بالبداية)
// بنحتاجها عشان نعيد عرض اللي كتبه المستخدم إذا صار خطأ بدل ما تنمسح الحقول
$name = "";
$email = "";
$mobile = "";
$governorate = "";
$track = "";
$skills = [];
$message = "";
$agree = "";

// ---------- الكوكي: لو المستخدم زارنا قبل، نعبي الاسم والتراك ----------
// الكوكي بتنحفظ بمتصفح المستخدم، فبنقدر نعبي الحقول تلقائياً بالزيارة الجاية
if (isset($_COOKIE["saved_name"])) {
    $name = $_COOKIE["saved_name"];
}
if (isset($_COOKIE["saved_track"])) {
    $track = $_COOKIE["saved_track"];
}

// ---------- لما المستخدم يضغط Register ----------
// الصفحة بتنفتح أول مرة بطريقة GET (نعرض النموذج بس)، وبعد الضغط على الزر بتصير POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1) نجمع البيانات من $_POST وننظفها بـ trim()
    // trim بتشيل المسافات من أول وآخر النص، و ?? "" بتعطي قيمة فاضية إذا الحقل مش موجود
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $governorate = trim($_POST["governorate"] ?? "");
    $track = trim($_POST["track"] ?? "");
    $message = trim($_POST["message"] ?? "");
    $skills = $_POST["skills"] ?? [];      // مصفوفة (من checkboxes اسمها skills[])
    $agree = $_POST["agree"] ?? "";

    // 2) التحقق (Validation)
    // أي حقل فيه مشكلة بنضيف له رسالة خطأ بمصفوفة $errors
    if ($name == "") {
        $errors["name"] = "الاسم مطلوب";
    }

    if ($email == "") {
        $errors["email"] = "الايميل مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var بتتأكد إنه شكل الايميل صحيح
        $errors["email"] = "صيغة الايميل غلط";
    }

    if ($mobile == "") {
        $errors["mobile"] = "رقم الموبايل مطلوب";
    } elseif (!preg_match("/^07[789][0-9]{7}$/", $mobile)) {
        // Regex: لازم يبدأ بـ 07 بعدها 7 أو 8 أو 9، وبعدين 7 أرقام (المجموع 10 أرقام)
        $errors["mobile"] = "الرقم لازم يكون مثل 0791234567";
    }

    if ($governorate == "") {
        $errors["governorate"] = "اختار المحافظة";
    }

    if ($track == "") {
        $errors["track"] = "اختار تراك";
    }

    // لو ما اختار ولا مهارة، المصفوفة بتكون فاضية
    if (count($skills) == 0) {
        $errors["skills"] = "اختار مهارة وحدة على الأقل";
    }

    if ($agree == "") {
        $errors["agree"] = "لازم توافق على الشروط";
    }

    // 3) لو ما في أخطاء → نكمل التسجيل
    if (count($errors) == 0) {

        // نعمل Object ونعبيه
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->mobile = $mobile;
        $user->governorate = $governorate;
        $user->track = $track;
        $user->skills = $skills;
        $user->message = $message;

        // نخزن البيانات بالـ Session
        // (بنخزنها كمصفوفة عشان صفحة profile.php تقرأها)
        $_SESSION["user"] = [
            "name" => $user->name,
            "email" => $user->email,
            "mobile" => $user->mobile,
            "governorate" => $user->governorate,
            "track" => $user->track,
            "skills" => $user->skills,
            "message" => $user->message
        ];

        // نخزن الاسم والتراك بكوكي لمدة 30 يوم
        // time() + 60*60*24*30 = الوقت الحالي + 30 يوم بالثواني
        setcookie("saved_name", $user->name, time() + 60 * 60 * 24 * 30);
        setcookie("saved_track", $user->track, time() + 60 * 60 * 24 * 30);

        $success = true; // بنخلي الصفحة تعرض رسالة النجاح بدل النموذج
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration</title>
  <!-- ربط ملف التنسيق CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if ($success) { ?>

  <!-- صفحة النجاح: نعرض البيانات من الـ Object -->
  <!-- htmlspecialchars بتحمي من هجمات XSS عن طريق تحويل الرموز الخاصة لنص عادي -->
  <div class="card">
    <h2 class="success">Registration successful!</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user->name); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user->email); ?></p>
    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($user->mobile); ?></p>
    <p><strong>Governorate:</strong> <?php echo htmlspecialchars($user->governorate); ?></p>
    <p><strong>Track:</strong> <?php echo htmlspecialchars($user->track); ?></p>
    <!-- المهارات مصفوفة، فبنستخدم implode لدمجها بنص واحد مفصول بفاصلة -->
    <p><strong>Skills:</strong> <?php echo htmlspecialchars(implode(", ", $user->skills)); ?></p>
    <p><strong>Message:</strong> <?php echo htmlspecialchars($user->message); ?></p>
    <!-- رابط لصفحة البروفايل (بتقرأ من الـ Session) ورابط للرجوع للنموذج -->
    <a class="btn" href="profile.php">View profile (Session)</a>
    <a class="btn" href="register.php">Back</a>
  </div>

<?php } else { ?>

<!-- النموذج: action فاضي يعني يرسل لنفس الصفحة، و novalidate بيطفي تحقق المتصفح عشان نستخدم تحقق PHP -->
<form action="" method="post" novalidate>

  <!-- كل حقل: قيمته القديمة بتنطبع بـ value، ورسالة الخطأ تحته بالـ span (إذا موجودة) -->
  <label for="name">Full Name</label>
  <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
  <span class="error"><?php echo $errors["name"] ?? ""; ?></span>

  <label for="email">Email</label>
  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
  <span class="error"><?php echo $errors["email"] ?? ""; ?></span>

  <label for="mobile">Mobile</label>
  <input type="tel" id="mobile" name="mobile" placeholder="0791234567" value="<?php echo htmlspecialchars($mobile); ?>">
  <span class="error"><?php echo $errors["mobile"] ?? ""; ?></span>

  <!-- القائمة المنسدلة: الخيار المطابق للقيمة القديمة بياخد selected -->
  <label for="governorate">Governorate</label>
  <select id="governorate" name="governorate">
    <option value="">Choose a governorate</option>
    <option value="Amman"  <?php if ($governorate == "Amman")  echo "selected"; ?>>Amman</option>
    <option value="Irbid"  <?php if ($governorate == "Irbid")  echo "selected"; ?>>Irbid</option>
    <option value="Aqaba"  <?php if ($governorate == "Aqaba")  echo "selected"; ?>>Aqaba</option>
    <option value="Zarqa"  <?php if ($governorate == "Zarqa")  echo "selected"; ?>>Zarqa</option>
  </select>
  <span class="error"><?php echo $errors["governorate"] ?? ""; ?></span>

  <!-- أزرار الراديو (اختيار واحد فقط): كلهم بنفس الاسم track، والمطابق للقيمة القديمة بياخد checked -->
  <label>Track</label>
  <label class="inline">
    <input type="radio" name="track" value="Full Stack" <?php if ($track == "Full Stack") echo "checked"; ?>>
    Full Stack
  </label>
  <label class="inline">
    <input type="radio" name="track" value="Frontend" <?php if ($track == "Frontend") echo "checked"; ?>>
    Frontend
  </label>
  <label class="inline">
    <input type="radio" name="track" value="Backend" <?php if ($track == "Backend") echo "checked"; ?>>
    Backend
  </label>
  <span class="error"><?php echo $errors["track"] ?? ""; ?></span>

  <!-- مربعات الاختيار (أكثر من خيار): الاسم skills[] عشان PHP يستقبلها كمصفوفة -->
  <!-- in_array بتتأكد إذا القيمة موجودة بالمصفوفة عشان نعلّم الخيار checked -->
  <label>Skills you already have</label>
  <label class="inline">
    <input type="checkbox" name="skills[]" value="HTML" <?php if (in_array("HTML", $skills)) echo "checked"; ?>>
    HTML
  </label>
  <label class="inline">
    <input type="checkbox" name="skills[]" value="CSS" <?php if (in_array("CSS", $skills)) echo "checked"; ?>>
    CSS
  </label>
  <label class="inline">
    <input type="checkbox" name="skills[]" value="JavaScript" <?php if (in_array("JavaScript", $skills)) echo "checked"; ?>>
    JavaScript
  </label>
  <span class="error"><?php echo $errors["skills"] ?? ""; ?></span>

  <!-- حقل اختياري، ما إله تحقق -->
  <label for="message">Why do you want to join? (optional)</label>
  <textarea id="message" name="message" rows="4"><?php echo htmlspecialchars($message); ?></textarea>

  <!-- الموافقة على الشروط: إجبارية، إذا ما انعلّمت ما بتنبعث بالـ POST أصلاً -->
  <label class="inline terms">
    <input type="checkbox" name="agree" <?php if ($agree != "") echo "checked"; ?>>
    I agree to the academy terms
  </label>
  <span class="error"><?php echo $errors["agree"] ?? ""; ?></span>

  <button type="submit">Register</button>
</form>

<?php } // نهاية شرط if ($success) ?>

</body>
</html>