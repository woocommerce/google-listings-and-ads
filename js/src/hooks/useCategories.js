/**
 * External dependencies
 */
import { _x, __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';
import { CATEGORIES_TO_SHOW_IN_TOOLTIP } from '.~/constants';

const SEPARATOR = _x(
	', ',
	'the separator for concatenating the categories where the Attribute mapping rule is applied.',
	'google-listings-and-ads'
);

const getDeletedCategoryName = ( categoryId ) => {
	return sprintf(
		// translators: %d: number of categories.
		__( 'Category ID %s (deleted)', 'google-listings-and-ads' ),
		[ categoryId ]
	);
};

/**
 * Returns the categories and selected categories in the SelectControl format and also the categories in string format
 * It also maps the deleted (and previously selected) categories
 *
 * @param {Array<string>} [selected] The selected category ID's
 * @return {{categories: Array, selected: Array, names: string, hasFinishedResolution: boolean}} The categories ready to insert in Select Control as well as the selected categories and the categories separated by commas together with the resolution state
 */
const useCategories = ( selected = [] ) => {
	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getStoreCategories'
	);

	if ( ! hasFinishedResolution ) {
		return {
			hasFinishedResolution,
			categories: [],
			selected: [],
			names: '',
		};
	}

	// Parse deleted categories previously selected in a rule
	const deletedCategories = selected
		.filter(
			( category ) => ! data.find( ( e ) => e.id.toString() === category )
		)
		.map( ( category ) => {
			return {
				id: category,
				name: getDeletedCategoryName( category ),
				parent: 0,
			};
		} );

	const categories = [ ...data, ...deletedCategories ];
	const selectedCategories = getSelected( selected, categories );

	return {
		hasFinishedResolution,
		selected: selectedCategories,
		categories: formatCategoriesForSelectControl( categories ),
		names: getCategoryNames( selectedCategories ),
	};
};

/**
 * Format the categories for showing it in SelectControl
 *
 * @param {Array} allCategories Array with all the categories
 * @return {Array} The categories formatted for being used in SelectControl
 */
const formatCategoriesForSelectControl = ( allCategories = [] ) => {
	return allCategories.map( getSelectControlFormat );
};

/**
 * Get the names of the categories separated by commas
 *
 * @param {Array} categories  The categories to render as a name string
 * @return {string} The category names separated by comma
 */
const getCategoryNames = ( categories ) => {
	return categories
		.slice( 0, CATEGORIES_TO_SHOW_IN_TOOLTIP )
		.map( ( category ) => category.label )
		.join( SEPARATOR );
};

/**
 * Get the selected Categories in SelectControl Format
 *
 * @param {Array} selected Selected category IDs
 * @param {Array} allCategories All the categories available
 * @return {Array} The selected categories in SelectControl format
 */
const getSelected = ( selected, allCategories ) => {
	return selected.map( ( selectedCategory ) => {
		const categoryData = allCategories.find(
			( category ) => category.id.toString() === selectedCategory
		);
		return getSelectControlFormat( categoryData );
	} );
};

/**
 * Return a category in SelectControl format
 *
 * @param {Object} category The category to be formatted
 * @return {{label: string, value: number, key: number}} The category formatted in SelectControl format
 */
const getSelectControlFormat = ( category ) => {
	return {
		key: category.id,
		label: category.name,
		value: category.id,
	};
};

export default useCategories;
