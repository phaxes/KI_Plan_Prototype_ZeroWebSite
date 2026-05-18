<?php

namespace App;

class Mailchimp
{
    private static $apiKey = null;
    private static $serverPrefix = null;
    private static $listId = null;

    public static function init()
    {
        self::$apiKey = Config::get('MAILCHIMP_API_KEY');
        self::$serverPrefix = Config::get('MAILCHIMP_SERVER_PREFIX');
        self::$listId = Config::get('MAILCHIMP_LIST_ID');

        if (!self::$apiKey || !self::$serverPrefix || !self::$listId) {
            throw new \Exception('Mailchimp configuration incomplete. Check .env for MAILCHIMP_API_KEY, MAILCHIMP_SERVER_PREFIX, MAILCHIMP_LIST_ID');
        }
    }

    /**
     * Subscribe email to Mailchimp list
     */
    public static function subscribe($email, $name = '')
    {
        try {
            self::init();

            $url = "https://{self::$serverPrefix}.api.mailchimp.com/3.0/lists/{self::$listId}/members";

            $data = [
                'email_address' => $email,
                'status' => 'pending', // Double opt-in
                'merge_fields' => [
                    'FNAME' => $name ? explode(' ', $name)[0] : '',
                    'LNAME' => $name && count(explode(' ', $name)) > 1 ? implode(' ', array_slice(explode(' ', $name), 1)) : ''
                ]
            ];

            $response = self::apiCall('POST', $url, $data);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'message' => 'Subscription pending confirmation email'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['detail'] ?? 'Subscription failed'
                ];
            }
        } catch (\Exception $e) {
            error_log('Mailchimp subscribe error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Unsubscribe email from Mailchimp list
     */
    public static function unsubscribe($email)
    {
        try {
            self::init();

            $subscriberHash = md5(strtolower($email));
            $url = "https://{self::$serverPrefix}.api.mailchimp.com/3.0/lists/{self::$listId}/members/{$subscriberHash}";

            $data = ['status' => 'unsubscribed'];

            $response = self::apiCall('PATCH', $url, $data);

            return [
                'success' => true,
                'message' => 'Unsubscribed'
            ];
        } catch (\Exception $e) {
            error_log('Mailchimp unsubscribe error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add tag to subscriber (for segmentation)
     */
    public static function addTag($email, $tag)
    {
        try {
            self::init();

            $subscriberHash = md5(strtolower($email));
            $url = "https://{self::$serverPrefix}.api.mailchimp.com/3.0/lists/{self::$listId}/members/{$subscriberHash}/tags";

            $data = [
                'tags' => [
                    [
                        'name' => $tag,
                        'status' => 'active'
                    ]
                ]
            ];

            self::apiCall('POST', $url, $data);

            return [
                'success' => true,
                'message' => 'Tag added'
            ];
        } catch (\Exception $e) {
            error_log('Mailchimp addTag error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send campaign to segment
     */
    public static function sendCampaign($tags, $templateId, $subject, $data = [])
    {
        try {
            self::init();

            $url = "https://{self::$serverPrefix}.api.mailchimp.com/3.0/campaigns";

            $recipients = [
                'list_id' => self::$listId,
                'segment_opts' => [
                    'saved_segment_id' => 0,
                    'match' => 'all',
                    'conditions' => []
                ]
            ];

            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    $recipients['segment_opts']['conditions'][] = [
                        'condition_type' => 'Interests',
                        'field' => $tag,
                        'value' => true
                    ];
                }
            }

            $campaignData = [
                'type' => 'regular',
                'recipients' => $recipients,
                'settings' => [
                    'subject_line' => $subject,
                    'from_name' => 'Our Site',
                    'reply_to' => Config::get('APP_EMAIL', 'noreply@example.com'),
                    'template_id' => $templateId
                ]
            ];

            $response = self::apiCall('POST', $url, $campaignData);

            return [
                'success' => true,
                'campaignId' => $response['id'] ?? null
            ];
        } catch (\Exception $e) {
            error_log('Mailchimp sendCampaign error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list statistics
     */
    public static function getListStats()
    {
        try {
            self::init();

            $url = "https://{self::$serverPrefix}.api.mailchimp.com/3.0/lists/{self::$listId}";

            $response = self::apiCall('GET', $url);

            return [
                'totalContacts' => $response['stats']['member_count'] ?? 0,
                'unsubscribed' => $response['stats']['unsubscribe_count'] ?? 0,
                'cleaned' => $response['stats']['cleaned_count'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log('Mailchimp getListStats error: ' . $e->getMessage());
            return [
                'totalContacts' => 0,
                'unsubscribed' => 0,
                'cleaned' => 0
            ];
        }
    }

    /**
     * Internal API call helper
     */
    private static function apiCall($method, $url, $data = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'anystring:' . self::$apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if (!empty($data) && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('Mailchimp API error: ' . ($result['detail'] ?? $response));
        }

        return $result ?? [];
    }
}
