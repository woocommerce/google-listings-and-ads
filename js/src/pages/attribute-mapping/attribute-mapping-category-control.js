/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useCategories from '~/hooks/useCategories';
import { CATEGORY_CONDITION_SELECT_TYPES } from '~/constants';
import SearchableSelectControl from '~/components/searchable-select-control';
import AppSelectControl from '~/components/app-select-control';

/**
 * Renders the selectors relative to the categories
 *
 * @param {Object} props Component props
 * @param {Array} props.selectedCategories Selected category IDs
 * @param {'ALL'|'EXCEPT'|'ONLY'} props.selectedConditionalType Selected conditional type
 * @param {Function} props.onConditionalTypeChange Callback when the conditional type changes
 * @param {Function} props.onCategoriesChange Callback when the categories change
 */
const AttributeMappingCategoryControl = ( {
	selectedConditionalType = CATEGORY_CONDITION_SELECT_TYPES.ALL,
	selectedCategories,
	onConditionalTypeChange = noop,
	onCategoriesChange = noop,
} ) => {
	const { categories, selected } = useCategories( selectedCategories );
	return (
		<>
			<AppSelectControl
				onChange={ onConditionalTypeChange }
				options={ [
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.ALL,
						label: __(
							'Apply to all categories',
							'google-listings-and-ads'
						),
					},
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.EXCEPT,
						label: __(
							'Apply to all categories EXCEPT',
							'google-listings-and-ads'
						),
					},
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.ONLY,
						label: __(
							'Apply ONLY to these categories',
							'google-listings-and-ads'
						),
					},
				] }
				value={ selectedConditionalType }
			/>
			{ ( selectedConditionalType ===
				CATEGORY_CONDITION_SELECT_TYPES.ONLY ||
				selectedConditionalType ===
					CATEGORY_CONDITION_SELECT_TYPES.EXCEPT ) && (
				<SearchableSelectControl
					inlineTags={ true }
					multiple={ true }
					onChange={ ( values ) =>
						onCategoriesChange(
							values.map( ( category ) => category.value )
						)
					}
					options={ categories }
					placeholder={ __(
						'Type for search',
						'google-listings-and-ads'
					) }
					selected={ selected }
					isSearchable
				/>
			) }
		</>
	);
};

export default AttributeMappingCategoryControl;
