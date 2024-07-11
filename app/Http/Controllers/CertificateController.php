<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public function get($course_id)
    {
        $user = Auth::user();

        if($user->role != 'Student'){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $certificate = Certificate::where('student_id', $user->id)
                            ->where('course_id', $course_id)
                            ->firstOrFail();

        $filePath = storage_path($certificate->certificate);

        if(!file_exists($filePath)){
            return response()->json(['error' => 'Sertifikat tidak ditemukan!'], 404);
        }

        return response()->download($filePath, explode('/', $certificate->certificate)[1], [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.explode('/', $certificate->certificate)[1].'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => 0
        ]);
    }
}
