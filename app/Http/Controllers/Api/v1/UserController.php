<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ClientAnswers;
use App\Models\QuestionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Image;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

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
                'profile_image' => ['required', 'mimes:jpeg,jpg,png,gif', 'max:10000'],
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
                    User::where('id', $user['id'])->update(['profile_pic' => $filename]);
                }
                return response()->json([
                    'status' => 1,
                    'message' => 'User successfully registered',
                    'data' => $user['id'],
                ]);
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => "Something Went Wrong.",
                'data' => "",
            ]);
        }
    }

    public function login(Request $request)
    {
        if (!empty($request->all())) {
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

            if (!Auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
                return response()->json([
                    'status' => 0,
                    'message' => "Invalid Credentials",
                    'data' => [],
                ]);
            } else {
                $checkUser = User::where('id', Auth::user()->id)->first();
                $checkUser['profile_pic'] = asset('uploads/profile_pictures/' . Auth::user()->id) . '/' . $checkUser['profile_pic'];
                $checkUser['accessToken'] = Auth()->user()->createToken('authToken')->accessToken;
                return response()->json([
                    'status' => 1,
                    'message' => "You have successfully logged in.",
                    'data' => $checkUser,
                ]);
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => "Something Went Wrong.",
                'data' => "",
            ]);
        }
    }

    public function questionList(Request $request)
    {
        $data = QuestionType::with(['question:id,questions,question_type', 'question.options:id,answers,question_id'])->select('id', 'type')->get();
        return response()->json([
            'status' => 1,
            'message' => "question fetch sucessfully",
            'data' => replace_null_with_empty_string($data),
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user()->token();
        $tokens = $user->tokens->pluck('id');
        Token::whereIn('id', $tokens)
            ->update(['revoked' => true]);

        RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
        return response()->json([
            'status' => 1,
            'message' => "Logout Successfully.",
            'data' => "",
        ]);
    }

    public function questionList1(Request $request)
    {
        if (!empty($request->all())) {
            $input = $request->all();
            $rules = [
                'question1' => ['required'],
                'question2' => ['required'],
                'question3' => ['required'],
                'question4' => ['required'],
                'question5' => ['required'],
                'question6' => ['required'],
                'question7' => ['required'],
                'question8' => ['required']
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    'data' => [],
                ]);
            } else {
                $data = $this->checkArray($request->all(), 1);
                ClientAnswers::insert($data);
                return response()->json([
                    'status' => 1,
                    'message' => "Thanks.",
                    'data' => "",
                ]);
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => "Something Went Wrong.",
                'data' => "",
            ]);
        }
    }

    public function checkArray($array = [], $check)
    {
        $finalData = [];
        if (!empty($array)) {
            if ($check == 1) {
                foreach ($array as $key => $value) {
                    if($key != "user_id") {
                        if ($key == "question8") {
                            $exp = json_decode(str_replace("'", '"', $value), true);
                            foreach ($exp as $exKey => $exValue) {
                                if ($exValue == 52) {
                                    $finalData[$exValue]['question_id'] = str_replace('question', '', $key);
                                    $finalData[$exValue]['answer_id'] = $exValue;
                                    $finalData[$exValue]['description'] = $exp[$exKey + 1];
                                    $finalData[$exValue]['type_id'] = 1;
                                    $finalData[$exValue]['user_id'] = $array['user_id'];
                                    break;
                                } else {
                                    $finalData[$exValue]['question_id'] = str_replace('question', '', $key);
                                    $finalData[$exValue]['answer_id'] = $exValue;
                                    $finalData[$exValue]['description'] = NULL;
                                    $finalData[$exValue]['type_id'] = 1;
                                    $finalData[$exValue]['user_id'] = $array['user_id'];
                                }
                            }
                        } else {
                            $exp = json_decode(str_replace("'", '"', $value), true);
                            $finalData[$exp[0]]['question_id'] = str_replace('question', '', $key) ?? NULL;
                            $finalData[$exp[0]]['answer_id'] = $exp[0] ?? NULL;
                            $finalData[$exp[0]]['description'] = $exp[1] ?? NULL;
                            $finalData[$exp[0]]['type_id'] = 1;
                            $finalData[$exp[0]]['user_id'] = $array['user_id'];
                        }
                    }
                }
                return $finalData;
            }
        }
    }
}
