/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import './google-search-console-select-control.scss';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleProperty} GoogleSearchConsoleProperty
 */

/**
 * Derives explanatory copy for a non-usable property, since the backend supplies no `reason`
 * field — only the `covers_store_url`/`permissionLevel` booleans a usability decision was made from.
 *
 * @param {GoogleSearchConsoleProperty} property A non-usable property.
 * @return {string} The explanation to show next to the property.
 */
function getUnusableReason( property ) {
	return property.covers_store_url
		? __( 'Not yet verified', 'google-listings-and-ads' )
		: __( "Doesn't cover this store's URL", 'google-listings-and-ads' );
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
	const options = properties.map( ( property ) => {
		return {
			value: property.siteUrl,
			label: property.usable
				? property.siteUrl
				: sprintf(
						// translators: 1: property URL, 2: reason why the property can't be selected.
						__( '%1$s (%2$s)', 'google-listings-and-ads' ),
						property.siteUrl,
						getUnusableReason( property )
				  ),
			disabled: ! property.usable,
		};
	} );

	return (
		<AppSelectControl
			className="gla-google-search-console-select-control"
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default GoogleSearchConsoleSelectControl;
