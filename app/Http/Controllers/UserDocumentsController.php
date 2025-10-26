<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserDocumentsController extends Controller
{
    public function index()
    {
        return Inertia::render('UserDocuments');
    }
}