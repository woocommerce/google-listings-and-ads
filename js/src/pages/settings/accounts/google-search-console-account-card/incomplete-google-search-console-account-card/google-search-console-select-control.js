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
 * Renders an `AppSelectControl` sourced from the candidate Google Search Console properties.
 *
 * No per-option "disabled but visible" primitive exists anywhere in this codebase (confirmed),
 * so non-covering properties are rendered as native disabled `<option>`s with an explanatory
 * suffix appended to their label — a deliberately provisional stand-in pending design for
 * this state.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleSearchConsoleSelectControl = ( props ) => {
	const { account } = useGoogleSearchConsoleAccount();
	const properties = account?.properties ?? [];

	const options = properties.map( ( property ) => {
		const isSelectable = property.selectable !== false;

		return {
			value: property.url,
			label: isSelectable
				? property.url
				: sprintf(
						// translators: 1: property URL, 2: reason why the property can't be selected.
						__( '%1$s (%2$s)', 'google-listings-and-ads' ),
						property.url,
						property.reason ??
							__(
								"Doesn't cover this store's URL",
								'google-listings-and-ads'
							)
				  ),
			disabled: ! isSelectable,
		};
	} );

	return <AppSelectControl options={ options } { ...props } />;
};

export default GoogleSearchConsoleSelectControl;
