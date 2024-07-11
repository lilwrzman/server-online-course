<?php

namespace App\Services;

use Intervention\Image\Laravel\Facades\Image;

class CertificateService
{
    public function generateCertificate($fullName, $courseName, $completionDate)
    {
        $fontBold = storage_path('app/fonts/Poppins-Bold.ttf');
        $fontRegular = storage_path('app/fonts/Poppins-Regular.ttf');
        $templatePath = storage_path('app/templates/certificate.png');
        $fileName = strtolower(str_replace(' ', '_', $fullName)) . '_' . uniqid() . '.png';
        $outputPath = storage_path('certificates/' . $fileName);

        $image = Image::read($templatePath);

        $image->text($fullName, 2435, 1550, function($font) use ($fontBold) {
            $font->file($fontBold);
            $font->size(200);
            $font->color("333333");
            $font->align('center');
            $font->valign('middle');
        });

        $image->text($courseName, 2435, 2050, function($font) use ($fontBold) {
            $font->file($fontBold);
            $font->size(120);
            $font->color("EF7748");
            $font->align('center');
            $font->valign('middle');
        });

        $image->text($completionDate->translatedFormat('l, j F Y'), 2435, 2220, function($font) use ($fontRegular) {
            $font->file($fontRegular);
            $font->size(80);
            $font->color("333333");
            $font->align('center');
            $font->valign('middle');
        });

        $image->save($outputPath);

        return $outputPath;
    }
}
