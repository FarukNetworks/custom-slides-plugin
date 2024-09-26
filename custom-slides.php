<?php

/**
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Custom_Slides
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Slides
 * Plugin URI:        http://example.com/custom-slider/
 * Description:       Slider Plugin, get yourself a basic, easy to use and lightweight slider. Made for Galleries, Reviews, Testimonials and more. 
 * Version:           1.0.0
 * Author:            Faruk Develpment
 * Author URI:        http://farukdevelopment.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       custom-slider
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('CS_VERSION', '1.0.0');



class CustomSlides
{

	public function __construct()
	{
		add_action('init', array($this, 'create_custom_post_type'));
		add_action('add_meta_boxes', array($this, 'add_slide_meta_box'));
		add_action('save_post', array($this, 'save_slide_meta'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// slick JS 
		add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
		// slick CSS
		add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));

		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

		add_shortcode('sliders-short', array($this, 'slide_post_shortcode'));

		// Add new column to slides post list
		add_filter('manage_slides_posts_columns', array($this, 'add_shortcode_column'));
		add_action('manage_slides_posts_custom_column', array($this, 'render_shortcode_column'), 10, 2);

		// Add shortcode meta box to the sidebar
		add_action('add_meta_boxes', array($this, 'add_shortcode_meta_box'));


		// Add meta box for slider options
		add_action('add_meta_boxes', array($this, 'add_slider_options_meta_box'));
		add_action('save_post', array($this, 'save_slider_options_meta'));

	}




	public function activate()
	{

		$this->create_custom_post_type();

		flush_rewrite_rules();
	}

	public function deactivate()
	{

		flush_rewrite_rules();
	}


	public function uninstall() {}


	function create_custom_post_type()
	{
		register_post_type('slides', array(
			'labels' => array(
				'name' => __('Slides'),
				'singular_name' => __('Slide'),
			),
			'supports' => array('title'),
			'public' => true,
			'menu_icon' => 'dashicons-slides',
			'menu_position' => 5,
			'has_archive' => false,
			'publicly_queryable' => false,
		));
	}


	public function add_slide_meta_box()
	{
		add_meta_box(
			'slide_meta_box', // ID
			'Slide Details', // Title
			array($this, 'render_slide_meta_box'), // Callback
			'slides' // Post type
		);
	}

	public function render_slide_meta_box($post)
	{
		// Retrieve existing slide data
		$slides = get_post_meta($post->ID, 'slides', true) ?: [];

		// Nonce field for security
		wp_nonce_field('save_slide_meta', 'slide_meta_nonce');

		echo '<div id="slides-container">';
		foreach ($slides as $index => $slide) {
			$this->render_slide_fields($index, $slide);
		}
		echo '</div>';

		echo '<button type="button" class="button" id="add-slide">Add Slide</button>';
	}

	public function render_slide_fields($index, $slide)
	{
?>
		<div class="slide" data-index="<?php echo $index; ?>">
			<h4>Slide <?php echo $index; ?></h4>
			<?php
			// Use wp_editor to render the WYSIWYG editor
			$editor_id = 'slides_' . $index . '_content'; // Unique ID for each editor
			$content = isset($slide['content']) ? $slide['content'] : ''; // Default content
			$settings = array(
				'textarea_name' => 'slides[' . $index . '][content]', // Set name for saving
				'media_buttons' => true, // Whether to display the "Add Media" button
				'textarea_rows' => 5, // Set number of rows (optional)
				'editor_class' => 'custom-editor', // Optional, custom class
			);

			wp_editor($content, $editor_id, $settings);
			?>
			<button type="button" class="button remove-slide" style="margin-left: auto; display: block; margin-top: 15px;">Remove Slide</button>
		</div>
	<?php
	}


	public function save_slide_meta($post_id)
	{
		if (!isset($_POST['slide_meta_nonce']) || !wp_verify_nonce($_POST['slide_meta_nonce'], 'save_slide_meta')) {
			return;
		}

		// Save slides data
		$slides = isset($_POST['slides']) ? $_POST['slides'] : [];
		update_post_meta($post_id, 'slides', $slides);
	}


	// SIDEBAR SLIDER OPTIONS 


	// Add the slider options meta box
	public function add_slider_options_meta_box()
	{
		add_meta_box(
			'slider_options_box', // Meta box ID
			'Slider Options', // Meta box title
			array($this, 'render_slider_options_meta_box'), // Callback to display the content
			'slides', // Post type
			'side', // Context: 'normal', 'advanced', or 'side'
			'low' // Priority: 'high', 'core', 'default', or 'low'
		);
	}

	// Render the slider options in the sidebar meta box
	public function render_slider_options_meta_box($post)
	{
		// Retrieve existing meta values
		$meta_values = get_post_meta($post->ID, 'slider_options', true) ?: [];

		// Nonce field for security
		wp_nonce_field('save_slider_options', 'slider_options_nonce');

		// Default values for options if not set
		$options = [
			'slidesToShow' => isset($meta_values['slidesToShow']) ? $meta_values['slidesToShow'] : 1,
			'slidesToScroll' => isset($meta_values['slidesToScroll']) ? $meta_values['slidesToScroll'] : 1,
			'autoplay' => isset($meta_values['autoplay']) ? $meta_values['autoplay'] : 0,
			'autoplaySpeed' => isset($meta_values['autoplaySpeed']) ? $meta_values['autoplaySpeed'] : 3000,
			'arrows' => isset($meta_values['arrows']) ? $meta_values['arrows'] : 1,
			'dots' => isset($meta_values['dots']) ? $meta_values['dots'] : 0,
			'fade' => isset($meta_values['fade']) ? $meta_values['fade'] : 0,
			'infinite' => isset($meta_values['infinite']) ? $meta_values['infinite'] : 1,
			'speed' => isset($meta_values['speed']) ? $meta_values['speed'] : 500,
			'centerMode' => isset($meta_values['centerMode']) ? $meta_values['centerMode'] : 0,
			'slidesToShow2' => isset($meta_values['slidesToShow2']) ? $meta_values['slidesToShow2'] : 1,
			'slidesToScroll2' => isset($meta_values['slidesToScroll2']) ? $meta_values['slidesToScroll2'] : 1,
			'slidesToShow3' => isset($meta_values['slidesToShow3']) ? $meta_values['slidesToShow3'] : 1,
			'slidesToScroll3' => isset($meta_values['slidesToScroll3']) ? $meta_values['slidesToScroll3'] : 1,
			'slidesToShow4' => isset($meta_values['slidesToShow4']) ? $meta_values['slidesToShow4'] : 1,
			'slidesToScroll4' => isset($meta_values['slidesToScroll4']) ? $meta_values['slidesToScroll4'] : 1,


		];

		// Display input fields for each option
	?>
		<p>
			<label for="slidesToShow">Slides To Show:</label>
			<input type="number" name="slider_options[slidesToShow]" id="slidesToShow" value="<?php echo esc_attr($options['slidesToShow']); ?>" />
		</p>
		<p>
			<label for="slidesToScroll">Slides To Scroll:</label>
			<input type="number" name="slider_options[slidesToScroll]" id="slidesToScroll" value="<?php echo esc_attr($options['slidesToScroll']); ?>" />
		</p>
		<p>
			<label for="autoplay">Autoplay:</label>
			<input type="checkbox" name="slider_options[autoplay]" id="autoplay" value="1" <?php checked($options['autoplay'], 1); ?> />
		</p>
		<p>
			<label for="autoplaySpeed">Autoplay Speed:</label>
			<input type="number" name="slider_options[autoplaySpeed]" id="autoplaySpeed" value="<?php echo esc_attr($options['autoplaySpeed']); ?>" />
		</p>
		<p>
			<label for="dots">Show Dots:</label>
			<input type="checkbox" name="slider_options[dots]" id="dots" value="1" <?php checked($options['dots'], 1); ?> />
		</p>
		<p>
			<label for="fade">Fade Effect:</label>
			<input type="checkbox" name="slider_options[fade]" id="fade" value="1" <?php checked($options['fade'], 1); ?> />
		</p>
		<p>
			<label for="infinite">Infinite Loop:</label>
			<input type="checkbox" name="slider_options[infinite]" id="infinite" value="1" <?php checked($options['infinite'], 1); ?> />
		</p>
		<p>
			<label for="arrows">Show Arrows:</label>
			<input type="checkbox" name="slider_options[arrows]" id="arrows" value="1" <?php checked($options['arrows'], 1); ?> />
		</p>
		<p>
			<label for="speed">Animation Speed:</label>
			<input type="number" name="slider_options[speed]" id="speed" value="<?php echo esc_attr($options['speed']); ?>" />
		</p>
		<p>
			<label for="centerMode">Center Mode:</label>
			<input type="checkbox" name="slider_options[centerMode]" id="centerMode" value="1" <?php checked($options['centerMode'], 1); ?> />
		</p>
		<p>
			<label for="slidesToShow2">Slides To Show Below 1200px (viewport):</label>
			<input type="number" name="slider_options[slidesToShow2]" id="slidesToShow2" value="<?php echo esc_attr($options['slidesToShow2']); ?>" />
		</p>
		<p>
			<label for="slidesToScroll2">Slides To Scroll Below 1200px (viewport):</label>
			<input type="number" name="slider_options[slidesToScroll2]" id="slidesToScroll2" value="<?php echo esc_attr($options['slidesToScroll2']); ?>" />
		</p>
		<p>
			<label for="slidesToShow3">Slides To Show Below 992px (viewport):</label>
			<input type="number" name="slider_options[slidesToShow3]" id="slidesToShow3" value="<?php echo esc_attr($options['slidesToShow3']); ?>" />
		</p>
		<p>
			<label for="slidesToScroll3">Slides To Scroll Below 992px (viewport):</label>
			<input type="number" name="slider_options[slidesToScroll3]" id="slidesToScroll3" value="<?php echo esc_attr($options['slidesToScroll3']); ?>" />
		</p>
		<p>
			<label for="slidesToShow4">Slides To Show Below 768px (viewport):</label>
			<input type="number" name="slider_options[slidesToShow4]" id="slidesToShow4" value="<?php echo esc_attr($options['slidesToShow4']); ?>" />
		</p>
		<p>
			<label for="slidesToScroll4">Slides To Scroll Below 768px (viewport):</label>
			<input type="number" name="slider_options[slidesToScroll4]" id="slidesToScroll4" value="<?php echo esc_attr($options['slidesToScroll4']); ?>" />
		</p>

<?php
	}

	// Save the slider options meta data
	public function save_slider_options_meta($post_id)
	{
		// Security checks
		if (!isset($_POST['slider_options_nonce']) || !wp_verify_nonce($_POST['slider_options_nonce'], 'save_slider_options')) {
			return;
		}

		// Check user permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save the meta values
		if (isset($_POST['slider_options'])) {
			update_post_meta($post_id, 'slider_options', $_POST['slider_options']);
		}
	}

	// SIDEBAR SLIDER OPTIONS END 







	function slide_post_shortcode($atts)
	{
		// Extract shortcode attributes
		$atts = shortcode_atts(
			array(
				'id' => null, // Default to null if no ID is passed
			),
			$atts
		);

		// Check if the ID is provided
		if (!$atts['id']) {
			return 'No slide ID provided.';
		}

		$post_id = intval($atts['id']); // Sanitize the ID to ensure it's an integer

		// Query the specific slide post
		$args = array(
			'post_type' => 'slides',
			'p' => $post_id, // Query for the specific post ID
		);

		$query = new WP_Query($args);

		if ($query->have_posts()) {
			$output = '<ul id="cs-slick-slide-' . $post_id . '" class="cs-slides-list" style="list-style: none;">';


			while ($query->have_posts()) {
				$query->the_post();

				// Retrieve the slides meta field for this post
				$slides = get_post_meta($post_id, 'slides', true);
				$sliderOptions = get_post_meta($post_id, 'slider_options', true);

				if (!empty($slides)) {
					foreach ($slides as $slide) {
						$output .= '<li><div>' .  $slide['content'] . '</div></li>';
					}
				} else {
					$output .= '<li>No slides found for this post.</li>';
				}
			}
			$output .= '</ul>';
			$output .= '<script type="text/javascript">jQuery(document).ready(function() {
				jQuery("#cs-slick-slide-' . $post_id . '").slick({
				slidesToShow: ' . $sliderOptions["slidesToShow"] . ', 
				slidesToScroll: ' . $sliderOptions["slidesToScroll"] . ',
				autoplay: ' . (isset($sliderOptions["autoplay"]) ? 'true' : 'false') . ',
				autoplaySpeed: ' . $sliderOptions["autoplaySpeed"] . ',
				dots: ' . (isset($sliderOptions["dots"]) ? 'true' : 'false') . ',
				fade: ' . (isset($sliderOptions["fade"]) ? 'true' : 'false') . ',
				infinite: ' . (isset($sliderOptions["infinite"]) ? 'true' : 'false') . ',
				arrows: ' . (isset($sliderOptions["arrows"]) ? 'true' : 'false') . ',
				speed: ' . $sliderOptions["speed"] . ',
				centerMode: ' . (isset($sliderOptions["centerMode"]) ? 'true' : 'false') . ',
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: ' . $sliderOptions["slidesToShow2"] . ',
							slidesToScroll: ' . $sliderOptions["slidesToScroll2"] . ',
						}
					},
					{
						breakpoint: 990,
						settings: {
							slidesToShow: ' . $sliderOptions["slidesToShow3"] . ',
							slidesToScroll: ' . $sliderOptions["slidesToScroll3"] . ',
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: ' . $sliderOptions["slidesToShow4"] . ',
							slidesToScroll: ' . $sliderOptions["slidesToScroll4"] . ',
						}
					}
				]
			});
			});</script>';
		} else {
			$output = 'No posts found.';
		}

		wp_reset_postdata();

		return $output;
	}




	// Add custom column for shortcode
	public function add_shortcode_column($columns)
	{
		// Insert a new column for the shortcode before the "date" column
		$new_columns = array();
		foreach ($columns as $key => $title) {
			if ($key == 'date') {
				$new_columns['shortcode'] = __('Shortcode');
			}
			$new_columns[$key] = $title;
		}
		return $new_columns;
	}

	// Render the shortcode in the new column
	public function render_shortcode_column($column, $post_id)
	{
		if ($column == 'shortcode') {
			echo '<code>[sliders-short id="' . $post_id . '"]</code>';
		}
	}


	// Add a meta box to display the shortcode in the post editor
	public function add_shortcode_meta_box()
	{
		add_meta_box(
			'slide_shortcode_box', // Meta box ID
			'Slide Shortcode', // Meta box title
			array($this, 'render_shortcode_meta_box'), // Callback to display the content
			'slides', // Post type
			'side', // Context: 'normal', 'advanced', or 'side'
			'low' // Priority: 'high', 'core', 'default', or 'low'
		);
	}

	// Render the shortcode in the sidebar meta box
	public function render_shortcode_meta_box($post)
	{
		echo '<p>Use this shortcode to display this slide:</p>';
		echo '<code>[sliders-short id="' . $post->ID . '"]</code>';
	}








	public function enqueue_admin_scripts()
	{
		wp_enqueue_script('main-js', plugin_dir_url(__FILE__) . 'admin/js/main.js', ['jquery'], CS_VERSION, true);

		wp_enqueue_editor();
	}

	public function enqueue_admin_styles()
	{
		wp_enqueue_style('custom-slides-main-css', plugin_dir_url(__FILE__) . 'admin/css/main.css', [], CS_VERSION);
	}


	public function enqueue_public_scripts()
	{
		wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);
	}


	public function enqueue_public_styles()
	{
		wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');

		wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', [], '1.8.1');


		wp_enqueue_style('custom-slides-main-css', plugin_dir_url(__FILE__) . 'css/main.css', [], CS_VERSION);
	}

}



if (class_exists('CustomSlides')) {
	$CustomSlides = new CustomSlides();
}


register_activation_hook(__FILE__, array($CustomSlides, 'activate'));
register_deactivation_hook(__FILE__, array($CustomSlides, 'deactivate'));
