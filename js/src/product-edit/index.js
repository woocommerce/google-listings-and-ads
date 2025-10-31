/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import './index.scss';

$( document ).on( 'ready', () => {
	const NOTICE_SELECTOR = '#gla-backorder-availability-notice';

	/**
	 * Check if backorders are allowed.
	 *
	 * @param {string} val Backorder value.
	 * @return {boolean} True if backorders are allowed.
	 */
	function backorderAllowed( val ) {
		return val && val !== 'no' && val !== '0';
	}

	/**
	 * Check if backorders are allowed for simple products.
	 *
	 * @return {boolean} True if backorders are allowed.
	 */
	function checkSimpleProduct() {
		const stockStatus = $( 'input[name="_stock_status"]:checked' ).val();
		const backorders = $( 'input[name="_backorders"]:checked' ).val();
		return stockStatus === 'onbackorder' || backorderAllowed( backorders );
	}

	/**
	 * Check if backorders are allowed for variable products.
	 *
	 * @return {boolean} True if backorders are allowed.
	 */
	function checkVariableProducts() {
		let showNotice = false;

		$( '.woocommerce_variation' ).each( function () {
			const $variation = $( this );
			const stockVal = $variation
				.find(
					'select[name^="variable_stock_status"], input[name^="variable_stock_status"]'
				)
				.val();
			const backVal = $variation
				.find(
					'select[name^="variable_backorders"], input[name^="variable_backorders"]'
				)
				.val();

			if ( stockVal === 'onbackorder' || backorderAllowed( backVal ) ) {
				showNotice = true;
				return false;
			}
		} );

		return showNotice;
	}

	/**
	 * Get the current product type.
	 *
	 * @return {string|null} The product type or null if not found.
	 */
	function getProductType() {
		const $ptype = $( '#product-type' );
		if ( $ptype.length ) {
			// ensure we return a string
			return String( $ptype.val() );
		}

		const bodyClass = $( 'body' ).attr( 'class' ) || '';
		const m = bodyClass.match( /product-type-([^\s]+)/ );
		return m ? m[ 1 ] : null;
	}

	/**
	 * Unified update handler
	 */
	function updateAll() {
		const productType = getProductType();
		const isSimple = productType === 'simple';
		const isVariable = productType === 'variable';

		let showNotice = false;

		if ( isSimple ) {
			showNotice = checkSimpleProduct();
		} else if ( isVariable ) {
			showNotice = checkVariableProducts();
		} else {
			// If we cannot determine product type, hide notice for safety.
			showNotice = false;
		}

		if ( showNotice ) {
			$( NOTICE_SELECTOR ).show();
		} else {
			$( NOTICE_SELECTOR ).hide();
		}
	}

	/**
	 * React when product type changes (simple <-> variable, etc.)
	 */
	function onProductTypeSwitch() {
		// product type changed — re-check immediately
		updateAll();

		// variations may be (re)rendered after a short delay — run again a bit later
		// to catch the dynamic markup load (helps when switching to Variable).
		setTimeout( updateAll, 250 );
	}

	// Initial run (also run slightly later to handle lazy-loaded markup)
	updateAll();
	setTimeout( updateAll, 250 );

	// Watch for inventory field changes and variation field changes
	$( document ).on(
		'change',
		'input[name="_stock_status"], input[name="_backorders"], select[name^="variable_stock_status"], input[name^="variable_stock_status"], select[name^="variable_backorders"], input[name^="variable_backorders"]',
		updateAll
	);

	// Re-run when variations load dynamically
	$( document ).on(
		'woocommerce_variations_loaded woocommerce_variations_added updated_wc_div',
		updateAll
	);

	// Observe DOM changes in variations container
	const container = document.querySelector( '.woocommerce_variations' );
	if ( container ) {
		const mo = new MutationObserver( updateAll );
		mo.observe( container, { childList: true, subtree: true } );
	}

	// Detect product type switch (reliable)
	$( document ).on( 'change', '#product-type', onProductTypeSwitch );

	// Anchor redirect: ensure clicking the tab anchor navigates correctly.
	$( document ).on(
		'click',
		'#gla-backorder-availability-notice a',
		function ( e ) {
			e.preventDefault();

			$( '.product_data_tabs a[href="#gla_attributes"]' ).trigger(
				'click'
			);
		}
	);
} );
