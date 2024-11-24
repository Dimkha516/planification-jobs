<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;

class FirebaseAuthController extends Controller
{
    protected $firebaseService;

    // public function __construct(FirebaseService $firebaseService)
    // {
    //     $this->firebaseService = $firebaseService;
    // }

    // public function createUser(Request $request)
    // {
    //     $auth = $this->firebaseService->getAuth();

    //     $user = $auth->createUser([
    //         'email' => $request->email,
    //         'password' => $request->password,
    //     ]);

    //     return response()->json($user);
    // }
}
