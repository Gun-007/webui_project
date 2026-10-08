<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main"><?php esc_html_e( 'Skip to content', 'eventnest' ); ?></a>
<header class="site-header">
	<div class="wrap site-header__in">
		<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo en_logo_mark(); // phpcs:ignore ?><span><?php bloginfo( 'name' ); ?></span></a>
		<?php endif; ?>

		<nav id="primary-nav" class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'eventnest' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => 'en_fallback_menu', 'depth' => 1 ) ); ?>
		</nav>

		<div class="site-header__actions">
			<button class="icon-btn" id="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Switch light or dark mode', 'eventnest' ); ?>">
				<svg class="i-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
				<svg class="i-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
			</button>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="btn btn--brand btn--sm header-login" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'eventnest' ); ?></a>
			<?php else : ?>
				<a class="btn btn--brand btn--sm header-login" href="<?php echo esc_url( en_opt( 'cta_url' ) ); ?>"><?php echo esc_html( en_opt( 'cta_text' ) ); ?></a>
			<?php endif; ?>
			<button class="icon-btn nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="<?php esc_attr_e( 'Menu', 'eventnest' ); ?>">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>
		</div>
	</div>
</header>
<main id="main">
