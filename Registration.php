<?php
// الكلاس هاد هو "الكائن" اللي بيحمل بيانات التسجيل بعد ما يعدّي التحقق بنجاح
class Registration
{
    public string $name;         // الاسم الكامل
    public string $email;        // الإيميل
    public string $mobile;       // رقم الموبايل
    public string $governorate;  // المحافظة
    public string $track;        // المسار (Track)
    public array  $skills;       // المهارات (مصفوفة لأنه ممكن أكثر من وحدة)
    public string $message;      // الرسالة

    // الـ constructor بيشتغل أول ما نعمل new Registration(...) وبيعبّي الخصائص
    public function __construct(
        string $name,
        string $email,
        string $mobile,
        string $governorate,
        string $track,
        array  $skills,
        string $message
    ) {
        $this->name        = $name;
        $this->email       = $email;
        $this->mobile      = $mobile;
        $this->governorate = $governorate;
        $this->track       = $track;
        $this->skills      = $skills;
        $this->message     = $message;
    }

    // بتحوّل مصفوفة المهارات لنص مفصول بفواصل عشان نعرضه بسهولة
    public function skillsText(): string
    {
        return implode(', ', $this->skills);
    }
}