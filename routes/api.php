<?php

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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::group(['as' => 'auth.', 'prefix' => 'v1/data', 'middleware' => ['apilog']], function() {
  Route::get('countrys', 'DataController@countrys');
  Route::get('langs', 'DataController@langs');
  Route::get('category', 'DataController@category');
  Route::post('sendsupport', 'UserController@sendsupport');
  Route::get('get_author/{id}', 'UserController@get_author');
});

Route::group(['as' => 'auth.', 'prefix' => 'v1/auth', 'middleware' => ['apilog']], function() {
  Route::post('check', 'AuthController@check');
  Route::post('login', 'AuthController@login');

});

Route::group(['as' => 'app.', 'prefix' => 'v1/user', 'middleware' => ['auth:sanctum', 'apilog']], function() {

  Route::get('get', 'UserController@get');
  Route::post('save', 'UserController@save');
  Route::post('set_device_token', 'UserController@set_device_token');
  Route::post('is_authors', 'UserController@set_authors');
  Route::get('is_authors', 'UserController@get_authors');

});
Route::group(['as' => 'app.', 'prefix' => 'v1/course', 'middleware' => ['auth:sanctum', 'apilog']], function() {

  Route::get('/', 'CoursesController@index');
  Route::post('/delete', 'CoursesController@delete');
  Route::post('/step/{id}', 'CoursesController@steps');
  Route::post('/deletecard', 'CoursesController@deletecard');
  Route::get('/card/{id}', 'CoursesController@getcard');
  Route::get('/{id}', 'CoursesController@getone');

});
Route::group(['as' => 'app.front', 'prefix' => 'v1/front', 'middleware' => [/*'auth:sanctum',*/ 'apilog']], function() {

  Route::get('/', 'FrontCourseController@index');
  Route::get('/views', 'FrontCourseController@views');
  Route::get('/{id}', 'FrontCourseController@show');


});
Route::group(['as' => 'app.front', 'prefix' => 'v1/study', 'middleware' => ['auth:sanctum', 'apilog']], function() {

  Route::post('/start', 'FrontCourseController@start');
  Route::post('/learn', 'FrontCourseController@learn');
  Route::post('/rating', 'FrontCourseController@rating');
  Route::post('/like', 'FrontCourseController@like');

  Route::get('/courses', 'FrontCourseController@courses');


});





