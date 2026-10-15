<?php
// ===== الإعدادات =====
// عنوان الـ API تبع Ollama (شغّال محلياً على جهازك)
const OLLAMA_URL = 'http://localhost:11434/api/chat';
// اسم الموديل المستخدم (يدعم الصور vl = vision-language)
const MODEL      = 'qwen3-vl:2b';
// تعليمات النظام: بتحدد شخصية وأسلوب المساعد
const SYSTEM     = 'أنت مساعد ذكي ودود. جاوب بنفس لغة المستخدم وبشكل واضح ومختصر. إذا أُرسلت لك صورة، حلّلها بدقة.';

// ===== معالجة الطلبات (الـ API الداخلي) =====
// نفس الملف بيشتغل كصفحة (GET) وكـ API (POST)
// لما JavaScript يبعث رسالة بيكون الطلب POST، فبنعالجه هون ونوقف التنفيذ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_time_limit(300); // XAMPP الافتراضي 30 ثانية وهاد قليل للموديلات
    header('Content-Type: application/json; charset=utf-8'); // الرد دايماً JSON

    // نص الرسالة (trim بتشيل المسافات الزايدة) و ?? '' بتعطي قيمة فاضية إذا مش موجود
    $text    = trim($_POST['message'] ?? '');
    // سجل المحادثة السابقة جاي كنص JSON، نحوله لمصفوفة
    $history = json_decode($_POST['history'] ?? '[]', true);
    // إذا التحويل فشل أو مش مصفوفة، نبدأ بسجل فاضي
    if (!is_array($history)) $history = [];

    // رسالة المستخدم الحالية، وإذا ما كتب نص (بس صورة) نحط نص افتراضي
    $userMsg = ['role' => 'user', 'content' => $text !== '' ? $text : 'حلّل هذه الصورة'];

    // الصورة (اختيارية): تتحول إلى base64 كما يطلب Ollama
    // نتأكد إنه في ملف مرفوع وما صار خطأ بالرفع
    if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // getimagesize بتتأكد إنه الملف صورة فعلاً وبترجع نوعه (mime)
        $info = @getimagesize($_FILES['image']['tmp_name']);
        // نقبل بس JPG و PNG و WEBP
        if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'])) {
            echo json_encode(['error' => 'الصورة لازم تكون JPG أو PNG أو WEBP']);
            exit;
        }
        // الحد الأقصى لحجم الصورة 8 ميجا
        if ($_FILES['image']['size'] > 8 * 1024 * 1024) {
            echo json_encode(['error' => 'حجم الصورة كبير (الحد الأقصى 8MB)']);
            exit;
        }
        // نقرأ الصورة ونحولها base64 ونحطها بمصفوفة images داخل الرسالة
        $userMsg['images'] = [base64_encode(file_get_contents($_FILES['image']['tmp_name']))];
    }

    // السجل: نص فقط (بدون صور قديمة) لتوفير الذاكرة
    // أول رسالة دايماً هي رسالة النظام
    $messages = [['role' => 'system', 'content' => SYSTEM]];
    foreach ($history as $h) {
        // نضيف بس الرسائل الصحيحة (لها role و content، والـ role إما user أو assistant)
        // هاد بيمنع المستخدم من حقن رسائل system مزيفة
        if (isset($h['role'], $h['content']) && in_array($h['role'], ['user', 'assistant'])) {
            $messages[] = ['role' => $h['role'], 'content' => (string)$h['content']];
        }
    }
    // آخر شي نضيف رسالة المستخدم الحالية
    $messages[] = $userMsg;

    // نجهز طلب cURL لإرساله لـ Ollama
    $ch = curl_init(OLLAMA_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,                                  // الطلب نوعه POST
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],    // نبعث البيانات كـ JSON
        // stream=false: بدنا الرد كامل مرة وحدة مش بالتدريج
        // think=false: نطفي وضع التفكير عشان الرد يكون أسرع
        // keep_alive=30m: خلي الموديل محمّل بالذاكرة 30 دقيقة عشان ما يتأخر بالرسائل الجاية
        CURLOPT_POSTFIELDS     => json_encode(['model' => MODEL, 'messages' => $messages, 'stream' => false, 'think' => false, 'keep_alive' => '30m']),
        CURLOPT_RETURNTRANSFER => true,                                  // رجّع الرد كنص بدل ما يطبعه
        CURLOPT_TIMEOUT        => 300,                                   // مهلة 5 دقايق
    ]);
    $raw  = curl_exec($ch);     // تنفيذ الطلب
    $err  = curl_error($ch);    // رسالة الخطأ (إن وجدت)
    curl_close($ch);

    // إذا فشل الاتصال بالكامل (مثلاً Ollama مش شغّال)
    if ($raw === false) {
        echo json_encode(['error' => 'ما قدرت أتصل بـ Ollama. تأكد إنه شغّال. (' . $err . ')']);
        exit;
    }
    $data = json_decode($raw, true);
    // إذا Ollama رجّع خطأ (مثلاً الموديل مش موجود)
    if (isset($data['error'])) {
        echo json_encode(['error' => $data['error']]);
        exit;
    }
    // نرجع نص رد الموديل للواجهة، وإذا ما في رد نرجع نص فاضي
    echo json_encode(['reply' => $data['message']['content'] ?? '']);
    exit; // نوقف هون عشان ما يطبع صفحة الـ HTML تحت
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>مساعدي الذكي</title>
</head>
<body>
<div class="app">
  <!-- العنوان: بيطبع اسم الموديل، و htmlspecialchars للحماية من XSS -->
  <header>مساعدي الذكي <small><?= htmlspecialchars(MODEL) ?> · يشتغل محلياً</small></header>

  <!-- منطقة عرض المحادثة، وفيها رسالة ترحيب بتنشال لما تبدأ أول رسالة -->
  <div id="log"><div class="empty">اكتب سؤالك، أو أرفق صورة واطلب تحليلها.</div></div>

  <!-- شريط معاينة اسم الصورة المرفقة، مخفي من البداية (hidden) ويظهر لما المستخدم يختار صورة -->
  <div id="preview" hidden><span id="fname"></span> <button type="button" id="rm">إزالة</button></div>

  <!-- نموذج الإرسال -->
  <form id="f">
    <!-- الـ label شغّال كزر لاختيار الصورة لأنه مربوط بالـ input عن طريق for="img" -->
    <label class="btn" for="img" title="إرفاق صورة">صورة</label>
    <!-- حقل رفع الصورة الحقيقي، مخفي، ويقبل أنواع صور محددة -->
    <input type="file" id="img" accept="image/jpeg,image/png,image/webp" hidden>
    <textarea id="msg" rows="1" placeholder="اكتب رسالتك..."></textarea>
    <button class="btn primary" id="send">إرسال</button>
  </form>
</div>

<script>
// ===== جلب عناصر الصفحة =====
const log = document.getElementById('log'), f = document.getElementById('f'),
      msg = document.getElementById('msg'), img = document.getElementById('img'),
      send = document.getElementById('send'), preview = document.getElementById('preview'),
      fname = document.getElementById('fname');

// سجل المحادثة (نص فقط)، بنبعثه مع كل رسالة عشان الموديل يتذكر السياق
let history = [];

// لما المستخدم يختار صورة: نظهر اسمها بشريط المعاينة
img.onchange = () => {
  if (img.files[0]) { fname.textContent = 'الصورة المرفقة: ' + img.files[0].name; preview.hidden = false; }
};
// زر "إزالة": بيمسح الصورة المختارة ويخفي الشريط
document.getElementById('rm').onclick = () => { img.value = ''; preview.hidden = true; };

// ===== دالة إضافة رسالة للمحادثة =====
// cls: نوع الرسالة (user / bot / err)، text: النص، imgFile: صورة اختيارية
function add(cls, text, imgFile) {
  // نشيل رسالة الترحيب إذا كانت موجودة
  const empty = log.querySelector('.empty'); if (empty) empty.remove();
  const d = document.createElement('div'); d.className = 'msg ' + cls;
  // إذا في صورة، نعرضها داخل الرسالة (createObjectURL بتعمل رابط مؤقت للملف المحلي)
  if (imgFile) { const i = document.createElement('img'); i.src = URL.createObjectURL(imgFile); d.appendChild(i); }
  // createTextNode بتعرض النص كنص عادي (مش HTML) فهي آمنة من XSS
  d.appendChild(document.createTextNode(text));
  log.appendChild(d); log.scrollTop = log.scrollHeight; // ننزل لآخر المحادثة
  return d; // نرجّعها عشان نقدر نحذف رسالة "جاري التفكير" بعدين
}

// Enter بيرسل الرسالة، و Shift+Enter بينزل سطر جديد
msg.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); f.requestSubmit(); } });

// ===== إرسال الرسالة =====
f.onsubmit = async e => {
  e.preventDefault(); // نمنع إعادة تحميل الصفحة
  const text = msg.value.trim(), file = img.files[0];
  if (!text && !file) return; // ما في شي نبعثه

  // نعرض رسالة المستخدم فوراً
  add('user', text || 'حلّل هذه الصورة', file);

  // FormData عشان نقدر نبعث نص + ملف مع بعض
  const fd = new FormData();
  fd.append('message', text);
  fd.append('history', JSON.stringify(history)); // السجل كنص JSON
  if (file) fd.append('image', file);

  // نفرّغ الحقول ونعطّل زر الإرسال لحد ما يجي الرد
  msg.value = ''; img.value = ''; preview.hidden = true;
  send.disabled = true;
  const wait = add('bot', 'جاري التفكير...'); // رسالة انتظار مؤقتة

  try {
    // fetch('') يعني نبعث الطلب لنفس الصفحة (وهي بتعالجه بجزء PHP فوق)
    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    wait.remove(); // نشيل رسالة الانتظار
    if (data.error) { add('err', data.error); }
    else {
      add('bot', data.reply);
      // نضيف الرسالة والرد للسجل (بدون صور) عشان تنبعث مع الرسالة الجاية
      history.push({ role: 'user', content: text || 'حلّل هذه الصورة' }, { role: 'assistant', content: data.reply });
    }
  } catch (err) {
    // خطأ بالاتصال بالسيرفر نفسه
    wait.remove(); add('err', 'صار خطأ بالاتصال بالسيرفر.');
  }
  send.disabled = false; msg.focus(); // نرجع نفعّل الزر ونحط المؤشر بحقل الكتابة
};
</script>
</body>
</html>