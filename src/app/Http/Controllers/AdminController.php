<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;

class AdminController extends Controller
{
    public function index() {

        return view('admin_nav');
    }
}
