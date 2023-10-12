<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Operation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redis;
use stdClass;

class DataController extends Controller
{
    function getOperationsWithSupervisors($status, $search)
    {
        $operations = Operation::where('name', 'like', "%$search%");
        switch($status) {
            case 'active_users':
                $operations = $operations->get()->isEmpty() ? Operation::where('active', true)->get() : $operations->where('active', true)->get();
                break;
            case 'all_users':
                $operations = $operations->get();
                break;
            case 'inactive_users':
                $operations = $operations->get()->isEmpty() ? Operation::where('active', false)->get() : $operations->where('active', false)->get();
                break;
        }
        $output = [];
        foreach ($operations as $operation) {
            $obj = new StdClass();
            $obj->operation = $operation->name;
            $queryFirstName = [];
            $queryLastName = [];
            switch($status) {
                case 'active_users':
                    $queryFirstName = $operation->users()->where('date_depart', null)->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    $queryLastName = $operation->users()->where('date_depart', null)->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    break;
                case 'all_users':
                    $queryFirstName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    $queryLastName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    break;
                case 'inactive_users':
                    $queryFirstName = $operation->users()->whereNotNull('date_depart')->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    $queryLastName = $operation->users()->whereNotNull('date_depart')->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
                    break;
            }
            if (!$queryFirstName->get()->isEmpty() && !$queryLastName->get()->isEmpty()) {
                $obj->users = $queryFirstName->get()->merge($queryLastName->get())->unique();
            } else if ($queryFirstName->get()->isEmpty()) {
                $obj->users = $queryLastName->get();
            } else if ($queryLastName->get()->isEmpty()) {
                $obj->users = $queryFirstName->get();
            } else {
                $obj->users = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->get();
            }
            $output[] = $obj;
        }
        return $output;
    }

    function getProfiles($search) {
        return Role::where('name', 'like', "%$search%")->pluck('name');
    }

    function getLanguages($search) {
        return Language::where('name', 'like', "%$search%")->pluck('name');
    }

    function getDataFromBody($input)
    {
        $output = [];
        if (!empty($input)) {
            foreach ($input as $item) {
                $output[] = $item;
            }
        }
        return $output;
    }

    function getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $firstNames, $lastNames)
    {
        $output = [];
        switch($status) {
            case 'active_users':
                $output = [
                    0 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('language_id', $language_ids)->paginate(),
                    4 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->paginate(),
                    6 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->paginate(),
                    7 => User::whereNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->paginate(),
                ];
                break;
            case 'all_users':
                $output = [
                    0 => User::whereIn('operation_id', $operation_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereIn('role_id', $role_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereIn('operation_id', $operation_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereIn('language_id', $language_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->paginate(),
                    4 => User::whereIn('operation_id', $operation_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereIn('role_id', $role_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->paginate(),
                    6 => User::whereIn('operation_id', $operation_ids)->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->paginate(),
                    7 => User::paginate(),
                ];
                break;
            case 'inactive_users':
                $output = [
                    0 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('language_id', $language_ids)->paginate(),
                    4 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('role_id', $role_ids)->paginate(),
                    6 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->whereIn('operation_id', $operation_ids)->paginate(),
                    7 => User::whereNotNull('date_depart')->whereIn('first_name', $firstNames)->whereIn('last_name', $lastNames)->paginate(),
                ];
                break;
        }
        return $output;
    }

    function getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names)
    {
        if (empty($names)) {
            return $this->getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status);
        } else {
            $firstNames = [];
            $lastNames = [];
            foreach ($names as $name) {
                $wholeName = explode(' ', $name);
                $firstNames[] = $wholeName[1];
                $lastNames[] = $wholeName[2];
            }
            return $this->getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $firstNames, $lastNames);
        }
    }

    function getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status)
    {
        $output = [];
        switch($status) {
            case 'active_users':
                $output = [
                    0 => User::whereNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereNull('date_depart')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereNull('date_depart')->whereIn('language_id', $language_ids)->paginate(),
                    4 => User::whereNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereNull('date_depart')->whereIn('role_id', $role_ids)->paginate(),
                    6 => User::whereNull('date_depart')->whereIn('operation_id', $operation_ids)->paginate(),
                    7 => User::whereNull('date_depart')->paginate(),
                ];
                break;
            case 'all_users':
                $output = [
                    0 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereIn('language_id', $language_ids)->paginate(),
                    4 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereIn('role_id', $role_ids)->paginate(),
                    6 => User::whereIn('operation_id', $operation_ids)->paginate(),
                    7 => User::paginate(),
                ];
                break;
            case 'inactive_users':
                $output = [
                    0 => User::whereNotNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    1 => User::whereNotNull('date_depart')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                    2 => User::whereNotNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                    3 => User::whereNotNull('date_depart')->whereIn('language_id', $language_ids)->paginate(),
                    4 => User::whereNotNull('date_depart')->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                    5 => User::whereNotNull('date_depart')->whereIn('role_id', $role_ids)->paginate(),
                    6 => User::whereNotNull('date_depart')->whereIn('operation_id', $operation_ids)->paginate(),
                    7 => User::whereNotNull('date_depart')->paginate(),
                ];
                break;
        }
        return $output;
    }

    function bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names)
    {
        $conditions = [
            0 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty(),
            1 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty(),
            2 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty(),
            3 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty(),
            4 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty(),
            5 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty(),
            6 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty(),
            7 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty(),
        ];

        $output = [];
        foreach ($conditions as $key => $condition) {
            $obj = new StdClass();
            $obj->condition = $condition;
            $obj->query = $this->getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names)[$key];
            $output[] = $obj;
        }
        return $output;
    }

    function getUsers($status, $body)
    {
        $operations = [];
        $names = [];
        if(!empty($body['teams'])) {
            foreach ($body['teams'] as $team) {
                if (str_contains($team, '/')) {
                    $output = explode("/", $team);
                    if (!in_array($output[0], $operations)) {
                        $operations[] = $output[0];
                    }
                    $names[] = $output[1];
                } else {
                    $operations[] = $team;
                }
            }
        }
        $profiles = $this->getDataFromBody($body['profiles'] ?? []);
        $languages = $this->getDataFromBody($body['languages'] ?? []);
        $operation_ids = Operation::whereIn('name', $operations)->pluck('id');
        $role_ids = Role::whereIn('name', $profiles)->pluck('id');
        $language_ids = Language::whereIn('name', $languages)->pluck('id');

        $objectToTake = [];
        foreach ($this->bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names) as $key => $obj) {
            if ($obj->condition) {
                $objectToTake = $obj->query;
            }
        }

        return $objectToTake;
    }

    public function getData(Request $request) {
        switch ($request['type']) {
            case 'user':
                return Redis::get($request->headers->get('Uuid'));
            case 'team':
                return $this->getOperationsWithSupervisors($request['status'], $request['search']);
            case 'profile':
                return $this->getProfiles($request['search']);
            case 'language':
                return $this->getLanguages($request['search']);
            case 'users':
                return $this->getUsers($request['status'], $request['data'] ?? []);
        }
        return ['empty'];
    }
}
