<?php

// namespace App\Http\Controllers;


// use App\Services\FirebaseService;
// use Illuminate\Http\Request;

// class FirestoreController extends Controller
// {
//     protected $firebaseService;

//     public function __construct(FirebaseService $firebaseService)
//     {
//         $this->firebaseService = $firebaseService;
//     }


//     public function addDocument()
//     {
//         $firestore = $this->firebaseService->getFirestore();
//         $collection = $firestore->collection('users');

//         $document = $collection->add([
//             'name' => 'Jane Doe',
//             'email' => 'jane.doe@example.com',
//         ]);

//         return response()->json(['message' => 'Document added', 'id' => $document->id()]);
//     }
// }
