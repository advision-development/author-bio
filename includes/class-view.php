<?php

defined( 'ABSPATH' ) || exit;

/**
 * Small rendering helpers shared by every template.
 */
class ABIO_View {

	/**
	 * An attachment image, or the design's labelled placeholder when there is
	 * no attachment. Templates always call this rather than reaching for
	 * wp_get_attachment_image() directly, so the empty state stays consistent.
	 *
	 * @param int    $id    Attachment ID.
	 * @param string $size  Registered image size.
	 * @param string $label Placeholder caption, e.g. "portrait 1:1".
	 * @param string $class Extra class on the returned element.
	 * @param string $alt   Fallback alt text, used only when the attachment
	 *                      carries none of its own. A portrait passes the
	 *                      author's name: an unlabelled photograph of a person
	 *                      is the one image here that is never decorative, and
	 *                      editors routinely upload without filling alt in.
	 * @return string
	 */
	public static function media( $id, $size, $label, $class = '', $alt = '' ) {
		$id      = absint( $id );
		$classes = trim( 'abio-media ' . $class );

		if ( $id && wp_attachment_is_image( $id ) ) {
			$attr = array( 'class' => $classes );

			if ( '' !== $alt ) {
				$existing = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );

				if ( '' === $existing ) {
					$attr['alt'] = $alt;
				}
			}

			return wp_get_attachment_image( $id, $size, false, $attr );
		}

		return sprintf(
			'<span class="%s abio-media--empty" aria-hidden="true">%s</span>',
			esc_attr( $classes ),
			esc_html( $label )
		);
	}

	/**
	 * A link when there is somewhere to send the visitor, plain escaped text
	 * otherwise. Used for "Other authors" entries: a profile with no linked
	 * user has no author archive to point to, and an anchor with an empty
	 * href resolves to the current page rather than going nowhere.
	 *
	 * @param string $url
	 * @param string $label
	 * @return string
	 */
	public static function optional_link( $url, $label ) {
		if ( ! $url ) {
			return esc_html( $label );
		}

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
	}

	/**
	 * An external "follows" link.
	 *
	 * These open in a new tab, which a sighted user infers from context and a
	 * screen reader user does not — the tab switch simply happens. The
	 * visually hidden suffix restores that cue, and lives here so the ten
	 * templates cannot drift apart on it.
	 *
	 * @param string $handle Visible label, e.g. "@CoinDesk".
	 * @param string $url
	 * @return string
	 */
	public static function follow_link( $handle, $url ) {
		if ( ! $url ) {
			return esc_html( $handle );
		}

		return sprintf(
			'<a href="%s" rel="nofollow ugc noopener" target="_blank">%s<span class="abio-sr-only"> %s</span></a>',
			esc_url( $url ),
			esc_html( $handle ),
			esc_html__( '(opens in a new tab)', 'author-bio' )
		);
	}
}
