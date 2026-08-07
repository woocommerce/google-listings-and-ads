/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingSearchConsoleProperties from '~/hooks/useExistingSearchConsoleProperties';
import AppSelectControl from '~/components/app-select-control';

/**
 * The value used for the "Create new" option (AC-028). Chosen so it can never collide with a
 * real property URL.
 */
export const CREATE_NEW_PROPERTY_VALUE = '__create_new_property__';

/**
 * Renders an `AppSelectControl` sourced from the candidate Search Console properties.
 *
 * No per-option "disabled but visible" primitive exists anywhere in this codebase (confirmed),
 * so non-covering properties are rendered as native disabled `<option>`s with an explanatory
 * suffix appended to their label (AC-007, AC-009) — a deliberately provisional stand-in pending
 * Q-003's still-outstanding design.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const SearchConsoleSelectControl = ( props ) => {
	const { data: properties } = useExistingSearchConsoleProperties();

	const options = ( properties ?? [] ).map( ( property ) => {
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

	options.push( {
		value: CREATE_NEW_PROPERTY_VALUE,
		label: __( 'Create a new property', 'google-listings-and-ads' ),
	} );

	return <AppSelectControl options={ options } { ...props } />;
};

export default SearchConsoleSelectControl;
