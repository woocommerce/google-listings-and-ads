/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import TreeSelectControl from '.~/components/tree-select-control';
import useCategories from '.~/hooks/useCategories';
import { CATEGORY_CONDITION_SELECT_TYPES } from '.~/constants';

/**
 * Renders the selectors relative to the categories
 *
 * @param {Object} props Component props
 * @param {Array} props.selectedCategories Selected category IDs
 * @param {'ALL'|'EXCEPT'|'ONLY'} props.selectedConditionalType Selected conditional type
 * @param {Function} props.onConditionalTypeChange Callback when the conditional type changes
 * @param {Function} props.onCategoriesChange Callback when the categories change
 * @param {Function} props.onCategorySelectorOpen Callback when the categories dropdown is open
 */
const AttributeMappingCategoryControl = ( {
	selectedConditionalType = CATEGORY_CONDITION_SELECT_TYPES.ALL,
	selectedCategories,
	onConditionalTypeChange = noop,
	onCategoriesChange = noop,
	onCategorySelectorOpen = noop,
} ) => {
	const { tree } = useCategories( selectedCategories );

	return (
		<>
			<AppSelectControl
				options={ [
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.ALL,
						label: __(
							'Apply to All categories',
							'google-listings-and-ads'
						),
					},
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.EXCEPT,
						label: __(
							'Apply to All categories EXCEPT',
							'google-listings-and-ads'
						),
					},
					{
						value: CATEGORY_CONDITION_SELECT_TYPES.ONLY,
						label: __(
							'Apply ONLY to this categories',
							'google-listings-and-ads'
						),
					},
				] }
				value={ selectedConditionalType }
				onChange={ onConditionalTypeChange }
			/>
			{ ( selectedConditionalType ===
				CATEGORY_CONDITION_SELECT_TYPES.ONLY ||
				selectedConditionalType ===
					CATEGORY_CONDITION_SELECT_TYPES.EXCEPT ) && (
				<TreeSelectControl
					onDropdownVisibilityChange={ onCategorySelectorOpen }
					options={ tree }
					onChange={ onCategoriesChange }
					value={ selectedCategories }
					placeholder={ __(
						'Select categories',
						'google-listings-and-ads'
					) }
				/>
			) }
		</>
	);
};

export default AttributeMappingCategoryControl;
