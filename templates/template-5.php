<?php
/**
 * Template 5 — Numbered rail.
 *
 * Ported from docs/design/author-page-templates.dc.html:513-601
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// The design hardcodes 01/02/03 beside the section headings. We derive the
// number from $d['nav'] instead, keyed by anchor, so a hidden section never
// leaves a gap in the sequence — and a section with no nav entry gets none.
$abio_t5_nav_num = array();
foreach ( $d['nav'] as $n ) {
	$abio_t5_nav_num[ $n['href'] ] = $n['num'];
}
?>
<div class="abio-t5">

	<nav class="abio-t5__rail">
		<div class="abio-t5__id">
			<?php echo ABIO_View::media( $a['portrait'], 'thumbnail', '', 'abio-t5__avatar', $a['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="abio-t5__id-text">
				<span class="abio-t5__id-name"><?php echo esc_html( $a['name'] ); ?></span>
				<span class="abio-t5__id-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
			</span>
		</div>

		<?php if ( ! empty( $d['nav'] ) ) : ?>
			<ol class="abio-t5__toc">
				<?php foreach ( $d['nav'] as $n ) : ?>
					<li>
						<a href="<?php echo esc_url( $n['href'] ); ?>">
							<span class="abio-t5__toc-num"><?php echo esc_html( $n['num'] ); ?></span>
							<span><?php echo esc_html( $n['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php if ( ! empty( $d['follows'] ) ) : ?>
			<div class="abio-t5__follows">
				<?php foreach ( $d['follows'] as $h ) : ?>
					<?php echo ABIO_View::follow_link( $h['handle'], $h['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</nav>

	<main class="abio-t5__main">

		<header class="abio-t5__header">
			<?php if ( $a['kicker'] || $a['location'] ) : ?>
				<span class="abio-kicker abio-t5__kicker">
					<?php echo esc_html( $a['kicker'] ); ?>
					<?php if ( $a['kicker'] && $a['location'] ) : ?> · <?php endif; ?>
					<?php echo esc_html( $a['location'] ); ?>
				</span>
			<?php endif; ?>

			<h1 class="abio-t5__name"><?php echo esc_html( $a['name'] ); ?></h1>

			<?php if ( $a['role'] ) : ?>
				<p class="abio-t5__role"><?php echo esc_html( $a['role'] ); ?></p>
			<?php endif; ?>

			<?php if ( $a['bio'] ) : ?>
				<div class="abio-t5__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
				<ul class="abio-t5__gallery">
					<?php foreach ( $d['gallery']['items'] as $g ) : ?>
						<li>
							<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['label'], 'abio-t5__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="abio-t5__gallery-caption"><?php echo esc_html( $g['caption'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $d['stats'] ) ) : ?>
				<ul class="abio-t5__stats">
					<?php foreach ( $d['stats'] as $s ) : ?>
						<li>
							<span class="abio-t5__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<span class="abio-t5__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $d['focus'] ) ) : ?>
			<section id="abio-focus" class="abio-t5__section abio-t5__section--focus">
				<div class="abio-t5__heading">
					<?php if ( isset( $abio_t5_nav_num['#abio-focus'] ) ) : ?>
						<span class="abio-t5__num"><?php echo esc_html( $abio_t5_nav_num['#abio-focus'] ); ?></span>
					<?php endif; ?>
					<h2><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
				</div>
				<ul class="abio-t5__focus">
					<?php foreach ( $d['focus'] as $f ) : ?>
						<li>
							<h3><?php echo esc_html( $f['title'] ); ?></h3>
							<p><?php echo esc_html( $f['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['edits'] ) ) : ?>
			<section id="abio-edits" class="abio-t5__section">
				<div class="abio-t5__heading">
					<?php if ( isset( $abio_t5_nav_num['#abio-edits'] ) ) : ?>
						<span class="abio-t5__num"><?php echo esc_html( $abio_t5_nav_num['#abio-edits'] ); ?></span>
					<?php endif; ?>
					<h2><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
				</div>
				<ul class="abio-t5__edits">
					<?php foreach ( $d['edits'] as $e ) : ?>
						<li>
							<div class="abio-t5__edit-body">
								<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
								<p><?php echo esc_html( $e['summary'] ); ?></p>
							</div>
							<div class="abio-t5__edit-meta">
								<span><?php echo esc_html( $e['date'] ); ?></span>
								<span><?php echo esc_html( $e['type'] ); ?></span>
								<span><?php echo esc_html( $e['readTime'] ); ?></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['experience'] ) ) : ?>
			<section id="abio-experience" class="abio-t5__section">
				<div class="abio-t5__heading">
					<?php if ( isset( $abio_t5_nav_num['#abio-experience'] ) ) : ?>
						<span class="abio-t5__num"><?php echo esc_html( $abio_t5_nav_num['#abio-experience'] ); ?></span>
					<?php endif; ?>
					<h2><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
				</div>
				<ul class="abio-t5__exp">
					<?php foreach ( $d['experience'] as $x ) : ?>
						<li>
							<span class="abio-t5__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
							<div>
								<h3><?php echo esc_html( $x['title'] ); ?></h3>
								<span class="abio-t5__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
								<p><?php echo esc_html( $x['body'] ); ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

	</main>
</div>
