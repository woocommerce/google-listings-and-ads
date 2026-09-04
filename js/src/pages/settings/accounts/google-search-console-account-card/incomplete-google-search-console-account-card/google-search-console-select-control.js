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
 * field — only the `covers`/`permissionLevel` booleans a usability decision was made from.
 *
 * @param {GoogleSearchConsoleProperty} property A non-usable property.
 * @return {string} The explanation to show next to the property.
 */
function getUnusableReason( property ) {
	return property.covers
		? __( 'Not yet verified', 'google-listings-and-ads' )
		: __( "Doesn't cover this store's URL", 'google-listings-and-ads' );
}

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Search Console properties.
 *
 * No per-option "disabled but visible" primitive exists anywhere in this codebase (confirmed),
 * so non-usable properties are rendered as native disabled `<option>`s with an explanatory
 * suffix appended to their label — a deliberately provisional stand-in pending design for
 * this state.
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
