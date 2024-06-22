<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Challenge::all();
        return response()->json($challenges);
    }
}
