<?php

/**
 * @wordpress-plugin
 * Plugin Name:       NMR Horizon plugin
 * Plugin URI:        https://github.com/niamor16
 * Description:       Petite plugin de découverte de l'environnement, des hooks et de la structure
 * Version:           0.0.1
 * Author:            Niamor
 * Author URI:        https://github.com/niamor16
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nmr-horizon
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('PLUGIN_NAME_VERSION', '0.1.0');

final class NmrHorizon
{
    /**
     * @var NmrHorizon 
     */
    static $_instance = false;

    const VERSION = '0.1.0';
    const TEXT_DOMAIN = 'nmr-horizon';
    const META_ENABLED = '_wc_hrz_enabled';

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct()
    {
        // i18n
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        // Garde-fou : prévenir si WooCommerce n'est pas actif
        add_action('admin_init', [$this, 'maybe_warn_missing_wc']);

        // hook metadata produit
        add_action('woocommerce_product_options_general_product_data', [$this, 'product_field']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_product_field']);

        // Fiche produit
        add_action('woocommerce_single_product_summary', [$this, 'render_badge']);
    }

    /**
     * Chargement des traductions
     * @return void
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Affiche un message d'admin si WooCommerce n'est pas actif.
     */
    public function maybe_warn_missing_wc()
    {
        if (class_exists('WooCommerce')) {
            return; // tout va bien
        }
        add_action('admin_notices', function () {
            if (!current_user_can('activate_plugins')) return;
            echo '<div class="notice notice-error"><p>'
                . esc_html__('NMR Horizon nécessite WooCommerce. Activez-le pour continuer.', self::TEXT_DOMAIN)
                . '</p></div>';
        });
    }

    public function product_field()
    {
        echo '<div class="options_group">';
        woocommerce_wp_checkbox([
            'id' => self::META_ENABLED, 
            'label' => __('Éligible à l\'emballage cadeau', self::TEXT_DOMAIN),
            'description' => __('Si activé, ce produit pourra proposer l\'option emballage au checkout.', self::TEXT_DOMAIN),
        ]);
        echo '</div>';
    }

    public function save_product_field($product)
    {
        // Valeur envoyée par woocommerce_wp_checkbox : 'yes' ou absente
        $val = isset($_POST[self::META_ENABLED]) && $_POST[self::META_ENABLED] === 'yes' ? 'yes' : 'no';
        $product->update_meta_data(self::META_ENABLED, $val);
    }

    public function render_badge()
    {
        if (!function_exists('is_product') || !is_product()) return;
        global $product;
        if (!$product || !($product instanceof WC_Product)) return;
        if ($product->get_meta(self::META_ENABLED) !== 'yes') return;

        $label = esc_html__('Emballage cadeau disponible', self::TEXT_DOMAIN);
        echo '<div class="wc-gw-badge" style="display:inline-block;margin:8px 0;padding:4px 8px;border:1px dashed; border-radius:4px; font-size:12px;">'
            . $label . '</div>';
    }
}

NmrHorizon::getInstance();
