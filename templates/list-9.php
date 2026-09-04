<?php
/**
 * Author index — research note.
 *
 * Mirrors the composition of templates/template-9.php so the index reads as
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

<div class="abio-l9">
	<div class="abio-l9__sheet">
		<?php if ( $d['header'] ) : ?>
		<header class="abio-l9__head">
			<?php if ( '' !== $d['site']['name'] ) : ?>
			<span class="abio-kicker"><?php echo esc_html( $d['site']['name'] ); ?></span>
		<?php endif; ?>
			<h2 class="abio-l9__h2"><?php echo esc_html( $abio_label ); ?></h2>
		</header>
		<?php endif; ?>

		<ol class="abio-l9__rows">
			<?php foreach ( $d['authors'] as $a ) : ?>
				<?php $abio_n++; ?>
				<li>
					<div class="abio-l9__inner">
						<span class="abio-l9__num"><?php echo esc_html( number_format_i18n( $abio_n ) ); ?>.</span>
						<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$a['portrait'], 'thumbnail', 'portrait 1:1', 'abio-l9__img', $a['name']
					);
					?>
						<div class="abio-l9__body">
							<h3 class="abio-l9__name"><?php echo ABIO_View::optional_link( $a['url'], $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
							<p class="abio-l9__meta">
								<span class="abio-l9__kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
								<?php if ( '' !== $a['role'] ) : ?><span class="abio-l9__role"><?php echo esc_html( $a['role'] ); ?></span><?php endif; ?>
								<?php if ( $d['stats'] && $a['posts'] ) : ?>
									<span class="abio-l9__count">
										<?php
										printf(
											/* translators: %s: number of published articles. */
											esc_html( _n( '%s article', '%s articles', $a['posts'], 'author-bio' ) ),
											esc_html( number_format_i18n( $a['posts'] ) )
										);
										?>
									</span>
								<?php endif; ?>
								<?php if ( $d['stats'] && '' !== $a['since'] ) : ?>
									<span class="abio-l9__since">
										<?php
										/* translators: %s: four-digit year. */
										printf( esc_html__( 'writing since %s', 'author-bio' ), esc_html( $a['since'] ) );
										?>
									</span>
								<?php endif; ?>
							</p>
							<?php if ( '' !== $a['short'] ) : ?><p class="abio-l9__short"><?php echo esc_html( $a['short'] ); ?></p><?php endif; ?>
						</div>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</div>
