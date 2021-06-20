<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    UserController as User,
    AuthController as Auth,
    GoogleController as Google,
    FacebookController as Facebook,
    UserPositionController as UserPosition
};

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/user', [User::class, 'store']);

Route::group(['prefix' => 'auth'], function ($router) {
    $router->post('/login', [Auth::class, 'login']);
    $router->get('/confirmation/{id}', [Auth::class, 'confirmation']);

    $router->get('/google', [Google::class, 'redirectTo']);
    $router->get('/google/callback', [Google::class, 'handleCallback']);

    $router->get('/facebook', [Facebook::class, 'redirectTo']);
    $router->get('/facebook/callback', [Facebook::class, 'handleCallback']);
});

Route::group(['middleware' => 'auth:api'], function ($router) {
    $router->get('/auth/logout', [Auth::class, 'logout']);

    $router->group(['prefix' => 'user'], function ($router) {
        $router->put('/update', [User::class, 'update']);
        $router->get('/', [User::class, 'show']);
    });

    $router->group(['prefix' => 'user-position'], function ($router) {
        $router->get('/', [UserPosition::class, 'index']);
        $router->post('/', [UserPosition::class, 'store']);
        $router->get('/{id}', [UserPosition::class, 'show']);
        $router->put('/{id}', [UserPosition::class, 'update']);
        $router->delete('/{id}', [UserPosition::class, 'destroy']);
    });
});
