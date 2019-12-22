<?php
/**
 * Renders the Entry List Page.
 *
 * @package Formation
 */

namespace Formation\UI;

use Formation\Component;
use Formation\Component\Utility\Input;
use Formation\UI\Extend\Entry_List_Table;

/**
 * Renders the Entry List Page.
 */
class Entry_List_Page implements Component\Setup {

	/**
	 * Holds the plugin instance.
	 *
	 * @since   0.1
	 * @var     Plugin Instance of the global plugin.
	 */
	private $plugin;

	/**
	 * The settings Title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The list table.
	 *
	 * @var \Formation\UI\Extent\Entry_List_Table
	 */
	private $list_table;

	/**
	 * Parent form ID.
	 *
	 * @var int
	 */
	private $parent_id;

	/**
	 * The list table screen hook name.
	 *
	 * @var mixed
	 */
	private $screen;

	/**
	 * Initiate the plugin resources.
	 *
	 * @param object $plugin Instance of the plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin    = $plugin;
		$this->title     = __( 'Form Entries', 'formation' );
		$this->parent_id = Input::int( 'parent', 0 );
	}

	/**
	 * Setup hooks
	 *
	 * @since  0.1
	 */
	public function setup() {
		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
		add_action( 'admin_menu', array( $this, 'add_list_page' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @param mixed $status Unused.
	 * @param mixed $option Unused.
	 * @param mixed $value Magic.
	 * @return mixed
	 */
	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	/**
	 * Creates the list page.
	 *
	 * @return void
	 */
	public function add_list_page() {
		global $menu;

		$this->screen = add_menu_page(
			'',
			'',
			'manage_options',
			\Formation\Entry::$slug,
			array( $this, 'render_list_page' ),
			'',
			0
		);

		foreach ( $menu as $key => $item ) {
			if ( \Formation\Entry::$slug === $item[2] ) {
				unset( $menu[ $key ] );
				break;
			}
		}

		add_action( "load-$this->screen", array( $this, 'add_screen_options' ) );
	}

	/**
	 * Set Screen Options.
	 *
	 * @return void
	 */
	public function add_screen_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Entries', 'formation' ),
			'default' => 25,
			'option'  => 'entries_per_page',
		);

		add_screen_option( $option, $args );

		// This is a good place, because it needs to happen before screen options
		// panel is loaded.
		$this->list_table = new Entry_List_Table( $this->parent_id, $this->screen );

		// Processing actions any later breaks required redirects.
		$this->list_table->process_action();
	}

	/**
	 * Because WP_List_Table's views are broken.
	 *
	 * @return void
	 */
	public function view_filters() {
		$views = apply_filters( "views_{$this->screen}", array() );

		echo "<ul class='subsubsub'>\n";
		$views_out = array();
		foreach ( $views as $class => $view ) {
			$views_out[] = sprintf(
				'<li class="%s">%s</li>',
				$class,
				$view
			);
		}
		echo wp_kses_post(
			implode( ' &#124; ', $views_out )
		);
		echo '</ul>';
	}

	/**
	 * Render the list page.
	 *
	 * @return void
	 */
	public function render_list_page() {

		$this->list_table->prepare_items();

		$parent      = get_post( $this->parent_id );
		$entries_cap = apply_filters( 'formation_view_entries_capability', 'manage_options' );

		// @TODO Move this to the forms class once its ready to be contributed to.
		$has_access = ( 0 !== $this->parent_id && \get_current_user_id() === $parent->post_author )
						|| current_user_can( 'manage_options' )
						|| current_user_can( $entries_cap )
						|| apply_filters( 'formation_can_access_form_entries', $is_form_owner )
						|| apply_filters( "formation_can_access_form_{$this->parent_id}_entries", $is_form_owner );

		if ( 0 === $this->parent_id || ! $has_access ) {
			?>
			<div class="wrap">
				<p><?php esc_html_e( 'Nothing to see here.', 'formation' ); ?>
			</div>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->title ); ?></h2>
			<p class="description">
			<?php

			$form_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url_raw( get_post_permalink( $this->parent_id ) ),
				esc_url( get_post_permalink( $this->parent_id ) )
			);

			echo sprintf(
				'<p><strong>%1$s<small> [ID: %2$d]</small></strong> &mdash; %3$s</p>',
				esc_html( $parent->post_title ),
				(int) $this->parent_id,
				wp_kses_post( $form_link )
			);
			?>
			</p>

			<?php
				$this->view_filters();
			?>

			<div class="formation-entry-list-table">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<?php
						$this->list_table->display();
						?>
					</form>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}
}
