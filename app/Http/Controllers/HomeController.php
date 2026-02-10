<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login.form');
        }

        $data['user'] = auth()->user();
        return view('home', $data);
    }
}