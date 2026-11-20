/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	GLA_TAB_TARGET,
	INVENTORY_PRODUCT_DATA,
	PRODUCT_TYPE_SELECT,
} from './constants';
import { isBackorderSelected, isProductTypeApplicable } from './utils';

/**
 * Whether the notice should be shown for the current form state.
 *
 * @param {Array<string>}    productTypes Product types the availability date applies to.
 * @param {HTMLInputElement} glaDateEl    The GLA date input element.
 * @return {boolean} True if the notice should be shown.
 */
const shouldShowNotice = ( productTypes, glaDateEl ) => {
	return (
		isProductTypeApplicable( productTypes ) &&
		isBackorderSelected() &&
		glaDateEl.value.trim() === ''
	);
};

/**
 * React component that renders a warning notice when backorder is selected
 * and no GLA availability date is set.
 *
 * @param {Object}        props
 * @param {Array<string>} props.productTypes Product types the availability date applies to.
 * @param {HTMLElement}   props.glaDateEl    The GLA date input element.
 * @param {HTMLElement}   props.glaTimeEl    The GLA time input element.
 */
const BackorderAvailabilityDateNotice = ( {
	productTypes,
	glaDateEl,
	glaTimeEl,
} ) => {
	const [ isVisible, setIsVisible ] = useState( () =>
		shouldShowNotice( productTypes, glaDateEl )
	);

	useEffect( () => {
		function updateVisibility() {
			setIsVisible( shouldShowNotice( productTypes, glaDateEl ) );
		}

		const elements = document.querySelectorAll(
			[
				PRODUCT_TYPE_SELECT,
				`${ INVENTORY_PRODUCT_DATA } [name="_manage_stock"]`,
				`${ INVENTORY_PRODUCT_DATA } [name="_stock"]`,
				`${ INVENTORY_PRODUCT_DATA } [name="_backorders"]`,
				`${ INVENTORY_PRODUCT_DATA } [name="_stock_status"]`,
			].join( ', ' )
		);

		elements.forEach( ( element ) =>
			element.addEventListener( 'change', updateVisibility )
		);
		glaDateEl.addEventListener( 'change', updateVisibility );
		glaTimeEl.addEventListener( 'change', updateVisibility );

		return () => {
			elements.forEach( ( element ) =>
				element.removeEventListener( 'change', updateVisibility )
			);
			glaDateEl.removeEventListener( 'change', updateVisibility );
			glaTimeEl.removeEventListener( 'change', updateVisibility );
		};
	}, [ productTypes, glaDateEl, glaTimeEl ] );

	if ( ! isVisible ) {
		return null;
	}

	/**
	 * Handle the link click event.
	 *
	 * @param {MouseEvent} event - The event object.
	 */
	function handleLinkClick( event ) {
		event.preventDefault();
		document
			.querySelector(
				`.product_data_tabs a[href="#${ GLA_TAB_TARGET }"]`
			)
			?.click();
	}

	const message = createInterpolateElement(
		__(
			'Google requires an availability date for products on backorder. Set the Availability date in the <link>Google for WooCommerce tab</link> so your product can be submitted correctly.',
			'google-listings-and-ads'
		),
		{
			link: (
				// createInterpolateElement injects the translated text as children at runtime.
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href={ `#${ GLA_TAB_TARGET }` }
					className="gla-availability-date-tab-link"
					onClick={ handleLinkClick }
				></a>
			),
		}
	);

	return (
		<Notice status="warning" isDismissible={ false }>
			{ message }
		</Notice>
	);
};

export default BackorderAvailabilityDateNotice;
