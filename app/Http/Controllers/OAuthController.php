<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Exception;
use http\Header;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use MongoDB\Driver\Exception\ConnectionException;

class OAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $token = $request->get('token');
        return Socialite::driver('azure')->with(['state' => "token=$token"])->redirect();
    }

    public function callback(Request $request)
    {
        $state = $request->input('state');
        parse_str($state, $result);
        $azureUser = Socialite::driver('azure')->stateless()->user();
        $name = $azureUser->getName();
        $lastName = substr($name, 0, strpos($name, ' '));
        $firstName = substr($name, strpos($name, ' ') + 1);
        if ($user = User::where(['first_name' => $firstName, 'last_name' => $lastName])->first()) {
            $user->email_1 = $azureUser->getEmail();
            $user->provider_id = $azureUser->getId();
            $user->save();
        } else {
            User::factory()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email_1' => $azureUser->getEmail(),
                'provider_id' => $azureUser->getId()
            ]);
        }
        auth()->login($user, true);
        Redis::set($result['token'], json_encode($user));

        return redirect()->to('http://localhost:3000/dashboard');
    }
}
