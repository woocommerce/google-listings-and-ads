/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import TreeSelectControl from '.~/components/tree-select-control';
import useCategoryTree from '.~/hooks/useCategoryTree';

const SELECT_TYPES = {
	ALL: 'ALL',
	EXCEPT: 'EXCEPT',
	ONLY: 'ONLY',
};

/**
 * Renders the selectors relative to the categories
 *
 * @param {Function} [onCategorySelectorOpen] callback when the Category Tree Selector is open
 */
const AttributeMappingCategoryControl = ( {
	onCategorySelectorOpen = noop(),
} ) => {
	const [ selectedType, setSelectedType ] = useState();
	const [ selectedCategories, setSelectedCategories ] = useState();
	const { data: categories } = useCategoryTree();

	return (
		<>
			<AppSelectControl
				options={ [
					{
						value: SELECT_TYPES.ALL,
						label: __(
							'Apply to All categories',
							'google-listings-and-ads'
						),
					},
					{
						value: SELECT_TYPES.EXCEPT,
						label: __(
							'Apply to All categories EXCEPT',
							'google-listings-and-ads'
						),
					},
					{
						value: SELECT_TYPES.ONLY,
						label: __(
							'Apply ONLY to this categories',
							'google-listings-and-ads'
						),
					},
				] }
				onChange={ setSelectedType }
			/>
			{ ( selectedType === SELECT_TYPES.ONLY ||
				selectedType === SELECT_TYPES.EXCEPT ) && (
				<TreeSelectControl
					onDropdownVisibilityChange={ onCategorySelectorOpen }
					options={ categories }
					onChange={ setSelectedCategories }
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
