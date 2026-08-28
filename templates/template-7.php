<?php
/**
 * Template 7 — Sports desk.
 *
 * Ported from docs/design/author-page-templates.dc.html:715-838
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// The "Scouting report" card frame must not render with only its heading
// when there is neither a bio nor gallery items to show (porting convention
// rule 5).
$abio_t7_show_report = $a['bio'] || ! empty( $d['gallery']['items'] );
?>
<div class="abio-t7">

	<div class="abio-t7__top abio-panel--dark">
		<div class="abio-t7__top-inner">
			<div class="abio-t7__portrait">
				<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 3:4', 'abio-t7__portrait-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<div class="abio-t7__intro">
				<?php if ( $a['kicker'] || $a['location'] ) : ?>
					<span class="abio-kicker abio-t7__kicker">
						<?php echo esc_html( $a['kicker'] ); ?>
						<?php if ( $a['kicker'] && $a['location'] ) : ?> · <?php endif; ?>
						<?php echo esc_html( $a['location'] ); ?>
					</span>
				<?php endif; ?>

				<h1 class="abio-t7__name"><?php echo esc_html( $a['name'] ); ?></h1>

				<?php if ( $a['role'] ) : ?>
					<p class="abio-t7__role"><?php echo esc_html( $a['role'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $d['stats'] ) ) : ?>
					<ul class="abio-t7__stats">
						<?php foreach ( $d['stats'] as $s ) : ?>
							<li>
								<span class="abio-t7__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
								<span class="abio-t7__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="abio-t7__body">
		<main class="abio-t7__main">

			<?php if ( $abio_t7_show_report ) : ?>
				<section class="abio-t7__card">
					<h2 class="abio-t7__card-heading"><?php esc_html_e( 'Scouting report', 'author-bio' ); ?></h2>

					<?php if ( $a['bio'] ) : ?>
						<div class="abio-t7__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
					<?php endif; ?>

					<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
						<ul class="abio-t7__gallery">
							<?php foreach ( $d['gallery']['items'] as $g ) : ?>
								<li>
									<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['label'], 'abio-t7__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $g['caption'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['focus'] ) ) : ?>
				<section id="abio-focus" class="abio-t7__card">
					<h2 class="abio-t7__card-heading"><?php esc_html_e( 'Beats covered', 'author-bio' ); ?></h2>
					<ul class="abio-t7__focus">
						<?php foreach ( $d['focus'] as $f ) : ?>
							<li>
								<span class="abio-t7__focus-n"><?php echo esc_html( $f['n'] ); ?></span>
								<div>
									<h3><?php echo esc_html( $f['title'] ); ?></h3>
									<p><?php echo esc_html( $f['body'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['edits'] ) ) : ?>
				<section id="abio-edits" class="abio-t7__card">
					<h2 class="abio-t7__card-heading"><?php esc_html_e( 'Box score · recent bylines', 'author-bio' ); ?></h2>
					<ul class="abio-t7__edits">
						<?php foreach ( $d['edits'] as $e ) : ?>
							<li>
								<span class="abio-t7__edit-date"><?php echo esc_html( $e['date'] ); ?></span>
								<span class="abio-t7__edit-type"><?php echo esc_html( $e['type'] ); ?> · <?php echo esc_html( $e['status'] ); ?></span>
								<a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a>
								<span class="abio-t7__edit-time"><?php echo esc_html( $e['readTime'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['experience'] ) ) : ?>
				<section id="abio-experience" class="abio-t7__card">
					<h2 class="abio-t7__card-heading"><?php esc_html_e( 'Career line', 'author-bio' ); ?></h2>
					<ul class="abio-t7__exp">
						<?php foreach ( $d['experience'] as $x ) : ?>
							<li>
								<span class="abio-t7__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
								<h3><?php echo esc_html( $x['title'] ); ?></h3>
								<span class="abio-t7__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
								<p><?php echo esc_html( $x['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</main>

		<aside class="abio-t7__aside">

			<?php if ( ! empty( $d['credentials'] ) ) : ?>
				<div class="abio-t7__card">
					<h3 class="abio-t7__card-heading"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
					<ul class="abio-t7__list">
						<?php foreach ( $d['credentials'] as $c ) : ?>
							<li><?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $a['badges'] ) ) : ?>
				<div class="abio-t7__card">
					<h3 class="abio-t7__card-heading"><?php esc_html_e( 'Verified', 'author-bio' ); ?></h3>
					<ul class="abio-t7__badges">
						<?php foreach ( $a['badges'] as $badge ) : ?>
							<li><?php echo esc_html( $badge ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['follows'] ) ) : ?>
				<div class="abio-t7__card">
					<h3 class="abio-t7__card-heading"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
					<ul class="abio-chips abio-t7__follows">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['others'] ) ) : ?>
				<div class="abio-t7__card">
					<h3 class="abio-t7__card-heading"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
					<ul class="abio-t7__others">
						<?php foreach ( $d['others'] as $o ) : ?>
							<li>
								<?php echo ABIO_View::optional_link( $o['url'], $o['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $o['role'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $d['pitch']['title'] ) : ?>
				<div class="abio-t7__pitch abio-panel--dark">
					<h3><?php echo esc_html( $d['pitch']['title'] ); ?></h3>
					<div class="abio-t7__pitch-body"><?php echo wp_kses_post( $d['pitch']['body'] ); ?></div>
					<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
						<a class="abio-cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</aside>
	</div>
</div>
