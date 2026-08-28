<?php
/**
 * Template 9 — Research note.
 *
 * Ported from docs/design/author-page-templates.dc.html:966-1084
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// The summary row and the qualifications row each pair a fixed, always-
// present block against a list-driven block. When the list side is empty
// the row must collapse to one column rather than leave a dead track
// (porting convention rule 5 / lesson on fixed-track grids).
$abio_t9_has_bio   = (bool) $a['bio'];
$abio_t9_has_stats = ! empty( $d['stats'] );
$abio_t9_has_creds = ! empty( $d['credentials'] );
$abio_t9_has_side  = ! empty( $d['follows'] ) || ! empty( $d['others'] );

// The five numbered sections below are numbered by how many of them
// actually render, not by their position in the markup, so hiding one
// never leaves a gap in the sequence.
$abio_t9_n = 0;
?>
<div class="abio-t9">
	<article class="abio-t9__article">

		<header class="abio-t9__header">
			<span class="abio-t9__label">
				<?php echo esc_html( $d['site']['name'] ); ?>
				<?php if ( $d['site']['name'] ) : ?> · <?php endif; ?>
				<?php esc_html_e( 'Analyst profile', 'author-bio' ); ?>
			</span>

			<div class="abio-t9__header-grid">
				<div class="abio-t9__intro">
					<h1 class="abio-t9__name"><?php echo esc_html( $a['name'] ); ?></h1>

					<?php if ( $a['role'] ) : ?>
						<p class="abio-t9__role"><?php echo esc_html( $a['role'] ); ?></p>
					<?php endif; ?>

					<?php if ( $a['location'] || $a['since'] ) : ?>
						<p class="abio-t9__meta">
							<?php if ( $a['location'] ) : ?><?php echo esc_html( $a['location'] ); ?><?php endif; ?>
							<?php if ( $a['location'] && $a['since'] ) : ?> · <?php endif; ?>
							<?php if ( $a['since'] ) : ?><?php esc_html_e( 'Contributing since', 'author-bio' ); ?> <?php echo esc_html( $a['since'] ); ?><?php endif; ?>
						</p>
					<?php endif; ?>
				</div>

				<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t9__portrait' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</header>

		<?php if ( $abio_t9_has_bio || $abio_t9_has_stats ) : ?>
			<div class="<?php echo esc_attr( 'abio-t9__summary' . ( ( $abio_t9_has_bio && $abio_t9_has_stats ) ? '' : ' abio-t9__summary--single' ) ); ?>">
				<?php if ( $abio_t9_has_bio ) : ?>
					<div class="abio-t9__summary-col">
						<h2 class="abio-t9__heading"><?php esc_html_e( 'Summary', 'author-bio' ); ?></h2>
						<div class="abio-t9__summary-bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $abio_t9_has_stats ) : ?>
					<div class="abio-t9__summary-col">
						<h2 class="abio-t9__heading"><?php esc_html_e( 'Key figures', 'author-bio' ); ?></h2>
						<ul class="abio-t9__figures">
							<?php foreach ( $d['stats'] as $s ) : ?>
								<li>
									<span class="abio-t9__figure-label"><?php echo esc_html( $s['label'] ); ?></span>
									<span class="abio-t9__figure-value"><?php echo esc_html( $s['value'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $d['focus'] ) ) : ?>
			<?php $abio_t9_n++; ?>
			<section id="abio-focus" class="abio-t9__section">
				<h2 class="abio-t9__heading">
					<?php
					printf(
						/* translators: %d: section number in this template's numbered sequence. */
						esc_html__( '%d · Coverage universe', 'author-bio' ),
						$abio_t9_n
					);
					?>
				</h2>
				<ol class="abio-t9__coverage">
					<?php foreach ( $d['focus'] as $f ) : ?>
						<li>
							<span class="abio-t9__coverage-sub"><?php echo esc_html( $f['sub'] ); ?></span>
							<div>
								<h3><?php echo esc_html( $f['title'] ); ?></h3>
								<p><?php echo esc_html( $f['body'] ); ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
			<?php $abio_t9_n++; ?>
			<section class="abio-t9__section">
				<h2 class="abio-t9__heading">
					<?php
					printf(
						/* translators: %d: section number in this template's numbered sequence. */
						esc_html__( '%d · Exhibits', 'author-bio' ),
						$abio_t9_n
					);
					?>
				</h2>
				<ul class="abio-t9__exhibits">
					<?php foreach ( $d['gallery']['items'] as $g ) : ?>
						<li>
							<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['label'], 'abio-t9__exhibit-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="abio-t9__exhibit-n">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: exhibit number. */
										__( 'Exhibit %d', 'author-bio' ),
										(int) $g['n']
									)
								);
								?>
							</span>
							<span class="abio-t9__exhibit-caption"><?php echo esc_html( $g['caption'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['edits'] ) ) : ?>
			<?php $abio_t9_n++; ?>
			<section id="abio-edits" class="abio-t9__section">
				<h2 class="abio-t9__heading">
					<?php
					printf(
						/* translators: %d: section number in this template's numbered sequence. */
						esc_html__( '%d · Published notes', 'author-bio' ),
						$abio_t9_n
					);
					?>
				</h2>
				<ul class="abio-t9__notes">
					<?php foreach ( $d['edits'] as $e ) : ?>
						<li>
							<span class="abio-t9__note-date"><?php echo esc_html( $e['date'] ); ?></span>
							<a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a>
							<span class="abio-t9__note-type"><?php echo esc_html( $e['type'] ); ?> · <?php echo esc_html( $e['status'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['experience'] ) ) : ?>
			<?php $abio_t9_n++; ?>
			<section id="abio-experience" class="abio-t9__section">
				<h2 class="abio-t9__heading">
					<?php
					printf(
						/* translators: %d: section number in this template's numbered sequence. */
						esc_html__( '%d · Track record', 'author-bio' ),
						$abio_t9_n
					);
					?>
				</h2>
				<ul class="abio-t9__track">
					<?php foreach ( $d['experience'] as $x ) : ?>
						<li>
							<div class="abio-t9__track-head">
								<h3><?php echo esc_html( $x['title'] ); ?> — <?php echo esc_html( $x['org'] ); ?></h3>
								<span class="abio-t9__track-years"><?php echo esc_html( $x['years'] ); ?></span>
							</div>
							<p><?php echo esc_html( $x['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $abio_t9_has_creds || $abio_t9_has_side ) : ?>
			<?php $abio_t9_n++; ?>
			<section class="abio-t9__section">
				<h2 class="abio-t9__heading">
					<?php
					printf(
						/* translators: %d: section number in this template's numbered sequence. */
						esc_html__( '%d · Qualifications', 'author-bio' ),
						$abio_t9_n
					);
					?>
				</h2>
				<div class="<?php echo esc_attr( 'abio-t9__quals' . ( ( $abio_t9_has_creds && $abio_t9_has_side ) ? '' : ' abio-t9__quals--single' ) ); ?>">
					<?php if ( $abio_t9_has_creds ) : ?>
						<ul class="abio-t9__list">
							<?php foreach ( $d['credentials'] as $c ) : ?>
								<li><?php echo esc_html( $c ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $abio_t9_has_side ) : ?>
						<div class="abio-t9__side">
							<?php if ( ! empty( $d['follows'] ) ) : ?>
								<div class="abio-t9__side-block">
									<h3 class="abio-t9__heading"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
									<ul class="abio-chips abio-t9__follows">
										<?php foreach ( $d['follows'] as $h ) : ?>
											<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $d['others'] ) ) : ?>
								<div class="abio-t9__side-block">
									<h3 class="abio-t9__heading"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
									<ul class="abio-t9__others">
										<?php foreach ( $d['others'] as $o ) : ?>
											<li>
												<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
												<span><?php echo esc_html( $o['role'] ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

	</article>
</div>
