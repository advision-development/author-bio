/*
 * Author Bio — settings screen: tabs and colour pickers.
 *
 * Loaded in the head rather than the footer. The first statement marks the
 * document as scripted so the stylesheet can hide inactive panels before the
 * body paints; deferred, every panel would render and then collapse.
 */
( function () {
	'use strict';

	document.documentElement.classList.add( 'js' );

	var STORE = 'abio-settings-tab';

	function init() {
		var wrap = document.querySelector( '.abio-settings' );

		if ( ! wrap ) {
			return;
		}

		var tabs = Array.prototype.slice.call( wrap.querySelectorAll( '[role="tab"]' ) );
		var panels = Array.prototype.slice.call( wrap.querySelectorAll( '[role="tabpanel"]' ) );

		if ( ! tabs.length ) {
			return;
		}

		function slugOf( tab ) {
			return tab.getAttribute( 'aria-controls' ).replace( 'abio-panel-', '' );
		}

		function show( slug, focusTab ) {
			var matched = false;

			tabs.forEach( function ( tab ) {
				var on = slugOf( tab ) === slug;
				matched = matched || on;

				tab.classList.toggle( 'nav-tab-active', on );
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				// Only the selected tab is in the tab order; the rest are
				// reached with the arrow keys, which is how a tablist behaves.
				tab.setAttribute( 'tabindex', on ? '0' : '-1' );

				if ( on && focusTab ) {
					tab.focus();
				}
			} );

			if ( ! matched ) {
				return false;
			}

			panels.forEach( function ( panel ) {
				panel.classList.toggle( 'is-active', panel.id === 'abio-panel-' + slug );
			} );

			try {
				window.localStorage.setItem( STORE, slug );
			} catch ( e ) {
				// Private browsing or a full quota. The tab still switches.
			}

			return true;
		}

		tabs.forEach( function ( tab, i ) {
			tab.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				show( slugOf( tab ), false );

				// Written without jumping the page: replaceState keeps the tab
				// linkable and survives a reload, while setting location.hash
				// would scroll the panel under the admin bar.
				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', '#abio-panel-' + slugOf( tab ) );
				}
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				var step = { ArrowLeft: -1, ArrowRight: 1, Home: 'first', End: 'last' }[ event.key ];

				if ( undefined === step ) {
					return;
				}

				event.preventDefault();

				var next;

				if ( 'first' === step ) {
					next = 0;
				} else if ( 'last' === step ) {
					next = tabs.length - 1;
				} else {
					next = ( i + step + tabs.length ) % tabs.length;
				}

				show( slugOf( tabs[ next ] ), true );
			} );
		} );

		// A hash wins, so a link to one tab lands on it. Otherwise the tab this
		// browser last used, which is what carries the choice across the save
		// redirect — options.php never sees the fragment.
		var fromHash = ( window.location.hash || '' ).replace( '#abio-panel-', '' );
		var remembered = '';

		try {
			remembered = window.localStorage.getItem( STORE ) || '';
		} catch ( e ) {
			remembered = '';
		}

		if ( ! show( fromHash, false ) && ! show( remembered, false ) ) {
			show( slugOf( tabs[ 0 ] ), false );
		}

		colors();
	}

	/**
	 * Core's colour picker on every colour field.
	 *
	 * No defaultColor is passed on purpose: that keeps the button labelled
	 * "Clear", and clearing a derived colour is what hands it back to the
	 * color-mix() in the stylesheet. Passing a default would relabel the button
	 * and write a literal, quietly pinning a colour meant to follow the seeds.
	 */
	function colors() {
		var $ = window.jQuery;

		if ( ! $ || ! $.fn || ! $.fn.wpColorPicker ) {
			return;
		}

		$( '.abio-color' ).each( function () {
			var $input = $( this );

			// The element is captured here rather than read from `this` inside
			// the callbacks: Iris and wpColorPicker do not bind it the same way
			// for change and for clear.
			$input.wpColorPicker( {
				change: function () {
					mark( $input[ 0 ] );
				},
				clear: function () {
					mark( $input[ 0 ] );
				}
			} );
		} );
	}

	/**
	 * Keep the "Using / Overriding" line agreeing with the field, so it does not
	 * contradict the input until the page is saved and reloaded.
	 *
	 * Read from the input's value rather than from which callback fired: the
	 * picker emits change during its own set-up, and trusting that would label
	 * an empty field as overridden.
	 */
	function mark( input ) {
		var cell = input.closest( 'td' );
		var note = cell ? cell.querySelector( '.abio-resolved__text' ) : null;

		if ( ! note ) {
			return;
		}

		var overridden = '' !== String( input.value || '' ).trim();
		var text = note.getAttribute( overridden ? 'data-overriding' : 'data-using' );

		if ( text ) {
			note.textContent = text;
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
