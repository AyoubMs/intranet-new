<?php

namespace App\Http\Controllers;

use App\Jobs\LoadDataFromDB;
use App\Models\Comment;
use App\Models\FamilySituation;
use App\Models\IdentityType;
use App\Models\Language;
use App\Models\MotifDepart;
use App\Models\Nationality;
use App\Models\Operation;
use App\Models\Role;
use App\Models\SourcingType;
use App\Models\User;
use Database\Seeders\Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use stdClass;

class DataController extends Controller
{
    function getOperationsWithSupervisors($status, $search, $active)
    {
        if ($status !== 'all_users') {
            $operations = Operation::where('name', 'like', "%$search%")->get()->isEmpty() ? Operation::where('active', $active)->get() : Operation::where('name', 'like', "%$search%")->where('active', $active)->get();
        } else {
            $operations = Operation::where('name', 'like', "%$search%")->get();
        }

        $output = [];
        foreach ($operations as $operation) {
            $obj = new StdClass();
            $obj->operation = $operation->name;
            if ($status !== 'all_users') {
                $queryFirstName = $operation->users()->where('active', $active)->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%")->get();
                $queryLastName = $operation->users()->where('active', $active)->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('last_name', 'like', "%$search%")->get();
            } else {
                $queryFirstName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%")->get();
                $queryLastName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('last_name', 'like', "%$search%")->get();
            }
            if (!$queryFirstName->isEmpty() && !$queryLastName->isEmpty()) {
                $obj->users = $queryFirstName->merge($queryLastName)->unique();
            } else if ($queryFirstName->isEmpty()) {
                $obj->users = $queryLastName;
            } else if ($queryLastName->isEmpty()) {
                $obj->users = $queryFirstName;
            } else {
                $obj->users = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->get();
            }
            $output[] = $obj;
        }
        return $output;
    }

    function getProfiles($search)
    {
        return Role::where('name', 'like', "%$search%")->pluck('name');
    }

    function getLanguages($search)
    {
        return Language::where('name', 'like', "%$search%")->pluck('name');
    }

    function getUser($search)
    {
        return User::where('matricule', 'like', "%$search%")->first();
    }

    function getDataByType($type, $query) {
        dispatch(new LoadDataFromDB($query, $type));
        return json_decode(Redis::get($type));
    }

    public function getData(Request $request)
    {
        switch ($request['type']) {
            case 'export_demands':
                return DemandeCongeController::exportDemandsFile($request);
            case 'search_demands':
                return DemandeCongeController::searchDemands($request);
            case 'inject_solde':
                InjectionController::injectSolde();
                break;
            case 'deactivate_user':
                return UserController::deactivateUser($request->body);
            case 'motifs_depart':
                return MotifDepart::pluck('name')->toArray();
            case 'affect_user':
                return UserController::affectUser($request->body);
            case 'edit_user':
                return UserController::editUser($request->body, $request);
            case 'add_user':
                return UserController::addUser($request->body, $request);
            case 'operations':
                return $this->getDataByType('operations', Operation::pluck('name'));
            case 'family_situations':
                return $this->getDataByType('family_situations', FamilySituation::pluck('name'));
            case 'sourcing_providers':
                return $this->getDataByType('sourcing_providers', SourcingType::select('name')->pluck('name'));
            case 'nationalities':
                return $this->getDataByType('nationalities', Nationality::pluck('name'));
            case 'sourcing_types':
                return $this->getDataByType('sourcing_types', SourcingType::select('type')->distinct()->pluck('type'));
            case 'userByRegNumber':
                if ($request['search'] !== '' && !is_null($request['search'])) {
                    return $this->getUser($request['search']);
                }
                return 'null';
            case 'user':
                return Redis::get($request->headers->get('Uuid'));
            case 'team':
                $active = true;
                if ($request['status'] === 'inactive_users') {
                    $active = false;
                }
                return $this->getOperationsWithSupervisors($request['status'], $request['search'], $active);
            case 'profile':
                return $this->getProfiles($request['search']);
            case 'language':
                return $this->getLanguages($request['search']);
            case 'users':
                return UserController::getUsers($request['status'], $request['data'] ?? []);
        }
        return ['empty'];
    }
}
