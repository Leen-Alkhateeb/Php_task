<?php
// لازم نحمّل الكلاس قبل session_start() عشان PHP يقدر يرجّع الكائن من الـ Session
require_once 'Registration.php';
session_start();

// دالة لتنظيف أي نص قبل ما نطبعه بالصفحة (حماية من XSS)
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ---------- القوائم الثابتة (منستخدمها بالفورم وبالتحقق كمان) ----------
$governorates = ['Amman', 'Irbid', 'Zarqa', 'Balqa', 'Mafraq', 'Jerash', 'Ajloun', 'Karak', 'Tafilah', 'Maan', 'Aqaba', 'Madaba'];
$tracks       = ['Web Development', 'Data Science', 'Mobile Apps', 'Cyber Security'];
$skillsList   = ['HTML', 'CSS', 'JavaScript', 'PHP', 'SQL', 'Python'];

$errors  = [];    // هون منخزن رسائل الأخطاء، المفتاح هو اسم الحقل
$success = null;  // بيصير فيه كائن Registration إذا التسجيل نجح

// القيم الافتراضية للفورم
$old = [
    'name' => '', 'email' => '', 'mobile' => '',
    'governorate' => '', 'track' => '', 'skills' => [],
    'message' => '', 'terms' => '',
];

// ---------- الكوكيز: نعبّي الاسم والإيميل والـ Track من الكوكي لما يزور الصفحة عادي (مش POST) ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    foreach (['name', 'email', 'track'] as $key) {
        if (isset($_COOKIE['reg_' . $key])) {
            $old[$key] = $_COOKIE['reg_' . $key];
        }
    }
}

// ---------- معالجة الفورم لما المستخدم يضغط Register ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) نجمع البيانات من $_POST وننظفها بـ trim() (بتشيل المسافات الزايدة)
    //    و ?? '' بتعطي قيمة فاضية إذا الحقل مش موجود
    $old['name']        = trim($_POST['name'] ?? '');
    $old['email']       = trim($_POST['email'] ?? '');
    $old['mobile']      = trim($_POST['mobile'] ?? '');
    $old['governorate'] = trim($_POST['governorate'] ?? '');
    $old['track']       = trim($_POST['track'] ?? '');
    $old['message']     = trim($_POST['message'] ?? '');
    // الـ checkbox إذا انضغط بينبعث، وإذا لا ما بينبعث أصلاً
    $old['terms']       = isset($_POST['terms']) ? '1' : '';

    // المهارات لازم تكون مصفوفة، ونشيل أي شي مش نص، وننظف كل عنصر
    $postedSkills = (isset($_POST['skills']) && is_array($_POST['skills'])) ? $_POST['skills'] : [];
    $old['skills'] = array_map('trim', array_filter($postedSkills, 'is_string'));

    // 2) التحقق (التحقق الحقيقي بيصير هون بالـ PHP مش بالـ HTML)

    // الاسم: مطلوب، وما يقل عن 3 أحرف
    if ($old['name'] === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($old['name']) < 3) {
        $errors['name'] = 'Name must be at least 3 characters.';
    }

    // الإيميل: مطلوب وبصيغة صحيحة (filter_var بتتأكد من الصيغة)
    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // الموبايل: بنشيل المسافات والشرطات أول، بعدين نتأكد من الصيغة الأردنية 07X XXXXXXX
    // (X بتكون 7 أو 8 أو 9)
    $mobileClean = preg_replace('/[\s\-]/', '', $old['mobile']);
    if ($old['mobile'] === '') {
        $errors['mobile'] = 'Mobile number is required.';
    } elseif (!preg_match('/^07[789][0-9]{7}$/', $mobileClean)) {
        $errors['mobile'] = 'Enter a valid mobile number, e.g. 0791234567.';
    }

    // المحافظة: لازم تكون مختارة ومن ضمن القائمة (عشان حدا ما يبعث قيمة مزيفة)
    if ($old['governorate'] === '') {
        $errors['governorate'] = 'Please select a governorate.';
    } elseif (!in_array($old['governorate'], $governorates, true)) {
        $errors['governorate'] = 'Invalid governorate.';
    }

    // الـ Track: لازم خيار واحد ومن ضمن القائمة
    if ($old['track'] === '') {
        $errors['track'] = 'Please choose a track.';
    } elseif (!in_array($old['track'], $tracks, true)) {
        $errors['track'] = 'Invalid track.';
    }

    // المهارات: وحدة على الأقل، وكلها لازم تكون من القائمة
    // array_diff بترجع أي قيمة مش موجودة بالقائمة الأصلية
    if (count($old['skills']) === 0) {
        $errors['skills'] = 'Select at least one skill.';
    } elseif (array_diff($old['skills'], $skillsList)) {
        $errors['skills'] = 'Invalid skill selected.';
    }

    // الشروط والأحكام: لازم توافق عليها
    if ($old['terms'] !== '1') {
        $errors['terms'] = 'You must accept the terms and conditions.';
    }

    // 3) إذا ما في ولا خطأ: نعمل الكائن ونحفظه بالـ Session والكوكيز
    if (empty($errors)) {
        $success = new Registration(
            $old['name'],
            $old['email'],
            $mobileClean,
            $old['governorate'],
            $old['track'],
            $old['skills'],
            $old['message']
        );

        // نحفظ الكائن بالـ Session عشان نعرضه بصفحة profile.php
        $_SESSION['registration'] = $success;

        // نحفظ 3 قيم بالكوكيز لمدة 30 يوم (60 ثانية × 60 دقيقة × 24 ساعة × 30 يوم)
        // لازم setcookie تنكتب قبل ما نطبع أي HTML، عشان هيك هي هون فوق
        $expire = time() + 60 * 60 * 24 * 30;
        setcookie('reg_name',  $success->name,  $expire, '/');
        setcookie('reg_email', $success->email, $expire, '/');
        setcookie('reg_track', $success->track, $expire, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <!-- شريط التنقل بين الصفحات -->
    <nav>
        <a href="register.php">Register</a>
        <a href="profile.php">My Profile (Session)</a>
        <a href="search.php">Search</a>
    </nav>

<?php if ($success): ?>

    <!-- ===== التسجيل نجح: نعرض المعلومات اللي انبعثت ===== -->
    <h1>Registration successful</h1>
    <div class="box">
        <p><strong>Name:</strong> <?= e($success->name) ?></p>
        <p><strong>Email:</strong> <?= e($success->email) ?></p>
        <p><strong>Mobile:</strong> <?= e($success->mobile) ?></p>
        <p><strong>Governorate:</strong> <?= e($success->governorate) ?></p>
        <p><strong>Track:</strong> <?= e($success->track) ?></p>
        <p><strong>Skills:</strong> <?= e($success->skillsText()) ?></p>
        <!-- nl2br بتحوّل نزول السطر لـ <br>، والرسالة اختيارية فإذا فاضية منعرض شرطة -->
        <p><strong>Message:</strong> <?= $success->message !== '' ? nl2br(e($success->message)) : '-' ?></p>
    </div>
    <p><a href="profile.php">View data from session</a> | <a href="register.php">Register again</a></p>

<?php else: ?>

    <!-- ===== فورم التسجيل ===== -->
    <h1>Registration Form</h1>

    <!-- method="post" حسب المطلوب، و novalidate بيطفي تحقق المتصفح عشان نختبر تحققنا إحنا بالـ PHP -->
    <form method="post" novalidate>

        <!-- الاسم: value بترجّع القيمة القديمة إذا فشل التحقق -->
        <div class="field">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>">
            <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <!-- الإيميل -->
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
            <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>

        <!-- الموبايل -->
        <div class="field">
            <label for="mobile">Mobile</label>
            <input type="text" id="mobile" name="mobile" placeholder="0791234567" value="<?= e($old['mobile']) ?>">
            <?php if (isset($errors['mobile'])): ?><span class="error"><?= e($errors['mobile']) ?></span><?php endif; ?>
        </div>

        <!-- المحافظة: نمرّ على القائمة ونحط selected على الخيار المختار سابقاً -->
        <div class="field">
            <label for="governorate">Governorate</label>
            <select id="governorate" name="governorate">
                <option value="">-- Select --</option>
                <?php foreach ($governorates as $g): ?>
                    <option value="<?= e($g) ?>" <?= $old['governorate'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['governorate'])): ?><span class="error"><?= e($errors['governorate']) ?></span><?php endif; ?>
        </div>

        <!-- الـ Track: radio buttons (خيار واحد بس)، و checked على الخيار القديم -->
        <div class="field">
            <label>Track</label>
            <div class="options">
                <?php foreach ($tracks as $t): ?>
                    <label class="inline">
                        <input type="radio" name="track" value="<?= e($t) ?>" <?= $old['track'] === $t ? 'checked' : '' ?>>
                        <?= e($t) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($errors['track'])): ?><span class="error"><?= e($errors['track']) ?></span><?php endif; ?>
        </div>

        <!-- المهارات: checkboxes، واسم الحقل skills[] عشان PHP يستقبلها كمصفوفة -->
        <div class="field">
            <label>Skills</label>
            <div class="options">
                <?php foreach ($skillsList as $s): ?>
                    <label class="inline">
                        <input type="checkbox" name="skills[]" value="<?= e($s) ?>" <?= in_array($s, $old['skills'], true) ? 'checked' : '' ?>>
                        <?= e($s) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($errors['skills'])): ?><span class="error"><?= e($errors['skills']) ?></span><?php endif; ?>
        </div>

        <!-- الرسالة (اختيارية، ما إلها تحقق) -->
        <div class="field">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4"><?= e($old['message']) ?></textarea>
        </div>

        <!-- الشروط والأحكام -->
        <div class="field">
            <label class="inline">
                <input type="checkbox" name="terms" value="1" <?= $old['terms'] === '1' ? 'checked' : '' ?>>
                I accept the Terms &amp; Conditions
            </label>
            <?php if (isset($errors['terms'])): ?><span class="error"><?= e($errors['terms']) ?></span><?php endif; ?>
        </div>

        <button type="submit">Register</button>
    </form>

<?php endif; ?>

</div>
</body>
</html>