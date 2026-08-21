/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import AppSelectControl from '~/components/app-select-control';

/**
 * Derives explanatory copy for a non-usable match, since the backend supplies no `reason`
 * field — only the `covers`/`permissionLevel` booleans a usability decision was made from.
 *
 * @param {import('~/data/types.js').GoogleSearchConsoleMatch} match A non-usable match.
 * @return {string} The explanation to show next to the match.
 */
function getUnusableReason( match ) {
	return match.covers
		? __( 'Not yet verified', 'google-listings-and-ads' )
		: __( "Doesn't cover this store's URL", 'google-listings-and-ads' );
}

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Search Console matches.
 *
 * No per-option "disabled but visible" primitive exists anywhere in this codebase (confirmed),
 * so non-usable matches are rendered as native disabled `<option>`s with an explanatory
 * suffix appended to their label — a deliberately provisional stand-in pending design for
 * this state.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleSearchConsoleSelectControl = ( props ) => {
	const { account } = useGoogleSearchConsoleAccount();
	const matches = account?.matches ?? [];

	const options = matches.map( ( match ) => {
		return {
			value: match.siteUrl,
			label: match.usable
				? match.siteUrl
				: sprintf(
						// translators: 1: property URL, 2: reason why the property can't be selected.
						__( '%1$s (%2$s)', 'google-listings-and-ads' ),
						match.siteUrl,
						getUnusableReason( match )
				  ),
			disabled: ! match.usable,
		};
	} );

	return <AppSelectControl options={ options } { ...props } />;
};

export default GoogleSearchConsoleSelectControl;
