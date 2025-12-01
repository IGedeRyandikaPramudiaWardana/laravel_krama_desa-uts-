<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banjar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BanjarController extends Controller
{
    public function index(): JsonResponse 
    {
        $banjars = Banjar::orderBy('nama_banjar')->get();
        return response()->json($banjars);
    }
}
