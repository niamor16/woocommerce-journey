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

## Notions

### Priorité

La **priorité**, c’est le moyen pour WordPress (et WooCommerce) de savoir **dans quel ordre exécuter plusieurs fonctions attachées au même hook**.

- **Priorité basse (petit nombre)** → exécution plus tôt
- **Priorité haute (grand nombre)** → plus tard



La signature de `add_action()` (et de `add_filter()`) est :

```php
add_action( $hook_name, $callback, $priority, $accepted_args );
```

Par défaut :

- `$priority` = 10
- `$accepted_args` = 1



Chaque hook WooCommerce est une **suite d’appels** dans un ordre précis.
 Par exemple, sur une fiche produit (`woocommerce_single_product_summary`) :

| Priorité | Élément affiché                |
| -------- | ------------------------------ |
| 5        | Titre du produit               |
| 10       | Note moyenne                   |
| 20       | Prix                           |
| 30       | Description courte             |
| 40       | Bouton “Ajouter au panier”     |
| 50       | Métadonnées (catégories, tags) |

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

#### 1. Créer les chaines

```php
const TEXT_DOMAIN = '{nom_du_domaine}'; 
// Base : __($text, $domain)
$text = __('key', self::TEXT_DOMAIN);

// Chaine avec variables
$label = sprintf(__('Emballage cadeau (%s)', self::TEXT_DOMAIN), wc_price($amount));

// Sur des attributs HTML, privilégie esc_attr__() ou esc_html__() pour bien échapper
$html = sprintf('<label title="%s"">', esc_attr__('Option cadeau', self::TEXT_DOMAIN));
    
// Pour une chaine Javascript
wp_set_script_translations('handle-script', self::TEXT_DOMAIN, plugin_dir_path(__FILE__) . 'languages');

// Avec contexte : _x($text, $context, $domain)
    // Dans l'admin produit
    _x('Wrap', 'gift wrapping option label', self::TEXT_DOMAIN);

    // Dans le front, CSS helper
    _x('Wrap', 'CSS wrapper element label', self::TEXT_DOMAIN);

// Pluriel : _n($text_sing, $text_pluriel, $count, $domain)
(_n('%d article emballé', '%d articles emballés', $count, self::TEXT_DOMAIN)
```

> Rappel : **ne concatène jamais** des bouts de phrases traduisibles, laisse une *seule* chaîne complète.

#### 2. Générer le .pot

la commande make pour générer le *.pot* :

```bash
make i18n mon-plugin
```

#### 3. Générer le .mo

plusieurs options :

### Option A — **POEdit** (simple et efficace)

1. Ouvre `wc-giftwrap-101.pot` dans POEdit.
2. “Créer une nouvelle traduction” → choisis **Français (France)** → `fr_FR`.
3. Traduis les chaînes (ex. *“Éligible à l’emballage cadeau”*).
4. Enregistre dans `wp-content/plugins/wc-giftwrap-101/languages/fr_FR.po`.
   POEdit génère **fr_FR.mo** automatiquement au même endroit.

### Option B — **Loco Translate** (plugin)

- Installe “Loco Translate” sur ton site → va dans **Loco Translate → Plugins → WC Gift Wrap 101** → “+ New language” → “French (France)” → choisis le **répertoire du plugin** pour stocker les fichiers → traduis → sauvegarde.
- Il te produira `languages/fr_FR.po` et `fr_FR.mo`.

> WordPress chargera `fr_FR.mo` quand le **langage du site** ou de **l’utilisateur** est `fr_FR` (Réglages → Général → *Langue du site*, ou Profil utilisateur → *Langue*).



## Produit

### Admin

#### Head

Le hook `admin_head` permet d'ajouter du html dans le header de l'admin (pour du css ou du js par exemple) .

```php
add_action('admin_head', [__CLASS__, 'admin_list_column_css']);

public static function admin_list_column_css() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-product') return;
    echo '<style>
        .fixed .column-wc_gw { width: 70px; text-align:center; }
    </style>';
}
```

#### Get by ID

Pour récuperer les infos d'un produit par son ID on utilise la fonction `wc_get_product`

```php
$product = wc_get_product($product_id);
if(!$product) return;
```

#### Créer/Modifier

**form :** *woocommerce_product_options_general_product_data*

**Enregistrer :** *woocommerce_admin_process_product_object*

*exemple :*

```php
const META_ENABLED = 'field_name';
const TEXT_DOMAIN = 'i18n_domain';

// Création du formulaire
/* Formulaire dans l'onglet "général" */
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


/* OU formulaire dans un onglet personnalisé */
add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tabs']);
add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);

public function product_data_tabs($tabs)
{
    $tabs['wc_nmrhrz_tab'] = [
        'label' => __('Emaballage cadeau', self::TEXT_DOMAIN),
        'target' => 'wc_nmrhrz_tab_panel',
        'class' => ['show_if_simple','show_if_variable','show_if_grouped','show_if_external'],
        // 'priority' => $prio,
    ];
    return $tabs;
}

public function product_data_panel()
{
    echo '<div id="wc_nmrhrz_tab_panel" class="panel woocommerce_options_panel hidden">';
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id'          => self::META_ENABLED,
        'label'       => __('Éligible à l\'emballage cadeau', self::TEXT_DOMAIN),
        'description' => __('Active l’option cadeau pour ce produit.', self::TEXT_DOMAIN),
    ]);
    // exemple d’autre champ (montant spécifique au produit)
    woocommerce_wp_text_input([
        'id'          => '_wc_gw_amount_override',
        'label'       => __('Montant spécifique (optionnel)', self::TEXT_DOMAIN),
        'desc_tip'    => true,
        'description' => __('Laisse vide pour utiliser le montant global.', self::TEXT_DOMAIN),
        'type'        => 'text',
    ]);
    echo '</div></div>';
}

/**
* Notes utiles :
* - target (filter) doit matcher l’id du <div> panneau.
* - La classe hidden est requise par WooCommerce (elle gère l’affichage via JS).
* - Les classes show_if_* pilotent sur quels types de produits l’onglet apparaît.
* - On réutilise le hook de sauvegarde standard woocommerce_admin_process_product_object.
*/


// enregistrement des données
add_action('woocommerce_admin_process_product_object', [$this, 'save_product_field']);

public function save_product_field($product)
{
    // Valeur envoyée par woocommerce_wp_checkbox : 'yes' ou absente
    $val = isset($_POST[self::META_ENABLED]) && $_POST[self::META_ENABLED] === 'yes' ? 'yes' : 'no';
    $product->update_meta_data(self::META_ENABLED, $val);
}
```

#### Modifier rapidement (depuis le listing)

**form :** *woocommerce_product_quick_edit_end*

**Enregistrer :** *woocommerce_product_quick_edit_save*

**Init (JS) :** *admin_footer-edit.php*

*exemple :*

```php
add_action('woocommerce_product_quick_edit_end', [__CLASS__, 'quick_edit_field']);
add_action('woocommerce_product_quick_edit_save', [__CLASS__, 'quick_edit_save'], 10, 1);
add_action('admin_footer-edit.php', [__CLASS__, 'quick_edit_js']);

public static function quick_edit_field() {
    ?>
    <label class="alignleft">
        <input type="checkbox" name="<?php echo esc_attr(self::META_ENABLED); ?>" value="yes">
        <span class="checkbox-title"><?php esc_html_e("Éligible à l'emballage cadeau", self::TEXT_DOMAIN); ?></span>
    </label>
    <?php
}

public static function quick_edit_save($product) {
    $val = isset($_REQUEST[self::META_ENABLED]) && $_REQUEST[self::META_ENABLED] === 'yes' ? 'yes' : 'no';
    $product->update_meta_data(self::META_ENABLED, $val);
    $product->save();
}

public static function quick_edit_js() {
    $screen = get_current_screen();
    if ($screen->id !== 'edit-product') return;
    ?>
    <script>
    jQuery(function($){
        // Quand on ouvre "Modification rapide"
        $('body').on('click', '.editinline', function(){
            const postId = $(this).closest('tr').attr('id').replace('post-', '');
            const enabled = $('#wc-gw-enabled-' + postId).data('enabled');
            $('input[name="<?php echo esc_js(self::META_ENABLED); ?>"]').prop('checked', enabled === 'yes');
        });
    });
    </script>
    <?php
}

/**
* Pour le JS on ajoute un data-attr dans la colonne
*/
public static function admin_list_render_column($column, $post_id) {
    if ($column !== 'wc_gw') return;
    $enabled = get_post_meta($post_id, self::META_ENABLED, true);
    printf(
        '<span id="wc-gw-enabled-%d" data-enabled="%s">%s</span>',
        $post_id,
        esc_attr($enabled),
        $enabled === 'yes' ? '<span class="dashicons dashicons-gift"></span>' : '&nbsp;'
    );
}
```



#### Listing

Modifier le listing des produits dans l'admin nécessite 2 hooks :

- `manage_edit-product_columns` pour ajouter une colonne au tableau
- `manage_product_posts_custom_column` pour gerer le rendu du tableau

**exemple :**

```php
add_filter('manage_edit-product_columns', [__CLASS__, 'admin_list_add_column']);
add_action('manage_product_posts_custom_column', [__CLASS__, 'admin_list_render_column'], 10, 2); // le 2 permet d'indiquer que la méthode attends 2 paramétres en entrée


public static function admin_list_add_column($cols) {
    // On ajoute une colonne à la suite
    $cols['wc_lorem'] = esc_html__('Ipsum', self::TEXT_DOMAIN);
    return $cols;
    
    // On insère une colonne juste après le titre
    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'name') {
            $new['wc_lorem'] = esc_html__('Ipsum', self::TEXT_DOMAIN);
        }
    }
    return $new;
}

public static function admin_list_render_column($column, $post_id) {
    if ($column !== 'wc_lorem') return;
    $p = wc_get_product($post_id);
    if (!$p) return;
    if ($p->get_meta(self::META_ENABLED) === 'yes') {
        echo '<span class="dashicons dashicons-gift" title="' . esc_attr__('Éligible à l’emballage cadeau', self::TEXT_DOMAIN) . '"></span>';
    } else {
        echo '&nbsp;'; // garde la ligne propre
    }
}

```



### Front

#### Instance

Dans un contexte fiche produit, le produit courrant n'est pas passé en paramétre, on le récupère en global :

```php
global $product; // Instance de WC_Product
// on peut d'ailleurs ajouter un controle (fortement conseillé)
if (!$product instanceof WC_Product) return;
```

Pour récuperer  des propriétés du produit on a des getters :

```php
$product->get_meta('_wc_gw_enabled');
$product->get_price();
$product->get_name();
```

#### Fiche produit

Pour toucher à la fiche produit, on utilise le hook `woocommerce_single_product_summary`

```php
add_action('woocommerce_single_product_summary', [__CLASS__, 'my_function'], 6); // 6 correspond a la priorité, la priorité (voir plus haut)

function my_function(){
    if (!function_exists('is_product') || !is_product()) return; // assure que le contexte soit bien une page produit
    global $product; // récupère le produit courrant
    if (!$product || !($product instanceof WC_Product)) return; // controle le produit courrant
    
    // Logique
}
```

