<?php
/**
 * Plugin Name:       Mein Seh-Check
 * Description:       Fügen Sie Ihren Seh-Check zu Ihrer WordPress-Webseite hinzu.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.6.6
 * Author:            Kuratorium Gutes Sehen e.V.
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       meinsehcheck-wp
 *
 * @package           sehende
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();

function sehende_meinsehcheck_wp_block_init() {
	$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/main.asset.php');
	wp_register_script(
		'meinsehcheck-wp-editor-script',
		plugins_url( 'build/main.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	wp_register_style(
		'meinsehcheck-wp-editor-style',
		plugins_url( 'build/main.css', __FILE__ ),
		array(),
		$asset_file['version']
	);

	wp_register_style(
		'meinsehcheck-wp-style',
		plugins_url( 'build/style-main.css', __FILE__ ),
		array(),
		$asset_file['version']
	);

	register_block_type( 'sehende/meinsehcheck-wp', array(
		'api_version'   => 2,
		'editor_script' => 'meinsehcheck-wp-editor-script',
		'editor_style'  => 'meinsehcheck-wp-editor-style',
		'style'         => 'meinsehcheck-wp-style',
		'render_callback' => 'sehende_meinsehcheck_wp_block_render_callback',
		'description' => 'Fügen Sie Ihren Seh-Check zu Ihrer WordPress-Webseite hinzu.',
		'category' => 'embed',
  	'icon' => 'visibility',
	) );
}

/**
 * Renders the block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with the Hello World appended.
 */
function sehende_meinsehcheck_wp_block_render_callback( $attributes ) {
	$customerNumber = empty( $attributes['customerNumber'] ) ? '' : str_replace( '-', '', $attributes['customerNumber'] );
	if ( ! preg_match( '/^\d{10}$/', $customerNumber ) ) {
		return '<div style="color: red;">Die angegebene Kundennummer "' . $attributes['customerNumber'] . '" ist ungültig. Bitte prüfen Sie die Einstellungen des "Mein Seh-Check" Gutenberg Blocks bzw. Shortcodes.</div>';
	}
	$url = $_ENV['REST_API_BASE_URL'] . '/customers/' . $customerNumber . '/snippet?noCss=1';
	$res = wp_remote_get( $url );
	return $res['body'];
}
add_action( 'init', 'sehende_meinsehcheck_wp_block_init' );

function sehende_meinsehcheck_wp_block_shortcode_callback( $atts ) {
	$attributes = shortcode_atts( array(
		'kd-nr' => '',
	), $atts );

	// render block and add style
	$block = sehende_meinsehcheck_wp_block_render_callback( array( 'customerNumber' => $attributes['kd-nr'] ) );
	$css = file_get_contents( plugin_dir_path( __FILE__ ) . 'build/style-main.css' );
	$style = '<style>' . $css . '</style>';
	return $style . $block;
}
add_shortcode( 'mein-seh-check', 'sehende_meinsehcheck_wp_block_shortcode_callback' );
