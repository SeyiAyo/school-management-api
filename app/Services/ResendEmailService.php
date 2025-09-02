<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Resend\Laravel\Facades\Resend;

class ResendEmailService
{
    protected EmailTemplateService $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Send email using environment-based dispatch
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $html HTML email content
     * @param string|null $from Sender email address
     * @return array|false Email response array or false on failure
     */
    private function sendEmail(string $to, string $subject, string $html, ?string $from = null): array|false
    {
        // Use PHPMailer for local development, Resend for production
        if (config('app.env') === 'local') {
            return $this->sendEmailWithPHPMailer($to, $subject, $html, $from);
        }

        return $this->sendEmailWithResend($to, $subject, $html, $from);
    }

    /**
     * Send email using PHPMailer with Gmail SMTP for local development
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $html HTML email content
     * @param string|null $from Sender email address
     * @return array|false Email response array or false on failure
     */
    private function sendEmailWithPHPMailer(string $to, string $subject, string $html, ?string $from): array|false
    {
        try {
            $fromAddress = $from ?? config('mail.from.address', 'noreply@schoolmanagement.com');
            $fromName = config('mail.from.name', 'School Management System');

            Log::info('Sending email via PHPMailer with Gmail SMTP', [
                'to' => $to,
                'subject' => $subject,
                'from' => "{$fromName} <{$fromAddress}>"
            ]);

            $mail = new PHPMailer(true);

            // Gmail SMTP configuration
            $mail->isSMTP();
            $mail->Host       = config('mail.mailers.smtp.host', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = config('mail.mailers.smtp.port', 587);
            $mail->SMTPDebug  = 2;
            $mail->Debugoutput = function($str, $level) {
                Log::info("PHPMailer Debug: $str", ['level' => $level]);
            };

            // Recipients
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;

            $mail->send();

            Log::info('Email sent successfully via Gmail SMTP', [
                'to' => $to,
                'subject' => $subject
            ]);

            // Return mock response similar to Resend format
            return [
                'id' => 'gmail-' . uniqid(),
                'to' => [$to],
                'from' => $fromAddress,
                'subject' => $subject,
                'created_at' => now()->toISOString()
            ];

        } catch (PHPMailerException $e) {
            Log::error('Failed to send email via Gmail SMTP', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'gmail_username' => config('mail.mailers.smtp.username'),
                'gmail_host' => config('mail.mailers.smtp.host'),
                'gmail_port' => config('mail.mailers.smtp.port')
            ]);

            // Fallback to logging for development if Gmail fails
            Log::info('📧 EMAIL FALLBACK (Gmail Auth Failed - Logged Only)', [
                'to' => $to,
                'from' => $fromAddress,
                'subject' => $subject,
                'verification_url' => $this->extractVerificationUrl($html),
                'note' => 'Fix Gmail credentials to send real emails'
            ]);

            return [
                'id' => 'fallback-' . uniqid(),
                'to' => [$to],
                'from' => $fromAddress,
                'subject' => $subject,
                'created_at' => now()->toISOString(),
                'status' => 'logged_fallback'
            ];
        }
    }

    /**
     * Extract verification URL from HTML content for logging purposes
     *
     * @param string $html HTML email content
     * @return string|null Extracted verification URL or null if not found
     */
    private function extractVerificationUrl(string $html): ?string
    {
        if (preg_match('/href=["\']([^"\']*)verify[^"\']*)["\']/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Send email using Resend API for production environment
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $html HTML email content
     * @param string|null $from Sender email address
     * @return array|false Email response array or false on failure
     */
    private function sendEmailWithResend(string $to, string $subject, string $html, ?string $from): array|false
    {
        try {
            $fromAddress = $from ?? config('mail.from.address', 'noreply@schoolmanagement.com');
            $fromName = config('mail.from.name', 'School Management System');

            // Check API key configuration
            $apiKey = config('services.resend.api_key');
            if (empty($apiKey)) {
                Log::error('Resend API key not configured', [
                    'config_path' => 'services.resend.api_key'
                ]);
                return false;
            }

            Log::info('Sending email via Resend', [
                'to' => $to,
                'subject' => $subject,
                'from' => "{$fromName} <{$fromAddress}>",
                'api_key_configured' => !empty($apiKey)
            ]);

            // Configure SSL for development environment
            if (config('app.env') === 'local') {
                // Temporarily disable SSL verification for XAMPP/Windows development
                $originalVerifyPeer = ini_get('curl.cainfo');
                ini_set('curl.cainfo', '');

                // Also set the environment variable
                putenv('CURL_CA_BUNDLE=');
            }

            $response = Resend::emails()->send([
                'from' => "{$fromName} <{$fromAddress}>",
                'to' => [$to],
                'subject' => $subject,
                'html' => $html
            ]);

            Log::info('Email sent successfully via Resend', [
                'to' => $to,
                'subject' => $subject,
                'email_id' => $response['id'] ?? null,
                'response_type' => gettype($response)
            ]);

            return $response->toArray();
        } catch (Exception $e) {
            Log::error('Failed to send email via Resend', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromAddress,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send batch emails using Resend
     *
     * @param array $emails Array of email data [['to' => '', 'subject' => '', 'html' => ''], ...]
     * @param string|null $from Sender email (optional, uses default)
     * @return array|false Returns batch response on success, false on failure
     */
    public function sendBatchEmails(array $emails, ?string $from = null): array|false
    {
        try {
            $fromAddress = $from ?? config('mail.from.address', 'noreply@schoolmanagement.com');
            $fromName = config('mail.from.name', 'School Management System');

            $batchData = [];
            foreach ($emails as $email) {
                $batchData[] = [
                    'from' => "{$fromName} <{$fromAddress}>",
                    'to' => [$email['to']],
                    'subject' => $email['subject'],
                    'html' => $email['html']
                ];
            }

            $response = Resend::batch()->send($batchData);

            Log::info('Batch emails sent successfully via Resend', [
                'count' => count($emails),
                'batch_id' => $response['id'] ?? null
            ]);

            return $response->toArray();
        } catch (Exception $e) {
            Log::error('Failed to send batch emails via Resend', [
                'count' => count($emails),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send email verification with OTP code
     *
     * @param string $to Recipient email address
     * @param string $otpCode 6-digit verification code
     * @param string $userName User's display name
     * @return array|false Email response array or false on failure
     */
    public function sendEmailVerification(string $to, string $otpCode, string $userName): array|false
    {
        try {
            $subject = 'Your Email Verification Code - EduSphere';
            $html = $this->templateService->renderTemplate('otp-verification', [
                'otpCode' => $otpCode,
                'userName' => $userName
            ]);
            $fromAddress = config('mail.from.address');

            Log::info('Attempting to send OTP email verification', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromAddress,
                'otp_code' => $otpCode
            ]);

            $result = $this->sendEmail($to, $subject, $html, $fromAddress);

            if ($result === false) {
                Log::error('sendEmail returned false for OTP email verification', [
                    'to' => $to,
                    'from' => $fromAddress
                ]);
                return false;
            }

            Log::info('OTP email verification sent successfully', [
                'to' => $to,
                'email_id' => $result['id'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception in sendEmailVerificationOtp', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send school verification status notification to admin
     *
     * @param string $to School admin email address
     * @param string $schoolName Name of the school
     * @param string $status Verification status (approved/rejected)
     * @param string|null $notes Optional admin notes
     * @return array|false Email response array or false on failure
     */
    public function sendSchoolVerificationStatus(string $to, string $schoolName, string $status, ?string $notes = null): array|false
    {
        $subject = $status === 'approved'
            ? "School Verification Approved - {$schoolName}"
            : "School Verification Update - {$schoolName}";

        $html = $this->templateService->renderTemplate('school-verification', [
            'schoolName' => $schoolName,
            'isApproved' => $status === 'approved',
            'message' => $status === 'approved'
                ? 'Your school has been successfully verified and you now have full access to all features.'
                : 'Your school verification requires additional review. Please check the details below.',
            'notes' => $notes
        ]);

        return $this->sendEmail($to, $subject, $html, config('mail.from.address'));
    }


    /**
     * Retrieve email information by ID
     *
     * @param string $emailId
     * @return array|false
     */
    public function getEmail(string $emailId): array|false
    {
        try {
            $response = Resend::emails()->get($emailId);
            return $response->toArray();
        } catch (Exception $e) {
            Log::error('Failed to retrieve email from Resend', [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cancel a scheduled email
     *
     * @param string $emailId
     * @return bool
     */
    public function cancelEmail(string $emailId): bool
    {
        try {
            Resend::emails()->cancel($emailId);
            Log::info('Email cancelled successfully', ['email_id' => $emailId]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to cancel email', [
                'email_id' => $emailId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
