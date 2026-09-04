<?php
/**
 * Author index — fintech product.
 *
 * Mirrors the composition of templates/template-8.php so the index reads as
 * that template's own directory rather than a generic list.
 *
 * @var array $d authors, heading, site
 */

defined( 'ABSPATH' ) || exit;

$abio_n     = 0;
$abio_total = count( $d['authors'] );
$abio_label = '' !== $d['heading']
	? $d['heading']
	: sprintf( _n( 'Author · %d', 'Authors · %d', $abio_total, 'author-bio' ), $abio_total );
?>

<div class="abio-l8">
	<header class="abio-l8__bar">
		<span class="abio-l8__bar-label"><?php echo esc_html( $abio_label ); ?></span>
		<?php if ( '' !== $d['site']['authorsUrl'] ) : ?>
			<a class="abio-l8__bar-link" href="<?php echo esc_url( $d['site']['authorsUrl'] ); ?>"><?php esc_html_e( 'Editorial team', 'author-bio' ); ?></a>
		<?php endif; ?>
	</header>

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
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
