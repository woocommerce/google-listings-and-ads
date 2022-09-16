/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

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

const AttributeMappingCategoryControl = () => {
	const [ selected, setSelected ] = useState();
	const { data: categories } = useCategoryTree();

	// Todo: Tree Select control to be implemented
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
				onChange={ setSelected }
			/>
		</>
	);
};

export default AttributeMappingCategoryControl;
