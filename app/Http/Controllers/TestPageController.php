<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class TestPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('TestPage', ['author' => 'Ruslan Madatov']);
    }

    public function ws()
    {
        return view('ws');
    }
}
