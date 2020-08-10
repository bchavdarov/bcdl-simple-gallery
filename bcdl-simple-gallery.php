<?php
/**
 * Plugin Name:       BCDLab Simple Gallery plugin
 * Plugin URI:        https://github.com/bchavdarov/bcdl-simple-gallery.git
 * Description:       BCDL gallery plugin written with ESNext standard and JSX support – build step required.
 * Version:           2.0.2
 * Requires at least: 5.2
 * Requires PHP:      5
 * Author:            Boncho Chavdarov
 * Author URI:         https://bchavdarov.github.io/bcdlab
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bcdl-sg
 *
 * @package           bcdl-sg
 */

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */

defined( 'ABSPATH' ) or die( 'Please!' );

function bcdl_sg_bcdl_simple_gallery_block_init() {
	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "bcdl-sg/bcdl-simple-gallery" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'bcdl-sg-bcdl-simple-gallery-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);

	$editor_css = 'build/index.css';
	wp_register_style(
		'bcdl-sg-bcdl-simple-gallery-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'bcdl-sg-bcdl-simple-gallery-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'bcdl-sg/bcdl-simple-gallery', array(
		'editor_script' => 'bcdl-sg-bcdl-simple-gallery-block-editor',
		'editor_style'  => 'bcdl-sg-bcdl-simple-gallery-block-editor',
		'style'         => 'bcdl-sg-bcdl-simple-gallery-block',
	) );
}
add_action( 'init', 'bcdl_sg_bcdl_simple_gallery_block_init' );

//BCDL: the shortcode function starts here
function bcdl_simple_gallery( $attr ) {
	
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) ) {
			$attr['orderby'] = 'post__in';
		}
		$attr['include'] = $attr['ids'];
	}

	/**
	 * Filters the default gallery shortcode output.
	 *
	 * If the filtered output isn't empty, it will be used instead of generating
	 * the default gallery template.
	 *
	 * @since 2.5.0
	 * @since 4.2.0 The `$instance` parameter was added.
	 *
	 * @see gallery_shortcode()
	 *
	 * @param string $output   The gallery output. Default empty.
	 * @param array  $attr     Attributes of the gallery shortcode.
	 * @param int    $instance Unique numeric ID of this gallery shortcode instance.
	 */
	$output = apply_filters( 'post_gallery', '', $attr, $instance );

	if ( ! empty( $output ) ) {
		return $output;
	}

	$html5 = current_theme_supports( 'html5', 'gallery' );
	$atts  = shortcode_atts(
		array(
			'order'      => 'ASC',
			//'orderby'    => 'menu_order ID',
			'orderby'    => 'post_date',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => $html5 ? 'div' : 'dl',
			'icontag'    => $html5 ? 'div' : 'dt',
			'captiontag' => $html5 ? 'div' : 'dd',
			'columns'    => 3,
			'size'       => 'medium',
			'include'    => '',
			'exclude'    => '',
			'link'       => '',
		),
		$attr,
		'gallery'
	);

	$id = intval( $atts['id'] );

	if ( ! empty( $atts['include'] ) ) {
		$_attachments = get_posts(
			array(
				'include'        => $atts['include'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[ $val->ID ] = $_attachments[ $key ];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$attachments = get_children(
			array(
				'post_parent'    => $id,
				'exclude'        => $atts['exclude'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);
	} else {
		$attachments = get_children(
			array(
				'post_parent'    => $id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			$output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
		}
		return $output;
	}

	$itemtag    = tag_escape( $atts['itemtag'] );
	$captiontag = tag_escape( $atts['captiontag'] );
	$icontag    = tag_escape( $atts['icontag'] );
	$valid_tags = wp_kses_allowed_html( 'post' );
	if ( ! isset( $valid_tags[ $itemtag ] ) ) {
		$itemtag = 'dl';
	}
	if ( ! isset( $valid_tags[ $captiontag ] ) ) {
		$captiontag = 'dd';
	}
	if ( ! isset( $valid_tags[ $icontag ] ) ) {
		$icontag = 'dt';
	}

	$columns   = intval( $atts['columns'] );
	$itemwidth = $columns > 0 ? floor( 100 / $columns ) : 100;
	$float     = is_rtl() ? 'right' : 'left';

	$selector = "gallery-{$instance}";

	$gallery_style = '';

	/**
	 * Filters whether to print default gallery styles.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $print Whether to print default gallery styles.
	 *                    Defaults to false if the theme supports HTML5 galleries.
	 *                    Otherwise, defaults to true.
	 */
	if ( apply_filters( 'use_default_gallery_style', ! $html5 ) ) {
		$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';

		$gallery_style = "";
	}

	$size_class  = sanitize_html_class( $atts['size'] );
	$gallery_div = "<div id='$selector' class='card-columns3 gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";

	/**
	 * Filters the default gallery shortcode CSS styles.
	 *
	 * @since 2.5.0
	 *
	 * @param string $gallery_style Default CSS styles and opening HTML div container
	 *                              for the gallery shortcode output.
	 */
	$output = apply_filters( 'gallery_style', $gallery_style . $gallery_div );

	$i = 0;

	foreach ( $attachments as $id => $attachment ) {

		$attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "$selector-$id" ) : '';
		
		//BCDL
		$image_output = "<img class='card-img' src='".wp_get_attachment_url( $attachment->ID )."' alt='". get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ."'/>";

		$image_meta = wp_get_attachment_metadata( $id );

		$orientation = '';

		if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
			$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
		}

		$output .= "<{$itemtag} class='card bcdl-mask-contain shadow gallery-item mb-4'>";
		$output .= "
			<{$icontag} class='img-contain gallery-icon {$orientation}'>
				$image_output
				<div class='bcdl-mask rounded'></div>
			</{$icontag}>";

		if ( $captiontag && trim( $attachment->post_excerpt ) ) {
			$output .= "
				<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>
				<a class='stretched-link clearlink' href='#' data-toggle='modal' data-target='#bcdlimg{$id}'>
					<h2 class='card-title h4 text-center bcdl-rounded font-weight-bold'>
					" . get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) . "
					</h2>
				</a>
				<p class='text-center'>$attachment->post_content</p>
				</{$captiontag}>
				<div class='modal fade' id='bcdlimg{$id}' tabindex='-1' role='dialog' aria-labelledby='BCDOL Modal Label' aria-hidden='true'>
					<div class='modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl h-100'>
						<div class='modal-content bg-dark'>
							<div class='modal-body'>
								<img class='img-fluid' src='".wp_get_attachment_url( $attachment->ID )."' alt='". get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ."'/>
							</div>
						</div>
					</div>
				</div>";
		}

		$output .= "</{$itemtag}>";

		if ( ! $html5 && $columns > 0 && 0 === ++$i % $columns ) {
			$output .= '<br style="clear: both" />';
		}
	}

	if ( ! $html5 && $columns > 0 && 0 !== $i % $columns ) {
		$output .= "
			<br style='clear: both' />";
	}

	$output .= "
		</div>\n";

	return $output;

};

add_shortcode( 'bcdlsimplegallery', 'bcdl_simple_gallery' );