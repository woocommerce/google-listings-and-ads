/**
 * External dependencies
 */
import { _n, sprintf, __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTooltip from '.~/components/app-tooltip';
import {
	CATEGORIES_TO_SHOW_IN_TOOLTIP,
	CATEGORY_CONDITION_SELECT_TYPES,
} from '.~/constants';
import useCategories from '.~/hooks/useCategories';

/**
 * Show a text with the number of categories
 *
 * @param {Object} props The component props
 * @param {Array} props.categories The categories to show the number
 * @return {JSX.Element} The component
 */
const CategoryHelperText = ( { categories } ) => {
	return (
		<div className="gla-attribute-mapping__table-categories-help">
			{ sprintf(
				// translators: %d: number of categories.
				_n(
					'%d category',
					'%d categories',
					categories.length,
					'google-listings-and-ads'
				),
				categories.length
			) }
		</div>
	);
};

/**
 * Renders a text and maybe a tooltip showing the categories for an Attribute Mapping rule
 *
 * @param {Object} props Component props
 * @param {string}  props.categories The categories to render
 * @param {string} props.condition The condition to which this categories apply
 *
 * @return {JSX.Element|string} The component
 */
const AttributeMappingTableCategories = ( { categories, condition } ) => {
	const categoryArray = categories?.split( ',' ) || [];
	const { names } = useCategories( categoryArray );

	if ( condition === CATEGORY_CONDITION_SELECT_TYPES.ALL ) {
		return __( 'All', 'google-listings-and-ads' );
	}

	const more =
		categoryArray.length > CATEGORIES_TO_SHOW_IN_TOOLTIP
			? sprintf(
					// translators: %d: The number of categories.
					__( '+ %d more', 'google-listings-and-ads' ),
					categoryArray.length - CATEGORIES_TO_SHOW_IN_TOOLTIP
			  )
			: '';

	return (
		<>
			<span>
				{ condition === CATEGORY_CONDITION_SELECT_TYPES.ONLY
					? __( 'Only in', 'google-listings-and-ads' )
					: __( 'All except', 'google-listings-and-ads' ) }
			</span>
			{ names.length ? (
				<AppTooltip
					text={
						<div className="gla-attribute-mapping__table-categories-tooltip">
							{ names }
							{ more && <span>{ more }</span> }
						</div>
					}
				>
					<CategoryHelperText categories={ categoryArray } />
				</AppTooltip>
			) : (
				<CategoryHelperText categories={ categoryArray } />
			) }
		</>
	);
};

export default AttributeMappingTableCategories;
