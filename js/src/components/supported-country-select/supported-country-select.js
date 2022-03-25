/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useViewportMatch } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import TreeSelectControl from '.~/components/tree-select-control';
import useMCCountryTreeOptions from './useMCCountryTreeOptions';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Returns a TreeSelectControl component with list of countries grouped by continents.
 *
 * This component is for selecting countries under Google Merchant Center supported countries.
 * And the selectable countries can be further limited by the `countryCodes` prop.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} [props.countryCodes] Country codes for converting to tree-based option structure. It will use all MC supported countries if not specified.
 * @param {Object} props.restProps Props to be forwarded to TreeSelectControl.
 */
export default function SupportedCountrySelect( {
	countryCodes,
	...restProps
} ) {
	const maxVisibleTags = useViewportMatch( 'medium', '<' ) ? 5 : 10;
	const treeOptions = useMCCountryTreeOptions( countryCodes );

	return (
		<TreeSelectControl
			placeholder={ __(
				'Start typing to filter countries…',
				'google-listings-and-ads'
			) }
			selectAllLabel={ __( 'All countries', 'google-listings-and-ads' ) }
			maxVisibleTags={ maxVisibleTags }
			options={ treeOptions }
			{ ...restProps }
		/>
	);
}
