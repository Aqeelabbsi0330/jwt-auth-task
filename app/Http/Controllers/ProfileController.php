<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        try {

            $user = $request->attributes->get('user');
            return response()->json([
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'message' => 'your profile your register and currently login user'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                $e->getMessage(),
                $e->getLine()
            ]);
        }
    }
}
