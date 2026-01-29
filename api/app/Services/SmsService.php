<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

class SmsService
{
    protected string $driver;
    protected array $config;

    public function __construct()
    {
        $this->driver = config('sms.default', 'log');
        $this->config = config('sms.drivers.' . $this->driver, []);
    }

    /**
     * Send SMS message
     */
    public function send(string $to, string $message): array
    {
        // Normalize phone number for Burkina Faso
        $to = $this->normalizePhone($to);

        return match ($this->driver) {
            'twilio' => $this->sendViaTwilio($to, $message),
            default => $this->sendViaLog($to, $message),
        };
    }

    /**
     * Send OTP code
     */
    public function sendOtp(string $to, string $code): array
    {
        $template = config('sms.templates.otp');
        $message = str_replace(
            [':code', ':minutes'],
            [$code, config('sms.otp.expires_minutes', 5)],
            $template
        );

        return $this->send($to, $message);
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(string $to, string $template, array $data = []): array
    {
        $message = config("sms.templates.{$template}", $template);

        foreach ($data as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }

        return $this->send($to, $message);
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio(string $to, string $message): array
    {
        try {
            $client = new TwilioClient(
                $this->config['sid'],
                $this->config['token']
            );

            $result = $client->messages->create($to, [
                'from' => $this->config['from'],
                'body' => $message,
            ]);

            Log::channel('api')->info('SMS sent via Twilio', [
                'to' => $this->maskPhone($to),
                'sid' => $result->sid,
                'status' => $result->status,
            ]);

            return [
                'success' => true,
                'provider' => 'twilio',
                'message_id' => $result->sid,
                'status' => $result->status,
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio SMS failed', [
                'to' => $this->maskPhone($to),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'provider' => 'twilio',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via Log (development)
     */
    protected function sendViaLog(string $to, string $message): array
    {
        Log::channel('sms')->info("SMS to {$to}: {$message}");

        // Also log to default for visibility in development
        Log::info("📱 SMS [{$to}]: {$message}");

        return [
            'success' => true,
            'provider' => 'log',
            'message_id' => 'log_' . uniqid(),
            'debug_message' => $message,
        ];
    }

    /**
     * Normalize phone to international format for Burkina Faso
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-\.]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Add Burkina Faso country code if not present
        if (!str_starts_with($phone, '+')) {
            if (!str_starts_with($phone, '226')) {
                $phone = '226' . $phone;
            }
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Mask phone for logs
     */
    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, 7) . '****' . substr($phone, -2);
    }

    /**
     * Check if SMS service is properly configured
     */
    public function isConfigured(): bool
    {
        if ($this->driver === 'log') {
            return true;
        }

        if ($this->driver === 'twilio') {
            return !empty($this->config['sid']) 
                && !empty($this->config['token']) 
                && !empty($this->config['from']);
        }

        return false;
    }

    /**
     * Get current driver
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
}
