# Setup dev env

> A REVOIR

## @woocommerce/create-woo-extension

https://www.npmjs.com/package/@woocommerce/create-woo-extension4

```bash
npx @wordpress/create-block -t @woocommerce/create-woo-extension my-extension-name
```

Navigate to the newly created folder and get started.

```bash
cd my-extension-name
npm install # Install dependencies
npm run build # Build the javascript
npm -g i @wordpress/env # If you don't already have wp-env
wp-env start # Start Wordpress environment
```



# Structure & boilerplate

## links

https://developer.wordpress.org/plugins/plugin-basics/best-practices/

## Structure

```
/plugin-name
     plugin-name.php
     uninstall.php
     /languages
     /includes
     /admin
          /js
          /css
          /images
     /public
          /js
          /css
          /images
```

## [Architecture Patterns](https://developer.wordpress.org/plugins/plugin-basics/best-practices/#architecture-patterns)

While there are a number of possible architecture patterns, they can broadly be grouped into three variations:

- [Single plugin file, containing functions](https://github.com/GaryJones/move-floating-social-bar-in-genesis/blob/master/move-floating-social-bar-in-genesis.php)
- [Single plugin file, containing a class, instantiated object and optionally functions](https://github.com/norcross/wp-comment-notes/blob/master/wp-comment-notes.php)
- [Main plugin file, then one or more class files](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate)

> Il est important d'implémenter une structure singleton (exception rare si le plugin peut etre instancié plusieurs fois)

## Headers

Les headers sont très importants. Ils sont lu par Wordpress pour décrire le plugin.

```php
<?php
    
/**
 * @wordpress-plugin
 * Plugin Name:       Mon plugin
 * Plugin URI:        https://github.com/niamor16
 * Description:       Petite plugin de découverte de l'environnement, des hooks et de la structure
 * Version:           0.0.1
 * Author:            Niamor
 * Author URI:        https://github.com/niamor16
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mon-plugin
 * Domain Path:       /languages
 */
    
if (!defined('ABSPATH')) exit; // juste une sécu
final class monPlugin {
    
}
```



# Features

## I18n

### Structure

### Chargement

Le mieux est de charger les traductions le plus tôt possible.
Pour ce faire, on va ajouter une action au hook `plugins_loaded` :

```php
add_action('plugins_loaded', [__CLASS__, 'load_textdomain']); // load_textdomain est une fonction libre de notre plugin
```

qui appelle une méthode de notre plugin chargé de charger les traductions :

```php
public static function load_textdomain()
    {
        load_plugin_textdomain(
            '{nom_du_domain}', // le nom du plugin
            false,
            dirname(plugin_basename(__FILE__)) . '/languages' //le chemin vers les fichiers de trad (normalement /languages)
        );
    }
```

> A adapter selon la structure (static ou non)

### Traduction



## Produit

### Form

**hook :** *woocommerce_product_options_general_product_data*

**exemple :**

```php
const META_ENABLED = 'field_name';
const TEXT_DOMAIN = 'i18n_domain';
add_action('woocommerce_product_options_general_product_data', [$this, 'product_field']);
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
```



### Save

**hook :** *woocommerce_admin_process_product_object*

**exemple :**

```php
const META_ENABLED = 'field_name';
add_action('woocommerce_admin_process_product_object', [$this, 'save_product_field']);
public function save_product_field($product)
{
    // Valeur envoyée par woocommerce_wp_checkbox : 'yes' ou absente
    $val = isset($_POST[self::META_ENABLED]) && $_POST[self::META_ENABLED] === 'yes' ? 'yes' : 'no';
    $product->update_meta_data(self::META_ENABLED, $val);
}
```

