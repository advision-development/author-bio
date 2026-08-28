<?php
/**
 * Template 10 — Brand feature.
 *
 * Ported from docs/design/author-page-templates.dc.html:1086-1200
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// The design hardcodes "What Jeff covers"; build it from the author's first
// name instead, falling back to a generic heading when there is no name.
// preg_split() on a guaranteed non-empty, trimmed string never returns an
// empty array, so the isset() guard is what keeps an empty/blank name from
// ever emitting a notice here.
$abio_t10_name  = trim( (string) $a['name'] );
$abio_t10_parts = '' !== $abio_t10_name ? preg_split( '/\s+/', $abio_t10_name ) : array();
$abio_t10_first = isset( $abio_t10_parts[0] ) ? $abio_t10_parts[0] : '';

if ( '' !== $abio_t10_first ) {
	$abio_t10_focus_heading = sprintf(
		/* translators: %s: the author's first name. */
		esc_html__( 'What %s covers', 'author-bio' ),
		esc_html( $abio_t10_first )
	);
} else {
	$abio_t10_focus_heading = esc_html__( 'Areas of focus', 'author-bio' );
}

// The statement band pairs the (non-list) bio against the (list) stats grid,
// and the footer strip has three independently optional columns. Both must
// collapse their fixed tracks rather than leave dead space when a list side
// is empty (porting convention rule 5 / lesson on fixed-track grids).
$abio_t10_has_bio   = (bool) $a['bio'];
$abio_t10_has_stats = ! empty( $d['stats'] );

$abio_t10_show_creds   = ! empty( $d['credentials'] ) || ! empty( $a['badges'] );
$abio_t10_show_follows = ! empty( $d['follows'] );
$abio_t10_show_others  = ! empty( $d['others'] );
$abio_t10_footer_cols  = (int) $abio_t10_show_creds + (int) $abio_t10_show_follows + (int) $abio_t10_show_others;
?>
<div class="abio-t10">

	<header class="abio-t10__hero">
		<?php if ( $a['kicker'] || $d['site']['name'] ) : ?>
			<span class="abio-kicker abio-t10__kicker">
				<?php echo esc_html( $a['kicker'] ); ?>
				<?php if ( $a['kicker'] && $d['site']['name'] ) : ?> · <?php endif; ?>
				<?php echo esc_html( $d['site']['name'] ); ?>
			</span>
		<?php endif; ?>

		<h1 class="abio-t10__name"><?php echo esc_html( $a['name'] ); ?></h1>

		<?php if ( $a['role'] ) : ?>
			<p class="abio-t10__role"><?php echo esc_html( $a['role'] ); ?></p>
		<?php endif; ?>

		<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
			<a class="abio-t10__cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
		<div class="abio-t10__gallery-wrap">
			<ul class="abio-t10__gallery">
				<?php foreach ( $d['gallery']['items'] as $g ) : ?>
					<li>
						<?php echo ABIO_View::media( $g['image'], 'medium', $g['label'], 'abio-t10__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="abio-t10__gallery-caption"><?php echo esc_html( $g['caption'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $abio_t10_has_bio || $abio_t10_has_stats ) : ?>
		<section class="abio-t10__statement abio-panel--dark">
			<div class="<?php echo esc_attr( 'abio-t10__statement-inner' . ( ( $abio_t10_has_bio && $abio_t10_has_stats ) ? '' : ' abio-t10__statement-inner--single' ) ); ?>">
				<?php if ( $abio_t10_has_bio ) : ?>
					<div class="abio-t10__statement-bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
				<?php endif; ?>
				<?php if ( $abio_t10_has_stats ) : ?>
					<ul class="abio-t10__stats">
						<?php foreach ( $d['stats'] as $s ) : ?>
							<li>
								<span class="abio-t10__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
								<span class="abio-t10__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['focus'] ) ) : ?>
		<section id="abio-focus" class="abio-t10__focus">
			<div class="abio-t10__focus-head">
				<h2 class="abio-t10__heading"><?php echo $abio_t10_focus_heading; // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php if ( $a['short'] ) : ?>
					<p class="abio-t10__focus-sub"><?php echo esc_html( $a['short'] ); ?></p>
				<?php endif; ?>
			</div>
			<ul class="abio-t10__focus-grid">
				<?php foreach ( $d['focus'] as $f ) : ?>
					<li>
						<span class="abio-t10__focus-n"><?php echo esc_html( $f['n'] ); ?></span>
						<h3><?php echo esc_html( $f['title'] ); ?></h3>
						<p><?php echo esc_html( $f['body'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['edits'] ) ) : ?>
		<section id="abio-edits" class="abio-t10__edits">
			<h2 class="abio-t10__heading"><?php esc_html_e( 'Recent work', 'author-bio' ); ?></h2>
			<ul class="abio-t10__edits-list">
				<?php foreach ( $d['edits'] as $e ) : ?>
					<li>
						<div class="abio-t10__edit-body">
							<span class="abio-t10__edit-type"><?php echo esc_html( $e['type'] ); ?> · <?php echo esc_html( $e['status'] ); ?></span>
							<h3 class="abio-t10__edit-title"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
							<p class="abio-t10__edit-summary"><?php echo esc_html( $e['summary'] ); ?></p>
						</div>
						<div class="abio-t10__edit-meta">
							<span><?php echo esc_html( $e['date'] ); ?></span>
							<span><?php echo esc_html( $e['readTime'] ); ?></span>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['experience'] ) ) : ?>
		<section id="abio-experience" class="abio-t10__experience">
			<h2 class="abio-t10__heading"><?php esc_html_e( 'Background', 'author-bio' ); ?></h2>
			<ul class="abio-t10__exp-grid">
				<?php foreach ( $d['experience'] as $x ) : ?>
					<li>
						<span class="abio-t10__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
						<h3><?php echo esc_html( $x['title'] ); ?></h3>
						<span class="abio-t10__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
						<p><?php echo esc_html( $x['body'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $abio_t10_footer_cols ) : ?>
		<section class="<?php echo esc_attr( 'abio-t10__footer abio-t10__footer--cols-' . $abio_t10_footer_cols ); ?>">

			<?php if ( $abio_t10_show_creds ) : ?>
				<div class="abio-t10__footer-col">
					<h3 class="abio-eyebrow"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
					<?php if ( ! empty( $d['credentials'] ) ) : ?>
						<ul class="abio-t10__credentials">
							<?php foreach ( $d['credentials'] as $c ) : ?>
								<li><?php echo esc_html( $c ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $a['badges'] ) ) : ?>
						<ul class="abio-chips abio-t10__badges">
							<?php foreach ( $a['badges'] as $badge ) : ?>
								<li><span><?php echo esc_html( $badge ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $abio_t10_show_follows ) : ?>
				<div class="abio-t10__footer-col">
					<h3 class="abio-eyebrow"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
					<ul class="abio-chips">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $abio_t10_show_others ) : ?>
				<div class="abio-t10__footer-col">
					<h3 class="abio-eyebrow"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
					<ul class="abio-t10__others">
						<?php foreach ( $d['others'] as $o ) : ?>
							<li>
								<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
								<span><?php echo esc_html( $o['role'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

		</section>
	<?php endif; ?>

</div>
