<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\IdentityType;
use App\Models\Language;
use App\Models\Nationality;
use App\Models\Operation;
use App\Models\Role;
use App\Models\SourcingType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class UserController extends Controller
{
    protected static function getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $manager_ids, $active, $dateDebut, $dateFin)
    {
        if ($status !== 'all_users') {
            $output = [
                0 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                1 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                2 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                3 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                4 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                5 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                6 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->paginate(),
                7 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('manager_id', $manager_ids)->paginate(),
                8 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                9 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                10 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                11 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                12 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                13 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                14 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut)->where('active', $active)->whereIn('manager_id', $manager_ids)->paginate(),
                16 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                17 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                18 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                19 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                20 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                21 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                22 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin)->where('active', $active)->whereIn('manager_id', $manager_ids)->paginate(),
                24 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                25 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                26 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->paginate(),
                27 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                28 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->paginate(),
                29 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                30 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->whereIn('operation_id', $operation_ids)->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('manager_id', $manager_ids)->paginate(),
            ];
        } else {
            $output = [
                0 => User::whereNull('date_entree_production')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                1 => User::whereNull('date_entree_production')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                2 => User::whereNull('date_entree_production')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                3 => User::whereNull('date_entree_production')->whereIn('language_id', $language_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                4 => User::whereNull('date_entree_production')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                5 => User::whereNull('date_entree_production')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                6 => User::whereNull('date_entree_production')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                7 => User::whereNull('date_entree_production')->paginate(),
                8 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                9 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                10 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                11 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('language_id', $language_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                12 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                13 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                14 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut)->paginate(),
                16 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                17 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                18 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                19 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('language_id', $language_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                20 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                21 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                22 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                24 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                25 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                26 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('language_id', $language_ids)->paginate(),
                27 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('language_id', $language_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                28 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->whereIn('role_id', $role_ids)->paginate(),
                29 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('role_id', $role_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                30 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->whereIn('operation_id', $operation_ids)->whereIn('manager_id', $manager_ids)->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
            ];
        }

        return $output;
    }

    protected static function getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)
    {
        $managers_ids = [];
        if (empty($names)) {
            return self::getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status, $active, $dateDebut, $dateFin);
        } else {
            foreach ($names as $name) {
                $wholeName = explode(' ', $name);
                $managers_ids[] = User::where('first_name', $wholeName[1])->where('last_name', $wholeName[2])->first()->id;
            }
            return self::getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $managers_ids, $active, $dateDebut, $dateFin);
        }
    }
    protected static function getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status, $active, $dateDebut, $dateFin)
    {
        if ($status !== 'all_users') {
            $output = [
                0 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                1 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                2 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                3 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                4 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                5 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                6 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereNull('date_entree_production')->paginate(),
                7 => User::where('active', $active)->whereNull('date_entree_production')->paginate(),
                8 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                9 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                10 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                11 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                12 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                13 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                14 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                15 => User::where('active', $active)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                16 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                17 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                18 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                19 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                20 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                21 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                22 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                23 => User::where('active', $active)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                24 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                25 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                26 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                27 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                28 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                29 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                30 => User::where('active', $active)->whereIn('operation_id', $operation_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                31 => User::where('active', $active)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
            ];
        } else {
            $output = [
                0 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                1 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                2 => User::whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                3 => User::whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                4 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                5 => User::whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                6 => User::whereIn('operation_id', $operation_ids)->whereNull('date_entree_production')->paginate(),
                7 => User::whereNull('date_entree_production')->paginate(),
                8 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                9 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                10 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                11 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                12 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                13 => User::whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                14 => User::whereIn('operation_id', $operation_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '')->paginate(),
                16 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                17 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                18 => User::whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                19 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                20 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                21 => User::whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                22 => User::whereIn('operation_id', $operation_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin ?? '')->paginate(),
                24 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                25 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                26 => User::whereIn('operation_id', $operation_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                27 => User::whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                28 => User::whereIn('operation_id', $operation_ids)->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                29 => User::whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                30 => User::whereIn('operation_id', $operation_ids)->whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut??'', $dateFin??''])->paginate(),
            ];
        }

        return $output;
    }

    protected static function getDataFromBody($input)
    {
        $output = [];
        if (!empty($input)) {
            foreach ($input as $item) {
                $output[] = $item;
            }
        }
        return $output;
    }

    protected static function bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)
    {
        $conditions = [
            0 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            1 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            2 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            3 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            4 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            5 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            6 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            7 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            8 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            9 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            10 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            11 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            12 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            13 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            14 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            15 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            16 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            17 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            18 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            19 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            20 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            21 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            22 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            23 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            24 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            25 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            26 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            27 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            28 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            29 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            30 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            31 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
        ];

        $output = [];
        foreach ($conditions as $key => $condition) {
            $obj = new StdClass();
            $obj->condition = $condition;
            $obj->query = self::getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)[$key];
            $output[] = $obj;
        }
        return $output;
    }

    protected static function validateUser($body) {
        $user = User::where('matricule', $body['past_matricule'])->first();
        $validator = Validator::make($body, [
            'matricule' => ['required', $body['edit_matricule'] ? Rule::unique('users')->ignore($user->id ?? null) : 'unique:users,matricule', 'min:5'],
            'email_1' => ['required', $body['edit_email'] ? Rule::unique('users')->ignore($user->id ?? null) : 'unique:users,email_1', 'email'],
            'nom' => 'required|min:2',
            'prenom' => 'required|min:2',
            'date_mep' => 'nullable|date',
            'sexe' => 'required',
            'date_entree_formation' => 'nullable|date',
            'type_identite' => 'nullable',
            'cin_number' => 'nullable',
            'passport_number' => 'nullable',
            'carte_sejour_number' => 'nullable',
            'langue_principale' => 'nullable',
            'nationalite' => 'nullable',
            'date_naissance' => 'nullable|date',
            'sourcing_provider' => 'nullable',
            'situation_familiale' => 'nullable',
            'phone_1' => 'nullable',
            'photo' => 'nullable',
            'phone_2' => 'nullable',
            'nombre_enfants' => 'nullable',
            'cnss_number' => 'nullable',
            'address' => 'nullable',
            'comment' => 'nullable'
        ]);
        return $validator;
    }

    protected static function createUser($body, $request) {
        return User::factory()->create([
            'matricule' => $body['matricule'],
            'email_1' => $body['email_1'],
            'first_name' => $body['nom'],
            'last_name' => $body['prenom'],
            'date_entree_production' => $body['date_mep'],
            'Sexe' => $body['sexe'] === 'homme' ? 'H' : 'F',
            'date_entree_formation' => $body['date_entree_formation'],
            'primary_language_id' => Language::where('name', 'like', "%".$body['langue_principale']."%")->first()->id,
            'nationality_id' => Nationality::where('name', 'like', $body['nationalite'])->first()->id,
            'date_naissance' => $body['date_naissance'],
            'sourcing_type_id' => SourcingType::where('name', 'like', "%".$body['sourcing_provider']."%")->first()->id,
            'situation_familiale' => $body['situation_familiale'],
            'phone_1' => $body['phone_1'],
            'photo' => $body['photo'],
            'phone_2' => $body['phone_2'],
            'nombre_enfants' => $body['nombre_enfants'],
            'cnss_number' => $body['cnss_number'],
            'address' => $body['address'],
            'comment_id' => Comment::factory()->create(['comment' => $body['comment']])->id,
            'creator_id' => json_decode(Redis::get($request->headers->get('Uuid')))->id,
            'solde_cp' => 0,
            'solde_rjf' => 0
        ]);
    }

    protected static function createIdentity($name, $user_id, $identity_number) {
        IdentityType::factory()->create([
            'name' => $name,
            'user_id' => $user_id,
            'identity_number' => $identity_number
        ]);
    }

    protected static function updateUser($body, $request) {
        $user = User::where('matricule', $body['past_matricule'])->first();
        $user->matricule = $body['matricule'];
        $user->email_1 = $body['email_1'];
        $user->first_name = $body['nom'];
        $user->last_name = $body['prenom'];
        $user->date_entree_production = $body['date_mep'];
        $user->Sexe = $body['sexe'] === 'homme' ? 'H' : 'F';
        $user->date_entree_formation = $body['date_entree_formation'];
        $user->primary_language_id = Language::where('name', 'like', "%".$body['langue_principale']."%")->first()->id;
        $user->nationality_id = Nationality::where('name', 'like', $body['nationalite'])->first()->id;
        $user->date_naissance = $body['date_naissance'];
        $user->sourcing_type_id = SourcingType::where('name', 'like', "%".($body['sourcing_provider'] ?? 'null')."%")->first()->id ?? null;
        $user->situation_familiale = $body['situation_familiale'];
        $user->phone_1 = $body['phone_1'];
        $user->photo = $body['photo'];
        $user->phone_2 = $body['phone_2'];
        $user->nombre_enfants = $body['nombre_enfants'];
        $user->cnss_number = $body['cnss_number'];
        $user->address = $body['address'];
        $user->comment_id = Comment::factory()->create(['comment' => $body['comment']])->id;
        $user->creator_id = json_decode(Redis::get($request->headers->get('Uuid')))->id;
        $user->solde_cp = $body['solde_cp'];
        $user->solde_rjf = $body['solde_rjf'];

        $user->save();
    }

    public static function getUsers($status, $body)
    {
        $operations = [];
        $names = [];
        if (!empty($body['teams'])) {
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
        $profiles = self::getDataFromBody($body['profiles'] ?? []);
        $languages = self::getDataFromBody($body['languages'] ?? []);
        $operation_ids = Operation::whereIn('name', $operations)->pluck('id');
        $role_ids = Role::whereIn('name', $profiles)->pluck('id');
        $language_ids = Language::whereIn('name', $languages)->pluck('id');
        $active = null;
        if ($status === 'active_users' || $status === 'inactive_users') {
            $active = ($status === 'active_users');
        }

        $objectToTake = [];
        foreach (self::bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names, $active, $body['dateDebut'], $body['dateFin']) as $key => $obj) {
            if ($obj->condition) {
                $objectToTake = $obj->query;
            }
        }

        return $objectToTake;
    }

    public static function addUser($body, $request)
    {
        $validator = self::validateUser($body);
        if($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            $user = self::createUser($body, $request);
            self::createIdentity('CIN', $user->id, $body['cin_number']);
            self::createIdentity('Carte sejour', $user->id, $body['carte_sejour_number']);
            self::createIdentity('Passeport', $user->id, $body['passport_number']);
            return 'done';
        }
    }

    public static function editUser($body, $request)
    {
        $validator = self::validateUser($body);
        if ($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            self::updateUser($body, $request);
            return 'done';
        }
    }
}
