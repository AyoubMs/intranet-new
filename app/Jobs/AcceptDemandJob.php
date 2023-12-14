<?php

namespace App\Jobs;

use App\Http\Controllers\DemandeCongeController;
use App\Models\DemandeConge;
use App\Models\DemandeCongeStack;
use App\Models\TypeConge;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class AcceptDemandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $request;

    /**
     * Create a new job instance.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $demand = DemandeConge::where('id', $this->request['data']['id'])->first();
        $user = json_decode(Redis::get($this->request->headers->get('Uuid')));
        $user = User::where('matricule', $user->matricule)->first();
        if (DemandeCongeController::isITResponsable($user->role_id)) {
            $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by resp it");
        } else if (DemandeCongeController::isSupervisor($user->role_id)) {
            $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by sup%");
        } else if (DemandeCongeController::isWFM($user->role_id)) {
            if (DemandeCongeController::isVigie($user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by vigie");
            } else if (DemandeCongeController::isCPS($user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by cps");
            } else if (DemandeCongeController::isCCI($user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by cci");
            } else if (DemandeCongeController::isCoordinator($user->role_id)) {
                if (str_contains(strtolower($user->role->name), 'vigie')) {
                    $demand->etat_demande_id = DemandeCongeController::getEtatDemande('validated by coordinateur vigie');
                } else if (str_contains(strtolower($user->role->name), 'cps')) {
                    $demand->etat_demande_id = DemandeCongeController::getEtatDemande('validated by coordinateur cps');
                }
            } else if (DemandeCongeController::isHeadOfOperationalExcellence($user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('validated by head%');
            } else {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('validated by wfm');
            }
        } else if (DemandeCongeController::isOpsManager($user->role_id) and !DemandeCongeController::isHeadOfOperationalExcellence($user->role_id)) {
            $demand->etat_demande_id = DemandeCongeController::getEtatDemande("validated by ops%");
        } else if (DemandeCongeController::isHR($user->role_id)) {
            if (DemandeCongeController::isResponsableRH($user->role_id) and DemandeCongeController::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('closed');
            } else {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('closed');
            }
        } else if (DemandeCongeController::isDirector($user->role_id)) {
            if (DemandeCongeController::isResponsableRH($demand->user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('closed');
            } else if (DemandeCongeController::isChargeRH($demand->user->role_id)) {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('closed');
            } else {
                $demand->etat_demande_id = DemandeCongeController::getEtatDemande('validated by director');
            }
        }
        $demand->type_conge_id = $this->request['data']['type_conge_id'];
        $conge_paye_id = TypeConge::where('name', 'conge paye')->first()->id;
        if ($demand->type_conge_id === $conge_paye_id) {
            $user = $demand->user;
            $demand_stack_elem = DemandeCongeStack::where('demande_conge_id', $demand->id)->first();
            $nombre_jours_confirmed = doubleval($this->request['data']['nombre_jours_confirmed']);
            DemandeCongeController::resetTheSoldes($demand, $user);
            DemandeCongeController::correctSoldes("conge paye", $nombre_jours_confirmed, $user->solde_rjf, $user, $demand_stack_elem, $this->request);

        }
        $demand->nombre_jours = doubleval($this->request['data']['nombre_jours_confirmed']);
        $demand->save();
        return $demand;
    }
}
