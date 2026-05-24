<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
     public function index()
    {
        $users = User::all();

        return view('admin.users', compact('users'));
    }

    public function makeAdmin($id)
    {
        $user = User::findOrFail($id);

        $user->is_admin = true;

        $user->save();

        return redirect('/admin/users');
    }

    public function removeAdmin($id)
    {
        $user = User::findOrFail($id);

        $user->is_admin = false;

        $user->save();

        return redirect('/admin/users');
    }
}
