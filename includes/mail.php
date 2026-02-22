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

            $site_title = get_setting('site_title', 'Tala Online');
            $base_url = get_base_url();
            $logo_url = $base_url . '/assets/images/logo.svg'; // Default logo

            $data['site_title'] = $site_title;
            $data['base_url'] = $base_url;

            foreach ($data as $key => $value) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
                $body = str_replace('{' . $key . '}', $value, $body);
            }

            $wrapped_body = self::getProfessionalLayout($body, [
                'site_title' => $site_title,
                'base_url' => $base_url,
                'logo_url' => $logo_url
            ]);

            return ['subject' => $subject, 'body' => $wrapped_body];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Wrap content in a professional RTL layout
     */
    public static function getProfessionalLayout($content, $params = []) {
        if (empty($params)) {
            $params = [
                'site_title' => get_setting('site_title', 'Tala Online'),
                'base_url' => get_base_url(),
                'logo_url' => get_base_url() . '/assets/images/logo.svg'
            ];
        }
        $site_title = $params['site_title'];
        $base_url = $params['base_url'];
        $logo_url = $params['logo_url'];

        // Build social links
        $socials = [
            'telegram' => ['label' => 'تلگرام', 'icon' => 'TG'],
            'instagram' => ['label' => 'اینستاگرام', 'icon' => 'IG'],
            'twitter' => ['label' => 'توییتر', 'icon' => 'TW'],
            'linkedin' => ['label' => 'لینکدین', 'icon' => 'IN'],
        ];

        $social_html = '';
        foreach ($socials as $key => $info) {
            $url = get_setting('social_' . $key);
            if ($url) {
                $social_html .= '<a href="' . htmlspecialchars($url) . '" style="display: inline-block; margin: 0 8px; text-decoration: none; color: #e29b21; font-weight: bold; font-size: 13px;">' . $info['label'] . '</a>';
            }
        }

        return '
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f7f9; font-family: Tahoma, Arial, sans-serif; direction: rtl;" dir="rtl">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f4f7f9; padding: 20px 0; direction: rtl;" dir="rtl">
                <tr>
                    <td align="center">
                        <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); max-width: 95%;">
                            <!-- Header -->
                            <tr>
                                <td align="center" style="padding: 40px 30px; border-bottom: 1px solid #f0f0f0;">
                                    <img src="' . $logo_url . '" alt="' . $site_title . '" width="80" style="display: block; outline: none; border: none; text-decoration: none;">
                                    <h1 style="margin: 15px 0 0 0; font-size: 20px; color: #1e293b; font-weight: 800;">' . $site_title . '</h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 50px 40px; color: #334155; line-height: 1.8; text-align: right; font-size: 15px;">
                                    ' . $content . '
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td align="center" style="padding: 40px; background-color: #f8fafc; border-top: 1px solid #f0f0f0; color: #64748b; font-size: 13px;">
                                    <p style="margin: 0 0 20px 0; font-weight: bold; color: #1e293b;">' . $site_title . '</p>
                                    <p style="margin: 0 0 20px 0; line-height: 1.5;">مرجع تخصصی و لحظه‌ای قیمت طلا، سکه و ارز<br>مقایسه هوشمند پلتفرم‌های معاملاتی</p>

                                    <div style="margin-bottom: 25px;">
                                        ' . $social_html . '
                                    </div>

                                    <a href="' . $base_url . '" style="display: inline-block; background-color: #e29b21; color: #ffffff; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(226, 155, 33, 0.2);">مشاهده وب‌سایت</a>

                                    <p style="margin: 30px 0 0 0; font-size: 11px; color: #94a3b8;">این یک ایمیل خودکار است. لطفاً به آن پاسخ ندهید.</p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top: 20px; color: #94a3b8; font-size: 12px;">&copy; ' . date('Y') . ' ' . $site_title . '. تمامی حقوق محفوظ است.</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Send a raw email with advanced headers (Synchronous)
     */
    public static function sendRaw($to, $subject, $body_html, $options = []) {
        // Allow overriding global settings (useful for testing)
        $config = $options['config'] ?? [];

        // Check if mailing is enabled
        $enabled = $config['mail_enabled'] ?? get_setting('mail_enabled', '1');
        if ($enabled !== '1') return false;

        $sender_email = $options['sender_email'] ?? $config['mail_sender_email'] ?? get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $sender_name = $options['sender_name'] ?? $config['mail_sender_name'] ?? get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));
        $debug = $options['debug'] ?? false;

        $mail_driver = $config['mail_driver'] ?? get_setting('mail_driver', 'mail');

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return self::sendNative($to, $subject, $body_html, $sender_email, $sender_name);
        }

        $mail = new PHPMailer(true);

        try {
            if ($mail_driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'] ?? get_setting('smtp_host');
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_user'] ?? get_setting('smtp_user');
                $mail->Password   = $config['smtp_pass'] ?? get_setting('smtp_pass');
                $mail->SMTPSecure = ($config['smtp_enc'] ?? get_setting('smtp_enc', 'tls')) === 'none' ? false : ($config['smtp_enc'] ?? get_setting('smtp_enc', 'tls'));
                $mail->Port       = (int)($config['smtp_port'] ?? get_setting('smtp_port', 587));
                $mail->Timeout    = 15;
                $mail->SMTPConnectTimeout = 10;

                // Disable SSL verification if requested
                $skip_verify = $config['smtp_skip_ssl_verify'] ?? get_setting('smtp_skip_ssl_verify', '0');
                if ($skip_verify === '1') {
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
