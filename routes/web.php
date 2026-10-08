<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\StudyPlanController;
use App\Http\Controllers\StudyTaskController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TextToSpeechController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Notas, carpetas y etiquetas
    Route::resource('notes', NoteController::class);
    Route::patch('notes/{note}/favorite', [NoteController::class, 'toggleFavorite'])->name('notes.favorite');
    Route::post('notes/{note}/summary', [NoteController::class, 'summarize'])->middleware('throttle:ai')->name('notes.summary');
    Route::post('notes/{note}/rescan', [NoteController::class, 'rescan'])->middleware('throttle:ai')->name('notes.rescan');
    Route::get('notes/{note}/file', [NoteController::class, 'download'])->name('notes.file');
    Route::resource('folders', FolderController::class)->only(['store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['store', 'destroy']);

    // Cuestionarios con IA y preguntas
    Route::resource('quizzes', QuizController::class);
    Route::post('quizzes/{quiz}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::put('questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    // Intentos: evaluación previa → cuestionario → resultados y evaluación posterior
    Route::get('quizzes/{quiz}/start', [QuizAttemptController::class, 'create'])->name('attempts.create');
    Route::post('quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store'])->name('attempts.store');
    Route::get('attempts', [QuizAttemptController::class, 'index'])->name('attempts.index');
    Route::get('attempts/{attempt}/take', [QuizAttemptController::class, 'take'])->name('attempts.take');
    Route::post('attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->name('attempts.submit');
    Route::get('attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('attempts.show');
    Route::patch('attempts/{attempt}/reflection', [QuizAttemptController::class, 'reflect'])->name('attempts.reflect');

    // Texto a voz
    Route::post('tts', TextToSpeechController::class)->middleware('throttle:ai')->name('tts');

    // Planificador de estudio
    Route::resource('study-plans', StudyPlanController::class)->except(['edit', 'update'])->parameters(['study-plans' => 'studyPlan']);
    Route::post('study-plans/{studyPlan}/regenerate', [StudyPlanController::class, 'regenerate'])->name('study-plans.regenerate');
    Route::patch('study-tasks/{studyTask}/toggle', [StudyTaskController::class, 'toggle'])->name('study-tasks.toggle');

    // Grupos de estudio
    Route::get('groups', [StudyGroupController::class, 'index'])->name('groups.index');
    Route::post('groups', [StudyGroupController::class, 'store'])->name('groups.store');
    Route::post('groups/join', [StudyGroupController::class, 'join'])->middleware('throttle:10,1')->name('groups.join');
    Route::get('groups/{group}', [StudyGroupController::class, 'show'])->name('groups.show');
    Route::put('groups/{group}', [StudyGroupController::class, 'update'])->name('groups.update');
    Route::delete('groups/{group}', [StudyGroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('groups/{group}/leave', [StudyGroupController::class, 'leave'])->name('groups.leave');
    Route::post('groups/{group}/code', [StudyGroupController::class, 'regenerateCode'])->name('groups.code');
    Route::delete('groups/{group}/members/{user}', [StudyGroupController::class, 'removeMember'])->name('groups.members.destroy');
    Route::post('groups/{group}/quizzes', [StudyGroupController::class, 'shareQuiz'])->name('groups.quizzes.store');
    Route::delete('groups/{group}/quizzes/{quiz}', [StudyGroupController::class, 'unshareQuiz'])->name('groups.quizzes.destroy');

    // Gamificación y analítica personal
    Route::get('leaderboard', LeaderboardController::class)->name('leaderboard');
    Route::get('achievements', AchievementController::class)->name('achievements.index');
    Route::get('analytics', AnalyticsController::class)->name('analytics');

    // Notificaciones
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('preferences', [PreferenceController::class, 'update'])->name('preferences.update');
    Route::patch('preferences/theme', [PreferenceController::class, 'theme'])->name('preferences.theme');

    Route::delete('sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.others');
    Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/logout', [Admin\UserController::class, 'forceLogout'])->name('users.logout');

    Route::get('quizzes', [Admin\QuizController::class, 'index'])->name('quizzes.index');
    Route::delete('quizzes/{quiz}', [Admin\QuizController::class, 'destroy'])->name('quizzes.destroy');

    Route::get('sessions', [Admin\SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions/{session}', [Admin\SessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('activity', [Admin\ActivityLogController::class, 'index'])->name('activity.index');

    Route::resource('announcements', Admin\AnnouncementController::class)->except(['show']);

    Route::get('backups', [Admin\BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [Admin\BackupController::class, 'store'])->name('backups.store');
    Route::post('backups/upload', [Admin\BackupController::class, 'upload'])->name('backups.upload');
    Route::get('backups/{backup}', [Admin\BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/{backup}/restore', [Admin\BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('backups/{backup}', [Admin\BackupController::class, 'destroy'])->name('backups.destroy');
});

require __DIR__.'/auth.php';
