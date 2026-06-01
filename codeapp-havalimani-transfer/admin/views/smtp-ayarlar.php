<?php
/**
 * SMTP Ayarları Sayfası - GÜNCELLENMİŞ (Hata detayları ile)
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

// WordPress mail fonksiyonu testi
$wp_mail_test = false;
$wp_mail_error = '';
?>

<style>
/* ... önceki CSS aynı ... */
/* Aşağıdaki yeni stilleri ekle */

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
</style>

<div class="caht-smtp-wrap">

    <div class="caht-smtp-header">
        <h2><i class="fas fa-envelope"></i> SMTP Settings</h2>
        <p>Configure your email server settings and test email delivery.</p>
    </div>

    <?php if ($test_result === 'success'): ?>
    <div class="caht-alert caht-alert-success">
        <i class="fas fa-check-circle"></i>
        <span><strong>Test email sent successfully!</strong> Please check your inbox at <?php echo esc_html($smtp_from_email); ?>.</span>
    </div>
    <?php elseif ($test_result === 'error'): ?>
    <div class="caht-alert caht-alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><strong>Failed to send test email.</strong> 
        <?php if (!empty($test_reason) && $test_reason !== 'empty'): ?>
            <br><small style="opacity:0.8;">Error: <?php echo esc_html(urldecode($test_reason)); ?></small>
        <?php elseif ($test_reason === 'empty'): ?>
            <br><small style="opacity:0.8;">Please fill in all required SMTP fields (Host, Username, Password).</small>
        <?php else: ?>
            <br><small style="opacity:0.8;">Unknown error. Check server error logs for details.</small>
        <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- SYSTEM STATUS -->
    <div class="caht-smtp-card">
        <h3><i class="fas fa-heartbeat"></i> System Status</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:15px;">
            
            <div style="background:#f9fafb;padding:15px;border-radius:10px;border:1px solid #e5e7eb;">
                <div class="caht-status-indicator <?php echo (class_exists('PHPMailer\PHPMailer\PHPMailer') || class_exists('PHPMailer')) ? 'ok' : 'warning'; ?>">
                    <span class="caht-status-dot"></span>
                    PHPMailer <?php echo (class_exists('PHPMailer\PHPMailer\PHPMailer') || class_exists('PHPMailer')) ? 'Available' : 'Not Found'; ?>
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

    <form method="post" action="">
        <?php wp_nonce_field('caht_nonce', '_wpnonce'); ?>
        <input type="hidden" name="caht_action" value="smtp_kaydet">

        <!-- ... geri kalan form aynı, önceki kod ile birebir ... -->
        
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
                    <input type="text" name="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" placeholder="smtp.gmail.com" required>
                    <span class="hint">Your SMTP server address</span>
                </div>
                <div class="caht-form-group">
                    <label>SMTP Port *</label>
                    <input type="number" name="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" placeholder="587" required>
                    <span class="hint">Common: 587 (TLS), 465 (SSL), 25 (none)</span>
                </div>
            </div>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>Encryption *</label>
                    <select name="smtp_encryption" required>
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
                    <input type="text" name="smtp_username" value="<?php echo esc_attr($smtp_username); ?>" placeholder="your@email.com" required>
                    <span class="hint">Usually your full email address</span>
                </div>
                <div class="caht-form-group">
                    <label>Password *</label>
                    <input type="password" name="smtp_password" value="<?php echo esc_attr($smtp_password); ?>" placeholder="••••••••" required>
                    <span class="hint">For Gmail, use App Password</span>
                </div>
            </div>
        </div>

        <div class="caht-smtp-card">
            <h3><i class="fas fa-paper-plane"></i> Sender Information</h3>
            <div class="caht-form-row">
                <div class="caht-form-group">
                    <label>From Name</label>
                    <input type="text" name="smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
                <div class="caht-form-group">
                    <label>From Email</label>
                    <input type="email" name="smtp_from_email" value="<?php echo esc_attr($smtp_from_email); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                </div>
            </div>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit" class="caht-btn caht-btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="submit" formaction="<?php echo admin_url('admin-post.php?action=caht_smtp_test'); ?>" 
                    class="caht-btn caht-btn-secondary" 
                    onclick="return confirm('Send test email to <?php echo esc_js($smtp_from_email); ?>?')">
                <i class="fas fa-paper-plane"></i> Send Test Email
            </button>
        </div>
    </form>

    <!-- DEBUG INFO (sadece admin görür) -->
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
                    'WordPress Version' => get_bloginfo('version'),
                    'PHP Version' => PHP_VERSION,
                    'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
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