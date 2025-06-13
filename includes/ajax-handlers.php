
<?php
if (!defined('ABSPATH')) {
    exit;
}

class CODVerifierAjax {
    
    public function __construct() {
        add_action('wp_ajax_cod_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_nopriv_cod_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_cod_verify_otp', array($this, 'verify_otp'));
        add_action('wp_ajax_nopriv_cod_verify_otp', array($this, 'verify_otp'));
        add_action('wp_ajax_cod_token_payment', array($this, 'handle_token_payment'));
        add_action('wp_ajax_nopriv_cod_token_payment', array($this, 'handle_token_payment'));
    }
    
    public function send_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cod_verifier_nonce')) {
            wp_send_json_error(__('Security check failed.', 'cod-verifier'));
            return;
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        
        if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) {
            wp_send_json_error(__('Please enter a valid 10-digit mobile number.', 'cod-verifier'));
            return;
        }
        
        $test_mode = get_option('cod_verifier_test_mode', '1');
        
        if (!session_id()) {
            session_start();
        }
        
        // Generate OTP
        $otp = sprintf('%06d', rand(100000, 999999));
        $_SESSION['cod_otp'] = $otp;
        $_SESSION['cod_otp_phone'] = $phone;
        $_SESSION['cod_otp_time'] = time();
        $_SESSION['cod_otp_verified'] = false;
        
        if ($test_mode === '1') {
            // Test mode - return OTP in response
            wp_send_json_success(array(
                'message' => __('OTP sent successfully! (Test Mode)', 'cod-verifier'),
                'otp' => $otp,
                'test_mode' => true
            ));
        } else {
            // Production mode - send actual SMS
            $api_key = get_option('cod_verifier_fast2sms_api_key', '');
            
            if (empty($api_key)) {
                wp_send_json_error(__('SMS service not configured. Please contact administrator.', 'cod-verifier'));
                return;
            }
            
            // Send SMS via Fast2SMS
            $message = "Your COD verification OTP is: {$otp}. Valid for 5 minutes.";
            
            $response = wp_remote_post('https://www.fast2sms.com/dev/bulkV2', array(
                'headers' => array(
                    'authorization' => $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'route' => 'v3',
                    'sender_id' => 'TXTIND',
                    'message' => $message,
                    'language' => 'english',
                    'flash' => 0,
                    'numbers' => $phone
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(__('Failed to send OTP. Please try again.', 'cod-verifier'));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('OTP sent successfully to your mobile number!', 'cod-verifier')
            ));
        }
    }
    
    public function verify_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cod_verifier_nonce')) {
            wp_send_json_error(__('Security check failed.', 'cod-verifier'));
            return;
        }
        
        $otp = sanitize_text_field($_POST['otp']);
        
        if (!session_id()) {
            session_start();
        }
        
        $stored_otp = isset($_SESSION['cod_otp']) ? $_SESSION['cod_otp'] : '';
        $otp_time = isset($_SESSION['cod_otp_time']) ? $_SESSION['cod_otp_time'] : 0;
        
        if (empty($stored_otp)) {
            wp_send_json_error(__('No OTP found. Please request a new OTP.', 'cod-verifier'));
            return;
        }
        
        // Check if OTP is expired (5 minutes)
        if (time() - $otp_time > 300) {
            unset($_SESSION['cod_otp']);
            wp_send_json_error(__('OTP expired. Please request a new OTP.', 'cod-verifier'));
            return;
        }
        
        if ($otp === $stored_otp) {
            $_SESSION['cod_otp_verified'] = true;
            wp_send_json_success(__('OTP verified successfully!', 'cod-verifier'));
        } else {
            wp_send_json_error(__('Invalid OTP. Please try again.', 'cod-verifier'));
        }
    }
    
    public function handle_token_payment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cod_verifier_nonce')) {
            wp_send_json_error(__('Security check failed.', 'cod-verifier'));
            return;
        }
        
        $test_mode = get_option('cod_verifier_test_mode', '1');
        
        if (!session_id()) {
            session_start();
        }
        
        if ($test_mode === '1') {
            // Test mode - simulate payment
            $_SESSION['cod_token_paid'] = true;
            wp_send_json_success(__('Token payment completed successfully! (Test Mode)', 'cod-verifier'));
        } else {
            // Production mode - integrate with Razorpay
            $razorpay_handler = new CODVerifierRazorpay();
            $razorpay_handler->create_order();
        }
    }
}

new CODVerifierAjax();
