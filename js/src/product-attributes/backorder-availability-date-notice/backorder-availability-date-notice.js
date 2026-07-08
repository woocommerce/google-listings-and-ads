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
import { INVENTORY_PRODUCT_DATA } from './constants';
import { isBackorderSelected } from './utils';

/**
 * React component that renders a warning notice when backorder is selected
 * and no GLA availability date is set.
 *
 * @param {Object}      props
 * @param {string}      props.tabTarget  The tab target ID to link to.
 * @param {HTMLElement} props.glaDateEl  The GLA date input element.
 * @param {HTMLElement} props.glaTimeEl  The GLA time input element.
 */
const BackorderAvailabilityDateNotice = ( {
	tabTarget,
	glaDateEl,
	glaTimeEl,
} ) => {
	const [ isVisible, setIsVisible ] = useState(
		() => isBackorderSelected() && glaDateEl.value.trim() === ''
	);

	useEffect( () => {
		function updateVisibility() {
			setIsVisible(
				isBackorderSelected() && glaDateEl.value.trim() === ''
			);
		}

		const elements = document.querySelectorAll(
			`${ INVENTORY_PRODUCT_DATA } [name="_backorders"], ${ INVENTORY_PRODUCT_DATA } [name="_stock_status"]`
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
	}, [ glaDateEl, glaTimeEl ] );

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
			.querySelector( `.product_data_tabs a[href="#${ tabTarget }"]` )
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
					href={ `#${ tabTarget }` }
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
