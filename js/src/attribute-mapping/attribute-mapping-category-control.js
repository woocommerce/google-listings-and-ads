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
import useCategoryTree from '.~/hooks/useCategoryTree';
import { CATEGORY_CONDITION_SELECT_TYPES } from '.~/constants';

/**
 * Renders the selectors relative to the categories

 * @param {Function} [onCategorySelectorOpen] callback when the Category Tree Selector is open
 */
const AttributeMappingCategoryControl = ( {
	selectedConditionalType = CATEGORY_CONDITION_SELECT_TYPES.ALL,
	selectedCategories,
	onConditionalTypeChange = noop,
	onCategoriesChange = noop,
	onCategorySelectorOpen = noop,
} ) => {
	const { data: categories } = useCategoryTree();

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
					options={ categories }
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
