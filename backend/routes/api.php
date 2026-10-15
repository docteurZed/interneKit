<?php

use App\Http\Controllers\Admin\AffectationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GardeController;
use App\Http\Controllers\Admin\HopitalController;
use App\Http\Controllers\Admin\ImportExcelController;
use App\Http\Controllers\Admin\InterneController;
use App\Http\Controllers\Admin\JourFerieController;
use App\Http\Controllers\Admin\PeriodeRotationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TypeGardeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Public\GardeController as PublicGardeController;
use App\Http\Controllers\Public\InterneController as PublicInterneController;
use App\Http\Resources\Admin\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/public/gardes', [PublicGardeController::class, 'index']);
Route::get('/public/internes', [PublicInterneController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/imports/excel/template', [ImportExcelController::class, 'template']);
    Route::post('/imports/excel', [ImportExcelController::class, 'store']);

    Route::prefix('profile')->group(function () {
        Route::put('/{user}/infos', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    Route::prefix('internes')->group(function () {
        Route::get('/', [InterneController::class, 'index']);
        Route::get('/{interne}', [InterneController::class, 'show']);
        Route::get('/{interne}/current-service', [InterneController::class, 'currentService']);
        Route::get('/{interne}/gardes', [GardeController::class, 'planningForInterne']);
        Route::post('/', [InterneController::class, 'store']);
        Route::put('/{interne}', [InterneController::class, 'update']);
        Route::delete('/{interne}', [InterneController::class, 'destroy']);
    });

    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{service}', [ServiceController::class, 'show']);
        Route::get('/{service}/internes', [ServiceController::class, 'internesAtDate']);
        Route::get('/{service}/gardes', [GardeController::class, 'betweenDatesForService']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{service}', [ServiceController::class, 'update']);
        Route::delete('/{service}', [ServiceController::class, 'destroy']);
    });

    Route::prefix('hopitaux')->group(function () {
        Route::get('/', [HopitalController::class, 'index']);
        Route::get('/{hopital}', [HopitalController::class, 'show']);
        Route::post('/', [HopitalController::class, 'store']);
        Route::put('/{hopital}', [HopitalController::class, 'update']);
        Route::delete('/{hopital}', [HopitalController::class, 'destroy']);
    });

    Route::prefix('periodes-rotation')->group(function () {
        Route::get('/', [PeriodeRotationController::class, 'index']);
        Route::get('/{periodeRotation}', [PeriodeRotationController::class, 'show']);
        Route::get('/{periodeRotation}/affectations', [PeriodeRotationController::class, 'affectations']);
        Route::post('/', [PeriodeRotationController::class, 'store']);
        Route::put('/{periodeRotation}', [PeriodeRotationController::class, 'update']);
        Route::delete('/{periodeRotation}', [PeriodeRotationController::class, 'destroy']);
    });

    Route::prefix('affectations')->group(function () {
        Route::get('/', [AffectationController::class, 'index']);
        Route::get('/{affectation}', [AffectationController::class, 'show']);
        Route::post('/', [AffectationController::class, 'store']);
        Route::put('/{affectation}', [AffectationController::class, 'update']);
        Route::delete('/{affectation}', [AffectationController::class, 'destroy']);
    });

    Route::prefix('types-garde')->group(function () {
        Route::get('/', [TypeGardeController::class, 'index']);
        Route::get('/{typeGarde}', [TypeGardeController::class, 'show']);
        Route::post('/', [TypeGardeController::class, 'store']);
        Route::put('/{typeGarde}', [TypeGardeController::class, 'update']);
        Route::delete('/{typeGarde}', [TypeGardeController::class, 'destroy']);
    });

    Route::prefix('jours-feries')->group(function () {
        Route::get('/', [JourFerieController::class, 'index']);
        Route::get('/{jourFerie}', [JourFerieController::class, 'show']);
        Route::post('/', [JourFerieController::class, 'store']);
        Route::put('/{jourFerie}', [JourFerieController::class, 'update']);
        Route::delete('/{jourFerie}', [JourFerieController::class, 'destroy']);
    });

    Route::prefix('gardes')->group(function () {
        Route::get('/', [GardeController::class, 'index']);
        Route::get('/{garde}', [GardeController::class, 'show']);
        Route::post('/', [GardeController::class, 'store']);
        Route::put('/{garde}', [GardeController::class, 'update']);
        Route::delete('/{garde}', [GardeController::class, 'destroy']);
        Route::post('/{garde}/participants', [GardeController::class, 'addParticipants']);
        Route::delete('/{garde}/participants/{interne}', [GardeController::class, 'removeParticipant']);
    });
});
