<?php
/**
 * Author index — résumé.
 *
 * Mirrors the composition of templates/template-2.php so the index reads as
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

<div class="abio-l2">
	<header class="abio-l2__head">
		<h2 class="abio-eyebrow"><?php echo esc_html( $abio_label ); ?></h2>
	</header>

	<ul class="abio-l2__rows">
		<?php foreach ( $d['authors'] as $a ) : ?>
			<li>
				<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'thumbnail', 'portrait 1:1', 'abio-l2__img', $a['name']
					);
					?>
				<div class="abio-l2__body">
					<h3 class="abio-l2__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					<span class="abio-l2__kicker abio-eyebrow"><?php echo esc_html( $a['kicker'] ); ?></span>
					<?php if ( '' !== $a['role'] ) : ?><p class="abio-l2__role"><?php echo esc_html( $a['role'] ); ?></p><?php endif; ?>
					<?php if ( '' !== $a['short'] ) : ?><p class="abio-l2__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
