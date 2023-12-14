<?php

namespace App\Jobs;

use App\Models\DemandeConge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class LoadDataFromDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $query;
    public $type;
    public $isRoles;
    public $etat_demande_ids;
    public $user_ids;
    public $isDemands;

    /**
     * Create a new job instance.
     */
    public function __construct($query, $type, $isRoles, $etat_demande_ids, $user_ids, $isDemands)
    {
        $this->query = $query;
        $this->type = $type;
        $this->isRoles = $isRoles;
        $this->etat_demande_ids = $etat_demande_ids;
        $this->user_ids = $user_ids;
        $this->isDemands = $isDemands;
        $this->handle();
    }

    function storeUsersInRedis($role_ids) {
        $this->query = User::whereIn('role_id', $role_ids)->pluck('id')->toArray();
        Redis::set($this->type, json_encode($this->query));
        return "";
    }

    function storeDemandsInRedis($etat_demande_ids, $user_ids) {
        $this->query = DemandeConge::whereIn('etat_demande_id', $etat_demande_ids)->whereIn('user_id', $user_ids)->pluck('id')->toArray();
        Redis::set($this->type, json_encode($this->query));
        return "";
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $role_ids = [];

        // process demands
        if ($this->isDemands) {
            return $this->storeDemandsInRedis($this->etat_demande_ids, $this->user_ids);
        }

        // process roles and users with specific roles
        if ($this->type === 'getITAgentIds') {
            $role_ids = Role::whereIn('name', ['informaticien', 'stagiaire it'])->pluck('id')->toArray();
        } else if ($this->type === 'getAgentIds') {
            $role_ids = Role::where('name', 'like', "agent%")->orWhere('name', 'like', "expert%")->orWhere('name', 'like', "conseiller%")->pluck('id')->toArray();
        } else if ($this->type === 'getSupervisorIds') {
            $role_ids = Role::where('name', 'superviseur')->pluck('id')->toArray();
        } else if ($this->type === 'getResponsableITIds') {
            $role_ids = Role::where('name', 'responsable it')->pluck('id')->toArray();
        } else if ($this->type === 'getCCIIds') {
            $role_ids = Role::where('name', 'like', "%incoh%")->pluck('id')->toArray();
        } else if ($this->type === 'getOpsManagersIds') {
            $role_ids = Role::where('name', 'like', '%operation%')->whereNot('name', 'like', 'head%')->pluck('id')->toArray();
        } else if ($this->type === 'getWFMCoordinatorIds') {
            $vigie_coordinators_role_ids = Role::where('name', 'like', 'coordinateur vigie')->first()->id;
            $cps_coordinators_role_ids = Role::where('name', 'like', 'coordinateur cps')->first()->id;
            $role_ids[] = $vigie_coordinators_role_ids;
            $role_ids[] = $cps_coordinators_role_ids;
        } else if ($this->type === 'getHeadOfOperationalExcellenceIds') {
            $role_ids = Role::where('name', 'like', '%operation%')->where('name', 'like', 'head%')->pluck('id')->toArray();
        } else if ($this->type === 'getVigieCoordinatorIds') {
            $role_ids = Role::where('name', 'coordinateur vigie')->pluck('id')->toArray();
        } else if ($this->type === 'getVigieIds') {
            $role_ids = Role::where('name', 'vigie')->pluck('id')->toArray();
        } else if ($this->type === 'getCPSIds') {
            $role_ids = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
        } else if ($this->type === 'getCPSCoordinatorIds') {
            $role_ids = Role::where('name', 'coordinateur cps')->pluck('id')->toArray();
        } else if ($this->type === 'getWFMAgentsIds') {
            $cps_role_ids = Role::where('name', 'like', "%statis%")->pluck('id')->toArray();
            $vigie_role_ids = Role::where('name', 'vigie')->pluck('id')->toArray();
            $cci_role_ids = Role::where('name', 'like', "%incoh%")->pluck('id')->toArray();
            $role_ids[] = $cps_role_ids;
            $role_ids[] = $vigie_role_ids;
            $role_ids[] = $cci_role_ids;
            $role_ids = array_merge(...$role_ids);
        } else if ($this->type === 'getChargeRHIds') {
            $role_ids = Role::where('name', 'like', "%charge% rh")->pluck('id')->toArray();
        } else if ($this->type === 'getResponsableHRIds') {
            $role_ids = Role::where('name', 'responsable rh')->pluck('id')->toArray();
        }
        if ($this->isRoles) {
            Redis::set($this->type."_roles", json_encode($role_ids));
            return '';
        }

        if ($role_ids !== []) {
            return $this->storeUsersInRedis($role_ids);
        }
        if (gettype($this->query) !== "array" && gettype($this->query) !== "string") {
            if (empty(array_diff(json_decode(Redis::get($this->type) ?? "[]"), $this->query->toArray()))) {
                Redis::set($this->type, $this->query);
            }
        }
    }
}
