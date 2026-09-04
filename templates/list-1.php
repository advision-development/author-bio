<?php
/**
 * Author index — classic sidebar.
 *
 * Mirrors the composition of templates/template-1.php so the index reads as
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

<div class="abio-l1">
	<header class="abio-l1__head">
		<h2 class="abio-l1__h2"><?php echo esc_html( $abio_label ); ?></h2>
	</header>

	<ul class="abio-l1__rows">
		<?php foreach ( $d['authors'] as $a ) : ?>
			<li>
				<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'medium', 'portrait 1:1', 'abio-l1__img', $a['name']
					);
					?>
				<div class="abio-l1__body">
					<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
					<h3 class="abio-l1__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<?php if ( '' !== $a['role'] ) : ?><p class="abio-l1__role"><?php echo esc_html( $a['role'] ); ?></p><?php endif; ?>
					<?php if ( '' !== $a['short'] ) : ?><p class="abio-l1__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
					<?php if ( $d['stats'] && ( $a['posts'] || '' !== $a['since'] ) ) : ?>
						<div class="abio-l1__stats">
							<?php if ( $a['posts'] ) : ?>
								<div class="abio-l1__stat">
									<span class="abio-l1__stat-value"><?php echo esc_html( number_format_i18n( $a['posts'] ) ); ?></span>
									<span class="abio-l1__stat-label"><?php echo esc_html( _n( 'article', 'articles', $a['posts'], 'author-bio' ) ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $a['since'] ) : ?>
								<div class="abio-l1__stat">
									<span class="abio-l1__stat-value"><?php echo esc_html( $a['since'] ); ?></span>
									<span class="abio-l1__stat-label"><?php esc_html_e( 'writing since', 'author-bio' ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
