<?php

namespace App\Controllers;

use App\Firebase;

class NewsletterController
{
    public function subscribe($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $email = trim($post['email'] ?? '');
            $name = trim($post['name'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email address']);
                return;
            }

            // Save subscriber to Firestore (M2+)
            $subscriberData = [
                'email' => $email,
                'name' => $name,
                'source' => 'website',
                'subscribedAt' => new \DateTime(),
                'active' => true
            ];

            // For now, just simulate success (full Firestore + Mailchimp in M5)
            // Firebase::createSubscriber($subscriberData);

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Danke für dein Abonnement!']);

        } catch (\Exception $e) {
            error_log('Newsletter subscribe error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred. Please try again.']);
        }
    }

    public function unsubscribe($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $email = $post['email'] ?? null;

            if (!$email) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email']);
                return;
            }

            // Unsubscribe logic will be in M5

            http_response_code(200);
            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log('Newsletter unsubscribe error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred']);
        }
    }
}
