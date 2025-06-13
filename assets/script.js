
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('COD Verifier: Script initialized');
    
    // Check if codVerifier is defined
    if (typeof codVerifier === 'undefined') {
        console.error('COD Verifier: codVerifier object not found.');
        return;
    }
    
    let otpVerified = false;
    let tokenPaid = false;
    let isBlockCheckout = $('.wc-block-checkout').length > 0;
    let verificationBoxCreated = false;
    
    console.log('COD Verifier: Checkout type:', isBlockCheckout ? 'Blocks' : 'Classic');
    
    // Function to get selected payment method
    function getSelectedPaymentMethod() {
        let selectedMethod = null;
        
        if (isBlockCheckout) {
            // WooCommerce Blocks - multiple possible selectors
            const selectors = [
                '.wc-block-components-radio-control__input:checked',
                'input[name*="radio-control-wc-payment-method"]:checked',
                'input[name*="payment-method"]:checked'
            ];
            
            for (let selector of selectors) {
                const $input = $(selector);
                if ($input.length > 0) {
                    selectedMethod = $input.val();
                    break;
                }
            }
        } else {
            // Classic WooCommerce
            selectedMethod = $('input[name="payment_method"]:checked').val();
        }
        
        console.log('COD Verifier: Selected payment method:', selectedMethod);
        return selectedMethod;
    }
    
    // Function to create verification box from template
    function createVerificationBox() {
        if (verificationBoxCreated) {
            return $('#cod-verifier-wrapper');
        }
        
        const $template = $('#cod-verification-template #cod-verifier-wrapper');
        if ($template.length === 0) {
            console.error('COD Verifier: Template not found in DOM');
            return $();
        }
        
        // Clone the template and add it to the page
        const $clonedBox = $template.clone();
        $clonedBox.attr('id', 'cod-verifier-wrapper-active');
        
        // Find the best insertion point
        let $insertionPoint = null;
        
        if (isBlockCheckout) {
            // For WooCommerce Blocks
            const blockSelectors = [
                '.wc-block-checkout__payment',
                '.wc-block-components-checkout-place-order-button',
                '.wp-block-woocommerce-checkout-order-summary-block',
                '.wc-block-checkout'
            ];
            
            for (let selector of blockSelectors) {
                $insertionPoint = $(selector).first();
                if ($insertionPoint.length > 0) {
                    console.log('COD Verifier: Found insertion point:', selector);
                    break;
                }
            }
        } else {
            // For Classic WooCommerce
            const classicSelectors = [
                '#order_review',
                '.woocommerce-checkout-review-order',
                '#place_order',
                '.place-order'
            ];
            
            for (let selector of classicSelectors) {
                $insertionPoint = $(selector).first();
                if ($insertionPoint.length > 0) {
                    console.log('COD Verifier: Found insertion point:', selector);
                    break;
                }
            }
        }
        
        if ($insertionPoint && $insertionPoint.length > 0) {
            $insertionPoint.before($clonedBox);
            verificationBoxCreated = true;
            console.log('COD Verifier: Verification box created and inserted');
            return $clonedBox;
        } else {
            console.error('COD Verifier: No suitable insertion point found');
            return $();
        }
    }
    
    // Function to handle payment method changes
    function handlePaymentMethodChange() {
        const selectedMethod = getSelectedPaymentMethod();
        
        if (selectedMethod === 'cod' || selectedMethod === 'cash_on_delivery') {
            console.log('COD Verifier: COD selected, showing verification box');
            showVerificationBox();
        } else {
            console.log('COD Verifier: Non-COD selected, hiding verification box');
            hideVerificationBox();
        }
    }
    
    function showVerificationBox() {
        let $wrapper = $('#cod-verifier-wrapper-active');
        
        if ($wrapper.length === 0) {
            $wrapper = createVerificationBox();
        }
        
        if ($wrapper.length > 0) {
            $wrapper.show();
            console.log('COD Verifier: Verification box shown');
            populatePhoneFromBilling();
            updateVerificationStatus();
        } else {
            console.error('COD Verifier: Could not create or show verification box');
        }
    }
    
    function hideVerificationBox() {
        const $wrapper = $('#cod-verifier-wrapper-active');
        if ($wrapper.length > 0) {
            $wrapper.hide();
            console.log('COD Verifier: Verification box hidden');
            resetVerificationStates();
        }
    }
    
    function populatePhoneFromBilling() {
        const phoneSelectors = ['#billing_phone', 'input[name*="billing-phone"]', 'input[name*="phone"]'];
        let billingPhone = '';
        
        for (let selector of phoneSelectors) {
            const $phone = $(selector);
            if ($phone.length > 0 && $phone.val()) {
                billingPhone = $phone.val();
                break;
            }
        }
        
        if (billingPhone && !$('#cod_phone').val()) {
            $('#cod_phone').val(billingPhone);
        }
    }
    
    function resetVerificationStates() {
        otpVerified = false;
        tokenPaid = false;
        $('#cod_otp').val('');
        $('#cod_phone').val('');
        $('#cod_token_confirmed').prop('checked', false);
        $('#cod_otp_message').removeClass('success error').hide();
        $('#cod_token_message').removeClass('success error').hide();
        $('#cod_verify_otp').prop('disabled', true).text('Verify').removeClass('verified');
        $('#cod_pay_token').text('Pay ₹1 Token').removeClass('verified');
        updateHiddenFields();
        updateVerificationStatus();
    }
    
    function updateHiddenFields() {
        // Update hidden form fields for server-side validation
        let $otpField = $('input[name="cod_otp_verified"]');
        let $tokenField = $('input[name="cod_token_verified"]');
        
        if ($otpField.length === 0) {
            $('form.checkout, form.wc-block-checkout__form, body').append('<input type="hidden" name="cod_otp_verified" value="0">');
            $otpField = $('input[name="cod_otp_verified"]');
        }
        if ($tokenField.length === 0) {
            $('form.checkout, form.wc-block-checkout__form, body').append('<input type="hidden" name="cod_token_verified" value="0">');
            $tokenField = $('input[name="cod_token_verified"]');
        }
        
        $otpField.val(otpVerified ? '1' : '0');
        $tokenField.val(tokenPaid ? '1' : '0');
        
        console.log('COD Verifier: Hidden fields updated - OTP:', otpVerified, 'Token:', tokenPaid);
    }
    
    function updateVerificationStatus() {
        if (codVerifier.enableOTP === '1') {
            const otpBadge = $('#cod-otp-badge');
            if (otpBadge.length) {
                if (otpVerified) {
                    otpBadge.text('✓ Verified').removeClass('pending').addClass('verified');
                } else {
                    otpBadge.text('Pending').removeClass('verified').addClass('pending');
                }
            }
        }
        
        if (codVerifier.enableToken === '1') {
            const tokenBadge = $('#cod-token-badge');
            if (tokenBadge.length) {
                if (tokenPaid) {
                    tokenBadge.text('✓ Completed').removeClass('pending').addClass('verified');
                } else {
                    tokenBadge.text('Pending').removeClass('verified').addClass('pending');
                }
            }
        }
        
        updateHiddenFields();
    }
    
    // Set up event listeners for payment method changes
    $(document).on('change', 'input[name="payment_method"], .wc-block-components-radio-control__input, input[name*="radio-control-wc-payment-method"], input[name*="payment-method"]', handlePaymentMethodChange);
    
    // Classic WooCommerce events
    $(document.body).on('updated_checkout', function() {
        setTimeout(handlePaymentMethodChange, 200);
    });
    
    // WooCommerce Blocks events
    $(document).on('change', '.wc-block-checkout', function() {
        setTimeout(handlePaymentMethodChange, 200);
    });
    
    // Initial check with multiple attempts
    setTimeout(handlePaymentMethodChange, 500);
    setTimeout(handlePaymentMethodChange, 1000);
    setTimeout(handlePaymentMethodChange, 2000);
    
    // OTP and Token event handlers
    $(document).on('click', '#cod_send_otp', function(e) {
        e.preventDefault();
        
        const phone = $('#cod_phone').val().trim();
        const $btn = $(this);
        
        if (!phone) {
            showMessage('otp', 'Please enter your mobile number', 'error');
            return;
        }
        
        if (!/^[6-9]\d{9}$/.test(phone)) {
            showMessage('otp', 'Please enter a valid 10-digit mobile number starting with 6-9', 'error');
            return;
        }
        
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: codVerifier.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cod_send_otp',
                phone: phone,
                nonce: codVerifier.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('otp', response.data.message, 'success');
                    if (response.data.test_mode && response.data.otp) {
                        alert('TEST MODE - Your OTP is: ' + response.data.otp);
                    }
                    startOTPTimer($btn);
                } else {
                    showMessage('otp', response.data, 'error');
                    $btn.prop('disabled', false).text('Send OTP');
                }
            },
            error: function() {
                showMessage('otp', 'Failed to send OTP. Please try again.', 'error');
                $btn.prop('disabled', false).text('Send OTP');
            }
        });
    });
    
    function startOTPTimer($btn) {
        let timeLeft = 60;
        const timer = setInterval(function() {
            $btn.text(`Resend in ${timeLeft}s`);
            timeLeft--;
            
            if (timeLeft < 0) {
                clearInterval(timer);
                $btn.prop('disabled', false).text('Send OTP');
            }
        }, 1000);
    }
    
    // Enable verify button when OTP is entered
    $(document).on('input', '#cod_otp', function() {
        const otp = $(this).val().trim();
        $('#cod_verify_otp').prop('disabled', otp.length !== 6);
    });
    
    // Verify OTP
    $(document).on('click', '#cod_verify_otp', function(e) {
        e.preventDefault();
        
        const otp = $('#cod_otp').val().trim();
        const $btn = $(this);
        
        if (!otp || otp.length !== 6) {
            showMessage('otp', 'Please enter a valid 6-digit OTP', 'error');
            return;
        }
        
        $btn.prop('disabled', true).text('Verifying...');
        
        $.ajax({
            url: codVerifier.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cod_verify_otp',
                otp: otp,
                nonce: codVerifier.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('otp', response.data, 'success');
                    otpVerified = true;
                    $btn.text('✓ Verified').addClass('verified');
                    updateVerificationStatus();
                } else {
                    showMessage('otp', response.data, 'error');
                    $btn.prop('disabled', false).text('Verify');
                }
            },
            error: function() {
                showMessage('otp', 'Failed to verify OTP. Please try again.', 'error');
                $btn.prop('disabled', false).text('Verify');
            }
        });
    });
    
    // Token Payment
    $(document).on('click', '#cod_pay_token', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: codVerifier.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cod_token_payment',
                nonce: codVerifier.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('token', response.data, 'success');
                    tokenPaid = true;
                    $('#cod_token_confirmed').prop('checked', true);
                    $btn.text('✓ Payment Complete').addClass('verified');
                    updateVerificationStatus();
                } else {
                    showMessage('token', response.data, 'error');
                    $btn.prop('disabled', false).text('Pay ₹1 Token');
                }
            },
            error: function() {
                showMessage('token', 'Payment failed. Please try again.', 'error');
                $btn.prop('disabled', false).text('Pay ₹1 Token');
            }
        });
    });
    
    function showMessage(type, message, status) {
        const $messageElement = $('#cod_' + type + '_message');
        $messageElement.removeClass('success error').addClass(status).html(message).show();
    }
    
    // STRICT VALIDATION - Prevent order placement without verification
    function preventOrderPlacement(e) {
        const selectedMethod = getSelectedPaymentMethod();
        
        if (selectedMethod === 'cod' || selectedMethod === 'cash_on_delivery') {
            let errors = [];
            
            if (codVerifier.enableOTP === '1' && !otpVerified) {
                errors.push('• Please verify your phone number with OTP');
            }
            
            if (codVerifier.enableToken === '1' && (!tokenPaid || !$('#cod_token_confirmed').is(':checked'))) {
                errors.push('• Please complete the ₹1 token payment and confirm');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();
                
                // Show verification box if hidden
                showVerificationBox();
                
                const errorMessage = 'COD Verification Required:\n\n' + errors.join('\n');
                alert(errorMessage);
                
                // Add WooCommerce notice
                $('.woocommerce-notices-wrapper').html('<div class="woocommerce-error" role="alert">' + errors.join('<br>') + '</div>');
                
                // Scroll to verification section
                const $wrapper = $('#cod-verifier-wrapper-active');
                if ($wrapper.length && $wrapper.is(':visible')) {
                    $('html, body').animate({
                        scrollTop: $wrapper.offset().top - 100
                    }, 500);
                }
                
                console.log('COD Verifier: Order placement prevented due to missing verification');
                return false;
            }
        }
        
        console.log('COD Verifier: Order placement allowed');
        return true;
    }
    
    // Multiple validation points to prevent order submission
    $(document).on('click', '#place_order, .wc-block-components-checkout-place-order-button, button[type="submit"]', function(e) {
        console.log('COD Verifier: Order placement attempted via click');
        if (!preventOrderPlacement(e)) {
            return false;
        }
    });
    
    $(document).on('submit', 'form.checkout, form.wc-block-checkout__form, form[name="checkout"]', function(e) {
        console.log('COD Verifier: Form submission attempted');
        if (!preventOrderPlacement(e)) {
            return false;
        }
    });
    
    // WooCommerce specific events
    $(document).on('checkout_place_order', function(e) {
        console.log('COD Verifier: WooCommerce checkout_place_order event');
        return preventOrderPlacement({ 
            preventDefault: function() { e.preventDefault(); }, 
            stopImmediatePropagation: function() { e.stopImmediatePropagation(); }, 
            stopPropagation: function() { e.stopPropagation(); } 
        });
    });
    
    $(document).on('checkout_place_order_cod', function(e) {
        console.log('COD Verifier: WooCommerce checkout_place_order_cod event');
        return preventOrderPlacement({ 
            preventDefault: function() { e.preventDefault(); }, 
            stopImmediatePropagation: function() { e.stopImmediatePropagation(); }, 
            stopPropagation: function() { e.stopPropagation(); } 
        });
    });
});
