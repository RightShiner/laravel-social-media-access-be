<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\QuestionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Image;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        if (!empty($request->all())) {
                $input = $request->all();
                $rules = [
                    'email' => ['required', 'email', 'max:50', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:6'],
                    'name' => ['required'],
                    'role' => ['required'],
                    'profile_image' =>['required','mimes:jpeg,jpg,png,gif', 'max:10000'],
                    'phone_no' => ['required'],
                ];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 0,
                        'message' => $validator->errors()->first(),
                        'data' => [],
                    ]);
                } else {
                    $user = User::create([
                        'name' => $input['name'],
                        'password' => bcrypt($input['password']),
                        'role_id' => $input['role'],
                        'email' => $input['email'],
                        'phone_no' => $input['phone_no']
                    ]);
                    if ($request->hasFile('profile_image')) {
                        $img = $request->file('profile_image');
                        $filename = time() . '.' . $img->getClientOriginalExtension();
                        $path = public_path('uploads/profile_pictures/' . $user['id']);
                        File::makeDirectory($path, $mode = 0777, true, true);

                        Image::make($img->getRealPath())->resize(300, 300)->save($path . '/' . $filename);
                        $user = User::where('id', $user['id'])->update(['profile_pic' => $filename]);
                    }
                    return response()->json([
                        'status' => 1,
                        'message' => 'User successfully registered',
                        'data' => "",
                    ]);
                }
            }
        }

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'data' => [],
            ]);
        }

        if (!Auth()->attempt(['email'=>$request->email,'password'=>$request->password])) {
            return response()->json([
                'status' => 0,
                'message' => "Invalid Credentials",
                'data' => [],
            ]);
        } else {
            $checkUser = User::where('id',Auth::user()->id)->first();
            $checkUser['profile_pic'] = asset('uploads/profile_pictures/' . Auth::user()->id).'/'.$checkUser['profile_pic'];
            $checkUser['accessToken'] = Auth()->user()->createToken('authToken')->accessToken;
            return response()->json([
                'status' => 1,
                'message' =>  "You have successfully logged in.",
                'data' =>$checkUser,
            ]);
        }
    }
    public function questionList(Request $request){
        $data = QuestionType::with(['question:id,questions,question_type','question.options:id,answers,question_id'])->select('id','type')->get();
        return response()->json([
            'status' => 1,
            'message' =>  "question fetch sucessfully",
            'data' =>replace_null_with_empty_string($data),
        ]);
    }
}
