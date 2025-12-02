<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SafeImageContent implements Rule
{
    protected $errorMessage = '';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // التحقق من أن الملف موجود وصالح
        if (!$value || !$value->isValid()) {
            $this->errorMessage = 'الملف غير صالح.';
            return false;
        }

        try {
            // الحصول على مسار الملف المؤقت
            $imagePath = $value->getRealPath();
            
            // تحميل الصورة باستخدام GD
            $imageInfo = getimagesize($imagePath);
            
            if (!$imageInfo) {
                $this->errorMessage = 'تعذر قراءة الصورة. يرجى التأكد من أن الملف صورة صالحة.';
                return false;
            }

            // التحقق من أبعاد الصورة
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            if ($width < 100 || $height < 100) {
                $this->errorMessage = 'الصورة صغيرة جداً. الحد الأدنى للأبعاد هو 100x100 بكسل.';
                return false;
            }
            
            if ($width > 4000 || $height > 4000) {
                $this->errorMessage = 'الصورة كبيرة جداً. الحد الأقصى للأبعاد هو 4000x4000 بكسل.';
                return false;
            }

            // تحميل الصورة حسب النوع
            $image = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $image = @imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = @imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $image = @imagecreatefromgif($imagePath);
                    break;
                default:
                    $this->errorMessage = 'نوع الصورة غير مدعوم. يرجى استخدام JPEG أو PNG.';
                    return false;
            }

            if (!$image) {
                $this->errorMessage = 'تعذر معالجة الصورة. يرجى التأكد من أن الملف غير تالف.';
                return false;
            }

            // تحليل محتوى الصورة
            $analysisResult = $this->analyzeImageContent($image, $width, $height);
            
            // تحرير الذاكرة
            imagedestroy($image);

            // التحقق من النتائج
            if ($analysisResult['skin_percentage'] > 60) {
                $this->errorMessage = 'الصورة تحتوي على محتوى غير مناسب. يرجى رفع صور متعلقة بالشكوى فقط.';
                return false;
            }

            if ($analysisResult['red_percentage'] > 70) {
                $this->errorMessage = 'الصورة تحتوي على محتوى غير مناسب (عنف محتمل). يرجى رفع صور متعلقة بالشكوى فقط.';
                return false;
            }

            return true;

        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نسمح بالصورة لتجنب حظر المستخدمين بسبب أخطاء تقنية
            // يمكن تسجيل الخطأ للمراجعة لاحقاً
            \Log::warning('Image validation error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * تحليل محتوى الصورة للكشف عن المحتوى غير المناسب
     *
     * @param resource $image
     * @param int $width
     * @param int $height
     * @return array
     */
    protected function analyzeImageContent($image, $width, $height)
    {
        $totalPixels = 0;
        $skinPixels = 0;
        $redPixels = 0;

        // أخذ عينات من البكسلات (كل 10 بكسل لتحسين الأداء)
        $step = 10;
        
        for ($x = 0; $x < $width; $x += $step) {
            for ($y = 0; $y < $height; $y += $step) {
                $totalPixels++;
                
                // الحصول على لون البكسل
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // الكشف عن البكسلات الجلدية
                // استخدام خوارزمية معروفة للكشف عن لون البشرة
                if ($this->isSkinColor($r, $g, $b)) {
                    $skinPixels++;
                }

                // الكشف عن البكسلات الحمراء (محتمل عنف/دم)
                if ($this->isRedColor($r, $g, $b)) {
                    $redPixels++;
                }
            }
        }

        return [
            'skin_percentage' => ($totalPixels > 0) ? ($skinPixels / $totalPixels) * 100 : 0,
            'red_percentage' => ($totalPixels > 0) ? ($redPixels / $totalPixels) * 100 : 0,
        ];
    }

    /**
     * التحقق من أن اللون يشبه لون البشرة
     * يستخدم معايير RGB المعروفة للكشف عن لون البشرة
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @return bool
     */
    protected function isSkinColor($r, $g, $b)
    {
        // خوارزمية الكشف عن لون البشرة
        // تعمل مع مختلف درجات البشرة
        return (
            $r > 95 && $g > 40 && $b > 20 &&
            $r > $g && $r > $b &&
            abs($r - $g) > 15 &&
            $r - $b > 15
        );
    }

    /**
     * التحقق من أن اللون أحمر بشكل مكثف
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @return bool
     */
    protected function isRedColor($r, $g, $b)
    {
        // كشف اللون الأحمر المكثف (دم، عنف)
        return (
            $r > 150 &&
            $r > ($g * 2) &&
            $r > ($b * 2)
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage ?: 'الصورة تحتوي على محتوى غير مناسب.';
    }
}
