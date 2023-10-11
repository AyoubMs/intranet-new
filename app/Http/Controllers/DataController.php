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
    function getOperationsWithSupervisors($status, $search) {
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
            $queryFirstName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('first_name', 'like', "%$search%");
            $queryLastName = $operation->users()->where('role_id', Role::where('name', 'Superviseur')->first()->id)->where('last_name', 'like', "%$search%");
            if ($queryFirstName->get()->isEmpty()) {
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
        }
        return ['empty'];
    }
}
