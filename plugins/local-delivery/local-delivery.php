<?php
/**
 * Plugin Name: Local Delivery (MVP)
 * Description: Livraison locale pour WooCommerce (zones par CP/rayon + créneaux).
 * Version: 0.1.0
 * Author: Toi
 * Text Domain: local-delivery
 */
if (!defined('ABSPATH')) exit;

final class LD_Plugin {
    const OPT = 'ld_options';

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_uninstall_hook(__FILE__, ['LD_Plugin', 'uninstall']);

        add_action('plugins_loaded', [$this, 'maybe_boot']);
    }

    public function activate() {
        add_option(self::OPT, [
            'enabled' => true,
            'postcodes' => '75001,75002',
            'base_address' => '',
            'radius_km' => 0,
            'fee_fixed' => 0.0
        ]);
    }

    public static function uninstall() { delete_option(self::OPT); }

    public function maybe_boot() {
        if (!class_exists('WooCommerce')) return; // Attendre WooCommerce
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_delivery_fee']);
        add_filter('woocommerce_checkout_fields', [$this, 'add_timeslot_field']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_zone'], 10, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_timeslot']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'show_timeslot_admin']);
        load_plugin_textdomain('local-delivery', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Livraison locale', 'local-delivery'),
            __('Livraison locale', 'local-delivery'),
            'manage_woocommerce',
            'ld-settings',
            [$this, 'render_settings']
        );
    }

    public function render_settings() {
        if (!current_user_can('manage_woocommerce')) return;
        if (!empty($_POST) && check_admin_referer('ld_save')) {
            $opts = [
                'enabled'      => !empty($_POST['enabled']),
                'postcodes'    => sanitize_text_field($_POST['postcodes'] ?? ''),
                'base_address' => sanitize_text_field($_POST['base_address'] ?? ''),
                'radius_km'    => floatval($_POST['radius_km'] ?? 0),
                'fee_fixed'    => floatval($_POST['fee_fixed'] ?? 0),
            ];
            update_option(self::OPT, $opts);
            echo '<div class="updated"><p>' . esc_html__('Enregistré.', 'local-delivery') . '</p></div>';
        }
        $o = get_option(self::OPT, []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Livraison locale – Réglages', 'local-delivery'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ld_save'); ?>
                <p><label><input type="checkbox" name="enabled" <?php checked(!empty($o['enabled'])); ?>> <?php esc_html_e('Activer', 'local-delivery'); ?></label></p>
                <p><label><?php esc_html_e('Codes postaux desservis (séparés par des virgules)', 'local-delivery'); ?><br>
                    <input type="text" name="postcodes" value="<?php echo esc_attr($o['postcodes'] ?? ''); ?>" class="regular-text">
                </label></p>
                <p><label><?php esc_html_e('Adresse de base (optionnel pour rayon)', 'local-delivery'); ?><br>
                    <input type="text" name="base_address" value="<?php echo esc_attr($o['base_address'] ?? ''); ?>" class="regular-text">
                </label></p>
                <p><label><?php esc_html_e('Rayon (km, 0 pour désactiver)', 'local-delivery'); ?><br>
                    <input type="number" step="0.1" name="radius_km" value="<?php echo esc_attr($o['radius_km'] ?? 0); ?>"></label></p>
                <p><label><?php esc_html_e('Frais fixes (€)', 'local-delivery'); ?><br>
                    <input type="number" step="0.01" name="fee_fixed" value="<?php echo esc_attr($o['fee_fixed'] ?? 0); ?>"></label></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function postcode_allowed($postcode) {
        $o = get_option(self::OPT, []);
        $list = array_filter(array_map('trim', explode(',', $o['postcodes'] ?? '')));
        return in_array($postcode, $list, true);
    }

    public function apply_delivery_fee(\WC_Cart $cart) {
        $o = get_option(self::OPT, []);
        if (empty($o['enabled'])) return;
        if (is_admin() && !defined('DOING_AJAX')) return;

        $fee = floatval($o['fee_fixed'] ?? 0);
        if ($fee > 0) {
            $cart->add_fee(__('Livraison locale', 'local-delivery'), $fee, false);
        }
    }

    public function add_timeslot_field($fields) {
        $fields['billing']['ld_timeslot'] = [
            'type'     => 'select',
            'label'    => __('Créneau de livraison', 'local-delivery'),
            'required' => true,
            'options'  => [
                '' => __('Choisir…', 'local-delivery'),
                '11-14' => '11:00 – 14:00',
                '18-21' => '18:00 – 21:00'
            ],
            'priority' => 120
        ];
        return $fields;
    }

    public function validate_zone($data, $errors) {
        $o = get_option(self::OPT, []);
        if (empty($o['enabled'])) return;

        $postcode = isset($data['billing_postcode']) ? trim($data['billing_postcode']) : '';
        if (!$postcode || (!$this->postcode_allowed($postcode) && floatval($o['radius_km'] ?? 0) <= 0)) {
            $errors->add('ld_zone', __('Désolé, nous ne livrons pas à cette adresse.', 'local-delivery'));
        }
        if (empty($data['ld_timeslot'])) {
            $errors->add('ld_timeslot', __('Veuillez choisir un créneau de livraison.', 'local-delivery'));
        }
    }

    public function save_timeslot($order_id) {
        if (!empty($_POST['ld_timeslot'])) {
            update_post_meta($order_id, '_ld_timeslot', sanitize_text_field($_POST['ld_timeslot']));
        }
    }

    public function show_timeslot_admin($order) {
        $slot = get_post_meta($order->get_id(), '_ld_timeslot', true);
        if ($slot) {
            echo '<p><strong>' . esc_html__('Créneau de livraison', 'local-delivery') . ':</strong> ' . esc_html($slot) . '</p>';
        }
    }
}
new LD_Plugin();
