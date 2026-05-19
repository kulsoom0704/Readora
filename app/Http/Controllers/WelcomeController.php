<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index(){
        return 'hello from readora';
        return view('welcome');
    }
}
