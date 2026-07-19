<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacultyTrendController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\FacultyRosterController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ModelingController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/trends', [FacultyTrendController::class, 'index']);
Route::redirect('/peers', '/trends');
Route::get('/scenario', [ScenarioController::class, 'index']);
Route::get('/faculty', [FacultyRosterController::class, 'index']);
Route::get('/modeling', [ModelingController::class, 'index'])->name('modeling.index');
Route::get('/imports', [ImportController::class, 'index']);
Route::post('/imports/faculty-summary', [ImportController::class, 'importFacultySummary']);
Route::post('/imports/faculty-trends', [ImportController::class, 'importFacultyTrends']);
Route::post('/imports/similarity-rankings', [ImportController::class, 'importSimilarityRankings']);
Route::post('/imports/trajectory-similarities', [ImportController::class, 'importTrajectorySimilarities']);
Route::post('/imports/forecasting', [ImportController::class, 'importForecastingOutputs']);
Route::post('/imports/institutional-rankings', [ImportController::class, 'importInstitutionalRankings']);
Route::post('/imports/all', [ImportController::class, 'importAll']);
