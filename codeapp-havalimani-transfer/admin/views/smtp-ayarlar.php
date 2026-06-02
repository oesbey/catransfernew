<?php
/**
 * SMTP Ayarları Sayfası - AJAX Test Maili ile
 */
if (!defined('ABSPATH')) exit;

// Mevcut ayarları çek
$smtp_host = get_option('caht_smtp_host', '');
$smtp_port = get_option('caht_smtp_port', '587');
$smtp_encryption = get_option('caht_smtp_encryption', 'tls');
$smtp_username = get_option('caht_smtp_username', '');
$smtp_password = get_option('caht_smtp_password', '');
$smtp_from_name = get_option('caht_smtp_from_name', get_bloginfo('name'));
$smtp_from_email = get_option('caht_smtp_from_email', get_option('admin_email'));
$smtp_enabled = get_option('caht_smtp_enabled', '0');

$test_result = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '';
$test_reason = isset($_GET['reason']) ? sanitize_text_field($_GET['reason']) : '';
$saved = isset($_GET['saved']) ? true : false;

// WordPress PHPMailer durumu
$wp_version = get_bloginfo('version');
$phpmailer_available = version_compare($wp_version, '5.5', '>=') || class_exists('PHPMailer\PHPMailer\PHPMailer') || class_exists('PHPMailer');
?>

<style>
.caht-smtp-wrap { max-width: 900px; }
.caht-smtp-header { margin-bottom: 25px; }
.caht-smtp-header h2 { margin: 0 0 8px 0; font-size: 24px; color: #1a1a1a; }
.caht-smtp-header p { margin: 0; color: #6b7280; font-size: 14px; }

.caht-smtp-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
}
.caht-smtp-card h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.caht-form-row { display: flex; gap: 20px; margin-bottom: 15px; }
.caht-form-group { flex: 1; }
.caht-form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}
.caht-form-group input,
.caht-form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
}
.caht-form-group input:focus,
.caht-form-group select:focus {
    outline: none;
    border-color: #237e12;
    box-shadow: 0 0 0 3px rgba(35,126,18,0.1);
}
.caht-form-group .hint {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #9ca3af;
}

.caht-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.caht-btn-primary {
    background: linear-gradient(135deg, #1b510d, #237e12);
    color: #fff;
}
.caht-btn-primary:hover {
    box-shadow: 0 4px 15px rgba(35,126,18,0.3);
    transform: translateY(-1px);
}
.caht-btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}
.caht-btn-secondary:hover {
    background: #e5e7eb;
}
.caht-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.caht-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}
.caht-alert-success {
    background: #d1fae5;
    border: 1px solid #86efac;
    color: #065f46;
}
.caht-alert-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.caht-alert-warning {
    background: #fef3c7;
    border: 1px solid #fde68a;
    color: #92400e;
}
.caht-alert i { font-size: 18px; margin-top: 2px; }

.caht-toggle-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.caht-toggle-switch {
    width: 44px;
    height: 24px;
    background: #d1d5db;
    border-radius: 12px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s;
}
.caht-toggle-switch.active {
    background: #237e12;
}
.caht-toggle-switch::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}
.caht-toggle-switch.active::after {
    transform: translateX(20px);
}
.caht-toggle-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.caht-debug-box {
    background: #1e293b;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    font-family: 'Courier New', monospace;
}
.caht-debug-box pre {
    color: #e2e8f0;
    font-size: 12px;
    line-height: 1.6;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-all;
}
.caht-debug-box .debug-label {
    color: #94a3b8;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: block;
}

.caht-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.caht-status-indicator.ok {
    background: #d1fae5;
    color: #065f46;
}
.caht-status-indicator.error {
    background: #fee2e2;
    color: #991b1b;
}
.caht-status-indicator.warning {
    background: #fef3c7;
    color: #92400e;
}
.caht-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.caht-status-indicator.ok .caht-status-dot {
    background: #10b981;
}
.caht-status-indicator.error .caht-status-dot {
    background: #ef4444;
}
.caht-status-indicator.warning .caht-status-dot {
    background: #f59e0b;
}

/* AJAX Loading */
.caht-loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-top-color: #237e12;
    border-radius: 50%;
    animation: caht-spin 0.8s linear infinite;
}
@keyframes caht-spin {
    to { transform: rotate(360deg); }
}

#ajax-test-result {
    display: none;
    margin-top: 15px;
    padding: 15px 20px;
    border-radius: 10px;
    font-size: 14px;
}
#ajax-test-result.success {
    display: block;
    background: #d1fae5;
    border: 1px solid #86efac;
    color: #065f46;
}
#ajax-test-result.error {
    display: block;
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
</style>

<div class="caht-smtp-wrap">

    <div class="caht-smtp-header">
        <h2><i class="fas fa-envelope"></i> SMTP Settings</h2>
        <p>Configure your email server settings and test email delivery.</p>
    </div>

    <?php if ($saved): ?>
    <div class="caht-alert caht-alert-success">
        <i class="fas fa-check-circle"></i>
        <span><strong>Settings saved successfully!</strong></span>
    </div>
    <?php endif; ?>

    <!-- SYSTEM STATUS -->
    <div class="caht-smtp-card">
        <h3><i class="fas fa-heartbeat"></i> System Status</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:15px;">
            
            <div style="background:#f9fafb;padding:15px;border-radius:10px;border:1px solid #e5e7eb;">
                <div class="caht-status-indicator <?php echo $phpmailer_available ? 'ok' : 'warning'; ?>">
                    <span class="caht-status-dot"></span>
                    PHPMailer <?php echo $phpmailer_available ? 'Available (WP ' . esc_html($wp_version) . ')' : 'Check Required'; ?>
                </div>
            </div>
            
            <div style="background:#f9fafb;padding:15px;border-radius:10px;border:1px solid #e5e7eb;">
                <div class="caht-status-indicator <?php echo function_exists('wp_mail') ? 'ok' : 'error'; ?>">
                    <span class="caht-status-dot"></span>
                    wp_mail() <?php echo function_exists('wp_mail') ? 'Available' : 'Not Found'; ?>
                </div>
            </div>
            
            <div style="background:#f9fafb;padding:15px;border-radius:10px;border:1px solid #e5e7eb;">
                <div class="caht-status-indicator <?php echo !empty($smtp_host) && !empty($smtp_username) ? 'ok' : 'warning'; ?>">
                    <span class="caht-status-dot"></span>
                    SMTP Config <?php echo !empty($smtp_host) && !empty($smtp_username) ? 'Complete' : 'Incomplete'; ?>
                </div>
            </div>
            
            <div style="background:#f9fafb;padding:15px;border-radius:10px;border:1px solid #e5e7eb;">
                <div class="caht-status-indicator <?php echo $smtp_enabled === '1' ? 'ok' : 'warning'; ?>">
                    <span class="caht-status-dot"></span>
                    SMTP <?php echo $smtp_enabled === '1' ? 'Enabled' : 'Disabled'; ?>
                </div>
            </div>
            
        </div>
    </div>

    <!-- AYARLAR FORM -->
    <form method="post" action="" id="smtp-settings-form">
        <?php wp_nonce_field('caht_nonce', '_wpnonce'); ?>
        <input type="hidden" name="caht_action" value="smtp_kaydet">

        <div class="caht-smtp-card">
            <h3><i class="fas fa-power-off"></i> SMTP Status</h3>
            <div class="caht-toggle-row">
                <div class="caht-toggle-switch <?php echo $smtp_enabled === '1' ? 'active' : ''; ?>" 
                     onclick="this.classList.toggle('active'); document.getElementById('smtp-enabled-input').value = this.classList.contains('active') ? '1' : '0';">
                </div>
                <span class="caht-toggle-label">Enable SMTP Email Delivery</span>
                <input type="hidden" name="smtp_enabled" id="smtp-enabled-input" value="<?php echo esc_attr($smtp_enabled); ?>">
            </div>
        </div>

        <div class="caht-smtp-card">
            <h3><i class="fas fa-server"></i> Server Configuration</h3>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>SMTP Host *</label>
                    <input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" placeholder="smtp.gmail.com" required>
                    <span class="hint">Your SMTP server address</span>
                </div>
                <div class="caht-form-group">
                    <label>SMTP Port *</label>
                    <input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" placeholder="587" required>
                    <span class="hint">Common: 587 (TLS), 465 (SSL), 25 (none)</span>
                </div>
            </div>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>Encryption *</label>
                    <select name="smtp_encryption" id="smtp_encryption" required>
                        <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS (Recommended)</option>
                        <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
                        <option value="none" <?php selected($smtp_encryption, 'none'); ?>>None</option>
                    </select>
                </div>
                <div class="caht-form-group">
                    <label>Authentication</label>
                    <select disabled style="opacity:0.6;">
                        <option value="yes" selected>Yes (Required)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="caht-smtp-card">
            <h3><i class="fas fa-user-lock"></i> Authentication</h3>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>Username *</label>
                    <input type="text" name="smtp_username" id="smtp_username" value="<?php echo esc_attr($smtp_username); ?>" placeholder="your@email.com" required>
                    <span class="hint">Usually your full email address</span>
                </div>
                <div class="caht-form-group">
                    <label>Password *</label>
                    <input type="password" name="smtp_password" id="smtp_password" value="<?php echo esc_attr($smtp_password); ?>" placeholder="••••••••" required>
                    <span class="hint">For Gmail, use App Password</span>
                </div>
            </div>
        </div>

        <div class="caht-smtp-card">
            <h3><i class="fas fa-paper-plane"></i> Sender Information</h3>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>From Name</label>
                    <input type="text" name="smtp_from_name" id="smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
                <div class="caht-form-group">
                    <label>From Email</label>
                    <input type="email" name="smtp_from_email" id="smtp_from_email" value="<?php echo esc_attr($smtp_from_email); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                </div>
            </div>
        </div>

        <div style="margin-top: 10px; display: flex; gap: 10px;">
            <button type="submit" class="caht-btn caht-btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="button" id="btn-test-email" class="caht-btn caht-btn-secondary">
                <i class="fas fa-paper-plane"></i> <span id="btn-test-text">Send Test Email</span>
            </button>
        </div>
        
        <!-- AJAX Test Sonucu -->
        <div id="ajax-test-result"></div>
    </form>

    <!-- DEBUG INFO -->
    <?php if (current_user_can('manage_options')): ?>
    <div class="caht-smtp-card" style="margin-top:25px;">
        <h3><i class="fas fa-bug"></i> Debug Information</h3>
        <div class="caht-debug-box">
            <span class="debug-label">Current Configuration</span>
            <pre><?php
                $debug = array(
                    'SMTP Enabled' => $smtp_enabled === '1' ? 'Yes' : 'No',
                    'Host' => $smtp_host ?: '(empty)',
                    'Port' => $smtp_port,
                    'Encryption' => strtoupper($smtp_encryption),
                    'Username' => $smtp_username ? substr($smtp_username, 0, 3) . '***@***' : '(empty)',
                    'Password' => $smtp_password ? '******** (' . strlen($smtp_password) . ' chars)' : '(empty)',
                    'From Name' => $smtp_from_name,
                    'From Email' => $smtp_from_email,
                    'WordPress Version' => $wp_version,
                    'PHP Version' => PHP_VERSION,
                    'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'PHPMailer Status' => $phpmailer_available ? 'Available (loaded by WordPress)' : 'Not detected',
                    'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF',
                );
                foreach ($debug as $k => $v) {
                    echo esc_html($k) . ': ' . esc_html($v) . "\n";
                }
            ?></pre>
        </div>
        <p style="color:#6b7280;font-size:12px;margin-top:10px;">
            <i class="fas fa-info-circle"></i> 
            If test emails fail, check your server's error logs or enable WordPress debug mode in wp-config.php.
        </p>
    </div>
    <?php endif; ?>

</div>

<script>
jQuery(document).ready(function($) {
    $('#btn-test-email').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $result = $('#ajax-test-result');
        var $btnText = $('#btn-test-text');
        
        // Önce ayarları kaydet (AJAX ile değil, form submit ile)
        // Ama kullanıcıya soralım
        if (!confirm('Send test email to <?php echo esc_js($smtp_from_email); ?>?\n\nNote: Make sure you saved settings first!')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $btnText.html('<span class="caht-loading"></span> Sending...');
        $result.removeClass('success error').hide();
        
        $.ajax({
            url: caht_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'caht_smtp_test_ajax',
                nonce: caht_admin.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $btnText.html('<i class="fas fa-paper-plane"></i> Send Test Email');
                
                if (response.success) {
                    $result.addClass('success').html('<i class="fas fa-check-circle"></i> <strong>Success!</strong> ' + response.data.message).show();
                } else {
                    $result.addClass('error').html('<i class="fas fa-exclamation-circle"></i> <strong>Failed!</strong> ' + (response.data.message || 'Unknown error')).show();
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false);
                $btnText.html('<i class="fas fa-paper-plane"></i> Send Test Email');
                
                var msg = 'AJAX Error: ' + status;
                if (xhr.responseText) {
                    msg += '<br><small>' + xhr.responseText.substring(0, 200) + '</small>';
                }
                $result.addClass('error').html('<i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> ' + msg).show();
            }
        });
    });
});
</script>