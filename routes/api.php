<?php

use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('questions', [UserController::class, 'questionList'])->name('user.questionList');
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login'])->name('user.api.login');
Route::post('logout', [UserController::class, 'logout'])->name('user.logout');
Route::post('questions-list1',[UserController::class, 'questionList1'])->name('user.backgroudInfoQuestion');
Route::group(['prefix' => 'coach','middleware'=>['auth:api', 'user-access:1','coach']],function () {

});
Route::group(['prefix' => 'client','middleware'=>['auth:api', 'user-access:2','client']],function () {


});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

