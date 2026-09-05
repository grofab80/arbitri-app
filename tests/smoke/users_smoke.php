<?php

require __DIR__ . '/../../api/v1/bootstrap.php';

use Api\Core\Database;
use Api\Models\Permission;
use Api\Models\User;
use Api\Validators\UserValidator;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function pass(string $message): void
{
    echo "[PASS] {$message}" . PHP_EOL;
}

$db = Database::get();
$db->beginTransaction();

try {
    $profiles = Permission::profiles();
    assertTrue(!empty($profiles), 'Nessun profilo disponibile');

    $profileId = (int)($profiles[0]['id'] ?? 0);
    assertTrue($profileId > 0, 'Profilo non valido');

    $username = 'smoke_user_' . date('YmdHis');
    $email = $username . '@example.test';

    $createPayload = [
        'username' => $username,
        'first_name' => 'Smoke',
        'last_name' => 'User',
        'email' => $email,
        'profile_id' => $profileId,
        'password' => 'test123!'
    ];

    $errors = UserValidator::save($createPayload);
    assertTrue(empty($errors), 'Validazione creazione non riuscita: ' . json_encode($errors));

    $userId = User::create($createPayload);
    assertTrue($userId > 0, 'Creazione utente non riuscita');

    $created = User::findById($userId);
    assertTrue($created !== null, 'Utente creato non trovato');
    assertTrue($created['username'] === $username, 'Username creato non corretto');
    assertTrue($created['first_name'] === 'Smoke', 'Nome creato non corretto');
    assertTrue($created['last_name'] === 'User', 'Cognome creato non corretto');
    assertTrue($created['email'] === $email, 'Email creata non corretta');
    assertTrue(password_verify('test123!', $created['password']), 'Password creata non valida');
    pass('creazione utente');

    $updatePayload = [
        'username' => $username,
        'first_name' => 'Smoke Updated',
        'last_name' => 'User Updated',
        'email' => 'updated_' . $email,
        'profile_id' => $profileId,
        'password' => ''
    ];

    $errors = UserValidator::save($updatePayload, $userId);
    assertTrue(empty($errors), 'Validazione modifica non riuscita: ' . json_encode($errors));
    assertTrue(User::update($userId, $updatePayload), 'Modifica utente non riuscita');

    $updated = User::findById($userId);
    assertTrue($updated['first_name'] === 'Smoke Updated', 'Nome modificato non corretto');
    assertTrue($updated['last_name'] === 'User Updated', 'Cognome modificato non corretto');
    assertTrue($updated['email'] === 'updated_' . $email, 'Email modificata non corretta');
    assertTrue(password_verify('test123!', $updated['password']), 'Password cambiata quando doveva restare invariata');
    pass('modifica utente senza cambio password');

    $passwordPayload = $updatePayload;
    $passwordPayload['password'] = 'nuova123!';
    assertTrue(User::update($userId, $passwordPayload), 'Cambio password utente non riuscito');

    $passwordUpdated = User::findById($userId);
    assertTrue(password_verify('nuova123!', $passwordUpdated['password']), 'Nuova password non valida');
    pass('modifica password utente');

    $duplicatePayload = $createPayload;
    $duplicatePayload['email'] = 'duplicate_' . $email;
    $errors = UserValidator::save($duplicatePayload);
    assertTrue(isset($errors['username']), 'Username duplicato non intercettato');
    pass('validazione username duplicato');

    $db->rollBack();
    echo "[OK] Users smoke test completed with rollback" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "[FAIL] " . $e->getMessage() . PHP_EOL);
    exit(1);
}
