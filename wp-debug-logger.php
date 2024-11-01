<?php
/*
Plugin Name: WP Debug Logger
Plugin URI: http://wordpress.org/extend/plugins/wp-debug-logger/
Description: A debug framework for logging plugin activity
Version: 0.1
Author: Donncha O Caoimh
Author URI: http://ocaoimh.ie/
*/

if ( false == defined( 'WP_DEBUG_LOG' ) )
	define( 'WP_DEBUG_LOG', 1 );

class WP_Log_Plugin {
	static $instance;
	const enabled      = 'wp_debug_logger_enabled';
	const log_filename = 'wp_debug_logger_filename';
	const ip_address   = 'wp_debug_logger_ip';
	const plugin_list  = 'wp_debug_logger_plugins';

	public function __construct() {
		self::$instance =& $this;
		add_action( 'init', array( $this, 'init' ) );
		// need to cache this because get_option() doesn't work reliably during shutdown.
		$GLOBALS[ 'wp_log_settings' ] = array(	'ip_address'   => get_option( self::ip_address ),
							'plugin_list'  => get_option( self::plugin_list ),
							'enabled'      => get_option( self::enabled ),
							'log_filename' => get_option( self::log_filename ),
							'upload_dir'   => wp_upload_dir() );
	}

	public function init() {
		// Translations
		load_plugin_textdomain( 'wp-debug-logger', false, basename( dirname( __FILE__ ) ) . '/i18n' );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		if ( false == is_super_admin() )
			return false;

		$callback = add_options_page( 'WP Debug Logger', 'WP Logger', 'manage_options', 'log_config', array( $this, 'admin_page' ) );
		add_action( "load-{$callback}", array( &$this, 'load_admin' ) );
	}

	public function load_admin() {
		add_option( self::enabled, 0 );
		if ( get_option( self::log_filename ) == false )
			update_option( self::log_filename, md5( date( 'Y-m-d H:i:s' ) ) . ".txt" );
		if ( $_POST ) {
			if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'deletelog' ) {
				check_admin_referer( 'wp-debug-logger-delete-log' );
				$upload = wp_upload_dir();
				if ( @file_exists( trailingslashit( $upload[ 'basedir' ] ) . get_option( self::log_filename ) ) ) {
					@unlink( trailingslashit( $upload[ 'basedir' ] ) . get_option( self::log_filename ) );
					set_transient( 'wp-debug-logger-updated', 'deleted', 120 );
				}
			} else {
				check_admin_referer( 'wp-debug-logger-update-options' );
				update_option( self::enabled, (int)$_POST[self::enabled] );
				update_option( self::ip_address, $_POST[self::ip_address] );
				update_option( self::plugin_list, $_POST[self::plugin_list] );
				set_transient( 'wp-debug-logger-updated', 'updated', 120 );
			}
			wp_redirect( admin_url( 'options-general.php?page=log_config' ) );
			exit();
		} elseif ( $this->updated = get_transient( 'wp-debug-logger-updated' ) ) {
			delete_transient( 'wp-debug-logger-updated' );
			add_action( 'wp-debug-logger-notices', array( $this, 'updated' ) );
		}
	}

	public function updated() {
		echo '<div class="updated"><p><strong>';
		switch( $this->updated ) {
			case "updated":
			echo __( 'Settings saved.', 'wp-debug-logger' );
			break;
			case "deleted":
			echo __( 'Log file deleted.', 'wp-debug-logger' );
			break;
		}
		echo '</strong></p></div>';
	}

	public function admin_page() {
		$upload = wp_upload_dir();
?>
	<div class="wrap">
	<?php screen_icon( 'edit-pages' ); ?>
	<h2><?php echo esc_html( $GLOBALS['title'] ); ?></h2>
	<?php do_action( 'wp-debug-logger-notices' ); ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'wp-debug-logger-update-options' ); ?>
		<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="wp-debug-logger-enabled"><?php _e( 'Enable Logging', 'wp-debug-logger' ); ?></label></th>
		<td><fieldset><input type="checkbox" name="<?php echo self::enabled; ?>" id="wp-debug-logger-enabled" <?php checked( get_option( self::enabled ), 1 ); ?> value="1" />
		</fieldset></td></tr>
		<tr valign="top">
		<th scope="row"><?php _e( 'Enabled Plugins', 'wp-debug-logger' ); ?></th>
		<td>
		<?php
		if ( isset( $GLOBALS[ 'wp_log_plugins' ] ) && is_array( $GLOBALS[ 'wp_log_plugins' ] ) ) {
			?>
			<fieldset>
			<?php
			foreach( $GLOBALS[ 'wp_log_plugins' ] as $plugin_name ) {
				?><input type="checkbox" name="<?php echo self::plugin_list; ?>[]" id="wp_log_plugins" <?php checked( in_array( $plugin_name, (array)get_option( self::plugin_list ) ), 1 ); ?> value="<?php echo $plugin_name; ?>" /> <?php echo $plugin_name;
			}
			?><p><?php _e( 'Only plugins enabled above will be logged.', 'wp-debug-logger' ); ?></p>
			</fieldset><?php
		} else {
			?><p><?php _e( 'No supported plugins found.', 'wp-debug-logger' ); ?></p><?php
		}
		?></td></tr>
		<tr valign="top">
		<th scope="row"><?php _e( 'Limit by IP address', 'wp-debug-logger' ); ?></th>
		<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Limit by IP address', 'wp-debug-logger' ); ?></span></legend>
		<p><input type="text" name="<?php echo self::ip_address; ?>" id="wp-debug-logger-ip" value='<?php echo esc_attr( get_option( self::ip_address ) ); ?>' /></p>
		<p><?php printf( __( 'Only log requests from this IP address. (Your IP address is %s)', 'wp-debug-logger' ), $_SERVER[ 'REMOTE_ADDR' ] ); ?></p>
		</fieldset></td></tr>
		</table>
	<?php submit_button(); ?>
	</form>
	<p><?php printf( __( '<strong>Log file:</strong> %s', 'wp-debug-logger' ), "<a href='" . trailingslashit( $upload[ 'baseurl' ] ) . get_option( self::log_filename ) . "'>" . trailingslashit( $upload[ 'baseurl' ] ) . get_option( self::log_filename ) . "</a>" ); ?></p>
	<?php
	if ( @file_exists( trailingslashit( $upload[ 'basedir' ] ) . get_option( self::log_filename ) ) ) {
		?>
		<form method="post" action="">
		<?php wp_nonce_field( 'wp-debug-logger-delete-log' ); ?>
		<input type='hidden' name='action' value='deletelog' />
		<?php submit_button( __( 'Delete Log File', 'wp-debug-logger' ) ); ?>
		</form>
		<?php
	}
	?>
	</div>

<?php
	}

}

new WP_Log_Plugin;

function wp_log_everything() {
	if ( $GLOBALS[ 'wp_log_settings' ][ 'enabled' ] == 0 )
		return false;

	if ( false == is_array( $GLOBALS[ 'wp_log' ] ) )
		return false;

	if ( $GLOBALS[ 'wp_log_settings' ][ 'ip_address' ] && $_SERVER[ 'REMOTE_ADDR' ] != $GLOBALS[ 'wp_log_settings' ][ 'ip_address' ] )
			return false;

	$plugin_list = $GLOBALS[ 'wp_log_settings' ][ 'plugin_list' ];

	$upload = wp_upload_dir();
	foreach ( $GLOBALS[ 'wp_log' ] as $plugin => $messages ) {
		if ( false == in_array( $plugin, $plugin_list ) )
			continue;
		foreach ( $messages as $message ) {
			error_log( date( 'Y-m-d H:i:s' ) . " " . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] . " *$plugin* $message\n", 3, trailingslashit( $GLOBALS[ 'wp_log_settings' ][ 'upload_dir' ][ 'basedir' ] ) . $GLOBALS[ 'wp_log_settings' ][ 'log_filename' ] );
		}
	}
}
add_action( 'shutdown', 'wp_log_everything' );
?>
