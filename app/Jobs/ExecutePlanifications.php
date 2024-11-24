<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirestoreREST;

class ExecutePlanifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreREST();

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Récupérer les planifications avec le statut "pending"
            $planifications = $this->firestore->getCollection('planifications');

            foreach ($planifications as $id => $planification) {
                // Vérifier si le statut est "pending"
                if ($planification['status'] === 'pending') {
                    // Exécuter la planification
                    $this->processPlanification($planification);

                    // Mettre à jour le statut dans Firestore
                    $this->firestore->addDocument("planifications/$id", [
                        'status' => 'completed',
                        'executed_at' => now()->toIso8601String(),
                    ]);
                } 
            } 
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'exécution des planifications : ' . $e->getMessage());
        }
    }

    private function processPlanification(array $planification)
    {
        // Exemple de logique métier (personnalisez selon vos besoins)
        \Log::info("Exécution de la planification pour le client : {$planification['client_id']}, montant : {$planification['montant']}");
    }
}
