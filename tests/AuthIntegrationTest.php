<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Auth;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_COOKIE = [];

        $this->pdo = new PDO('sqlite::memory:');

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->createSchema();
        $this->seedUsers();

        Auth::configure([
            'db' => $this->pdo,

            'user_table' => 'users',

            'id_field' => 'id',
            'name_field' => 'name',
            'login_field' => ['email'],
            'password_field' => 'password',
            'role_field' => 'role',

            'session_name' => 'auth_user',
            'session_lifetime' => 3600,

            'remember_enabled' => true,
            'remember_cookie' => 'remember_token',
            'remember_selector_field' => 'remember_selector',
            'remember_token_field' => 'remember_token',
            'remember_days' => 30,

            'rate_limit' => [
                'enabled' => true,
                'max_attempts' => 2,
                'decay_seconds' => 300,
                'key' => 'login',
            ],

            'providers' => [
                'table' => 'user_providers',
                'provider_field' => 'provider',
                'provider_id_field' => 'provider_id',
                'user_id_field' => 'user_id',
            ],

            'permissions' => [
                'admin' => ['*'],
                'patient' => [
                    'appointments.view',
                    'appointments.create',
                    'profile.*',
                ],
            ],


            'profiles' => [
                'patient' => [
                    'table' => 'patients',
                    'foreign_key' => 'user_id',
                ],
            ],
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = Auth::attempt('patient@example.com', 'secret');

        $this->assertNotFalse($user);
        $this->assertTrue(Auth::check());

        $this->assertSame(1, Auth::id());
        $this->assertSame('Patient User', Auth::user()?->name());
        $this->assertSame('patient', Auth::user()?->role());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = Auth::attempt('patient@example.com', 'wrong-password');

        $this->assertFalse($user);
        $this->assertFalse(Auth::check());
        $this->assertSame('Invalid credentials.', Auth::error());
    }

    public function test_user_can_login_with_provider(): void
    {
        $user = Auth::loginWithProvider('google', 'google-123');

        $this->assertNotFalse($user);
        $this->assertTrue(Auth::check());
        $this->assertSame(1, Auth::id());
        $this->assertSame('patient', Auth::user()?->role());
    }

    public function test_provider_login_fails_with_unknown_provider_identity(): void
    {
        $user = Auth::loginWithProvider('google', 'invalid-id');

        $this->assertFalse($user);
        $this->assertFalse(Auth::check());
        $this->assertSame('Provider login failed.', Auth::error());
    }

    public function test_provider_login_fails_with_unknown_provider(): void
    {
        $user = Auth::loginWithProvider('facebook', 'google-123');

        $this->assertFalse($user);
        $this->assertFalse(Auth::check());
    }

    public function test_user_can_logout(): void
    {
        Auth::attempt('patient@example.com', 'secret');

        $this->assertTrue(Auth::check());

        Auth::logout();

        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }

    public function test_user_has_role(): void
    {
        Auth::attempt('patient@example.com', 'secret');

        $this->assertTrue(Auth::hasRole('patient'));
        $this->assertFalse(Auth::hasRole('admin'));
    }

    public function test_user_has_permissions(): void
    {
        Auth::attempt('patient@example.com', 'secret');

        $this->assertTrue(Auth::can('appointments.view'));
        $this->assertTrue(Auth::can('profile.update'));
        $this->assertFalse(Auth::can('admin.access'));
    }

    public function test_admin_has_all_permissions(): void
    {
        Auth::attempt('admin@example.com', 'secret');

        $this->assertTrue(Auth::hasRole('admin'));
        $this->assertTrue(Auth::can('admin.access'));
        $this->assertTrue(Auth::can('anything.delete'));
    }

    public function test_profile_is_loaded_for_role(): void
    {
        Auth::attempt('patient@example.com', 'secret');

        $profile = Auth::profile();

        $this->assertNotNull($profile);
        $this->assertSame('Praia', $profile?->city);
        $this->assertSame('Praia', $profile['city']);
    }

    public function test_admin_has_no_profile_when_not_configured(): void
    {
        Auth::attempt('admin@example.com', 'secret');

        $this->assertNull(Auth::profile());
    }

    public function test_login_is_rate_limited_after_too_many_failures(): void
    {
        $this->assertFalse(Auth::attempt('patient@example.com', 'wrong'));
        $this->assertFalse(Auth::attempt('patient@example.com', 'wrong'));

        $this->assertFalse(Auth::attempt('patient@example.com', 'secret'));

        $this->assertStringContainsString(
            'Too many login attempts',
            Auth::error()
        );
    }

    public function test_diagnose_returns_successful_checks(): void
    {
        $diagnose = Auth::diagnose();

        $this->assertTrue($diagnose['pdo_connection']['ok']);
        $this->assertTrue($diagnose['user_table']['ok']);
        $this->assertTrue($diagnose['id_field']['ok']);
        $this->assertTrue($diagnose['login_fields']['ok']);
        $this->assertTrue($diagnose['login_field_email']['ok']);
        $this->assertTrue($diagnose['password_field']['ok']);
        $this->assertTrue($diagnose['remember_selector_field']['ok']);
        $this->assertTrue($diagnose['remember_token_field']['ok']);
    }


    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL,
                remember_selector TEXT NULL,
                remember_token TEXT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE user_providers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                provider TEXT NOT NULL,
                provider_id TEXT NOT NULL,
                UNIQUE(provider, provider_id)
            )
        ");

        $this->pdo->exec("
            CREATE TABLE patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                city TEXT NULL,
                phone TEXT NULL
            )
        ");
    }

    private function seedUsers(): void
    {
        $password = password_hash('secret', PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ");

        $stmt->execute([
            'name' => 'Patient User',
            'email' => 'patient@example.com',
            'password' => $password,
            'role' => 'patient',
        ]);

        $stmt->execute([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $password,
            'role' => 'admin',
        ]);

        $this->pdo->prepare("
            INSERT INTO user_providers (user_id, provider, provider_id)
            VALUES (:user_id, :provider, :provider_id)
        ")->execute([
            'user_id' => 1,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->pdo->prepare("
            INSERT INTO patients (user_id, city, phone)
            VALUES (:user_id, :city, :phone)
        ")->execute([
            'user_id' => 1,
            'city' => 'Praia',
            'phone' => '9999999',
        ]);
    }
}
