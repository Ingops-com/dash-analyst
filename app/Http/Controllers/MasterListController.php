<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterListController extends Controller
{
    public function index()
    {
        return Inertia::render('MasterList');
    }
}