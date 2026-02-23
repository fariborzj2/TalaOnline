<?php
/**
 * SMS Service - Kavenegar Integration
 */

class SMS {
    /**
     * Sends a verification code using Kavenegar Lookup API
     *
     * @param string $receptor Mobile number
     * @param string $token Verification code
     * @return array Response status and message
     */
    public static function sendLookup($receptor, $token) {
        $api_key = get_setting('kavenegar_api_key');
        $template = get_setting('kavenegar_template');
        $enabled = get_setting('mobile_verification_enabled') === '1';

        if (!$enabled || empty($api_key) || empty($template)) {
            return ['success' => false, 'message' => 'سرویس پیامک فعال نیست یا تنظیم نشده است.'];
        }

        // Clean phone number (remove leading zero if present, ensure international format or 09xx)
        // Kavenegar usually expects 09xxxxxxxx or international
        $receptor = trim($receptor);

        $url = "https://api.kavenegar.com/v1/{$api_key}/verify/lookup.json";
        $params = [
            'receptor' => $receptor,
            'token' => $token,
            'template' => $template
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200) {
                $result = json_decode($response, true);
                if (isset($result['return']['status']) && $result['return']['status'] === 200) {
                    return ['success' => true, 'message' => 'کد تایید ارسال شد.'];
                }
                return ['success' => false, 'message' => $result['return']['message'] ?? 'خطا در ارسال پیامک.'];
            }

            return ['success' => false, 'message' => "خطای سیستم در ارتباط با سرویس پیامک (کد: {$http_code})"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در برقراری ارتباط با سرور پیامک.'];
        }
    }
}
