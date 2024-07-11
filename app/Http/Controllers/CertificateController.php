<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function generate()
    {
        $certificateService = new CertificateService();
        $certificatePath = $certificateService->generateCertificate(
            "Egy Dya Hermawan", "Building Trust", now()
        );

        return response()->json(['status' => true, 'path' => $certificatePath], 200);
    }
}
