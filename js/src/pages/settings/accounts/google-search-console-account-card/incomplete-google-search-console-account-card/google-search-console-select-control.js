/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleProperty} GoogleSearchConsoleProperty
 */

/**
 * Derives explanatory copy for a property, since the backend supplies no `reason` field of its
 * own — only the `covers_store_url`/`exact_match`/`usable` booleans a rendering decision was
 * made from. Returns `null` for an exact match, which needs no annotation.
 *
 * @param {GoogleSearchConsoleProperty} property A candidate property.
 * @return {string|null} The explanation to show next to the property, if any.
 */
function getPropertyAnnotation( property ) {
	if ( ! property.usable ) {
		return property.covers_store_url
			? __( 'Not yet verified', 'google-listings-and-ads' )
			: __( "Doesn't cover this store's URL", 'google-listings-and-ads' );
	}

	return property.exact_match
		? null
		: __(
				"Not an exact match for your store's URL",
				'google-listings-and-ads'
		  );
}

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Search Console properties.
 *
 * @param {Object} props Component props.
 * @param {GoogleSearchConsoleProperty[]} props.properties The candidate
 *   properties to render as options. The remaining props are forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleSearchConsoleSelectControl = ( { properties = [], ...props } ) => {
	// `AppSelectControl`'s `autoSelectFirstOption` always pre-selects `options[0]` regardless
	// of `disabled` — sort usable properties first so a disabled one is never silently
	// pre-selected (or, with exactly one candidate, left as the sole non-interactive option).
	const sortedProperties = [ ...properties ].sort(
		( a, b ) => Number( b.usable ) - Number( a.usable )
	);

	const options = sortedProperties.map( ( property ) => {
		const annotation = getPropertyAnnotation( property );

		return {
			value: property.siteUrl,
			label: annotation
				? sprintf(
						// translators: 1: property URL, 2: a note about the property (e.g. why it can't be selected).
						__( '%1$s (%2$s)', 'google-listings-and-ads' ),
						property.siteUrl,
						annotation
				  )
				: property.siteUrl,
			disabled: ! property.usable,
		};
	} );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default GoogleSearchConsoleSelectControl;
