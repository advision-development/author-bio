<?php
/**
 * Author index — fintech product.
 *
 * Mirrors the composition of templates/template-8.php so the index reads as
 * that template's own directory rather than a generic list.
 *
 * @var array $d authors, header, heading, stats, site
 */

defined( 'ABSPATH' ) || exit;

$abio_n     = 0;
$abio_total = count( $d['authors'] );
$abio_label = '' !== $d['heading']
	? $d['heading']
	: sprintf( _n( 'Author · %d', 'Authors · %d', $abio_total, 'author-bio' ), $abio_total );
?>

<div class="abio-l8">
	<?php if ( $d['header'] ) : ?>
	<header class="abio-l8__bar">
		<span class="abio-l8__bar-label"><?php echo esc_html( $abio_label ); ?></span>
		<?php if ( '' !== $d['site']['authorsUrl'] ) : ?>
			<a class="abio-l8__bar-link" href="<?php echo esc_url( $d['site']['authorsUrl'] ); ?>"><?php esc_html_e( 'Editorial team', 'author-bio' ); ?></a>
		<?php endif; ?>
	</header>
	<?php endif; ?>

	<ul class="abio-l8__rows">
		<?php foreach ( $d['authors'] as $a ) : ?>
			<li>
				<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'thumbnail', 'portrait 1:1', 'abio-l8__img', $a['name']
					);
					?>
				<div class="abio-l8__body">
					<div class="abio-l8__line">
						<h3 class="abio-l8__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
						<span class="abio-l8__pill"><?php echo esc_html( $a['kicker'] ); ?></span>
					</div>
					<?php if ( '' !== $a['role'] ) : ?><p class="abio-l8__role"><?php echo esc_html( $a['role'] ); ?></p><?php endif; ?>
					<?php if ( '' !== $a['short'] ) : ?><p class="abio-l8__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
					<?php if ( $d['stats'] && ( $a['posts'] || '' !== $a['since'] ) ) : ?>
						<div class="abio-l8__stats">
							<?php if ( $a['posts'] ) : ?>
								<div class="abio-l8__stat">
									<span class="abio-l8__stat-value"><?php echo esc_html( number_format_i18n( $a['posts'] ) ); ?></span>
									<span class="abio-l8__stat-label"><?php echo esc_html( _n( 'article', 'articles', $a['posts'], 'author-bio' ) ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $a['since'] ) : ?>
								<div class="abio-l8__stat">
									<span class="abio-l8__stat-value"><?php echo esc_html( $a['since'] ); ?></span>
									<span class="abio-l8__stat-label"><?php esc_html_e( 'writing since', 'author-bio' ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
