<?php
/**
 * Settings screen for Smile Studio Login.
 *
 * Settings -> Studio Login. Deliberately one page of plain fields: the point of
 * this plugin is that it can be dropped on a client site and handed over, and
 * anything that needs a developer to change is a thing that will never be
 * changed.
 *
 * @package smile-studio-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the page.
 */
function ssl_admin_menu() {
	add_options_page(
		__( 'Studio Login', 'smile-studio-login' ),
		__( 'Studio Login', 'smile-studio-login' ),
		'manage_options',
		'smile-studio-login',
		'ssl_admin_page'
	);
}
add_action( 'admin_menu', 'ssl_admin_menu' );

/**
 * The fields, in the order they appear.
 */
function ssl_fields() {
	return array(
		'brand'     => array( __( 'Brand name', 'smile-studio-login' ), 'text', __( 'Shown top left, beside the mark.', 'smile-studio-login' ) ),
		'mark'      => array( __( 'Mark', 'smile-studio-login' ), 'text', __( 'One or two letters inside the circle. Ignored if a logo is set.', 'smile-studio-login' ) ),
		'logo'      => array( __( 'Logo URL', 'smile-studio-login' ), 'url', __( 'Optional. Replaces the circle and the brand name.', 'smile-studio-login' ) ),
		'eyebrow'   => array( __( 'Small line above the heading', 'smile-studio-login' ), 'text', '' ),
		'heading'   => array( __( 'Heading', 'smile-studio-login' ), 'text', '' ),
		'lede'      => array( __( 'Paragraph under the heading', 'smile-studio-login' ), 'textarea', '' ),
		'divider'   => array( __( 'Divider text', 'smile-studio-login' ), 'text', '' ),
		'footnote'  => array( __( 'Footnote under the form', 'smile-studio-login' ), 'textarea', __( 'Optional. A link is allowed here.', 'smile-studio-login' ) ),
		'back_text' => array( __( '"Back" link text', 'smile-studio-login' ), 'text', '' ),
		'footer'    => array( __( 'Line along the bottom', 'smile-studio-login' ), 'text', __( 'Leave blank for the site name and the current year.', 'smile-studio-login' ) ),
		'gold'      => array( __( 'Accent colour', 'smile-studio-login' ), 'color', '' ),
		'gold_hi'   => array( __( 'Accent colour, hover', 'smile-studio-login' ), 'color', '' ),
		'ink'       => array( __( 'Background', 'smile-studio-login' ), 'color', '' ),
		'cream'     => array( __( 'Text', 'smile-studio-login' ), 'color', '' ),
	);
}

/**
 * Save and render.
 */
function ssl_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$saved  = get_option( 'ssl_settings', array() );
	$notice = '';

	if ( isset( $_POST['ssl_save'] ) && check_admin_referer( 'ssl_save_settings' ) ) {
		$new = array();

		foreach ( ssl_fields() as $key => $f ) {
			$raw = isset( $_POST[ 'ssl_' . $key ] ) ? wp_unslash( $_POST[ 'ssl_' . $key ] ) : '';
			switch ( $f[1] ) {
				case 'url':
					$new[ $key ] = esc_url_raw( $raw );
					break;
				case 'color':
					$new[ $key ] = sanitize_hex_color( $raw );
					break;
				case 'textarea':
					$new[ $key ] = wp_kses_post( $raw );
					break;
				default:
					$new[ $key ] = sanitize_text_field( $raw );
			}
		}

		/*
		 * The slug is the one field that can lock somebody out, so it is
		 * checked rather than merely sanitised: a slug that collides with a
		 * real page would make that page unreachable, and reserved words break
		 * the admin.
		 */
		$slug     = sanitize_title( wp_unslash( isset( $_POST['ssl_slug'] ) ? $_POST['ssl_slug'] : '' ) );
		$reserved = array( 'wp-admin', 'wp-login', 'wp-content', 'wp-includes', 'admin', 'feed', 'index', 'wp-json' );

		if ( '' !== $slug && in_array( $slug, $reserved, true ) ) {
			$notice = __( 'That address is reserved by WordPress. Everything else was saved; the login address was left alone.', 'smile-studio-login' );
			$slug   = isset( $saved['slug'] ) ? $saved['slug'] : '';
		} elseif ( '' !== $slug && get_page_by_path( $slug ) ) {
			$notice = __( 'A page already exists at that address. Everything else was saved; the login address was left alone.', 'smile-studio-login' );
			$slug   = isset( $saved['slug'] ) ? $saved['slug'] : '';
		}

		$new['slug'] = $slug;
		update_option( 'ssl_settings', $new );
		$saved = $new;

		if ( '' === $notice ) {
			$notice = __( 'Saved.', 'smile-studio-login' );
		}
	}

	$val = function ( $k ) use ( $saved ) {
		return isset( $saved[ $k ] ) ? $saved[ $k ] : ssl_opt( $k );
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Studio Login', 'smile-studio-login' ); ?></h1>

		<?php if ( $notice ) : ?>
			<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'ssl_save_settings' ); ?>

			<h2 class="title"><?php esc_html_e( 'Where the login lives', 'smile-studio-login' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ssl_slug"><?php esc_html_e( 'Login address', 'smile-studio-login' ); ?></label></th>
					<td>
						<code><?php echo esc_html( trailingslashit( home_url() ) ); ?></code>
						<input name="ssl_slug" id="ssl_slug" type="text" class="regular-text" value="<?php echo esc_attr( ssl_slug() ); ?>" placeholder="studio">
						<p class="description">
							<?php esc_html_e( 'Leave blank to keep the standard /wp-login.php. Fill it in and wp-login.php answers 404 instead, which is what stops the automated login attempts: they ask for that file by name.', 'smile-studio-login' ); ?>
							<br>
							<strong><?php esc_html_e( 'Write the new address down before you save.', 'smile-studio-login' ); ?></strong>
							<?php esc_html_e( 'If it is ever lost, add this line to wp-config.php and /wp-login.php?letmein=1 works again:', 'smile-studio-login' ); ?>
							<br><code>define( 'SSL_EMERGENCY_KEY', 'letmein' );</code>
						</p>
						<?php if ( ssl_hide_login_active() ) : ?>
							<p class="description">
								<?php esc_html_e( 'Currently live at:', 'smile-studio-login' ); ?>
								<a href="<?php echo esc_url( home_url( '/' . ssl_slug() . '/' ) ); ?>"><?php echo esc_html( home_url( '/' . ssl_slug() . '/' ) ); ?></a>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'How it looks', 'smile-studio-login' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( ssl_fields() as $key => $f ) : ?>
					<tr>
						<th scope="row"><label for="ssl_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $f[1] ) : ?>
								<textarea name="ssl_<?php echo esc_attr( $key ); ?>" id="ssl_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $val( $key ) ); ?></textarea>
							<?php else : ?>
								<input name="ssl_<?php echo esc_attr( $key ); ?>" id="ssl_<?php echo esc_attr( $key ); ?>"
									type="<?php echo 'color' === $f[1] ? 'text' : esc_attr( $f[1] ); ?>"
									class="<?php echo 'color' === $f[1] ? 'small-text' : 'regular-text'; ?>"
									value="<?php echo esc_attr( $val( $key ) ); ?>">
							<?php endif; ?>
							<?php if ( $f[2] ) : ?><p class="description"><?php echo esc_html( $f[2] ); ?></p><?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( __( 'Save', 'smile-studio-login' ), 'primary', 'ssl_save' ); ?>
		</form>
	</div>
	<?php
}
