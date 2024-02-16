<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required | string',
            'lastname' => 'required | string',
            'email' => 'email | required | string | unique:users',
            'mobile' => 'required | string',
            'password' => 'required | string | min:8',
            'image' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        } else {


            $s3 = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);


            $image = $request->file('image');
            $filename =$request->firstname.'-'.$request->lastname. time() . '.' . $image->getClientOriginalExtension();
            try {
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => 'Mmagic/' .  $filename, // Adjust the S3 key as needed
                    'Body' => fopen($image->getRealPath(), 'rb'),
                    'ACL' => 'public-read',
                ]);
                $path=$result['ObjectURL'];
                
            } catch (\Throwable $th) {
                Log::info($th);
                $path="";
            }
            $validatedData = [
                'first_name' => $request->firstname,
                'last_name' => $request->lastname,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'image' => $path
            ];
            $user=User::create($validatedData);
            if($user){
                return response()->json(["status" => true, "message" => "User Registered","user" => $validatedData], 201);
            }
            else{
                return response()->json(["status" => false, "message" => "Failed To Register"],422);

            }
        }
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email | required | string ',
            'password' => 'required | string'
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        else{
            if (Auth::attempt($request->all())) {
                $user = Auth::user();
                $token = JWTAuth::fromUser($user);
            
                return response()->json(['status'=>true,"message"=>"Logged In","token"=>$token]);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }
    public function getDetails(){
        $user = Auth::user();
        return response()->json(['status'=>true,"details"=>$user]);
    }
    public function logout()
    {
    }
    public function refreshToken(Request $request)
    {
    }
}
