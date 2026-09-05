<?php

declare(strict_types=1);

use Api\Core\Database;
use Api\Models\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$username = trim((string)getenv('ADMIN_USERNAME'));
$password = (string)getenv('ADMIN_PASSWORD');
$firstName = trim((string)getenv('ADMIN_FIRST_NAME'));
$lastName = trim((string)getenv('ADMIN_LAST_NAME'));
$email = trim((string)getenv('ADMIN_EMAIL'));

if ($username === '' || $password === '') {
    fwrite(STDERR, "Impostare ADMIN_USERNAME e ADMIN_PASSWORD.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "ADMIN_PASSWORD deve contenere almeno 12 caratteri.\n");
    exit(1);
}

$pdo = Database::get();
$profileId = $pdo->query(
    "SELECT id FROM profiles WHERE code = 'admin' LIMIT 1"
)->fetchColumn();

if (!$profileId) {
    fwrite(STDERR, "Profilo admin assente: importare prima i dati di riferimento.\n");
    exit(1);
}

if (User::usernameExists($username)) {
    fwrite(STDERR, "Username gia presente.\n");
    exit(1);
}

$userId = User::create([
    'username' => $username,
    'password' => $password,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'profile_id' => (int)$profileId
]);

fwrite(STDOUT, "Utente amministratore creato con ID {$userId}.\n");
