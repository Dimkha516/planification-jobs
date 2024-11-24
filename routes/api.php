<?php

use App\Http\Controllers\FirebaseAuthController;
use App\Http\Controllers\FirestoreController;
use App\Services\FirestoreREST;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Firestore\FirestoreClient;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test-firebase', function () {
    try {
        $firestore = new FirestoreREST();
        
        // Lire des documents
        $documents = $firestore->getCollection('planifications');
        
        // // Ajouter un nouveau document
        // $newDoc = $firestore->addDocument('planifications', [
        //     'title' => 'Test',
        //     'date' => '2024-03-24',
        //     'status' => true,
        //     'metadata' => [
        //         'created_by' => 'user1',
        //         'tags' => ['important', 'urgent']
        //     ]
        // ]);

        return response()->json([
            'documents' => $documents,
            // 'new_document' => $newDoc
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});