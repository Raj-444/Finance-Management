<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Core\I18n;
use App\Models\User;
use App\Models\AppSetting;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        return View::render('auth.login', [
            'title' => I18n::get('login'),
        ], 'auth');
    }

    public function login(Request $request): Response
    {
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');
        $ip = $request->ip();

        if (empty($email) || empty($password)) {
            Session::setFlash('error', I18n::get('invalid_credentials'));
            return Response::redirect(url('/login'));
        }

        $result = Auth::attempt($email, $password, $ip);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            return Response::redirect(url('/login'));
        }

        Session::setFlash('success', I18n::get('login_successful'));
        return Response::redirect(url('/dashboard'));
    }

    public function showRegister(Request $request): Response
    {
        if (!AppSetting::isRegistrationOpen()) {
            Session::setFlash('warning', I18n::get('registration_disabled'));
            return Response::redirect(url('/login'));
        }

        return View::render('auth.register', [
            'title' => I18n::get('register'),
        ], 'auth');
    }

    public function register(Request $request): Response
    {
        if (!AppSetting::isRegistrationOpen()) {
            Session::setFlash('error', I18n::get('registration_disabled'));
            return Response::redirect(url('/login'));
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');
        $passwordConfirmation = (string)$request->input('password_confirmation', '');

        if (empty($name) || empty($email) || empty($password)) {
            Session::setFlash('error', 'All fields are required.');
            return Response::redirect(url('/register'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Please enter a valid email address.');
            return Response::redirect(url('/register'));
        }

        if (strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            return Response::redirect(url('/register'));
        }

        if ($password !== $passwordConfirmation) {
            Session::setFlash('error', 'Password confirmation does not match.');
            return Response::redirect(url('/register'));
        }

        // Check duplicate email
        $existing = User::findByEmail($email);
        if ($existing) {
            Session::setFlash('error', 'An account with this email address already exists.');
            return Response::redirect(url('/register'));
        }

        try {
            $userId = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
            ]);

            Session::setFlash('success', I18n::get('account_created'));
            return Response::redirect(url('/login'));
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Registration failed. Please try again.');
            return Response::redirect(url('/register'));
        }
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Session::setFlash('info', 'You have been logged out.');
        return Response::redirect(url('/login'));
    }

    public function showForgotPassword(Request $request): Response
    {
        return View::render('auth.forgot-password', [
            'title' => I18n::get('forgot_password'),
        ], 'auth');
    }

    public function sendResetLink(Request $request): Response
    {
        $email = trim(strtolower((string)$request->input('email', '')));
        if (!empty($email)) {
            Auth::sendResetLink($email);
        }

        Session::setFlash('success', I18n::get('password_reset_sent'));
        return Response::redirect(url('/forgot-password'));
    }

    public function showResetPassword(Request $request): Response
    {
        $token = (string)$request->input('token', '');
        return View::render('auth.reset-password', [
            'title' => I18n::get('reset_password'),
            'token' => $token,
        ], 'auth');
    }

    public function resetPassword(Request $request): Response
    {
        $token = (string)$request->input('token', '');
        $password = (string)$request->input('password', '');
        $passwordConfirmation = (string)$request->input('password_confirmation', '');

        if ($password !== $passwordConfirmation) {
            Session::setFlash('error', 'Password confirmation does not match.');
            return Response::redirect(url('/reset-password?token=' . urlencode($token)));
        }

        $result = Auth::resetPassword($token, $password);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            return Response::redirect(url('/reset-password?token=' . urlencode($token)));
        }

        Session::setFlash('success', I18n::get('password_reset_success'));
        return Response::redirect(url('/login'));
    }
}
