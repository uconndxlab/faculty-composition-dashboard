<?php

namespace App\Http\Controllers;

use App\Services\FacultyModelingService;
use Illuminate\Http\Request;

class ModelingController extends Controller
{
    public function index(Request $request, FacultyModelingService $modelingService)
    {
        $result = $modelingService->build($request->query());

        return view('modeling.index', $result);
    }
}