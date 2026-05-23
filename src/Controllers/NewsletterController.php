<?php

namespace App\Controllers;

use App\Firebase;
use App\ResendMailer;
use App\View;

class NewsletterController
{
    public function subscribe($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $email = trim($input['email'] ?? '');
            $name = trim($input['name'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email address']);
                return;
            }

            // Save subscriber to Firestore
            $subscriberData = [
                'email' => $email,
                'name' => $name,
                'source' => 'website',
                'subscribedAt' => new \DateTime(),
                'active' => true
            ];

            $subscriberId = Firebase::createSubscriber($subscriberData);
            if ($subscriberId) {
                // Send welcome email via Resend (fire-and-forget, don't block on failure)
                ResendMailer::sendWelcome($email, $name);
            }

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Danke für dein Abonnement!']);

        } catch (\Exception $e) {
            error_log('Newsletter subscribe error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred. Please try again.']);
        }
    }

    public function landingPage($params = [], $post = [], $get = [])
    {
        $latestNews = Firebase::getPosts('news', true, 3, 0);
        $latestBlog = Firebase::getPosts('blog', true, 3, 0);

        echo View::render('newsletter/index', [
            'title' => 'Newsletter',
            'pageTitle' => 'Newsletter abonnieren',
            'latestNews' => $latestNews ?? [],
            'latestBlog' => $latestBlog ?? [],
        ]);
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

            // Unsubscribe logic: set active = false
            $docId = md5(strtolower($email));
            Firebase::updateSubscriber($docId, ['active' => false]);

            http_response_code(200);
            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log('Newsletter unsubscribe error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred']);
        }
    }
}
