<?php
/**
 * Core Email Library - Enhanced for Deliverability & Async Queuing
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader if it exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class Mail {
    /**
     * Send an email using a template (Synchronous)
     */
    public static function send($to, $template_slug, $data = []) {
        $mail_data = self::prepareTemplateData($template_slug, $data);
        if (!$mail_data) return false;

        return self::sendRaw($to, $mail_data['subject'], $mail_data['body']);
    }

    /**
     * Queue an email for background sending
     */
    public static function queue($to, $template_slug, $data = []) {
        $mail_data = self::prepareTemplateData($template_slug, $data);
        if (!$mail_data) return false;

        return self::queueRaw($to, $mail_data['subject'], $mail_data['body']);
    }

    /**
     * Queue a raw email
     */
    public static function queueRaw($to, $subject, $body_html, $options = []) {
        global $pdo;
        $sender_email = $options['sender_email'] ?? get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $sender_name = $options['sender_name'] ?? get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));

        try {
            $stmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body_html, sender_name, sender_email) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$to, $subject, $body_html, $sender_name, $sender_email]);
        } catch (Exception $e) {
            error_log("Queue Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare subject and body from template
     */
    private static function prepareTemplateData($template_slug, $data) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE slug = ?");
            $stmt->execute([$template_slug]);
            $template = $stmt->fetch();

            if (!$template) return false;

            $subject = $template['subject'];
            $body = $template['body'];

            $data['site_title'] = get_setting('site_title', 'Tala Online');
            $data['base_url'] = get_base_url();

            foreach ($data as $key => $value) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
                $body = str_replace('{' . $key . '}', $value, $body);
            }

            return ['subject' => $subject, 'body' => $body];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Send a raw email with advanced headers (Synchronous)
     */
    public static function sendRaw($to, $subject, $body_html, $options = []) {
        // Check if mailing is enabled
        $enabled = get_setting('mail_enabled', '1');
        if ($enabled !== '1') return false;

        $sender_email = $options['sender_email'] ?? get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $sender_name = $options['sender_name'] ?? get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));
        $debug = $options['debug'] ?? false;

        $mail_driver = get_setting('mail_driver', 'mail');

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return self::sendNative($to, $subject, $body_html, $sender_email, $sender_name);
        }

        $mail = new PHPMailer(true);

        try {
            if ($mail_driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = get_setting('smtp_host');
                $mail->SMTPAuth   = true;
                $mail->Username   = get_setting('smtp_user');
                $mail->Password   = get_setting('smtp_pass');
                $mail->SMTPSecure = get_setting('smtp_enc', 'tls') === 'none' ? false : get_setting('smtp_enc', 'tls');
                $mail->Port       = (int)get_setting('smtp_port', 587);
                $mail->Timeout    = 15;
                $mail->SMTPConnectTimeout = 10;

                // Disable SSL verification if requested (fixes TLS/SSL handshake hangs on many hosts)
                if (get_setting('smtp_skip_ssl_verify', '0') === '1') {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                }

                if ($debug) {
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = function($str, $level) { echo $str . "\n"; };
                }
            } else {
                $mail->isMail();
            }

            $mail->setFrom($sender_email, $sender_name);
            $mail->addAddress($to);
            $mail->addReplyTo($sender_email, $sender_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body_html;

            $body_text = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n\n"], $body_html));
            $mail->AltBody = html_entity_decode($body_text);
            $mail->CharSet = 'UTF-8';

            return $mail->send();
        } catch (Exception $e) {
            if ($debug) echo "PHPMailer Error: " . $mail->ErrorInfo;
            error_log("PHPMailer Error: " . $mail->ErrorInfo);

            if ($mail_driver === 'smtp' && !$debug) {
                 return self::sendNative($to, $subject, $body_html, $sender_email, $sender_name);
            }
            return false;
        }
    }

    /**
     * Fallback to PHP native mail()
     */
    private static function sendNative($to, $subject, $body_html, $sender_email, $sender_name) {
        $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encoded_name = '=?UTF-8?B?' . base64_encode($sender_name) . '?=';

        $boundary = md5(time());
        $message_id = sprintf("<%s.%s@%s>",
            base_convert(microtime(), 10, 36),
            base_convert(bin2hex(random_bytes(8)), 16, 36),
            $_SERVER['HTTP_HOST'] ?? 'localhost'
        );

        $body_text = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n\n"], $body_html));
        $body_text = html_entity_decode($body_text);

        $headers = [
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "From: $encoded_name <$sender_email>",
            "Reply-To: $encoded_name <$sender_email>",
            "Return-Path: $sender_email",
            "Message-ID: $message_id",
            "Date: " . date('r'),
            "X-Mailer: PHP/" . phpversion(),
        ];

        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($body_text)) . "\r\n";

        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($body_html)) . "\r\n";
        $message .= "--$boundary--";

        return mail($to, $encoded_subject, $message, implode("\r\n", $headers), "-f $sender_email");
    }
}
