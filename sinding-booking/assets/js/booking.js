/**
 * Sinding Photography Booking System – Front-end JavaScript
 *
 * Responsibilities:
 *  1. Listen for item selection changes and recalculate the total price.
 *  2. Enable/disable the "Book Now" CTA based on selection state.
 *  3. Open / close the booking modal.
 *  4. Populate the order summary inside the modal.
 *  5. Validate the booking form client-side.
 *  6. Submit the form via AJAX and show success / error feedback.
 */

( function () {
	'use strict';

	// Guard: script data must be available.
	if ( typeof sindingBooking === 'undefined' ) {
		return;
	}

	var cfg = sindingBooking; // { ajaxUrl, nonce, items, i18n }

	// ── DOM references ──────────────────────────────────────────────────────
	var wrap        = document.getElementById( 'sinding-booking-wrap' );
	var totalDisplay = document.getElementById( 'sb-total-display' );
	var summaryList  = document.getElementById( 'sb-summary-list' );
	var summaryTotal = document.getElementById( 'sb-summary-total' );
	var openBtn      = document.getElementById( 'sb-open-booking' );
	var modal        = document.getElementById( 'sb-modal' );
	var backdrop     = document.getElementById( 'sb-modal-backdrop' );
	var closeBtn     = document.getElementById( 'sb-modal-close' );
	var form         = document.getElementById( 'sb-booking-form' );
	var submitBtn    = document.getElementById( 'sb-submit-btn' );
	var notice       = document.getElementById( 'sb-form-notice' );
	var dateInput    = document.getElementById( 'sb-date' );

	if ( ! wrap ) {
		return;
	}

	// Build a lookup map from the PHP-passed catalogue.
	var itemMap = {};
	cfg.items.forEach( function ( item ) {
		itemMap[ item.id ] = item;
	} );

	// ── Set minimum date on date picker (tomorrow) ───────────────────────────
	( function setMinDate() {
		if ( ! dateInput ) { return; }
		var tomorrow = new Date();
		tomorrow.setDate( tomorrow.getDate() + 1 );
		var yyyy = tomorrow.getFullYear();
		var mm   = String( tomorrow.getMonth() + 1 ).padStart( 2, '0' );
		var dd   = String( tomorrow.getDate() ).padStart( 2, '0' );
		dateInput.min = yyyy + '-' + mm + '-' + dd;
	}() );

	// ── Price calculation ────────────────────────────────────────────────────

	/**
	 * Return the currently selected session ID (or null).
	 *
	 * @returns {string|null}
	 */
	function getSelectedSession() {
		var radios = wrap.querySelectorAll( 'input[type="radio"][data-type="session"]' );
		for ( var i = 0; i < radios.length; i++ ) {
			if ( radios[ i ].checked ) {
				return radios[ i ].value;
			}
		}
		return null;
	}

	/**
	 * Return an array of selected add-on IDs.
	 *
	 * @returns {string[]}
	 */
	function getSelectedAddons() {
		var boxes    = wrap.querySelectorAll( 'input[type="checkbox"][data-type="addon"]:checked' );
		var selected = [];
		boxes.forEach( function ( cb ) { selected.push( cb.value ); } );
		return selected;
	}

	/**
	 * Recalculate total and update UI.
	 */
	function updatePrice() {
		var total   = 0;
		var session = getSelectedSession();
		var addons  = getSelectedAddons();

		if ( session && itemMap[ session ] ) {
			total += itemMap[ session ].price;
		}
		addons.forEach( function ( id ) {
			if ( itemMap[ id ] ) {
				total += itemMap[ id ].price;
			}
		} );

		// Update sticky bar amount.
		totalDisplay.textContent = 'NOK ' + formatNumber( total );

		// Enable / disable CTA.
		openBtn.disabled = ( session === null );
	}

	/**
	 * Format a number with spaces as thousands separator (Norwegian style).
	 *
	 * @param {number} n
	 * @returns {string}
	 */
	function formatNumber( n ) {
		return n.toString().replace( /\B(?=(\d{3})+(?!\d))/g, '\u00a0' );
	}

	// Attach change listeners to all item inputs.
	wrap.querySelectorAll( 'input[data-type="session"], input[data-type="addon"]' )
		.forEach( function ( input ) {
			input.addEventListener( 'change', updatePrice );
		} );

	// ── Order summary ────────────────────────────────────────────────────────

	/**
	 * Populate the order summary list inside the modal.
	 */
	function buildSummary() {
		var session = getSelectedSession();
		var addons  = getSelectedAddons();
		var total   = 0;

		summaryList.innerHTML = '';

		if ( session && itemMap[ session ] ) {
			var item  = itemMap[ session ];
			total    += item.price;
			addListItem( item.label, item.price );
		}

		addons.forEach( function ( id ) {
			if ( itemMap[ id ] ) {
				var addon  = itemMap[ id ];
				total     += addon.price;
				addListItem( addon.label, addon.price, true );
			}
		} );

		summaryTotal.textContent = 'NOK ' + formatNumber( total );
	}

	/**
	 * Append an <li> to the summary list.
	 *
	 * @param {string}  label
	 * @param {number}  price
	 * @param {boolean} isAddon
	 */
	function addListItem( label, price, isAddon ) {
		var li         = document.createElement( 'li' );
		var nameSpan   = document.createElement( 'span' );
		var priceSpan  = document.createElement( 'span' );

		nameSpan.textContent  = ( isAddon ? '+ ' : '' ) + label;
		priceSpan.textContent = 'NOK ' + formatNumber( price );

		li.appendChild( nameSpan );
		li.appendChild( priceSpan );
		summaryList.appendChild( li );
	}

	// ── Modal open / close ───────────────────────────────────────────────────

	function openModal() {
		buildSummary();
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		closeBtn.focus();
	}

	function closeModal() {
		modal.hidden = true;
		document.body.style.overflow = '';
		openBtn.focus();
	}

	openBtn.addEventListener( 'click', openModal );
	closeBtn.addEventListener( 'click', closeModal );
	backdrop.addEventListener( 'click', closeModal );

	// Close on Escape key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && ! modal.hidden ) {
			closeModal();
		}
	} );

	// ── Form validation helpers ──────────────────────────────────────────────

	function showFieldError( inputId, errorId, message ) {
		var input = document.getElementById( inputId );
		var error = document.getElementById( errorId );
		if ( input )  { input.classList.add( 'sb-input--error' ); }
		if ( error )  { error.textContent = message; }
	}

	function clearFieldError( inputId, errorId ) {
		var input = document.getElementById( inputId );
		var error = document.getElementById( errorId );
		if ( input )  { input.classList.remove( 'sb-input--error' ); }
		if ( error )  { error.textContent = ''; }
	}

	function clearAllErrors() {
		[ 'sb-name', 'sb-email', 'sb-phone', 'sb-date' ].forEach( function ( id ) {
			clearFieldError( id, id + '-error' );
		} );
		hideNotice();
	}

	/**
	 * Client-side validation.
	 *
	 * @returns {boolean} true if all fields pass.
	 */
	function validateForm() {
		var valid = true;
		clearAllErrors();

		var name  = document.getElementById( 'sb-name' ).value.trim();
		var email = document.getElementById( 'sb-email' ).value.trim();
		var phone = document.getElementById( 'sb-phone' ).value.trim();
		var date  = document.getElementById( 'sb-date' ).value;

		if ( ! name ) {
			showFieldError( 'sb-name', 'sb-name-error', cfg.i18n.required );
			valid = false;
		}

		if ( ! email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
			showFieldError( 'sb-email', 'sb-email-error', cfg.i18n.invalidEmail );
			valid = false;
		}

		if ( ! phone || ! /^[\d\s\+\-\(\)]{6,20}$/.test( phone ) ) {
			showFieldError( 'sb-phone', 'sb-phone-error', cfg.i18n.invalidPhone );
			valid = false;
		}

		if ( ! date ) {
			showFieldError( 'sb-date', 'sb-date-error', cfg.i18n.invalidDate );
			valid = false;
		} else {
			var chosen = new Date( date );
			chosen.setHours( 0, 0, 0, 0 );
			var today  = new Date();
			today.setHours( 0, 0, 0, 0 );
			if ( chosen <= today ) {
				showFieldError( 'sb-date', 'sb-date-error', cfg.i18n.invalidDate );
				valid = false;
			}
		}

		return valid;
	}

	// ── Notice helpers ───────────────────────────────────────────────────────

	function showNotice( message, type ) {
		notice.textContent  = message;
		notice.className    = 'sb-form__notice sb-form__notice--' + type;
		notice.hidden       = false;
	}

	function hideNotice() {
		notice.hidden = true;
	}

	// Store the original submit button text for restoration after AJAX.
	var submitBtnOriginalText = submitBtn ? submitBtn.textContent : '';

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		if ( ! validateForm() ) {
			return;
		}

		var session = getSelectedSession();
		if ( ! session ) {
			showNotice( cfg.i18n.selectSession, 'error' );
			return;
		}

		// Build FormData.
		var fd = new FormData();
		fd.append( 'action', 'sinding_submit_booking' );
		fd.append( 'nonce',  cfg.nonce );
		fd.append( 'name',   document.getElementById( 'sb-name' ).value.trim() );
		fd.append( 'email',  document.getElementById( 'sb-email' ).value.trim() );
		fd.append( 'phone',  document.getElementById( 'sb-phone' ).value.trim() );
		fd.append( 'booking_date', document.getElementById( 'sb-date' ).value );
		fd.append( 'session_items[]', session );
		getSelectedAddons().forEach( function ( id ) {
			fd.append( 'addon_items[]', id );
		} );

		// UI feedback.
		submitBtn.disabled    = true;
		submitBtn.textContent = cfg.i18n.submitting;
		hideNotice();

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', cfg.ajaxUrl );
		xhr.onload = function () {
			submitBtn.disabled    = false;
			submitBtn.textContent = submitBtnOriginalText;

			var resp;
			try {
				resp = JSON.parse( xhr.responseText );
			} catch ( err ) {
				showNotice( cfg.i18n.error, 'error' );
				return;
			}

			if ( resp.success ) {
				showNotice( resp.data.message, 'success' );
				form.reset();
				// Deselect cards visually.
				wrap.querySelectorAll( 'input[type="radio"], input[type="checkbox"]' )
					.forEach( function ( i ) { i.checked = false; } );
				updatePrice();
				// Close modal after a short delay so the user reads the message.
				setTimeout( function () {
					closeModal();
					hideNotice();
				}, 4000 );
			} else {
				showNotice( ( resp.data && resp.data.message ) ? resp.data.message : cfg.i18n.error, 'error' );
			}
		};
		xhr.onerror = function () {
			submitBtn.disabled    = false;
			submitBtn.textContent = submitBtnOriginalText;
			showNotice( cfg.i18n.error, 'error' );
		};
		xhr.send( fd );
	} );

}() );
