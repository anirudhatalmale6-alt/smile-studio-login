<?php
/**
 * Plugin Name: Smile Studio Login
 * Plugin URI:  https://smilecreative.agency/
 * Description: Replaces the standard WordPress login screen with a branded one, and optionally moves it off wp-login.php so the automated traffic that hammers that file never finds a form to hammer. Built to drop onto any site, not just this one -- every label, colour and word is a setting.
 * Version:     1.0.0
 * Author:      Smile Creative
 * Author URI:  https://smilecreative.agency/
 * License:     GPLv2 or later
 * Text Domain: smile-studio-login
 *
 * Two things this does, and they are separate on purpose:
 *
 *   1. APPEARANCE. wp-login.php is restyled -- same form, same WordPress
 *      authentication, nothing intercepted. Passwords are handled by core
 *      exactly as before. A login page that "handles" credentials itself is a
 *      liability, so this one does not.
 *
 *   2. THE ADDRESS. Optionally the login moves to a slug of your choosing and
 *      wp-login.php answers 404. This is the part that actually reduces the
 *      noise: the bulk scanners ask for /wp-login.php and /xmlrpc.php by name
 *      and give up when they get nothing. It is obscurity, not security -- it
 *      does not make a weak password strong -- but it removes the constant
 *      brute-force chatter from the logs, which is what was asked for.
 *
 * The address change is OFF until a slug is set, and there is a defined escape
 * hatch (SSL_EMERGENCY_KEY) so nobody can be locked out of their own site.
 *
 * @package smile-studio-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSL_VERSION', '1.0.0' );
define( 'SSL_FILE', __FILE__ );
define( 'SSL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Settings, with defaults that suit Smile Creative and can be changed per site.
 */
function ssl_opt( $key ) {
	$defaults = array(
		'brand'      => 'Smile Creative',
		'mark'       => 'S',
		'logo'       => '',                       // Optional image URL; replaces the S mark.
		'eyebrow'    => 'Studio access',
		'heading'    => 'Sign in.',
		'lede'       => 'This area is for the studio, not for visitors. If you have landed here looking for the site itself, the link above will take you back.',
		'divider'    => 'Studio only',
		'footnote'   => '',                       // Optional line under the form. HTML links allowed.
		'back_text'  => 'Back to the site',
		'gold'       => '#ffdb00',
		'gold_hi'    => '#ffe64d',
		'ink'        => '#0b0a08',
		'cream'      => '#f4eee2',
		'slug'       => '',                       // '' = leave the login at wp-login.php.
		'footer'     => '',                       // Defaults to the site name and the year.
	);

	$saved = get_option( 'ssl_settings', array() );
	if ( isset( $saved[ $key ] ) && '' !== $saved[ $key ] ) {
		return $saved[ $key ];
	}
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/* -------------------------------------------------------------------------
 * 1. Appearance
 * ---------------------------------------------------------------------- */

/**
 * The whole login stylesheet, printed inline.
 *
 * Inline rather than a file request because the login page is one page loaded
 * rarely -- a second round trip to save 6 KB is the wrong trade -- and because
 * it keeps this plugin to two files, which matters when it is being copied on
 * to fifty sites.
 */
function ssl_login_styles() {
	$gold    = sanitize_hex_color( ssl_opt( 'gold' ) ) ? ssl_opt( 'gold' ) : '#ffdb00';
	$gold_hi = sanitize_hex_color( ssl_opt( 'gold_hi' ) ) ? ssl_opt( 'gold_hi' ) : '#ffe64d';
	$ink     = sanitize_hex_color( ssl_opt( 'ink' ) ) ? ssl_opt( 'ink' ) : '#0b0a08';
	$cream   = sanitize_hex_color( ssl_opt( 'cream' ) ) ? ssl_opt( 'cream' ) : '#f4eee2';

	/*
	 * Fonts are used only if the active theme already self-hosts them, which is
	 * the case on smilecreative.agency. Nothing is fetched from Google: a login
	 * screen that phones a third party on every visit is exactly the kind of
	 * detail that is easy to leave in and hard to notice.
	 */
	$fonts     = get_template_directory() . '/assets/fonts/fonts.css';
	$fonts_url = file_exists( $fonts ) ? get_template_directory_uri() . '/assets/fonts/fonts.css' : '';
	if ( $fonts_url ) {
		printf( "<link rel='stylesheet' href='%s'>\n", esc_url( $fonts_url ) );
	}
	$serif = $fonts_url ? "Fraunces,Georgia,serif" : "Georgia,'Times New Roman',serif";
	$sans  = $fonts_url ? "Archivo,system-ui,-apple-system,sans-serif" : "system-ui,-apple-system,'Segoe UI',sans-serif";
	?>
<style id="ssl-login">
:root{
  --ssl-ink:<?php echo esc_html( $ink ); ?>;
  --ssl-panel:#141210;
  --ssl-gold:<?php echo esc_html( $gold ); ?>;
  --ssl-gold-hi:<?php echo esc_html( $gold_hi ); ?>;
  --ssl-cream:<?php echo esc_html( $cream ); ?>;
  --ssl-dim:rgba(244,238,226,.66);
  --ssl-faint:rgba(244,238,226,.38);
  --ssl-line:rgba(244,238,226,.16);
  --ssl-ease:cubic-bezier(.16,1,.3,1);
  color-scheme:dark;
}
html{background:var(--ssl-ink)}
body.login{
  background:var(--ssl-ink);color:var(--ssl-cream);
  font-family:<?php echo esc_html( $sans ); ?>;
  min-height:100vh;display:flex;flex-direction:column;
  padding:0;margin:0;
}
/* Texture rather than decoration: two very soft pools of the brand colour. */
body.login::before{
  content:"";position:fixed;inset:0;pointer-events:none;
  background:
    radial-gradient(1100px 620px at 50% -8%, rgba(255,219,0,.07), transparent 60%),
    radial-gradient(900px 700px at 100% 100%, rgba(255,219,0,.035), transparent 55%);
}
body.login a{color:inherit;text-decoration:none}

/* ---- top bar ---- */
.ssl-top{
  position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;
  padding:2rem clamp(1.25rem,4vw,3rem);
}
.ssl-brand{display:flex;align-items:center;gap:.7rem}
.ssl-mark{
  width:32px;height:32px;border-radius:50%;border:1.5px solid var(--ssl-gold);
  display:flex;align-items:center;justify-content:center;flex:none;
  font-family:<?php echo esc_html( $serif ); ?>;font-size:.95rem;color:var(--ssl-gold);
}
.ssl-brand img{height:34px;width:auto;display:block}
.ssl-brand b{font-family:<?php echo esc_html( $serif ); ?>;font-size:1.02rem;letter-spacing:.02em;font-weight:600}
.ssl-back{
  font-size:.76rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  color:var(--ssl-dim);display:flex;align-items:center;gap:.5rem;padding:.4rem 0;position:relative;
}
.ssl-back svg{width:13px;height:13px;transition:transform .18s var(--ssl-ease)}
.ssl-back::after{content:"";position:absolute;left:0;right:100%;bottom:0;height:1px;
  background:var(--ssl-gold);transition:right .22s var(--ssl-ease)}
@media (hover:hover){
  .ssl-back:hover{color:var(--ssl-cream)}
  .ssl-back:hover::after{right:0}
  .ssl-back:hover svg{transform:translateX(-3px)}
}

/* ---- the panel ---- */
/* The panel sits in the middle of what is left after the brand bar. WordPress
   ships #login with `padding:8% 0 0`, which on a tall screen leaves the form
   stranded near the top with the divider marooned at the bottom. */
#login{
  width:100%;max-width:400px;margin:auto;padding:1rem 1.25rem 0;
  position:relative;z-index:1;
  animation:ssl-rise .65s var(--ssl-ease) both;
}
body.login #login h1{display:none}          /* the WordPress logo block */
body.login #login h1 a{display:none}
.ssl-eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;
  color:var(--ssl-gold);margin-bottom:1rem;display:block}
.ssl-h1{font-family:<?php echo esc_html( $serif ); ?>;font-weight:600;
  font-size:clamp(2rem,5vw,2.7rem);line-height:1.05;margin:0 0 .6rem;letter-spacing:-.01em}
.ssl-lede{color:var(--ssl-dim);font-size:.92rem;line-height:1.6;margin:0 0 2.2rem;max-width:34ch}

/* ---- the form itself ---- */
.login form{
  background:transparent;border:0;box-shadow:none;padding:0;margin-top:.4rem;overflow:visible;
}
.login form .input,
.login input[type=text],
.login input[type=password],
.login input[type=email]{
  width:100%;background:transparent;border:0;border-bottom:1px solid var(--ssl-line);
  border-radius:0;box-shadow:none;outline:none;color:var(--ssl-cream);
  font-family:<?php echo esc_html( $sans ); ?>;font-size:1rem;
  padding:.2rem 0 .6rem;margin:0 0 1.7rem;transition:border-color .22s var(--ssl-ease);
}
.login form .input:focus,
.login input[type=text]:focus,
.login input[type=password]:focus,
.login input[type=email]:focus{
  border-color:var(--ssl-gold);box-shadow:none;outline:0;
}
.login label{
  display:block;font-size:.72rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
  color:var(--ssl-faint);margin-bottom:.55rem;
}
/* Core's markup is `<p class="forgetmenot"><input><label></label></p>` -- the
   label is a SIBLING of the checkbox, not a wrapper, so the row has to be the
   flex container or the words drop underneath the box. */
.login .forgetmenot{float:none;margin:0 0 .4rem;display:flex;align-items:center;gap:.6rem}
.login .forgetmenot label{
  text-transform:none;letter-spacing:0;
  font-size:.82rem;font-weight:400;color:var(--ssl-dim);margin:0;cursor:pointer;
}
/* body.login in the selector on purpose: core's own checkbox rules are
   `.wp-core-ui input[type=checkbox]`, which ties on specificity, and a tie is
   settled by load order -- which is not something to rely on. */
body.login input[type="checkbox"]{
  appearance:none;-webkit-appearance:none;width:17px;height:17px;flex:none;margin:0;
  border:1.5px solid var(--ssl-line);background:transparent;border-radius:0;position:relative;
  box-shadow:none;transition:border-color .18s,background .18s;
}
body.login input[type="checkbox"]:focus{border-color:var(--ssl-gold);box-shadow:none;outline:0}
body.login input[type="checkbox"]:checked{border-color:var(--ssl-gold);background:rgba(255,219,0,.14)}
body.login input[type="checkbox"]:checked::before{
  content:"";position:absolute;left:4px;top:0;width:6px;height:11px;
  border:solid var(--ssl-gold);border-width:0 2px 2px 0;transform:rotate(45deg);
  margin:0;color:transparent;font-size:0;background:none;
}
.login .submit{margin-top:2rem}
.login .button-primary,
.wp-core-ui .button-primary{
  width:100%;float:none;display:flex;align-items:center;justify-content:center;
  padding:1rem 1.4rem;height:auto;line-height:1;
  background:var(--ssl-gold);border:1px solid var(--ssl-gold);color:var(--ssl-ink);
  font-size:.82rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  border-radius:0;box-shadow:none;text-shadow:none;
  transition:background .22s var(--ssl-ease),box-shadow .22s var(--ssl-ease),transform .12s;
}
.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus{
  background:var(--ssl-gold-hi);border-color:var(--ssl-gold-hi);color:var(--ssl-ink);
  box-shadow:0 10px 30px -12px rgba(255,219,0,.5);
}
.wp-core-ui .button-primary:active{transform:scale(.985)}
.login .wp-pwd{position:relative}
.login .wp-pwd .button.wp-hide-pw{
  background:none;border:0;box-shadow:none;color:var(--ssl-faint);
  top:.1rem;right:0;height:auto;
}
.login .wp-pwd .button.wp-hide-pw:hover{color:var(--ssl-gold-hi)}
.login .wp-pwd .button.wp-hide-pw .dashicons{width:19px;height:19px;font-size:19px}

/* messages, links, the bits underneath */
.login #login_error,.login .message,.login .success{
  background:rgba(255,219,0,.06);border:0;border-left:2px solid var(--ssl-gold);
  color:var(--ssl-cream);box-shadow:none;padding:.9rem 1.1rem;margin:0 0 1.6rem;font-size:.88rem;
}
.login #login_error{border-left-color:#ff7a6b;background:rgba(255,122,107,.08)}
.login #nav,.login #backtoblog{
  padding:0;margin:1.4rem 0 0;text-align:left;font-size:.78rem;
}
.login #nav a,.login #backtoblog a{color:var(--ssl-dim);font-weight:600}
.login #nav a:hover,.login #backtoblog a:hover{color:var(--ssl-gold-hi)}
.login #backtoblog{display:none}     /* the top bar already offers this */
/* The divider and footnote are printed by login_footer, which is outside
   #login, so they have to be re-constrained to the same column or they run the
   full width of the screen. */
/* width:100% is load-bearing. These are flex items of the body, and a flex item
   with `margin:auto` and no width shrink-wraps to its own content -- the
   divider came out 165px wide in the middle of a 1280px screen. Same trap as
   .wrap in the theme, and it does not look like a bug until you measure it. */
.ssl-divider,.ssl-footnote{width:100%;max-width:400px;margin-left:auto;margin-right:auto;
  padding-left:1.25rem;padding-right:1.25rem;position:relative;z-index:1}
.ssl-divider{
  display:flex;align-items:center;gap:1rem;margin-top:2.2rem;margin-bottom:1.4rem;
  color:var(--ssl-faint);font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;
}
.ssl-divider::before,.ssl-divider::after{content:"";flex:1;height:1px;background:var(--ssl-line)}
.ssl-footnote{font-size:.82rem;color:var(--ssl-faint);text-align:center;line-height:1.6;
  margin-top:0;margin-bottom:0}   /* NOT `margin:0` -- that resets the auto side margins above */
.ssl-footnote a{color:var(--ssl-dim);font-weight:600}
.ssl-footnote a:hover{color:var(--ssl-gold-hi)}
.ssl-foot{
  position:relative;z-index:1;margin-top:auto;text-align:center;
  padding:0 1.25rem 2.4rem;font-size:.76rem;color:var(--ssl-faint);
}
.login .privacy-policy-page-link{margin:1.2rem 0 0;text-align:left}
.login .privacy-policy-page-link a{color:var(--ssl-faint);font-size:.78rem}
.login .privacy-policy-page-link a:hover{color:var(--ssl-gold-hi)}
.login .language-switcher{display:none}

@keyframes ssl-rise{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){
  #login{animation:none}
  *{transition-duration:.001ms !important}
}
@media (max-width:480px){
  .ssl-top{padding:1.5rem 1.25rem}
  .ssl-back span{display:none}
}
</style>
	<?php
}
add_action( 'login_head', 'ssl_login_styles' );

/**
 * The brand bar across the top of the login screen.
 */
function ssl_login_top() {
	$logo = ssl_opt( 'logo' );
	?>
	<div class="ssl-top">
		<span class="ssl-brand">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( ssl_opt( 'brand' ) ); ?>">
			<?php else : ?>
				<span class="ssl-mark"><?php echo esc_html( ssl_opt( 'mark' ) ); ?></span>
				<b><?php echo esc_html( ssl_opt( 'brand' ) ); ?></b>
			<?php endif; ?>
		</span>
		<a class="ssl-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
			<span><?php echo esc_html( ssl_opt( 'back_text' ) ); ?></span>
		</a>
	</div>
	<?php
}
add_action( 'login_header', 'ssl_login_top' );

/**
 * The heading block above the form.
 *
 * Only on the sign-in screen -- the lost-password and reset screens keep their
 * own wording, because telling somebody "sign in" while they are resetting a
 * password is the sort of small wrongness that makes a site feel careless.
 */
function ssl_login_message( $message ) {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'login' !== $action ) {
		return $message;
	}

	ob_start();
	?>
	<span class="ssl-eyebrow"><?php echo esc_html( ssl_opt( 'eyebrow' ) ); ?></span>
	<h2 class="ssl-h1"><?php echo esc_html( ssl_opt( 'heading' ) ); ?></h2>
	<p class="ssl-lede"><?php echo esc_html( ssl_opt( 'lede' ) ); ?></p>
	<?php
	return ob_get_clean() . $message;
}
add_filter( 'login_message', 'ssl_login_message' );

/**
 * Divider, optional footnote, and the line along the bottom.
 */
function ssl_login_footer() {
	$footnote = ssl_opt( 'footnote' );
	$foot     = ssl_opt( 'footer' );
	if ( '' === $foot ) {
		$foot = sprintf(
			/* translators: 1: year, 2: site name */
			__( '© %1$s %2$s', 'smile-studio-login' ),
			gmdate( 'Y' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
	}
	?>
	<div class="ssl-divider"><?php echo esc_html( ssl_opt( 'divider' ) ); ?></div>
	<?php if ( $footnote ) : ?>
		<p class="ssl-footnote"><?php echo wp_kses_post( $footnote ); ?></p>
	<?php endif; ?>
	<div class="ssl-foot"><?php echo esc_html( $foot ); ?></div>
	<?php
}
add_action( 'login_footer', 'ssl_login_footer' );

/**
 * Point the (now hidden) logo link and its title at the site rather than at
 * wordpress.org, so nothing on the page advertises what it is running.
 */
add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return ssl_opt( 'brand' ); } );

/**
 * The stock title is "Log In &lsaquo; Site Name &#8212; WordPress", which tells
 * a scanner what it has found even when the page looks like nothing familiar.
 */
add_filter( 'login_title', function ( $title ) {
	return sprintf( '%s — %s', __( 'Sign in', 'smile-studio-login' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
}, 20 );

/**
 * Say the same thing whichever field was wrong.
 *
 * The stock messages distinguish "unknown username" from "the password you
 * entered for the username X is incorrect", which confirms to anyone guessing
 * that X exists. One message for both tells them nothing.
 */
function ssl_vague_errors( $errors ) {
	$codes = $errors->get_error_codes();
	if ( ! $codes ) {
		return $errors;
	}
	$leaky = array( 'invalid_username', 'invalid_email', 'incorrect_password', 'invalidcombo' );
	foreach ( $codes as $code ) {
		if ( in_array( $code, $leaky, true ) ) {
			$fresh = new WP_Error();
			$fresh->add( 'denied', __( '<strong>That did not work.</strong> Check the username and password and try again.', 'smile-studio-login' ) );
			return $fresh;
		}
	}
	return $errors;
}
add_filter( 'wp_login_errors', 'ssl_vague_errors' );
add_filter( 'shake_error_codes', function ( $codes ) { $codes[] = 'denied'; return $codes; } );

/* -------------------------------------------------------------------------
 * 2. The address
 * ---------------------------------------------------------------------- */

/**
 * The configured slug, or '' when the feature is switched off.
 */
function ssl_slug() {
	$slug = trim( (string) ssl_opt( 'slug' ), "/ \t\n\r" );
	return sanitize_title( $slug );
}

/**
 * Everything below only runs when a slug has been set. With no slug the plugin
 * is a skin and nothing more, which is the safe default for a site somebody
 * else has to log in to.
 */
function ssl_hide_login_active() {
	return '' !== ssl_slug();
}

/**
 * Serve wp-login.php at the new address.
 */
function ssl_route() {
	if ( ! ssl_hide_login_active() ) {
		return;
	}

	$req  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = trim( (string) wp_parse_url( $req, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( '' !== $home && 0 === strpos( $path, $home ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}

	if ( $path === ssl_slug() ) {
		// Tell wp-login.php it is being loaded normally.
		global $pagenow;
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		/*
		 * Mark the request as having come in by the front door. Without this the
		 * 404 guard below fires on the new address too -- it hooks login_init,
		 * which runs whether wp-login.php was reached directly or included from
		 * here, so the new login answered 404 as well as the old one.
		 */
		define( 'SSL_VIA_SLUG', true );
		require_once ABSPATH . 'wp-login.php';
		exit;
	}
}
/*
 * wp_loaded, not plugins_loaded. Loading wp-login.php at plugins_loaded gives a
 * 500: $wp_query does not exist yet at that point, and the first plugin whose
 * wp_head callback asks get_queried_object() -- All in One SEO here -- fatals
 * on null. By wp_loaded WordPress is fully set up and wp-login.php behaves
 * exactly as it does when the web server calls it directly.
 */
add_action( 'wp_loaded', 'ssl_route', 1 );

/**
 * And give a 404 at the old one.
 *
 * The escape hatch: put
 *
 *     define( 'SSL_EMERGENCY_KEY', 'something-long-and-private' );
 *
 * in wp-config.php and /wp-login.php?SSL_EMERGENCY_KEY=... still works. Nobody
 * gets locked out of their own site by a plugin of mine.
 */
function ssl_block_default_login() {
	if ( ! ssl_hide_login_active() || defined( 'SSL_VIA_SLUG' ) ) {
		return;
	}
	if ( defined( 'SSL_EMERGENCY_KEY' ) && isset( $_GET[ SSL_EMERGENCY_KEY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	// A logout has to be allowed through: it carries a nonce and it arrives at
	// wp-login.php by definition.
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( in_array( $action, array( 'logout', 'postpass' ), true ) ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}

	global $wp_query;
	status_header( 404 );
	nocache_headers();
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}
	$tpl = get_404_template();
	if ( $tpl ) {
		include $tpl;
	} else {
		echo '<h1>Not found</h1>';
	}
	exit;
}
add_action( 'login_init', 'ssl_block_default_login', 1 );

/**
 * Rewrite every URL WordPress builds for wp-login.php to the new slug, so
 * password-reset emails, redirects and the "log in" links all point somewhere
 * that exists.
 */
function ssl_filter_login_url( $url ) {
	if ( ! ssl_hide_login_active() || ! is_string( $url ) ) {
		return $url;
	}
	return str_replace( 'wp-login.php', ssl_slug(), $url );
}
add_filter( 'site_url', 'ssl_filter_login_url' );
add_filter( 'network_site_url', 'ssl_filter_login_url' );
add_filter( 'wp_redirect', 'ssl_filter_login_url' );
add_filter( 'login_url', 'ssl_filter_login_url' );
add_filter( 'lostpassword_url', 'ssl_filter_login_url' );
add_filter( 'register_url', 'ssl_filter_login_url' );
add_filter( 'logout_url', 'ssl_filter_login_url' );

/**
 * /wp-admin for a logged-out visitor normally bounces to wp-login.php, which
 * would hand the new address straight back to whoever asked. Give a 404
 * instead.
 */
function ssl_block_admin_redirect() {
	if ( ! ssl_hide_login_active() || is_user_logged_in() || wp_doing_ajax() ) {
		return;
	}
	if ( defined( 'SSL_EMERGENCY_KEY' ) && isset( $_GET[ SSL_EMERGENCY_KEY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$req = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( false === strpos( $req, 'admin-post.php' ) && false === strpos( $req, 'admin-ajax.php' ) ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
}
add_action( 'admin_init', 'ssl_block_admin_redirect', 1 );

/* -------------------------------------------------------------------------
 * 3. A settings screen, so none of the above needs a developer
 * ---------------------------------------------------------------------- */

require_once plugin_dir_path( __FILE__ ) . 'admin.php';
