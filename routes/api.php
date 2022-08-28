<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\notebookController;

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

Route::prefix('v1/notebook/')->group(function () {
    Route::post('/', 'App\Http\Controllers\notebookController@store')->name('notebook.store');
    Route::get('count/{count}/page/{page}', 'App\Http\Controllers\notebookController@index')->name('notebook.index');
    Route::get('id{id}', 'App\Http\Controllers\notebookController@show')->name('notebook.show');
    Route::post('id{id}', 'App\Http\Controllers\notebookController@update')->name('notebook.update');
    Route::delete('id{id}', 'App\Http\Controllers\notebookController@destroy')->name('notebook.destroy');
    Route::get('id{id}/getPhoto', 'App\Http\Controllers\notebookController@showPhoto')->name('notebook.showPhoto');
});