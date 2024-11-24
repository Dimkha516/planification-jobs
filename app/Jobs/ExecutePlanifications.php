<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirestoreREST;
use Carbon\Carbon;


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

    public function handle(): void
    {
        try {
            // Récupérer les planifications depuis Firestore
            $planifications = $this->firestore->getCollection('planifications');

            foreach ($planifications as $id => $planification) {
                $status = $planification['status'] ?? null;
                $frequence = $planification['frequence'] ?? null;
                $prochaineEcheance = isset($planification['prochaineEcheance'])
                    ? new \DateTime($planification['prochaineEcheance'])
                    : null;

                $now = new \DateTime(); // Heure actuelle
                $tolerance = 60; // Tolérance en secondes (1 minute)

                // Vérifier si la planification est prête à être exécutée
                if (
                    $status === 'En attente' &&
                    $prochaineEcheance &&
                    abs($now->getTimestamp() - $prochaineEcheance->getTimestamp()) <= $tolerance
                ) {
                    \Log::info("Exécution de la planification ID: {$id}, Client: {$planification['client_id']}, Montant: {$planification['montant']}");

                    // Simuler l'exécution de la transaction
                    $this->processPlanification($planification);

                    // Calculer la prochaine échéance
                    $newProchaineEcheance = $this->calculateNextEcheance($frequence, $prochaineEcheance);

                    // Mettre à jour la planification dans Firestore
                    $this->firestore->addDocument("planifications/$id", [
                        'id' => $planification['id'],
                        'client_id' => $planification['client_id'],
                        'destinataire' => $planification['destinataire'],
                        'montant' => $planification['montant'],
                        'frequence' => $frequence,
                        'prochaineEcheance' => $newProchaineEcheance->format(DATE_ISO8601),
                        'status' => 'En attente', // Réinitialiser pour la prochaine exécution
                        'executed_at' => $now->format(DATE_ISO8601),
                    ]);

                    \Log::info("Planification mise à jour : Nouvelle échéance -> {$newProchaineEcheance->format(DATE_ISO8601)}");
                }
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'exécution des planifications : ' . $e->getMessage());
        }
    }

    /**
     * Execute the job.
     */
    // public function handle(): void
    // {
    //     try {
    //         // Récupérer les planifications avec le statut "pending"
    //         $planifications = $this->firestore->getCollection('planifications');

    //         foreach ($planifications as $id => $planification) {

    //             $status = $planification['status'] ?? null;
    //             $prochaineEcheance = isset($planification['prochaineEcheance'])
    //                 ? new \DateTime($planification['prochaineEcheance'])
    //                 : null;

    //             $now = new \DateTime(); // Heure actuelle
    //             $tolerance = 60; // Tolérance en secondes (par ex. 1 minute)

    //             if (
    //                 // $planification['status'] === 'En attente' && $planification['prochaineEcheance'] === now()->toIso8601String()
    //                 $status === 'En attente' &&
    //                 $prochaineEcheance &&
    //                 abs($now->getTimestamp() - $prochaineEcheance->getTimestamp()) <= $tolerance

    //             ) {
    //                 // Exécuter la planification
    //                 $this->processPlanification($planification);

    //                 // Mettre à jour le statut dans Firestore
    //                 $this->firestore->addDocument("planifications/$id", [
    //                     'id' => $planification['id'],
    //                     'client_id' => $planification['client_id'],
    //                     'destinataire' => $planification['destinataire'],
    //                     'montant' => $planification['montant'],
    //                     'frequence' => $planification['frequence'],
    //                     'prochaineEcheance' => $planification['prochaineEcheance'],
    //                     // 'status' => 'En attente',
    //                     'status' => 'exécutée',
    //                     'executed_at' => now()->toIso8601String(),
    //                 ]);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Erreur lors de l\'exécution des planifications : ' . $e->getMessage());
    //     }
    // }


    /**
     * Calculer la prochaine échéance en fonction de la fréquence
     *
     * @param string|null $frequence
     * @param \DateTime $currentEcheance
     * @return \DateTime
     */
    private function calculateNextEcheance(?string $frequence, \DateTime $currentEcheance): \DateTime
    {
        switch ($frequence) {
            case 'journalier':
                return $currentEcheance->add(new \DateInterval('P1D')); // +1 jour
            case 'hebdomadaire':
                return $currentEcheance->add(new \DateInterval('P7D')); // +7 jours
            case 'mensuel':
                return $currentEcheance->add(new \DateInterval('P1M')); // +1 mois
            default:
                throw new \Exception("Fréquence inconnue : $frequence");
        }
    }





    private function processPlanification(array $planification)
    {
        $id = $planification['id'];
        $transactions = $this->firestore->getCollection('transactions');
        $this->firestore->addDocument("transactions/$id", [
            'client_id' => $planification['client_id'],
            'type' => '⏰',
            'numero_destinataire' => $planification['destinataire'],
            'montant' => $planification['montant'],
            'etat' => 'exécutée',
            'date' => Carbon::now()->toIso8601String()
        ]);

        // Exemple de logique métier (personnalisez selon vos besoins)
        \Log::info("Exécution de la planification pour le client : {$planification['client_id']}, montant : {$planification['montant']}");
    }
}


