
# --- Réglages WP/Woo via WP-CLI ---
echo "⚙️  Réglages permaliens + pages WooCommerce"
npm run cli -- "option update permalink_structure '/%postname%/'"
npm run cli -- "wc tool run install_pages --user=admin" || true

echo "🇫🇷 Réglages boutique FR/EUR/TVA"
npm run cli -- "option update woocommerce_currency EUR"
npm run cli -- "option update woocommerce_calc_taxes yes"
npm run cli -- \"option update woocommerce_store_address '10 Rue de Rivoli'\"
npm run cli -- "option update woocommerce_store_city 'Paris'"
npm run cli -- "option update woocommerce_store_postcode '75001'"
npm run cli -- "option update woocommerce_default_country 'FR'"

# --- TVA 20% FR (direct DB) ---
echo "🧮 Insertion TVA 20% (FR) sur livraison"
npm run cli -- "db query \"INSERT IGNORE INTO wp_woocommerce_tax_rates (tax_rate_country,tax_rate_state,tax_rate,tax_rate_name,tax_rate_priority,tax_rate_compound,tax_rate_shipping,tax_rate_order,tax_rate_class) VALUES ('FR','',20.0000,'TVA',1,0,1,0,'');\""

# --- Activer le plugin Local Delivery ---
echo "🔌 Activation du plugin Local Delivery"
npm run cli -- "plugin activate ${PLUGIN_SLUG}" || true

# --- Import produits de démo WooCommerce ---
# On utilise l'import XML officiel livré avec WooCommerce
echo "📦 Import des produits de démo WooCommerce"
npm run cli -- "plugin install wordpress-importer --activate" || true
# Chemin de l'XML dans l'extension WooCommerce
DEMO_PATH="/var/www/html/wp-content/plugins/woocommerce/sample-data/sample_products.xml"
npm run cli -- "eval 'echo file_exists(\"${DEMO_PATH}\") ? \"OK\" : \"KO\";'"
npm run cli -- "import ${DEMO_PATH} --authors=create" || {
  echo "⚠️  Import XML non trouvé. Tu pourras importer manuellement depuis WooCommerce si besoin."
}
