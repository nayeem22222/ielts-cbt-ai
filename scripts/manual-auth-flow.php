<?php

declare(strict_types=1);

/**
 * Manual auth flow verifier against a running `php artisan serve` instance.
 * Usage: php scripts/manual-auth-flow.php
 */

$baseUrl = getenv('APP_URL') ?: 'http://127.0.0.1:8000';
$cookieFile = tempnam(sys_get_temp_dir(), 'auth_flow_cookies_');

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$superAdminId = App\Models\User::query()->where('email', 'admin@example.com')->value('id');

function httpRequest(
    string $method,
    string $url,
    string $cookieFile,
    array $postFields = [],
    bool $followRedirects = false
): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_POSTFIELDS => $postFields !== [] ? http_build_query($postFields) : null,
    ]);

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status' => $status,
        'headers' => substr((string) $raw, 0, $headerSize),
        'body' => substr((string) $raw, $headerSize),
    ];
}

function extractCsrfToken(string $html): string
{
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $matches) !== 1) {
        throw new RuntimeException('CSRF token not found.');
    }

    return $matches[1];
}

function extractLocation(string $headers): ?string
{
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $matches) !== 1) {
        return null;
    }

    return trim($matches[1]);
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('FAIL: '.$message);
    }

    echo "PASS: {$message}\n";
}

echo "Manual auth flow verification against {$baseUrl}\n\n";

// 1. Student registration
$registerPage = httpRequest('GET', "{$baseUrl}/register", $cookieFile);
$registerToken = extractCsrfToken($registerPage['body']);
$studentEmail = 'manual-student-'.time().'@example.com';
$registerResponse = httpRequest('POST', "{$baseUrl}/register", $cookieFile, [
    '_token' => $registerToken,
    'name' => 'Manual Student',
    'email' => $studentEmail,
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$registerLocation = extractLocation($registerResponse['headers']);
assertTrue($registerResponse['status'] === 302, 'Registration returns redirect');
assertTrue(str_contains((string) $registerLocation, '/student/dashboard'), 'Registration redirects to student dashboard');

// 2. Student already logged in after registration - logout first
$logoutPage = httpRequest('GET', "{$baseUrl}/student/dashboard", $cookieFile);
$logoutToken = extractCsrfToken($logoutPage['body']);
httpRequest('POST', "{$baseUrl}/logout", $cookieFile, ['_token' => $logoutToken]);

// 2. Student login
$loginPage = httpRequest('GET', "{$baseUrl}/login", $cookieFile);
$loginToken = extractCsrfToken($loginPage['body']);
$studentLogin = httpRequest('POST', "{$baseUrl}/login", $cookieFile, [
    '_token' => $loginToken,
    'email' => $studentEmail,
    'password' => 'password123',
]);
$studentLoginLocation = extractLocation($studentLogin['headers']);
assertTrue($studentLogin['status'] === 302, 'Student login returns redirect');
assertTrue(str_contains((string) $studentLoginLocation, '/student/dashboard'), 'Student login redirects to student dashboard');

// 5. Student cannot access admin dashboard
$studentAdminAttempt = httpRequest('GET', "{$baseUrl}/admin/dashboard", $cookieFile);
assertTrue($studentAdminAttempt['status'] === 403, 'Student blocked from admin dashboard');

// Logout student
$dashPage = httpRequest('GET', "{$baseUrl}/student/dashboard", $cookieFile);
$dashToken = extractCsrfToken($dashPage['body']);
httpRequest('POST', "{$baseUrl}/logout", $cookieFile, ['_token' => $dashToken]);

// 3. Admin login
$adminLoginPage = httpRequest('GET', "{$baseUrl}/login", $cookieFile);
$adminLoginToken = extractCsrfToken($adminLoginPage['body']);
$adminLogin = httpRequest('POST', "{$baseUrl}/login", $cookieFile, [
    '_token' => $adminLoginToken,
    'email' => 'admin@example.com',
    'password' => 'password',
]);
$adminLoginLocation = extractLocation($adminLogin['headers']);
assertTrue($adminLogin['status'] === 302, 'Admin login returns redirect');
assertTrue(str_contains((string) $adminLoginLocation, '/admin/dashboard'), 'Admin login redirects to admin dashboard');

// 4. Role-based redirect verified via login responses above

// Create normal admin via super admin for further tests
$usersPage = httpRequest('GET', "{$baseUrl}/admin/users/create", $cookieFile);
$createToken = extractCsrfToken($usersPage['body']);
$normalAdminEmail = 'manual-admin-'.time().'@example.com';
httpRequest('POST', "{$baseUrl}/admin/users", $cookieFile, [
    '_token' => $createToken,
    'name' => 'Manual Admin',
    'email' => $normalAdminEmail,
    'role' => 'admin',
    'status' => 'active',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

// Logout super admin
$adminDash = httpRequest('GET', "{$baseUrl}/admin/dashboard", $cookieFile);
$adminDashToken = extractCsrfToken($adminDash['body']);
httpRequest('POST', "{$baseUrl}/logout", $cookieFile, ['_token' => $adminDashToken]);

// Login as normal admin
$normalAdminLoginPage = httpRequest('GET', "{$baseUrl}/login", $cookieFile);
$normalAdminLoginToken = extractCsrfToken($normalAdminLoginPage['body']);
httpRequest('POST', "{$baseUrl}/login", $cookieFile, [
    '_token' => $normalAdminLoginToken,
    'email' => $normalAdminEmail,
    'password' => 'password123',
]);

// 7-9. Admin CRUD - create student
$createStudentPage = httpRequest('GET', "{$baseUrl}/admin/users/create", $cookieFile);
$createStudentToken = extractCsrfToken($createStudentPage['body']);
$managedStudentEmail = 'managed-student-'.time().'@example.com';
$createStudent = httpRequest('POST', "{$baseUrl}/admin/users", $cookieFile, [
    '_token' => $createStudentToken,
    'name' => 'Managed Student',
    'email' => $managedStudentEmail,
    'role' => 'student',
    'status' => 'active',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
assertTrue($createStudent['status'] === 302, 'Admin can create student');

// 10. Admin cannot delete own account - need user id from users list
$usersList = httpRequest('GET', "{$baseUrl}/admin/users?search=".urlencode($normalAdminEmail), $cookieFile);
preg_match('/admin\/users\/(\d+)\/edit/', $usersList['body'], $idMatch);
assertTrue(isset($idMatch[1]), 'Found normal admin user id in listing');
$selfDelete = httpRequest('POST', "{$baseUrl}/admin/users/{$idMatch[1]}", $cookieFile, [
    '_token' => extractCsrfToken($usersList['body']),
    '_method' => 'DELETE',
]);
assertTrue($selfDelete['status'] === 403, 'Admin cannot delete own account');

// 11. Normal admin cannot manage super_admin
assertTrue($superAdminId !== null, 'Super admin exists in database');
$superEdit = httpRequest('GET', "{$baseUrl}/admin/users/{$superAdminId}/edit", $cookieFile);
assertTrue($superEdit['status'] === 403, 'Normal admin cannot edit super_admin');
$superDelete = httpRequest('POST', "{$baseUrl}/admin/users/{$superAdminId}", $cookieFile, [
    '_token' => extractCsrfToken(httpRequest('GET', "{$baseUrl}/admin/users", $cookieFile)['body']),
    '_method' => 'DELETE',
]);
assertTrue($superDelete['status'] === 403, 'Normal admin cannot delete super_admin');

// 6. Teacher cannot access admin - create teacher and test
$createTeacherPage = httpRequest('GET', "{$baseUrl}/admin/users/create", $cookieFile);
$teacherEmail = 'manual-teacher-'.time().'@example.com';
httpRequest('POST', "{$baseUrl}/admin/users", $cookieFile, [
    '_token' => extractCsrfToken($createTeacherPage['body']),
    'name' => 'Manual Teacher',
    'email' => $teacherEmail,
    'role' => 'teacher',
    'status' => 'active',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

$logoutAdminDash = httpRequest('GET', "{$baseUrl}/admin/dashboard", $cookieFile);
httpRequest('POST', "{$baseUrl}/logout", $cookieFile, ['_token' => extractCsrfToken($logoutAdminDash['body'])]);

$teacherLoginPage = httpRequest('GET', "{$baseUrl}/login", $cookieFile);
httpRequest('POST', "{$baseUrl}/login", $cookieFile, [
    '_token' => extractCsrfToken($teacherLoginPage['body']),
    'email' => $teacherEmail,
    'password' => 'password123',
]);
$teacherAdminAttempt = httpRequest('GET', "{$baseUrl}/admin/dashboard", $cookieFile);
assertTrue($teacherAdminAttempt['status'] === 403, 'Teacher blocked from admin dashboard');

@unlink($cookieFile);

echo "\nAll manual auth flow checks passed.\n";
