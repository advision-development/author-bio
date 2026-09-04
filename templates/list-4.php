<?php
/**
 * Author index — bento.
 *
 * Mirrors the composition of templates/template-4.php so the index reads as
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

<div class="abio-l4">
	<header class="abio-l4__head abio-panel--dark">
		<span class="abio-kicker"><?php echo esc_html( $abio_label ); ?></span>
	</header>

	<ul class="abio-l4__rows">
		<?php foreach ( $d['authors'] as $a ) : ?>
			<li>
				<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'medium', 'portrait 1:1', 'abio-l4__img', $a['name']
					);
					?>
				<div class="abio-l4__body">
					<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
					<h3 class="abio-l4__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<?php if ( '' !== $a['role'] ) : ?><p class="abio-l4__role"><?php echo esc_html( $a['role'] ); ?></p><?php endif; ?>
					<?php if ( '' !== $a['short'] ) : ?><p class="abio-l4__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
