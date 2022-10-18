/**
 * External dependencies
 */
import { _x, sprintf } from '@wordpress/i18n';

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
		'Category ID %s (deleted)',
		[ categoryId ],
		'google-listings-and-ads'
	);
};

/**
 * Returns the category tree used for Tree Select Control
 * It also maps the deleted (and previously selected) categories
 *
 * @param {Array<string>} [selected] The selected category ID's
 * @return {{tree: *[], names: string, hasFinishedResolution: *}} The tree ready to insert in Tree Select Control, the categories separated by commas and the resolution state
 */
const useCategories = ( selected = [] ) => {
	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getStoreCategories'
	);

	if ( ! hasFinishedResolution ) {
		return {
			hasFinishedResolution,
			tree: [],
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

	return {
		hasFinishedResolution,
		tree: getTree( categories ),
		names: getSelectedNames( selected, categories ),
	};
};

/**
 * Get the categories in hierarchical tree format
 *
 * @param {Array} allCategories Array with all the categories
 * @param {number} [parent=0] The ID for the parent categories to get the leaf tree. By default 0 (root)
 * @return {Array} The categories formatted as a tree
 */
const getTree = ( allCategories = [], parent = 0 ) => {
	const currentCategories = [];
	const categories = allCategories.filter( ( cat ) => cat.parent === parent );

	for ( const category of categories ) {
		const categoryWithChildren = {
			value: category.id.toString(),
			label: category.name,
			children: getTree( allCategories, category.id ),
		};

		currentCategories.push( categoryWithChildren );
	}

	return currentCategories;
};

/**
 * Get the names of the selected categories separated by commas
 *
 * @param {Array} selected Selected category IDs
 * @param {Array} allCategories All the categories available
 * @return {string} The selected category names separated by comma
 */
const getSelectedNames = ( selected, allCategories ) => {
	return selected
		.slice( 0, CATEGORIES_TO_SHOW_IN_TOOLTIP )
		.map( ( category ) => {
			return (
				allCategories.find( ( e ) => e.id.toString() === category )
					?.name || getDeletedCategoryName( category )
			);
		} )
		.filter( ( x ) => x !== undefined )
		.join( SEPARATOR );
};

export default useCategories;
