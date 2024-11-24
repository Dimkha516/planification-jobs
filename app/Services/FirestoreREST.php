<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;


class FirestoreREST
{
    private $projectId;
    private $accessToken;
    private $baseUrl;

    public function __construct($projectId = null)
    {
        // Si projectId n'est pas fourni, on essaie de le récupérer du fichier service-account
        $serviceAccountPath = storage_path('app/firebase/firebase_credentials.json');
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        $this->projectId = $projectId ?? $serviceAccount['project_id'];
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";

        // Initialiser les credentials
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/datastore',
            $serviceAccount
        );

        // Obtenir le token
        $this->accessToken = $credentials->fetchAuthToken()['access_token'];
    }

    public function getCollection($collectionName)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/{$collectionName}");

            if ($response->successful()) {
                return $this->parseDocuments($response->json());
            }

            throw new \Exception('Erreur Firestore: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('Erreur FirestoreREST: ' . $e->getMessage());
            throw $e;
        }
    }
    public function addDocument($path, $data)
    {
        try {
            // Vérifie si le chemin correspond à un document ou une collection
            $isDocument = substr_count($path, '/') % 2 == 1;

            $url = $isDocument ? "{$this->baseUrl}/{$path}" : "{$this->baseUrl}/{$path}?documentId=" . $data['id'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->patch($url, [
                        'fields' => $this->prepareFields($data)
                    ]);

            if ($response->successful()) {
                return $this->parseFields($response->json()['fields']);
            }

            throw new \Exception('Erreur Firestore: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('Erreur FirestoreREST: ' . $e->getMessage());
            throw $e;
        }
    }

    // public function addDocument($collectionName, $data)
    // {
    //     try {
    //         // Vérifie si le chemin correspond à un document ou une collection
    //         $isDocument = substr_count($path, '/') % 2 == 1;
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->accessToken,
    //             'Content-Type' => 'application/json',
    //         ])->post("{$this->baseUrl}/{$collectionName}", [
    //                     'fields' => $this->prepareFields($data)
    //                 ]);

    //         if ($response->successful()) {
    //             return $this->parseFields($response->json()['fields']);
    //         }

    //         throw new \Exception('Erreur Firestore: ' . $response->body());
    //     } catch (\Exception $e) {
    //         \Log::error('Erreur FirestoreREST: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }

    private function prepareFields($data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->prepareValue($value);
        }
        return $fields;
    }

    private function prepareValue($value)
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        }
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }
        if (is_float($value)) {
            return ['doubleValue' => $value];
        }
        if (is_string($value)) {
            return ['stringValue' => $value];
        }
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                // C'est un tableau indexé
                return [
                    'arrayValue' => [
                        'values' => array_map([$this, 'prepareValue'], $value)
                    ]
                ];
            } else {
                // C'est un tableau associatif (map)
                return [
                    'mapValue' => [
                        'fields' => $this->prepareFields($value)
                    ]
                ];
            }
        }
        throw new \Exception('Type de données non supporté');
    }

    private function parseDocuments($response)
    {
        $documents = [];
        if (isset($response['documents'])) {
            foreach ($response['documents'] as $doc) {
                $docName = basename($doc['name']);
                $documents[$docName] = $this->parseFields($doc['fields'] ?? []);
            }
        }
        return $documents;
    }

    private function parseFields($fields)
    {
        $result = [];
        foreach ($fields as $key => $value) {
            $type = array_key_first($value);
            $result[$key] = $this->parseValue($value);
        }
        return $result;
    }

    private function parseValue($value)
    {
        $type = array_key_first($value);
        $actualValue = $value[$type];

        switch ($type) {
            case 'nullValue':
                return null;
            case 'booleanValue':
                return (bool) $actualValue;
            case 'integerValue':
                return (int) $actualValue;
            case 'doubleValue':
                return (float) $actualValue;
            case 'stringValue':
                return (string) $actualValue;
            case 'arrayValue':
                return array_map(
                    [$this, 'parseValue'],
                    $actualValue['values'] ?? []
                );
            case 'mapValue':
                return $this->parseFields($actualValue['fields'] ?? []);
            default:
                return $actualValue;
        }
    }
}