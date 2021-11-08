<?php

namespace App\Http\Controllers;

use App\Models\IPInfo;
use App\Models\IPLogs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required||unique:users',
            'password'  => 'required|min:8',
        ]);
        if($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first(),
                'success' => false,
                'statusCode' => 422
            ]);
        }
        $user = new User();
        $user->name =  $request->name;
        $user->email =  $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return [
            'success' => true,
            'statusCode' => 200,
            'message' => 'Successfully registered !',
        ];
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password'  => 'required|min:8',
        ]);
        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => $validator->errors()->first(),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        $token_validity = 24 * 60;

        auth()->factory()->setTTL($token_validity);
        if(!$token = auth()->attempt($validator->validated())) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'The credentials you provided is wrong. Please try again.'
            ]);
        }
        $IPLogs = new IPLogs();
        $IPLogs->userId =  $user->id;
        $IPLogs->type =  "Logged in";
        $IPLogs->save();
        return $this->respondWithToken($token, $user);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();
        $user = new IPLogs();
        $user->userId =  $request->userId;
        $user->type =  "LogOut!";
        $user->save();
        return response()->json(['success'=>true,'message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user)
    {
        $data = [
            'accessToken' => $token,
            'tokenType' => 'bearer',
            'name' => $user->name,
            'email' => $user->email,
            'userId' => $user->id
        ];

        return [
            'success' => true,
            'message' => 'Sign In Successfully',
            'data' => $data
        ];
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
