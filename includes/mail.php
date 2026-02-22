<?php
/**
 * Core Email Library
 */

class Mail {
    /**
     * Send an email using a template
     *
     * @param string $to Recipient email
     * @param string $template_slug Template slug from email_templates table
     * @param array $data Associative array of placeholders to replace
     * @return bool
     */
    public static function send($to, $template_slug, $data = []) {
        global $pdo;

        try {
            // Fetch template
            $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE slug = ?");
            $stmt->execute([$template_slug]);
            $template = $stmt->fetch();

            if (!$template) {
                error_log("Email template not found: $template_slug");
                return false;
            }

            $subject = $template['subject'];
            $body = $template['body'];

            // Common placeholders
            $data['site_title'] = get_setting('site_title', 'Tala Online');
            $data['base_url'] = get_base_url();

            // Replace placeholders
            foreach ($data as $key => $value) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
                $body = str_replace('{' . $key . '}', $value, $body);
            }

            return self::sendRaw($to, $subject, $body);

        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a raw email
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public static function sendRaw($to, $subject, $body) {
        // Check if mailing is enabled
        $enabled = get_setting('mail_enabled', '1');
        if ($enabled !== '1') {
            return false;
        }

        $sender_email = get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $sender_name = get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));

        $encoded_name = '=?UTF-8?B?' . base64_encode($sender_name) . '?=';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $encoded_name . ' <' . $sender_email . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
