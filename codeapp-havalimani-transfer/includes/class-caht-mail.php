<?php
/**
 * CAHT Mail System - SMTP Support
 */
/**
 * CAHT Mail System - SMTP Support
 */

if (!defined('ABSPATH')) exit;

// PHPMailer'ı yükle (WordPress'in kendi sürümünü kullan veya dahil et)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && !class_exists('PHPMailer')) {
    // WordPress 5.5+ yeni namespace
    if (file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
    } 
    // Eski WordPress sürümleri
    elseif (file_exists(ABSPATH . WPINC . '/class-phpmailer.php')) {
        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';
    }
}

class CAHT_Mail {
    
    /**
     * Initialize SMTP configuration
     */
    public static function init() {
        $smtp_enabled = get_option('caht_smtp_enabled', '0');
        if ($smtp_enabled === '1') {
            add_action('phpmailer_init', array(__CLASS__, 'configure_smtp'));
        }
    }
    
    /**
     * Configure PHPMailer for SMTP
     */
    public static function configure_smtp($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = get_option('caht_smtp_host');
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = intval(get_option('caht_smtp_port', 587));
        $phpmailer->Username = get_option('caht_smtp_username');
        $phpmailer->Password = get_option('caht_smtp_password');
        
        $encryption = get_option('caht_smtp_encryption', 'tls');
        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } else {
            $phpmailer->SMTPSecure = '';
        }
        
        $phpmailer->From = get_option('caht_smtp_from_email', get_option('admin_email'));
        $phpmailer->FromName = get_option('caht_smtp_from_name', get_bloginfo('name'));
    }
    
    /**
     * Send email with HTML template
     */
    public static function send($to, $subject, $content, $attachments = array()) {
        $from_email = get_option('caht_smtp_from_email', get_option('admin_email'));
        $from_name = get_option('caht_smtp_from_name', get_bloginfo('name'));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
        );
        
        $body = self::get_email_template($subject, $content);
        
        return wp_mail($to, $subject, $body, $headers, $attachments);
    }
    
    /**
     * Get modern email template
     */
    private static function get_email_template($title, $content) {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $logo_url = get_site_icon_url(128, get_template_directory_uri() . '/assets/images/logo.png');
        
        // Fallback logo: customizer'dan site logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
        
        $year = date('Y');
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; padding: 10px !important; }
            .header { padding: 25px 20px !important; }
            .content { padding: 25px 20px !important; }
            .footer { padding: 20px !important; }
            h1 { font-size: 22px !important; }
            .detail-row { flex-direction: column !important; }
            .detail-label { width: 100% !important; margin-bottom: 4px; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding: 20px 10px;">
                <table role="presentation" class="container" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                    
                    <!-- HEADER -->
                    <tr>
                        <td class="header" style="background:linear-gradient(135deg,#1b510d 0%,#237e12 50%,#2d8a5a 100%);padding:40px 35px;text-align:center;">
                            ' . (!empty($logo_url) ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-height:50px;margin-bottom:15px;border-radius:8px;background:#fff;padding:5px;">' : '') . '
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:800;letter-spacing:-0.5px;">' . esc_html($site_name) . '</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:8px 0 0 0;font-size:14px;">Premium Airport Transfer Service</p>
                        </td>
                    </tr>
                    
                    <!-- CONTENT -->
                    <tr>
                        <td class="content" style="padding:35px 35px 25px 35px;">
                            ' . $content . '
                        </td>
                    </tr>
                    
                    <!-- FOOTER -->
                    <tr>
                        <td class="footer" style="background:#1a1a1a;padding:30px 35px;text-align:center;">
                            <p style="color:rgba(255,255,255,0.6);margin:0 0 8px 0;font-size:13px;">
                                ' . esc_html($site_name) . ' &copy; ' . $year . '
                            </p>
                            <p style="color:rgba(255,255,255,0.4);margin:0;font-size:12px;">
                                This is an automated email. Please do not reply to this message.
                            </p>
                            <p style="margin:15px 0 0 0;">
                                <a href="' . esc_url($site_url) . '" style="display:inline-block;padding:10px 24px;background:#237e12;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;">Visit Our Website</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Send reservation confirmation to customer
     */
    public static function send_customer_confirmation($rezervasyon_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $rez = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, a.ad as arac_ad, a.resim as arac_resim 
             FROM {$prefix}rezervasyonlar r
             LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id
             WHERE r.id = %d",
            $rezervasyon_id
        ));
        
        if (!$rez) return false;
        
        $para_birimi = $rez->para_birimi;
        if ($para_birimi === 'USD') {
            $sembol = '$';
        } elseif ($para_birimi === 'EUR') {
            $sembol = '€';
        } else {
            $para_birimi = 'TL';
            $sembol = '₺';
        }
        
        $payment_methods = array(
            'kredi_karti' => 'Credit Card',
            'nakit' => 'Cash',
            'bacs' => 'Bank Transfer',
            'cod' => 'Cash on Delivery',
            'cheque' => 'Cheque',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
        );
        
        $gidis_tarih = date('d.m.Y H:i', strtotime($rez->gidis_tarih));
        $donus_tarih = $rez->donus_tarih ? date('d.m.Y H:i', strtotime($rez->donus_tarih)) : null;
        
        $content = '
        <div style="text-align:center;margin-bottom:30px;">
            <div style="width:70px;height:70px;background:linear-gradient(135deg,#1b510d,#237e12);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:15px;">
                <span style="color:#fff;font-size:32px;">✓</span>
            </div>
            <h2 style="color:#1a1a1a;margin:0 0 8px 0;font-size:24px;font-weight:700;">Reservation Received!</h2>
            <p style="color:#6b7280;margin:0;font-size:15px;line-height:1.6;">Thank you for choosing us. We will contact you as soon as possible to confirm your transfer details.</p>
        </div>
        
        <div style="background:#f9fafb;border-radius:12px;padding:25px;margin-bottom:25px;border:1px solid #e5e7eb;">
            <h3 style="margin:0 0 20px 0;color:#1a1a1a;font-size:16px;font-weight:700;">
                <span style="display:inline-block;width:28px;height:28px;background:#237e12;color:#fff;border-radius:50%;text-align:center;line-height:28px;font-size:13px;margin-right:8px;">1</span>
                Reservation Details
            </h3>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Reservation No</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:700;">#' . intval($rez->id) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Vehicle</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->arac_ad) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Route</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->nereden) . ' → ' . esc_html($rez->nereye) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Date</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($gidis_tarih) . '</span>
            </div>';
            
        if ($donus_tarih) {
            $content .= '
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Return</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($donus_tarih) . '</span>
            </div>';
        }
        
        $content .= '
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Passengers</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . intval($rez->kisi_sayisi) . ' Person</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Payment</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($payment_methods[$rez->odeme_yontemi] ?? ucfirst($rez->odeme_yontemi)) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Status</span>
                <span style="display:inline-block;padding:4px 14px;background:#fef3c7;color:#92400e;border-radius:20px;font-size:12px;font-weight:700;">Pending Confirmation</span>
            </div>
        </div>
        
        <div style="background:linear-gradient(135deg,#1a1a1a,#333);border-radius:12px;padding:25px;text-align:center;margin-bottom:25px;">
            <p style="color:rgba(255,255,255,0.7);margin:0 0 5px 0;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Total Amount</p>
            <p style="color:#fff;margin:0;font-size:36px;font-weight:800;">' . esc_html($sembol . number_format($rez->secilen_fiyat, 2)) . '</p>
            <p style="color:rgba(255,255,255,0.5);margin:5px 0 0 0;font-size:13px;">' . esc_html($para_birimi) . '</p>
        </div>
        
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:20px;margin-bottom:25px;">
            <p style="color:#166534;margin:0;font-size:14px;line-height:1.6;">
                <strong style="display:block;margin-bottom:5px;"><i class="fas fa-headset" style="margin-right:6px;"></i>We Will Contact You Shortly</strong>
                Our customer service team will reach out to you within 15 minutes to confirm your reservation and provide driver information.
            </p>
        </div>
        
        <div style="text-align:center;">
            <a href="' . esc_url(home_url('/transfer-detay/?rezervasyon_id=' . $rez->id)) . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#1b510d,#237e12);color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;box-shadow:0 4px 15px rgba(35,126,18,0.3);">View Reservation Details</a>
        </div>';
        
        $subject = 'Your Transfer Reservation #' . $rez->id . ' - ' . get_bloginfo('name');
        
        return self::send($rez->eposta, $subject, $content);
    }
    
    /**
     * Send notification to admin
     */
    public static function send_admin_notification($rezervasyon_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $rez = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, a.ad as arac_ad 
             FROM {$prefix}rezervasyonlar r
             LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id
             WHERE r.id = %d",
            $rezervasyon_id
        ));
        
        if (!$rez) return false;
        
        // Admin email - ilk admin kullanıcısını bul
        $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
        $admin_email = !empty($admin_users) ? $admin_users[0]->user_email : get_option('admin_email');
        
        $para_birimi = $rez->para_birimi;
        $sembol = ($para_birimi === 'USD') ? '$' : (($para_birimi === 'EUR') ? '€' : '₺');
        
        $payment_methods = array(
            'kredi_karti' => 'Credit Card',
            'nakit' => 'Cash',
            'bacs' => 'Bank Transfer',
            'cod' => 'Cash on Delivery',
            'cheque' => 'Cheque',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
        );
        
        $gidis_tarih = date('d.m.Y H:i', strtotime($rez->gidis_tarih));
        
        $content = '
        <div style="text-align:center;margin-bottom:30px;">
            <div style="width:70px;height:70px;background:linear-gradient(135deg,#dc2626,#ef4444);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:15px;">
                <span style="color:#fff;font-size:28px;">!</span>
            </div>
            <h2 style="color:#1a1a1a;margin:0 0 8px 0;font-size:24px;font-weight:700;">New Reservation Alert!</h2>
            <p style="color:#6b7280;margin:0;font-size:15px;">A new transfer reservation has been received.</p>
        </div>
        
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:25px;text-align:center;">
            <p style="color:#991b1b;margin:0;font-size:14px;font-weight:600;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                Action Required: Please confirm this reservation with the customer.
            </p>
        </div>
        
        <div style="background:#f9fafb;border-radius:12px;padding:25px;margin-bottom:25px;border:1px solid #e5e7eb;">
            <h3 style="margin:0 0 20px 0;color:#1a1a1a;font-size:16px;font-weight:700;">Reservation Details</h3>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Reservation No</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:700;">#' . intval($rez->id) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Customer</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->yolcu_ad . ' ' . $rez->yolcu_soyad) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Phone</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->telefon) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Email</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->eposta) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Vehicle</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->arac_ad) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Route</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($rez->nereden) . ' → ' . esc_html($rez->nereye) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Date</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($gidis_tarih) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Passengers</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . intval($rez->kisi_sayisi) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Payment</span>
                <span style="color:#1a1a1a;font-size:14px;font-weight:600;">' . esc_html($payment_methods[$rez->odeme_yontemi] ?? ucfirst($rez->odeme_yontemi)) . '</span>
            </div>
            
            <div class="detail-row" style="display:flex;padding:10px 0;">
                <span class="detail-label" style="width:140px;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;">Total</span>
                <span style="color:#237e12;font-size:16px;font-weight:800;">' . esc_html($sembol . number_format($rez->secilen_fiyat, 2)) . '</span>
            </div>
        </div>
        
        <div style="text-align:center;">
            <a href="' . esc_url(admin_url('admin.php?page=caht-rezervasyonlar')) . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#1b510d,#237e12);color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;box-shadow:0 4px 15px rgba(35,126,18,0.3);">View in Admin Panel</a>
        </div>';
        
        $subject = '[NEW] Transfer Reservation #' . $rez->id . ' - ' . esc_html($rez->yolcu_ad . ' ' . $rez->yolcu_soyad);
        
        return self::send($admin_email, $subject, $content);
    }
}

// Initialize
add_action('init', array('CAHT_Mail', 'init'));