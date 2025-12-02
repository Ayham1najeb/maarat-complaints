<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NoProfanity implements Rule
{
    /**
     * قائمة الكلمات المحظورة
     * يمكن توسيع هذه القائمة لاحقاً أو نقلها لقاعدة البيانات
     */
    protected $profanityList = [
        'حمار', 'كلب', 'حيوان', 'غبي', 'حقير', 'تفه', 'زفت', 
        'لعنة', 'سافل', 'واطي', 'منحط', 'قذر', 'وسخ',
        'احمق', 'أحمق', 'جحش', 'ثور', 'بقر', 'بهيم',
        'خرا', 'زب', 'كس', 'شرموط', 'قحبة', 'عرص', 'ديوث', // كلمات بذيئة جداً
        'طيز', 'نيك', 'مني', 'طز', 'قواد', 'منيك', 'شرموطة',
        'fuck', 'shit', 'ass', 'bitch', 'whore', 'dick', 'pussy', 'bastard' // Common English profanity
    ];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true;
        }

        foreach ($this->profanityList as $word) {
            // Check if the word exists in the text (case insensitive for English)
            // For Arabic, simple strpos is usually enough, but we can be more sophisticated if needed
            if (mb_strpos($value, $word) !== false || stripos($value, $word) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'النص يحتوي على كلمات غير لائقة. يرجى استخدام لغة محترمة.';
    }
}
