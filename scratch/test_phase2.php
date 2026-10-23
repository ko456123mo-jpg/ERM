<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Application;
use App\Models\Analysis;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

echo "====================================================\n";
echo "       ERM PHASE 2 FULL VERIFICATION TEST           \n";
echo "====================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $name\n";
        $passCount++;
    } else {
        echo " [FAIL] $name : $details\n";
        $failCount++;
    }
}

// 1. Check Database Models & Seeds
assertTest("Settings Table has records", Setting::count() >= 5, "Count: " . Setting::count());
assertTest("Users exist", User::count() > 0, "Count: " . User::count());
assertTest("Roles exist", Role::count() >= 5, "Count: " . Role::count());
assertTest("Permissions exist", Permission::count() >= 29, "Count: " . Permission::count());

// 2. Test Super Admin Access to All APIs
$superAdmin = User::whereHas('role', fn($q) => $q->where('name', 'Super Admin'))->first();
assertTest("Super Admin exists", $superAdmin !== null);

Auth::login($superAdmin);

// Test Dashboard API
$dashController = app(\App\Http\Controllers\DashboardController::class);
$dashStats = $dashController->stats(Request::create('/api/dashboard', 'GET'))->getData(true);
assertTest("Dashboard Stats API returns real DB stats", isset($dashStats['total_users']) && isset($dashStats['total_applications']) && isset($dashStats['engine_health']), json_encode($dashStats));

// Test Settings API
$settingsController = app(\App\Http\Controllers\SettingsController::class);
$settingsData = $settingsController->index(Request::create('/api/settings', 'GET'))->getData(true);
assertTest("Settings Index API returns settings", isset($settingsData['platform_name']), json_encode($settingsData));

$updateReq = Request::create('/api/settings', 'POST', ['platform_name' => 'ERM Cyber Platform']);
$settingsUpdate = $settingsController->update($updateReq)->getData(true);
assertTest("Settings Update API works", isset($settingsUpdate['updated_keys']), json_encode($settingsUpdate));

// Test Reports API
$reportController = app(\App\Http\Controllers\ReportController::class);
$reportsData = $reportController->index(Request::create('/api/reports', 'GET'))->getData(true);
assertTest("Reports Index API works", is_array($reportsData));

// Test Tools API
$toolController = app(\App\Http\Controllers\ToolController::class);
$toolsData = $toolController->status(Request::create('/api/tools/status', 'GET'))->getData(true);
assertTest("Tools Status API works", isset($toolsData['tools']) && count($toolsData['tools']) === 3);

// Test Profile API
$profileController = app(\App\Http\Controllers\ProfileController::class);
$profReq = Request::create('/api/profile', 'GET');
$profReq->setUserResolver(fn() => $superAdmin);
$profileData = $profileController->show($profReq)->getData(true);
assertTest("Profile Show API works", $profileData['username'] === $superAdmin->username);

// 3. Test RBAC Enforcement on Viewer Role
$viewerUser = User::whereHas('role', fn($q) => $q->where('name', 'Viewer'))->first();
if ($viewerUser) {
    Auth::login($viewerUser);
    
    // Viewer should have settings.view = false
    $canEditSettings = $viewerUser->hasPermission('settings.edit');
    assertTest("Viewer cannot edit settings (RBAC check)", $canEditSettings === false);
    
    $canDeleteUsers = $viewerUser->hasPermission('users.delete');
    assertTest("Viewer cannot delete users (RBAC check)", $canDeleteUsers === false);

    $canViewReports = $viewerUser->hasPermission('reports.view');
    assertTest("Viewer can view reports (RBAC check)", $canViewReports === true);
} else {
    echo " [INFO] No Viewer user found to test role restrictions.\n";
}

echo "\n====================================================\n";
echo " TOTAL TESTS PASSED: $passCount | FAILED: $failCount\n";
echo "====================================================\n";
