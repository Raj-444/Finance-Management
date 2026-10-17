<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\I18n;
use App\Core\View;
use App\Models\User;
use App\Models\Account;
use App\Models\AppSetting;
use App\Services\ExcelService;
use App\Services\BackupService;
use App\Services\Mailer;

class SettingsController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = User::findById($userId);
        $accounts = Account::getAllForUser($userId);
        $importBatches = ExcelService::getBatches($userId);

        $smtpSettings = [
            'host' => AppSetting::get('smtp_host', ''),
            'port' => AppSetting::get('smtp_port', '587'),
            'username' => AppSetting::get('smtp_username', ''),
            'password' => AppSetting::get('smtp_password', ''),
            'encryption' => AppSetting::get('smtp_encryption', 'tls'),
            'from_address' => AppSetting::get('smtp_from_address', ''),
            'from_name' => AppSetting::get('smtp_from_name', 'Personal Finance Manager'),
        ];
        $allowRegistration = AppSetting::get('allow_registration', '1') === '1';

        return View::render('settings.index', [
            'title' => 'Settings & Preferences',
            'user' => $user,
            'accounts' => $accounts,
            'importBatches' => $importBatches,
            'smtp' => $smtpSettings,
            'allowRegistration' => $allowRegistration,
            'tab' => $request->query('tab', 'profile'),
        ], 'app');
    }

    public function updateProfile(Request $request): Response
    {
        $userId = (int) Auth::id();

        $name = trim((string)$request->input('name', ''));
        $currency = strtoupper(trim((string)$request->input('currency', 'BDT')));
        $locale = in_array($request->input('locale'), ['en', 'bn']) ? $request->input('locale') : 'en';
        $numberFormat = in_array($request->input('number_format'), ['indian', 'international']) ? $request->input('number_format') : 'indian';
        $monthStartDay = max(1, min(28, (int)$request->input('month_start_day', 1)));
        $weekStart = (int)$request->input('week_start', 6);
        $quietFrom = $request->input('quiet_from') ?: null;
        $quietTo = $request->input('quiet_to') ?: null;

        if (empty($name)) {
            Session::setFlash('error', 'Name cannot be empty.');
            return Response::redirect(url('/settings?tab=profile'));
        }

        try {
            $sql = "UPDATE users SET name = ?, currency = ?, locale = ?, number_format = ?, month_start_day = ?, week_start = ?, quiet_from = ?, quiet_to = ? WHERE id = ?";
            Database::query($sql, [
                $name, $currency, $locale, $numberFormat, $monthStartDay, $weekStart, $quietFrom, $quietTo, $userId
            ]);

            I18n::setLocale($locale);
            Session::set('locale', $locale);

            Session::setFlash('success', 'Profile and preferences updated successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to update preferences: ' . $e->getMessage());
        }

        return Response::redirect(url('/settings?tab=profile'));
    }

    public function updatePassword(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = User::findById($userId);

        $currentPass = (string)$request->input('current_password', '');
        $newPass = (string)$request->input('new_password', '');
        $confirmPass = (string)$request->input('confirm_password', '');

        if (!password_verify($currentPass, $user['password_hash'])) {
            Session::setFlash('error', 'The current password you entered is incorrect.');
            return Response::redirect(url('/settings?tab=security'));
        }

        if (strlen($newPass) < 8) {
            Session::setFlash('error', 'New password must be at least 8 characters long.');
            return Response::redirect(url('/settings?tab=security'));
        }

        if ($newPass !== $confirmPass) {
            Session::setFlash('error', 'New password and confirmation do not match.');
            return Response::redirect(url('/settings?tab=security'));
        }

        try {
            User::updatePassword($userId, $newPass);
            Session::setFlash('success', 'Password changed successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to change password: ' . $e->getMessage());
        }

        return Response::redirect(url('/settings?tab=security'));
    }

    public function exportCsv(Request $request): Response
    {
        $userId = (int) Auth::id();
        ExcelService::exportCsv($userId, $request->all());
        return Response::raw('');
    }

    public function exportXlsx(Request $request): Response
    {
        $userId = (int) Auth::id();
        ExcelService::exportXlsx($userId, $request->all());
        return Response::raw('');
    }

    public function importUpload(Request $request): Response
    {
        if (empty($_FILES['import_file']['tmp_name'])) {
            Session::setFlash('error', 'Please choose a file to upload (.csv or .xlsx).');
            return Response::redirect(url('/settings?tab=data'));
        }

        $file = $_FILES['import_file'];
        if ($file['size'] > 5 * 1024 * 1024) {
            Session::setFlash('error', 'File size exceeds 5MB limit.');
            return Response::redirect(url('/settings?tab=data'));
        }

        $parsed = ExcelService::parseImportFile($file['tmp_name'], $file['name']);
        if (empty($parsed['headers'])) {
            Session::setFlash('error', 'Could not read columns from the uploaded file. Please verify file format.');
            return Response::redirect(url('/settings?tab=data'));
        }

        // Store temporarily in session for mapping confirmation
        Session::set('pending_import', [
            'filename' => $file['name'],
            'headers' => $parsed['headers'],
            'rows' => $parsed['rows'],
        ]);

        return Response::redirect(url('/settings?tab=data&wizard=map'));
    }

    public function importPreview(Request $request): Response
    {
        $userId = (int) Auth::id();
        $pending = Session::get('pending_import');
        if (!$pending) {
            return Response::json(['ok' => false, 'error' => 'No active import session.']);
        }

        $mapping = $request->input('mapping', []);
        $defaultAccount = (int)$request->input('default_account_id', 0);

        $preview = ExcelService::preview($userId, $pending['rows'], $mapping, $defaultAccount);

        return Response::json(['ok' => true, 'data' => $preview]);
    }

    public function importCommit(Request $request): Response
    {
        $userId = (int) Auth::id();
        $pending = Session::get('pending_import');
        if (!$pending) {
            Session::setFlash('error', 'No active import session found. Please upload again.');
            return Response::redirect(url('/settings?tab=data'));
        }

        $mapping = [
            'date_col' => (int)$request->input('date_col', 0),
            'amount_col' => (int)$request->input('amount_col', 1),
            'type_col' => (int)$request->input('type_col', -1),
            'cat_col' => (int)$request->input('cat_col', -1),
            'note_col' => (int)$request->input('note_col', -1),
            'skip_duplicates' => (bool)$request->input('skip_duplicates', 1),
        ];

        $defaultAccountId = (int)$request->input('default_account_id');

        try {
            $result = ExcelService::commitImport($userId, $pending['filename'], $pending['rows'], $mapping, $defaultAccountId);
            Session::forget('pending_import');
            Session::setFlash('success', "Import completed! {$result['imported']} transactions imported, {$result['skipped']} skipped.");
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Import error: ' . $e->getMessage());
        }

        return Response::redirect(url('/settings?tab=data'));
    }

    public function importUndo(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $batchId = (int)$id;

        if (ExcelService::rollbackImport($userId, $batchId)) {
            Session::setFlash('success', 'Import undone. Transactions from that batch have been removed.');
        } else {
            Session::setFlash('error', 'Could not rollback import batch.');
        }

        return Response::redirect(url('/settings?tab=data'));
    }

    public function backupDownload(Request $request): Response
    {
        $userId = (int) Auth::id();
        $backup = BackupService::export($userId);

        $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'pfm_backup_' . date('Ymd_His') . '.json';

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $json;
        exit;
    }

    public function backupRestore(Request $request): Response
    {
        $userId = (int) Auth::id();

        if (empty($_FILES['backup_file']['tmp_name'])) {
            Session::setFlash('error', 'Please select a JSON backup file to restore.');
            return Response::redirect(url('/settings?tab=backup'));
        }

        $content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $payload = json_decode((string)$content, true);

        if (!$payload || !is_array($payload)) {
            Session::setFlash('error', 'Invalid JSON backup file.');
            return Response::redirect(url('/settings?tab=backup'));
        }

        try {
            $result = BackupService::restore($userId, $payload);
            Session::setFlash('success', "Backup restored successfully! ({$result['transactions_restored']} transactions restored)");
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Backup restore failed: ' . $e->getMessage());
        }

        return Response::redirect(url('/settings?tab=backup'));
    }

    public function updateSmtp(Request $request): Response
    {
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Access denied.');
            return Response::redirect(url('/settings'));
        }

        AppSetting::set('smtp_host', trim((string)$request->input('smtp_host', '')));
        AppSetting::set('smtp_port', trim((string)$request->input('smtp_port', '587')));
        AppSetting::set('smtp_username', trim((string)$request->input('smtp_username', '')));
        AppSetting::set('smtp_password', (string)$request->input('smtp_password', ''));
        AppSetting::set('smtp_encryption', (string)$request->input('smtp_encryption', 'tls'));
        AppSetting::set('smtp_from_address', trim((string)$request->input('smtp_from_address', '')));
        AppSetting::set('smtp_from_name', trim((string)$request->input('smtp_from_name', 'Personal Finance Manager')));

        Session::setFlash('success', 'SMTP email settings saved.');
        return Response::redirect(url('/settings?tab=system'));
    }

    public function testSmtp(Request $request): Response
    {
        if (!Auth::isAdmin()) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized']);
        }

        $user = Auth::user();
        $targetEmail = $request->input('test_email') ?: $user['email'];

        $sent = Mailer::send(
            $targetEmail,
            $user['name'],
            'Test Email from Personal Finance Manager',
            '<h3>Personal Finance Manager</h3><p>Your SMTP email configuration is functioning correctly.</p>',
            "Personal Finance Manager\n\nYour SMTP configuration is functioning correctly."
        );

        if ($sent) {
            return Response::json(['ok' => true, 'message' => "Test email dispatched to {$targetEmail}"]);
        } else {
            return Response::json(['ok' => false, 'error' => 'Failed to send test email. Check server log for details.']);
        }
    }

    public function toggleRegistration(Request $request): Response
    {
        if (!Auth::isAdmin()) {
            return Response::json(['ok' => false, 'error' => 'Unauthorized']);
        }

        $allow = $request->input('allow') ? '1' : '0';
        AppSetting::set('allow_registration', $allow);

        return Response::json(['ok' => true, 'allow' => ($allow === '1')]);
    }

    public function setLocale(Request $request): Response
    {
        $locale = $request->input('locale', 'en');
        if (in_array($locale, ['en', 'bn'], true)) {
            I18n::setLocale($locale);
            if (Auth::check()) {
                Database::query("UPDATE users SET locale = ? WHERE id = ?", [$locale, Auth::id()]);
            }
        }

        $referer = $request->header('Referer') ?? url('/dashboard');
        return Response::redirect($referer);
    }

    public function setTheme(Request $request): Response
    {
        $theme = $request->input('theme', 'system');
        if (in_array($theme, ['light', 'dark', 'system'], true)) {
            Session::set('theme', $theme);
            if (Auth::check()) {
                Database::query("UPDATE users SET theme = ? WHERE id = ?", [$theme, Auth::id()]);
            }
        }

        return Response::json(['ok' => true, 'theme' => $theme]);
    }

    public function downloadTemplate(Request $request): Response
    {
        $format = $request->query('format', 'csv') === 'xlsx' ? 'xlsx' : 'csv';
        ExcelService::downloadTemplate($format);
        return Response::raw('');
    }

    public function deleteAccount(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = User::findById($userId);
        $password = (string)$request->input('password', '');

        if (!password_verify($password, $user['password_hash'])) {
            Session::setFlash('error', 'Incorrect password. Account deletion was not processed.');
            return Response::redirect(url('/settings?tab=security'));
        }

        User::delete($userId);
        Auth::logout();
        Session::destroy();
        Session::start();
        Session::setFlash('success', 'Your account and all associated financial records have been permanently deleted.');

        return Response::redirect(url('/register'));
    }

    public function revokeOtherSessions(Request $request): Response
    {
        $revoked = Session::revokeOtherSessions();
        Session::regenerate();
        Session::setFlash('success', "Revoked other active session(s). Your current session remains secure.");
        return Response::redirect(url('/settings?tab=security'));
    }

    public function testMonthlySummary(Request $request): Response
    {
        $userId = (int) Auth::id();
        try {
            $result = \App\Services\MonthlySummaryService::sendSummaryForUser($userId);
            if ($result['ok']) {
                return Response::json(['ok' => true, 'message' => 'Monthly summary statement dispatched to ' . $result['metrics']['user']['email']]);
            } else {
                return Response::json(['ok' => false, 'error' => $result['error'] ?? 'Failed to send monthly statement. Check server mail logs.']);
            }
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
