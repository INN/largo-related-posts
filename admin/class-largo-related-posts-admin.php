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
		wp_enqueue_script( 'jquery-ui-autocomplete', '', array( 'jquery-ui-widget', 'jquery-ui-position' ), '1.8.6' );
		wp_localize_script( 'jquery-ui-autocomplete', 'ajax_object', array( 'largo_related_posts_ajax_nonce' => wp_create_nonce( 'largo_related_posts_ajax_nonce' ) ) );
	}

	/**
	 * Add javascript to trigger ajax search for manual related posts
	 *
	 * @since    1.0.0
	 */
	public function related_posts_ajax_js() {
		?>
		<script type="text/javascript">
			var se_ajax_url = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

			jQuery(document).ready(function($) {

				$('input#se_search_element_id').autocomplete({
					source: se_ajax_url + '?action=related_posts_ajax_search&security='+ajax_object.largo_related_posts_ajax_nonce,
					select: function (event, ui) {

						// Reset the search value
						$("input#se_search_element_id").val('');

						// Add the selected search term to the list below
						var link = $( '<a />');
						link.attr( 'href', ui.item.permalink );
						link.text( ui.item.label );

						var edit_link = $( '<a />');
						edit_link.attr( 'href', ui.item.edit_link );
						edit_link.attr( 'class', 'edit-post-link' );
						edit_link.text( 'Edit Post' );

						var li = $( '<li />' );
						li.attr( 'data-id', ui.item.value );
						li.attr( 'data-title', ui.item.label );
						li.append( link );
						li.append( '<br/>' );
						li.append( edit_link );
						li.append( ' | <a class="remove-related">Remove</a>' );
						$("#related-posts-saved ul").append( li );

						// Select all items in the list
						var optionTexts = [];
						$("#related-posts-saved ul li").each(function() { optionTexts.push( [ $(this).attr('data-id'), $(this).attr('data-title') ] ) });

						// Save the list in it's current state
						jQuery.post( ajaxurl, {
							action: 'related_posts_ajax_save',
							data: optionTexts,
							post_id: $('#post_ID').val(),
							largo_related_posts_nonce:  $('#largo_related_posts_nonce').attr('value'),
						});
						return false;
					}
				});

				$('#related-posts-saved').on( "click", '.remove-related', function(evt) {

					// Select all items in the list
					var optionTexts = [];
					$("#related-posts-saved ul li").each(function() { optionTexts.push( [ $(this).attr('data-id'), $(this).attr('data-title') ] ) });

						// Save the list without the new item
					$.post(ajaxurl, {
						action: 'related_posts_ajax_save',
						data:  optionTexts,
						post_id: $('#post_ID').val(),
						remove: jQuery(this).parent().attr("data-id"),
						largo_related_posts_nonce:  $('#largo_related_posts_nonce').attr('value'),
					});
					$(this).parent().remove();
				});

			});
		</script>
		<?php
	}

	/**
	 * Perform ajax search using jQuery Autocomplete
	 *
	 * @since    1.0.0
	 */
	public function related_posts_ajax_search() {
		global $wpdb;
		check_ajax_referer( 'largo_related_posts_ajax_nonce', 'security' );
		$search = '%' . $wpdb->esc_like( $_REQUEST['term'] ) . '%';

		$sql = $wpdb->prepare(
			"
			SELECT post_title, ID
			FROM wp_posts
			WHERE post_title LIKE '%s'
				AND post_status IN ('publish', 'draft', 'future')
				AND post_type IN ('post')
			ORDER BY ID DESC
			LIMIT 50
			",
			$search
		);

		$result = wp_cache_get( 'largo_related_posts_query' );
		if ( false === $result ) {
			$result = $wpdb->get_results( $sql );
			wp_cache_set( 'largo_related_posts_query', $result );
		}

		$suggestions = array();

		foreach ( $result as $row ) {
			$suggestion['value'] = $row->ID;
			$suggestion['label'] = $row->post_title;
			$suggestion['permalink'] = get_permalink( $row->ID );
			$suggestion['edit_link'] = get_edit_post_link( $row->ID );
			$suggestions[] = $suggestion;
		}

		wp_send_json( $suggestions );

	}

	/**
	 * Perform ajax save
	 *
	 * @since    1.0.0
	 */
	public function related_posts_ajax_save() {

		// Verify form submission is coming from WordPress using a nonce.
		if ( ! isset( $_POST['largo_related_posts_nonce'] ) || ! wp_verify_nonce( $_POST['largo_related_posts_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		// Check if the current user has permission to edit the post.
		if ( ! current_user_can( 'edit_post', $_POST['post_id'] ) ) {
			return;
		}

		$data = array();
		foreach ( $_POST['data'] as $item ) {

			$clean_post_id = sanitize_text_field( $item[0] );
			// Skip over removed item, if set
			if ( isset( $_POST['remove'] ) && $clean_post_id == $_POST['remove'] ) {
				continue;
			} else {
				$data[] = $clean_post_id;
			}

		}

		update_post_meta( sanitize_key( $_POST['post_id'] ), 'manual_related_posts', $data );
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

		// Make sure the form request comes from WordPress.
		wp_nonce_field( basename( __FILE__ ), 'largo_related_posts_nonce' );

		echo esc_html__( 'Start typing to search by post title.', 'mj' ) . '</p>';
		echo '<input type="text" id="se_search_element_id" name="se_search_element_id" value="" />';

		echo '<div id="related-posts-saved">';
			echo '<ul>';
			$related_posts = get_post_meta( $post->ID, 'manual_related_posts', true );
			if ( $related_posts ) {
				foreach ( $related_posts as $related_post ) {
					$title = get_the_title( $related_post );
					$link = get_permalink( $related_post );
					$edit_link = get_edit_post_link( $related_post );
					echo '<li data-id="' . esc_attr( $related_post ) . '" data-title="' . esc_html( $title ) . '">
						<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a><br/>
						<a class="edit-post-link" href="' . esc_url( $edit_link ) . '">Edit Post</a> |
						<a class="remove-related">Remove</a></li>';
				}
			}
			echo '</ul>';
		echo '</div>';

		do_action( 'largo_related_posts_metabox' );
	}


}
