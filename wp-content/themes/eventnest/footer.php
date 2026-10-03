</main>
<footer class="site-footer">
	<div class="wrap site-footer__in">
		<div>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo en_logo_mark(); // phpcs:ignore ?><span><?php bloginfo( 'name' ); ?></span></a>
			<p class="site-footer__tag"><?php echo esc_html( en_opt( 'footer_text' ) ); ?></p>
			<?php $wa = preg_replace( '/\D/', '', (string) en_opt( 'whatsapp' ) ); if ( $wa ) : ?>
				<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( 'https://wa.me/' . $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Chat on WhatsApp', 'eventnest' ); ?></a>
			<?php endif; ?>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Footer', 'eventnest' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => 'en_fallback_menu', 'depth' => 1 ) ); ?>
		</nav>
	</div>
	<div class="wrap site-footer__legal">
		<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
