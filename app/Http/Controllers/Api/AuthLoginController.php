<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;

use DB;
use Hash;
use Mail;
use Crypt;
use Session;

use App\User;
use Illuminate\Support\Str;

class AuthLoginController extends Controller
{
    use GeneralTrait;
    public function LoginUser(request $request)
    {
        
        $rules = [
            "phone" => "required",
            "password" => "required",
            "device_token" => "required"
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        // $messages = [
        //     'phone.required'=>' رقم الهاتف مطلوب ويجب ان يكون ارقام فقط ',  
        //     'phone.regex'=>' يجب أن يكون ارقام فقط ',
        //     'password' => 'صخقهبتخهقتبق',
        // ];
        // $validator = Validator::make($request->all(), [
        //     'phone' => 'required|regex:/[0-9]/',
        //     'password'=>'required',
        // ], $messages);
        // if($validator->fails()) {
        //     return response()->json([$validator->errors(), 401]);
        // }
           
        $user = User::where("phone" ,$request->phone)->first();
        if(!$user) {
            return $this -> returnError('','رقم الهاتف غير صحيح');
        }else{
            $request->merge(['email' => $user->email]);
            $credentials = $request->only(['email','password']);
            $token =  Auth::guard('user-api') -> attempt($credentials);
            if(!$token)
                return $this -> returnError('','كلمة المرور غير صحيحة');
            // $user = User::where("email" , $request->email)->first();
            $user->token=$token;
            $user->device_token=$request->device_token;
            $user->save();
            
        }
        return response()->json(['success' => 'تم تسجيل الدخول بنجاح','data' =>$user]); 
    }

    
    public function registerNewUser(Request $request)
    {
        $rules = [
            "name" => "required",
            "phone" => "required",
            "password" => "required",
            
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
      
        $checkemail = User::where("phone" , $request->phone)->first();
        if($checkemail){
            return $this -> returnError('','رقم الهاتف موجود مسبقا');
        }else{
            $add = new User();
            $add->name  = $request->name;
            $add->phone  = $request->phone;
            $add->email  = $request->name.''.$request->phone.'@araqi.com';
            $add->password = Hash::make($request->password);
            $add->save();
        }

        $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://whatsapp-otp-verification.p.rapidapi.com/auth/client-request-otp",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "phone=". $request->phone ."&country=EG&message=Your%20OTP%3A%20*%7Bcode%7D*%20-%20You%20have%20*5%20minutes*%20to%20enter%20this%20code",
                CURLOPT_HTTPHEADER => [
                    "X-RapidAPI-Host: whatsapp-otp-verification.p.rapidapi.com",
                    "X-RapidAPI-Key: 7e840e8ec9mshe876dabeed464d0p1d8bccjsneb6d367c8e0d",
                    "content-type: application/x-www-form-urlencoded"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $to_obj = json_decode($response);
            }

        // return response()->json(['success' => 'تم إرسال كود التفعيل','data' =>$add]); 
        return response()->json(['success' => 'تم إرسال كود التفعيل','data' =>$add,'code_response'=>$to_obj]); 

    }
    public function verifyRegisterCode(Request $request)
    {
        $rules = [
            "phone" => "required",
            "verify_code" => "required",
            "device_token" => "required",
            "results" => "required"
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }


        // $messages = [
        //     'verify_code.required'=>'كود التفعيل مطلوب',   
        // ];
        // $validator = Validator::make($request->all(), [
        //     'verify_code' => 'required',
        // ], $messages);
        // if ($validator->fails()) {
        //     return response()->json(['error' => 'كود التفعيل مطلوب', 401]);
        // }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://whatsapp-otp-verification.p.rapidapi.com/auth/client-verify-otp",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "requestId=".$request->results."&otp=". $request->verify_code,
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: whatsapp-otp-verification.p.rapidapi.com",
                "X-RapidAPI-Key: 7e840e8ec9mshe876dabeed464d0p1d8bccjsneb6d367c8e0d",
                "content-type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $to_obj = json_decode($response);
        }

       
        $user = User::where("phone" ,$request->phone)->first();
        if(!$user) {
            return $this -> returnError('','رقم الهاتف غير صحيح');
        }else{
            $user = User::where("phone" , $request->phone)->first();
            $token = Str::random(60).''.Str::random(60);
            $user->token=$token;
            $user->device_token=$request->device_token;
            $user->save();
            Auth::login($user);
        } 
        return response()->json(['success' => 'تم التسجيل  بنجاح','data' =>$user,'code_response'=>$to_obj]); 
    }
    public function forgetPassword(Request $request)
    {
        $rules = [
            'phone' => 'required|regex:/[0-9]/'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }else {
            try {
                $user= User::where("phone" ,$request->phone)->first();
                if(!$user){
                    return $this -> returnError('phone was not found');
                }else{
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => "https://whatsapp-otp-verification.p.rapidapi.com/auth/client-request-otp",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => "phone=". $request->phone ."&country=EG&message=Your%20OTP%3A%20*%7Bcode%7D*%20-%20You%20have%20*5%20minutes*%20to%20enter%20this%20code",
                        CURLOPT_HTTPHEADER => [
                            "X-RapidAPI-Host: whatsapp-otp-verification.p.rapidapi.com",
                            "X-RapidAPI-Key: 7e840e8ec9mshe876dabeed464d0p1d8bccjsneb6d367c8e0d",
                            "content-type: application/x-www-form-urlencoded"
                        ],
                    ]);
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                    if ($err) {
                        echo "cURL Error #:" . $err;
                    } else {
                        $to_obj = json_decode($response);
                    }
                   
                     return response()->json(['success' => 'تم التسجيل  بنجاح','code_response'=>$to_obj]); 
                }
            } catch (\Swift_TransportException $ex) {
                   $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
            } catch (Exception $ex) {
                   $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
            }
        }
        // return \Response::json('doneeeee');
    }
    public function verifyPassword(Request $request)
    {
        $rules = [
            "phone" => "required",
            "verify_code" => "required",
            "results" => "required"
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://whatsapp-otp-verification.p.rapidapi.com/auth/client-verify-otp",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "requestId=".$request->results."&otp=". $request->verify_code,
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: whatsapp-otp-verification.p.rapidapi.com",
                "X-RapidAPI-Key: 7e840e8ec9mshe876dabeed464d0p1d8bccjsneb6d367c8e0d",
                "content-type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $to_obj = json_decode($response);
        }

       
        return response()->json(['success' => 'تم التسجيل  بنجاح','code_response'=>$to_obj]); 
    }
    public function resetUserPasswordPost(Request $request)
    {
        // $messages = [
        //      'password.required'=>'New password',
        //           'password.min'=>'No less than three letters and numbers',
        //           'password_confirmation.required'=>' Confirm the password',  
        // ];
        // $validator = Validator::make($request->all(), [
        //     'password' => 'required|string|min:3|confirmed',
        //           'password_confirmation' => 'required'
        // ], $messages);
        // if ($validator->fails()) {
        //     return response()->json(['error' => 'كود التفعيل مطلوب', 401]);
        // }

        $rules = [
            "phone" => "required",
            'password' => 'required|string|min:3|confirmed',
            'password_confirmation' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }
        $user = User::where('phone', $request->phone)->first();
        if(!$user){
                    return $this -> returnError('phone was not found');
        $user->password  = bcrypt($request->password);
        $user-> save();
        return response()->json(['success' => 'تم إنشاء كلمة مرور جديدة']); 

    }
    
}
