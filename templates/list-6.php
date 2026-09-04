<?php
/**
 * Author index — full-bleed dossier.
 *
 * Mirrors the composition of templates/template-6.php so the index reads as
 * that template's own directory rather than a generic list.
 *
 * @var array $d authors, heading, stats, site
 */

defined( 'ABSPATH' ) || exit;

$abio_n     = 0;
$abio_total = count( $d['authors'] );
$abio_label = '' !== $d['heading']
	? $d['heading']
	: sprintf( _n( 'Author · %d', 'Authors · %d', $abio_total, 'author-bio' ), $abio_total );
?>

<div class="abio-l6">
	<header class="abio-l6__head abio-panel--dark">
		<?php if ( '' !== $d['site']['name'] ) : ?>
			<span class="abio-kicker"><?php echo esc_html( $d['site']['name'] ); ?></span>
		<?php endif; ?>
		<h2 class="abio-l6__h2"><?php echo esc_html( $abio_label ); ?></h2>
	</header>

	<ul class="abio-l6__rows">
		<?php foreach ( $d['authors'] as $a ) : ?>
			<li>
				<div class="abio-l6__rule" aria-hidden="true"></div>
				<div class="abio-l6__inner">
					<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'medium', 'portrait 1:1', 'abio-l6__img', $a['name']
					);
					?>
					<div class="abio-l6__body">
						<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
						<h3 class="abio-l6__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
						<?php if ( '' !== $a['short'] ) : ?><p class="abio-l6__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
					</div>
					<?php $abio_l6_stats = $d['stats'] && ( $a['posts'] || '' !== $a['since'] ); ?>
					<?php if ( '' !== $a['role'] || $abio_l6_stats ) : ?>
						<div class="abio-l6__meta">
							<?php if ( '' !== $a['role'] ) : ?>
								<p class="abio-l6__role"><?php echo esc_html( $a['role'] ); ?></p>
							<?php endif; ?>
							<?php if ( $abio_l6_stats ) : ?>
								<div class="abio-l6__stats">
									<?php if ( $a['posts'] ) : ?>
										<div class="abio-l6__stat">
											<span class="abio-l6__stat-value"><?php echo esc_html( number_format_i18n( $a['posts'] ) ); ?></span>
											<span class="abio-l6__stat-label"><?php echo esc_html( _n( 'article', 'articles', $a['posts'], 'author-bio' ) ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( '' !== $a['since'] ) : ?>
										<div class="abio-l6__stat">
											<span class="abio-l6__stat-value"><?php echo esc_html( $a['since'] ); ?></span>
											<span class="abio-l6__stat-label"><?php esc_html_e( 'writing since', 'author-bio' ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
