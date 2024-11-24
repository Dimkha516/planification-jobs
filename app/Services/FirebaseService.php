<?php
// namespace App\Services;

// use Http;
// use Kreait\Firebase\Factory;
// use Kreait\Firebase\Auth;
// use Kreait\Firebase\Firestore;
// use Google\Cloud\Firestore\FirestoreClient;
// use Google\Cloud\Storage\StorageClient;




// class FirebaseService
// {
//     private $projectId;
//     private $accessToken;
//     private $baseUrl;

//     public function __construct($projectId)
//     {
//         $this->projectId = $projectId;
//         $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";

//         // Obtenir le token d'authentification depuis le fichier de service
//         $storage = new StorageClient([
//             'keyFilePath' => storage_path('app/firebase/service-account.json')
//         ]);
//         $this->accessToken = $storage->authorizeClient()->authorize()['access_token'];
//     }

//     public function getCollection($collectionName)
//     {
//         $response = Http::withHeaders([
//             'Authorization' => 'Bearer ' . $this->accessToken,
//             'Content-Type' => 'application/json',
//         ])->get("{$this->baseUrl}/{$collectionName}");

//         if ($response->successful()) {
//             return $this->parseDocuments($response->json());
//         }

//         return null;
//     }

//     private function parseDocuments($response)
//     {
//         $documents = [];
//         if (isset($response['documents'])) {
//             foreach ($response['documents'] as $doc) {
//                 $docName = basename($doc['name']);
//                 $documents[$docName] = $this->parseFields($doc['fields']);
//             }
//         }
//         return $documents;
//     }

//     private function parseFields($fields)
//     {
//         $result = [];
//         foreach ($fields as $key => $value) {
//             $type = key($value);
//             $result[$key] = $this->parseValue($value[$type]);
//         }
//         return $result;
//     }

//     private function parseValue($value)
//     {
//         if (is_array($value)) {
//             if (isset($value['arrayValue'])) {
//                 return array_map([$this, 'parseValue'], $value['arrayValue']['values'] ?? []);
//             }
//             if (isset($value['mapValue'])) {
//                 return $this->parseFields($value['mapValue']['fields'] ?? []);
//             }
//         }
//         return $value;
//     }



//     // protected $firestore; 

//     // public function __construct()
//     // {
//     //     // Configurer Firestore avec le transport REST
//     //     $this->firestore = new FirestoreClient([
//     //         'keyFilePath' => base_path(env('FIREBASE_CREDENTIALS')),
//     //         'transport' => 'rest',
//     //     ]);
//     // }

//     // public function getFirestore(): FirestoreClient
//     // {
//     //     return $this->firestore;
//     // }
// }