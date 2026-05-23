<?php

namespace App;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Resend Email Service
 * Sends transactional emails via Resend API
 */
class ResendMailer
{
    /**
     * Send welcome email to new subscriber
     */
    public static function sendWelcome(string $toEmail, string $toName = ''): bool
    {
        $name = !empty($toName) ? $toName : 'Newsletter-Abonnent';
        $subject = 'Willkommen beim Newsletter!';
        $html = self::welcomeHtml($name);
        return self::send($toEmail, $subject, $html);
    }

    /**
     * Send generic email via Resend
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        $apiKey = Config::get('RESEND_API_KEY');

        // Check if API key is configured
        if (!$apiKey || str_starts_with($apiKey, 're_xxxx')) {
            error_log('ResendMailer: No API key configured');
            return false;
        }

        try {
            $client = new GuzzleClient();
            $response = $client->post('https://api.resend.com/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'from' => Config::get('RESEND_FROM_NAME', 'Newsletter') . ' <' . Config::get('RESEND_FROM_EMAIL', 'onboarding@resend.dev') . '>',
                    'to' => [$to],
                    'subject' => $subject,
                    'html' => $html,
                ],
            ]);

            $success = $response->getStatusCode() === 200;
            if ($success) {
                error_log('ResendMailer: Email sent successfully to ' . $to);
            } else {
                error_log('ResendMailer: Email send failed (HTTP ' . $response->getStatusCode() . ')');
            }
            return $success;

        } catch (RequestException $e) {
            error_log('ResendMailer: Request failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('ResendMailer: Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Welcome email HTML template
     */
    private static function welcomeHtml(string $name): string
    {
        $websiteUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 28px; color: #1a1a1a; }
        .content { margin: 30px 0; }
        .content p { margin: 15px 0; }
        .cta { display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 10px; }
        .footer { border-top: 1px solid #f0f0f0; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Willkommen beim Newsletter! 🎉</h1>
        </div>

        <div class="content">
            <p>Hallo $name,</p>

            <p>vielen Dank für deine Anmeldung! Ab sofort bekommst du regelmäßig:</p>

            <ul>
                <li>Neue Artikel und Stories aus unserem Blog</li>
                <li>Exklusive News und Updates</li>
                <li>Besondere Angebote und Produktneuheiten</li>
            </ul>

            <p>Wir freuen uns, dich in unserer Community zu haben!</p>

            <a href="$websiteUrl" class="cta">Zur Website</a>
        </div>

        <div class="footer">
            <p>Du kannst dich jederzeit abmelden, indem du auf diese E-Mail antwortest.</p>
            <p>© 2026 Zero Cost Website. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
