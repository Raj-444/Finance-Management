<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Models\AppSetting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        // Check if PHPMailer is available
        if (!class_exists(PHPMailer::class)) {
            Logger::error("PHPMailer library not found in vendor.");
            return false;
        }

        $smtpHost = AppSetting::get('smtp_host', (string)Config::get('mail.host', ''));
        $smtpPort = (int)AppSetting::get('smtp_port', (string)Config::get('mail.port', 587));
        $smtpUser = AppSetting::get('smtp_username', (string)Config::get('mail.username', ''));
        $smtpPass = AppSetting::get('smtp_password', (string)Config::get('mail.password', ''));
        $smtpEnc = AppSetting::get('smtp_encryption', (string)Config::get('mail.encryption', 'tls'));
        $fromEmail = AppSetting::get('smtp_from_address', (string)Config::get('mail.from_address', ''));
        $fromName = AppSetting::get('smtp_from_name', (string)Config::get('mail.from_name', 'Personal Finance Manager'));

        // If no SMTP host configured, log to file (useful during local dev)
        if (empty($smtpHost)) {
            Logger::info("SMTP not configured. Email logged instead: To: {$toEmail}, Subject: {$subject}");
            Logger::debug("Email Body:\n" . ($textBody ?: strip_tags($htmlBody)));
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUser);
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;

            if ($smtpEnc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpEnc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail ?: $smtpUser, $fromName);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            Logger::error("Mailer error: {$mail->ErrorInfo}");
            return false;
        } catch (\Throwable $e) {
            Logger::error("Email dispatch failed: {$e->getMessage()}");
            return false;
        }
    }
}
