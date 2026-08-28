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
	 * @return string
	 */
	public static function media( $id, $size, $label, $class = '' ) {
		$id      = absint( $id );
		$classes = trim( 'abio-media ' . $class );

		if ( $id && wp_attachment_is_image( $id ) ) {
			return wp_get_attachment_image(
				$id,
				$size,
				false,
				array( 'class' => $classes )
			);
		}

		return sprintf(
			'<span class="%s abio-media--empty" aria-hidden="true">%s</span>',
			esc_attr( $classes ),
			esc_html( $label )
		);
	}
}
