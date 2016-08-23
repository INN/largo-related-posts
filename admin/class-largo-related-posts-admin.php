<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://nerds.inn.org
 * @since      1.0.0
 *
 * @package    Largo_Related_Posts
 * @subpackage Largo_Related_Posts/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Largo_Related_Posts
 * @subpackage Largo_Related_Posts/admin
 * @author     INN Nerds <nerds@inn.org>
 */
class Largo_Related_Posts_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Largo_Related_Posts_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Largo_Related_Posts_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/largo-related-posts-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Largo_Related_Posts_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Largo_Related_Posts_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/largo-related-posts-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.8.6');

	}

	/**
	 * Add javascript to trigger ajax search for manual related posts 
	 *
	 * @since    1.0.0
	 */
	public function related_posts_ajax_js() {
		?>
		<script type="text/javascript">
			var se_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

			jQuery(document).ready(function($) {
				
				$('input#se_search_element_id').autocomplete({
					source: se_ajax_url + '?action=related_posts_ajax_search',
				});

			});
		</script>
		<?php
	}

	/**
	 * Perform ajax search using suggest.js 
	 *
	 * @since    1.0.0
	 */
	public function related_posts_ajax_search() {
		global $wpdb;
		$search = like_escape($_REQUEST['term']);

		$query = 'SELECT post_title, ID FROM wp_posts
		WHERE post_title LIKE \'%' . $search . '%\'';

		$suggestions = array();

		foreach ($wpdb->get_results($query) as $row) {
			$suggestion['value'] = $row->ID;
			$suggestion['label'] = $row->post_title;
			
			$suggestions[] = $suggestion;
		}

		$response = json_encode( $suggestions );
		echo $response;
		die();
	}

	/**
	 * Register the related posts metabox 
	 *
	 * @since    1.0.0
	 */
	public function largo_add_related_posts_meta_box() {
		add_meta_box(
			'largo_related_posts',
			__( 'Related Posts', 'largo' ),
			array( $this, 'largo_related_posts_meta_box_display' ), 
			'post',
			'side',
			'core'
		);
	}

	/**
	 * Related posts metabox callback 
	 *
	 * Allows the user to set custom related posts for a post.
	 *
	 * @global $post
	 */
	public function largo_related_posts_meta_box_display( $post ) {

		// make sure the form request comes from WordPress
		wp_nonce_field( basename( __FILE__ ), 'largo_related_posts_nonce' );

		$value = get_post_meta( $post->ID, 'largo_custom_related_posts', true );

		echo '<p><strong>' . __('Related Posts', 'largo') . '</strong><br />';
		echo __('To override the default related posts functionality,  enter post titles to manually select below.') . '</p>';
		echo '<input type="text" name="largo_custom_related_posts" value="' . esc_attr( $value ) . '" />';
		echo '<input type="text" id="se_search_element_id" name="se_search_element_id" value="" />';

		echo '<div id="stop-log">stoplog</div>';

		do_action( 'largo_related_posts_metabox' );
	}


	public function largo_related_posts_meta_box_save( $post_id ) {

		// Verify form submission is coming from WordPress using a nonce
		if ( !isset( $_POST['largo_related_posts_nonce'] ) || !wp_verify_nonce( $_POST['largo_related_posts_nonce'], basename( __FILE__ ) ) ){
			return;
		}

		// return if autosave 
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ){
			return;
		}

		$key = 'largo_custom_related_posts';
		$value = $_POST[$key];
		if ( isset( $_POST['largo_custom_related_posts'] ) ) {
			if ( get_post_meta( $post_id, $key, FALSE ) ) {
				update_post_meta( $post_id, $key, $value ); //if the custom field already has a value, update it
			} else {
				add_post_meta( $post_id, $key, $value );//if the custom field doesn't have a value, add the data
			}
			if ( ! $value ) {
				delete_post_meta( $post_id, $key ); //and delete if blank
			}
		}


	}
}
