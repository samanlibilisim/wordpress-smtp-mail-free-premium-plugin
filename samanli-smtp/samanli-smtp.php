<?php
/**
 * Plugin Name: Samanlı SMTP Mail
 * Description: samanlibilisim.com.tr için gelişmiş SMTP ayarları - tam özellikli, modern admin arayüzü ve test mail gönderimi.
 * Version: 1.0.1
 * Author: Samanlı Bilişim
 * Text Domain: samanli-smtp
 */

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

class Samanli_SMTP_Plugin {
    const OPTION_KEY = 'samanli_smtp_options_v1';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_samanli_send_test', [$this, 'ajax_send_test']);

        // ÇEKİRDEK FİLTRELERİ: WordPress'in localhost hatasını engellemek için araya giriyoruz
        add_filter('wp_mail_from', [$this, 'filter_wp_mail_from']);
        add_filter('wp_mail_from_name', [$this, 'filter_wp_mail_from_name']);
    }

    // Gönderici e-posta adresini filtreler
    public function filter_wp_mail_from($original_email) {
        $opts = get_option(self::OPTION_KEY, []);
        $from = !empty($opts['from_email']) ? $opts['from_email'] : '';
        // Eğer geçerli bir adres yoksa varsayılan olarak bunu kullan
        return is_email($from) ? $from : 'iletisim@samanlibilisim.com.tr';
    }

    // Gönderici adını filtreler
    public function filter_wp_mail_from_name($original_name) {
        $opts = get_option(self::OPTION_KEY, []);
        return !empty($opts['from_name']) ? $opts['from_name'] : 'Samanlibilisim.com.tr';
    }

    public function add_admin_menu() {
        add_options_page(
                __('Samanlı SMTP', 'samanli-smtp'),
                __('Samanlı SMTP', 'samanli-smtp'),
                'manage_options',
                'samanli-smtp',
                [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        $out = [];
        $out['host'] = isset($input['host']) ? sanitize_text_field($input['host']) : '';
        $out['port'] = isset($input['port']) ? absint($input['port']) : 587;
        $out['encryption'] = in_array($input['encryption'] ?? '', ['none','ssl','tls']) ? $input['encryption'] : 'tls';
        $out['auth'] = !empty($input['auth']) ? 1 : 0;
        $out['username'] = isset($input['username']) ? sanitize_text_field($input['username']) : '';
        $out['password'] = isset($input['password']) ? sanitize_text_field($input['password']) : '';
        $out['from_email'] = isset($input['from_email']) ? sanitize_email($input['from_email']) : get_option('admin_email');
        $out['from_name'] = isset($input['from_name']) ? sanitize_text_field($input['from_name']) : get_bloginfo('name');
        $out['return_path'] = !empty($input['return_path']) ? 1 : 0;
        $out['auto_tls'] = isset($input['auto_tls']) ? (bool) $input['auto_tls'] : true;
        $out['allow_self_signed'] = isset($input['allow_self_signed']) ? (bool) $input['allow_self_signed'] : false;
        $out['timeout'] = isset($input['timeout']) ? absint($input['timeout']) : 15;
        return $out;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $opts = get_option(self::OPTION_KEY, []);
        $defaults = [
                'host' => '', 'port' => 587, 'encryption' => 'tls', 'auth' => 1, 'username'=>'', 'password'=>'',
                'from_email' => get_option('admin_email'), 'from_name' => get_bloginfo('name'), 'return_path' => 0,
                'auto_tls' => true, 'allow_self_signed' => false, 'timeout' => 15
        ];
        $opts = wp_parse_args($opts, $defaults);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Samanlı SMTP Ayarları', 'samanli-smtp'); ?></h1>
            <p style="color:#666; max-width:600px;">Sunucunuzdan e-posta göndermek için ayrıntılı SMTP yapılandırması. Güvenlik için bu sayfaya yalnızca güvenilir yöneticiler erişmelidir.</p>

            <form method="post" action="options.php" style="max-width:600px;">
                <?php settings_fields(self::OPTION_KEY); ?>

                <div style="display:flex; gap:24px; flex-wrap:wrap; margin-top:18px;">
                    <div style="flex:1 1 420px; background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 20px rgba(18,38,63,0.06);">
                        <h2 style="margin-top:0;"><?php esc_html_e('SMTP Sunucu', 'samanli-smtp'); ?></h2>

                        <table class="form-table" role="presentation">
                            <tbody>
                            <tr>
                                <th scope="row"><label for="samanli_host"><?php esc_html_e('Sunucu (Host)', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[host]" id="samanli_host" type="text" value="<?php echo esc_attr($opts['host']); ?>" class="regular-text" placeholder="smtp.example.com"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="samanli_port"><?php esc_html_e('Port', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[port]" id="samanli_port" type="number" value="<?php echo esc_attr($opts['port']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Şifreleme', 'samanli-smtp'); ?></th>
                                <td>
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[encryption]" class="regular-text">
                                        <option value="none" <?php selected($opts['encryption'],'none'); ?>><?php esc_html_e('Yok', 'samanli-smtp'); ?></option>
                                        <option value="ssl" <?php selected($opts['encryption'],'ssl'); ?>><?php esc_html_e('SSL', 'samanli-smtp'); ?></option>
                                        <option value="tls" <?php selected($opts['encryption'],'tls'); ?>><?php esc_html_e('TLS', 'samanli-smtp'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Kimlik Doğrulama (SMTP Auth)', 'samanli-smtp'); ?></th>
                                <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[auth]" value="1" <?php checked($opts['auth'],1); ?>> <?php esc_html_e('Kullanıcı adı/şifre ile giriş yapılacak', 'samanli-smtp'); ?></label></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="samanli_username"><?php esc_html_e('Kullanıcı Adı', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[username]" id="samanli_username" type="text" value="<?php echo esc_attr($opts['username']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="samanli_password"><?php esc_html_e('Şifre', 'samanli-smtp'); ?></label></th>
                                <td>
                                    <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[password]" id="samanli_password" type="password" value="<?php echo esc_attr($opts['password']); ?>" class="regular-text" autocomplete="new-password">
                                    <p class="description"><?php esc_html_e('Not: Güvenlik için şifreli bağlantı (SSL/TLS) kullanın.', 'samanli-smtp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="samanli_timeout"><?php esc_html_e('Zaman Aşımı (saniye)', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[timeout]" id="samanli_timeout" type="number" value="<?php echo esc_attr($opts['timeout']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Özel SSL Ayarları', 'samanli-smtp'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[allow_self_signed]" value="1" <?php checked($opts['allow_self_signed'],true); ?>> <?php esc_html_e('Self-signed/Doğrulama hatalarını görmezden gel (geliştirme için)', 'samanli-smtp'); ?></label>
                                    <p class="description"><?php esc_html_e('Geliştirme/dahili sunucular için; üretimde kapatın.', 'samanli-smtp'); ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="flex:1 1 420px; background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 20px rgba(18,38,63,0.06);">
                        <h2 style="margin-top:0;"><?php esc_html_e('Gönderen & Gelişmiş', 'samanli-smtp'); ?></h2>

                        <table class="form-table" role="presentation">
                            <tbody>
                            <tr>
                                <th scope="row"><label for="samanli_from_email"><?php esc_html_e('Gönderen E-posta', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[from_email]" id="samanli_from_email" type="email" value="<?php echo esc_attr($opts['from_email']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="samanli_from_name"><?php esc_html_e('Gönderen Adı', 'samanli-smtp'); ?></label></th>
                                <td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[from_name]" id="samanli_from_name" type="text" value="<?php echo esc_attr($opts['from_name']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Return-Path', 'samanli-smtp'); ?></th>
                                <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[return_path]" value="1" <?php checked($opts['return_path'],1); ?>> <?php esc_html_e('Return-Path başlığını gönderen e-posta ile ayarla', 'samanli-smtp'); ?></label></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Otomatik TLS (AUTO_TLS)', 'samanli-smtp'); ?></th>
                                <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[auto_tls]" value="1" <?php checked($opts['auto_tls'],true); ?>> <?php esc_html_e('Sunucu TLS destekliyorsa otomatik olarak kullan', 'samanli-smtp'); ?></label></td>
                            </tr>
                            </tbody>
                        </table>

                        <p style="margin-top:18px;">
                            <?php submit_button(__('Ayarları Kaydet', 'samanli-smtp')); ?>
                        </p>

                    </div>
                </div>
            </form>

            <div style="max-width:564px; margin-top:24px; background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 20px rgba(18,38,63,0.06);">
                <h2><?php esc_html_e('Test E-posta Gönder', 'samanli-smtp'); ?></h2>
                <p class="description"><?php esc_html_e('Aşağıya bir e-posta adresi girip "Test Gönder" butonuna basın. Hata mesajı alırsanız hata ayrıntısı burada gösterilecektir.', 'samanli-smtp'); ?></p>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="samanli_test_to"><?php esc_html_e('Alıcı E-posta', 'samanli-smtp'); ?></label></th>
                        <td><input id="samanli_test_to" type="email" class="regular-text" placeholder="you@example.com"></td>
                    </tr>
                    </tbody>
                </table>

                <p>
                    <button id="samanli_test_send" class="button button-primary"><?php esc_html_e('Test Gönder', 'samanli-smtp'); ?></button>
                    <span id="samanli_test_status" style="margin-left:12px;"></span>
                </p>
            </div>
        </div>

        <script>
            (function(){
                const btn = document.getElementById('samanli_test_send');
                const status = document.getElementById('samanli_test_status');
                btn && btn.addEventListener('click', function(e){
                    e.preventDefault();
                    status.textContent = '';
                    const to = document.getElementById('samanli_test_to').value.trim();
                    if(!to){ status.textContent = 'Lütfen bir e-posta adresi girin.'; return; }
                    btn.disabled = true; btn.textContent = 'Gönderiliyor...';

                    const data = new FormData();
                    data.append('action', 'samanli_send_test');
                    data.append('test_to', to);
                    data.append('nonce', '<?php echo esc_js(wp_create_nonce('samanli_test_nonce')); ?>');

                    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    }).then(r => r.json()).then(function(res){
                        if(res.success){
                            status.style.color = 'green';
                            status.textContent = res.data || 'Test e-posta gönderildi.';
                        } else {
                            status.style.color = 'crimson';
                            status.textContent = res.data || 'Gönderim hatası.';
                        }
                        btn.disabled = false; btn.textContent = '<?php esc_html_e('Test Gönder', 'samanli-smtp'); ?>';
                    }).catch(function(err){
                        status.style.color = 'crimson';
                        status.textContent = err.message || 'Beklenmedik hata.';
                        btn.disabled = false; btn.textContent = '<?php esc_html_e('Test Gönder', 'samanli-smtp'); ?>';
                    });
                });
            })();
        </script>

        <style>
            .wrap h1{font-size:20px; margin-bottom:6px}
            input.regular-text, select { padding:8px; border-radius:6px; border:1px solid #ddd }
            .form-table th{ width:180px; vertical-align:middle }
        </style>
        <?php
    }

    public function configure_phpmailer($phpmailer) {
        if (!is_object($phpmailer)) return;
        $opts = get_option(self::OPTION_KEY, []);
        if (empty($opts['host'])) return;

        $secure = '';
        if ($opts['encryption'] === 'ssl') $secure = 'ssl';
        if ($opts['encryption'] === 'tls') $secure = 'tls';

        try {
            $phpmailer->isSMTP();
            $phpmailer->Host = $opts['host'];
            $phpmailer->Port = !empty($opts['port']) ? intval($opts['port']) : 587;
            $phpmailer->SMTPSecure = $secure;
            $phpmailer->SMTPAutoTLS = !empty($opts['auto_tls']);
            $phpmailer->Timeout = !empty($opts['timeout']) ? intval($opts['timeout']) : 15;

            if (!empty($opts['auth'])) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $opts['username'];
                $phpmailer->Password = $opts['password'];
            } else {
                $phpmailer->SMTPAuth = false;
            }

            // ---- EKLENEN KISIM: Göndericiyi (From) PHPMailer seviyesinde ZORLA ----
            // Bu sayede formlarda manuel From başlığı yazmanıza gerek kalmaz,
            // WordPress'in diğer eklentileri de hata vermez.
            $from_email = !empty($opts['from_email']) ? $opts['from_email'] : $opts['username'];
            $from_name  = !empty($opts['from_name']) ? $opts['from_name'] : get_bloginfo('name');
            $phpmailer->setFrom($from_email, $from_name);
            // ------------------------------------------------------------------------

            if (!empty($opts['return_path'])) {
                $phpmailer->Sender = $phpmailer->From;
            }

            if (!empty($opts['allow_self_signed'])) {
                $phpmailer->SMTPOptions = [
                        'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                        ]
                ];
            }
        } catch (Exception $e) {
            // Hataları ajax fonksiyonuna bırak
        }
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'settings_page_samanli-smtp') return;
    }

    public function ajax_send_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Yetkiniz yok.', 'samanli-smtp'));
        }
        check_ajax_referer('samanli_test_nonce', 'nonce');

        $to = isset($_POST['test_to']) ? sanitize_email(wp_unslash($_POST['test_to'])) : '';
        if (empty($to) || !is_email($to)) {
            wp_send_json_error(__('Geçersiz e-posta adresi.', 'samanli-smtp'));
        }

        $subject = sprintf(__('Samanlı SMTP Test Mesajı — %s', 'samanli-smtp'), get_bloginfo('name'));
        $body = '<p>' . sprintf(__('Bu, %s eklentisi tarafından gönderilen test e-postasıdır.', 'samanli-smtp'), 'Samanlı SMTP') . '</p>';
        $body .= '<p>' . __('Eğer bu e-postayı aldıysanız SMTP ayarlarınız çalışıyor demektir.', 'samanli-smtp') . '</p>';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $sent = wp_mail($to, $subject, $body, $headers);

        if ($sent) {
            wp_send_json_success(__('Test e-postası başarıyla gönderildi. Gelen kutunuzu kontrol edin.', 'samanli-smtp'));
        } else {
            global $phpmailer;
            $err = '';
            if (isset($phpmailer) && is_object($phpmailer)) {
                if (!empty($phpmailer->ErrorInfo)) $err = $phpmailer->ErrorInfo;
            }
            $message = __('E-posta gönderilemedi.', 'samanli-smtp');
            if ($err) $message .= ' ' . esc_html($err);
            wp_send_json_error($message);
        }
    }
}

new Samanli_SMTP_Plugin();
?>