( function () {
	'use strict';

	/**
	 * Renumber a repeater's field names so indexes stay contiguous after a removal.
	 */
	function renumber( repeater ) {
		var key = repeater.getAttribute( 'data-key' );
		var rows = repeater.querySelectorAll( '[data-abio-repeater-row]' );

		Array.prototype.forEach.call( rows, function ( row, index ) {
			var inputs = row.querySelectorAll( '[name]' );

			Array.prototype.forEach.call( inputs, function ( input ) {
				input.name = input.name.replace(
					new RegExp( '^abio\\[' + key + '\\]\\[[^\\]]*\\]' ),
					'abio[' + key + '][' + index + ']'
				);
			} );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var add = event.target.closest( '[data-abio-repeater-add]' );

		if ( add ) {
			event.preventDefault();

			var repeater = add.closest( '[data-abio-repeater]' );
			var template = repeater.querySelector( '[data-abio-repeater-template]' );
			var rows = repeater.querySelector( '[data-abio-repeater-rows]' );
			var index = rows.querySelectorAll( '[data-abio-repeater-row]' ).length;

			rows.insertAdjacentHTML(
				'beforeend',
				template.innerHTML.split( '__i__' ).join( String( index ) )
			);

			return;
		}

		var remove = event.target.closest( '[data-abio-repeater-remove]' );

		if ( remove ) {
			event.preventDefault();

			var owner = remove.closest( '[data-abio-repeater]' );
			remove.closest( '[data-abio-repeater-row]' ).remove();
			renumber( owner );

			return;
		}

		var choose = event.target.closest( '[data-abio-media-choose]' );

		if ( choose ) {
			event.preventDefault();
			openMedia( choose.closest( '[data-abio-media]' ) );

			return;
		}

		var clear = event.target.closest( '[data-abio-media-remove]' );

		if ( clear ) {
			event.preventDefault();

			var field = clear.closest( '[data-abio-media]' );
			field.querySelector( '[data-abio-media-input]' ).value = '';
			field.querySelector( '[data-abio-media-preview]' ).innerHTML = '';
		}
	} );

	/**
	 * Open the WordPress media modal and write the chosen attachment back.
	 */
	function openMedia( field ) {
		var frame = wp.media( {
			title: 'Select image',
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var size = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			field.querySelector( '[data-abio-media-input]' ).value = attachment.id;
			field.querySelector( '[data-abio-media-preview]' ).innerHTML =
				'<img src="' + size + '" alt="" />';
		} );

		frame.open();
	}

	/**
	 * Show only the inputs that the selected stat mode actually uses.
	 */
	function syncStat( tile ) {
		var mode = tile.querySelector( '[data-abio-stat-mode]' ).value;

		tile.querySelector( '[data-abio-stat-post-type]' ).hidden = mode !== 'auto_type_count';
		tile.querySelector( '[data-abio-stat-value]' ).hidden = mode !== 'manual';
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( '[data-abio-stat-mode]' ) ) {
			syncStat( event.target.closest( '[data-abio-stat]' ) );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-abio-stat]' ),
			syncStat
		);
	} );
}() );
