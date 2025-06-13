
<?php
if (!defined('ABSPATH')) {
    exit;
}

$enable_otp = get_option('cod_verifier_enable_otp', '1');
$enable_token = get_option('cod_verifier_enable_token', '1');
$test_mode = get_option('cod_verifier_test_mode', '1');
?>

<div id="cod-verifier-wrapper" style="display:none !important; margin: 20px 0; position: relative;">
    <div class="cod-verifier-container">
        <!-- Header -->
        <div class="cod-verifier-header">
            <div class="cod-header-content">
                <div class="cod-icon">🔐</div>
                <div class="cod-header-text">
                    <h3>COD Verification Required</h3>
                    <p>Complete verification to place your Cash on Delivery order</p>
                </div>
            </div>
            <?php if ($test_mode === '1'): ?>
            <div class="cod-test-badge">TEST MODE</div>
            <?php endif; ?>
        </div>
        
        <!-- Content -->
        <div class="cod-verifier-content">
            <?php if ($enable_otp === '1'): ?>
            <div class="cod-section" id="cod-otp-section">
                <div class="cod-section-header">
                    <span class="cod-section-icon">📱</span>
                    <h4>Phone Verification</h4>
                    <span class="cod-step-badge">Step 1</span>
                </div>
                
                <div class="cod-form-group">
                    <label for="cod_phone">Mobile Number</label>
                    <div class="cod-input-group">
                        <input type="tel" id="cod_phone" name="cod_phone" placeholder="Enter 10-digit mobile number" maxlength="10" class="cod-input">
                        <button type="button" id="cod_send_otp" class="cod-btn cod-btn-primary">Send OTP</button>
                    </div>
                </div>
                
                <div class="cod-form-group">
                    <label for="cod_otp">Enter OTP</label>
                    <div class="cod-input-group">
                        <input type="text" id="cod_otp" name="cod_otp" placeholder="6-digit OTP" maxlength="6" class="cod-input">
                        <button type="button" id="cod_verify_otp" class="cod-btn cod-btn-success" disabled>Verify</button>
                    </div>
                </div>
                
                <div id="cod_otp_message" class="cod-message"></div>
            </div>
            <?php endif; ?>
            
            <?php if ($enable_token === '1'): ?>
            <div class="cod-section" id="cod-token-section">
                <div class="cod-section-header">
                    <span class="cod-section-icon">💳</span>
                    <h4>Token Payment</h4>
                    <span class="cod-step-badge">Step <?php echo ($enable_otp === '1') ? '2' : '1'; ?></span>
                </div>
                
                <div class="cod-token-info">
                    <p>Pay ₹1 token to confirm your order and prevent fake bookings</p>
                </div>
                
                <div class="cod-form-group">
                    <button type="button" id="cod_pay_token" class="cod-btn cod-btn-warning cod-btn-large">
                        Pay ₹1 Token
                    </button>
                </div>
                
                <div class="cod-checkbox-group">
                    <label class="cod-checkbox-label">
                        <input type="checkbox" id="cod_token_confirmed" name="cod_token_confirmed" value="1">
                        <span class="cod-checkmark"></span>
                        <span class="cod-checkbox-text">I confirm that I have completed the ₹1 token payment</span>
                    </label>
                </div>
                
                <div id="cod_token_message" class="cod-message"></div>
            </div>
            <?php endif; ?>
            
            <!-- Status Summary -->
            <div class="cod-status-summary">
                <h4>Verification Status</h4>
                <div class="cod-status-items">
                    <?php if ($enable_otp === '1'): ?>
                    <div class="cod-status-item" id="cod-otp-status">
                        <span class="cod-status-icon">📱</span>
                        <span class="cod-status-text">Phone Verification</span>
                        <span class="cod-status-badge pending" id="cod-otp-badge">Pending</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($enable_token === '1'): ?>
                    <div class="cod-status-item" id="cod-token-status">
                        <span class="cod-status-icon">💳</span>
                        <span class="cod-status-text">Token Payment</span>
                        <span class="cod-status-badge pending" id="cod-token-badge">Pending</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* COD Verifier Styles */
#cod-verifier-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.cod-verifier-container {
    background: #ffffff;
    border-radius: 11px;
}

.cod-verifier-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cod-header-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cod-icon {
    font-size: 24px;
    background: rgba(255, 255, 255, 0.2);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cod-header-text h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.cod-header-text p {
    margin: 4px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.cod-test-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.cod-verifier-content {
    padding: 20px;
}

.cod-section {
    background: #f8fafc;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
}

.cod-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.cod-section-icon {
    font-size: 18px;
}

.cod-section-header h4 {
    margin: 0;
    flex: 1;
    font-size: 16px;
    font-weight: 600;
    color: #374151;
}

.cod-step-badge {
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 500;
}

.cod-form-group {
    margin-bottom: 14px;
}

.cod-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.cod-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.cod-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
    background: white;
}

.cod-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.cod-btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.cod-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.cod-btn-primary {
    background: #667eea;
    color: white;
}

.cod-btn-primary:hover:not(:disabled) {
    background: #5a67d8;
}

.cod-btn-success {
    background: #10b981;
    color: white;
}

.cod-btn-success:hover:not(:disabled) {
    background: #059669;
}

.cod-btn-warning {
    background: #f59e0b;
    color: white;
}

.cod-btn-warning:hover:not(:disabled) {
    background: #d97706;
}

.cod-btn-large {
    padding: 12px 24px;
    font-size: 15px;
    width: 100%;
}

.cod-token-info {
    background: #f3f4f6;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 14px;
    border-left: 3px solid #667eea;
}

.cod-token-info p {
    margin: 0;
    color: #374151;
    font-size: 14px;
}

.cod-checkbox-group {
    margin: 14px 0;
}

.cod-checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
}

.cod-checkbox-label input[type="checkbox"] {
    display: none;
}

.cod-checkmark {
    width: 18px;
    height: 18px;
    border: 1px solid #d1d5db;
    border-radius: 3px;
    margin-right: 10px;
    position: relative;
    transition: all 0.2s;
}

.cod-checkbox-label input[type="checkbox"]:checked + .cod-checkmark {
    background: #667eea;
    border-color: #667eea;
}

.cod-checkbox-label input[type="checkbox"]:checked + .cod-checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 11px;
    font-weight: bold;
}

.cod-status-summary {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e5e7eb;
}

.cod-status-summary h4 {
    margin: 0 0 12px 0;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

.cod-status-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.cod-status-item:last-child {
    border-bottom: none;
}

.cod-status-icon {
    margin-right: 10px;
    font-size: 14px;
}

.cod-status-text {
    flex: 1;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.cod-status-badge {
    padding: 3px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 500;
}

.cod-status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.cod-status-badge.verified {
    background: #d1fae5;
    color: #065f46;
}

.cod-message {
    margin-top: 10px;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 13px;
    display: none;
}

.cod-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    display: block;
}

.cod-message.error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .cod-input-group {
        flex-direction: column;
    }
    
    .cod-input {
        margin-bottom: 8px;
    }
    
    .cod-btn {
        width: 100%;
    }
    
    .cod-section-header {
        flex-wrap: wrap;
    }
    
    .cod-step-badge {
        order: -1;
        margin-bottom: 8px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('COD Verifier: Template loaded, element exists:', $('#cod-verifier-wrapper').length > 0);
});
</script>
