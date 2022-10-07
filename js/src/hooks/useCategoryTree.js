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

/**
 * Returns the category tree used for Tree Select Control
 * It also maps the deleted (and previously selected) categories
 *
 * @param {Array} [selected] The selected category ID's
 * @return {{tree: *[], names: string, hasFinishedResolution: *}} The tree ready to insert in Tree Select Control, the categories separated by commas and the resolution state
 */
const useCategoryTree = ( selected = [] ) => {
	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getCategoryTree'
	);

	if ( ! hasFinishedResolution ) {
		return {
			hasFinishedResolution,
			tree: [],
			names: '',
		};
	}

	const deletedCategories = selected
		.filter(
			( category ) => ! data.find( ( e ) => e.id.toString() === category )
		)
		.map( ( category ) => {
			return {
				id: category,
				name: sprintf(
					// Translators: %s The category id
					'Category ID %s (deleted)',
					[ category ],
					'google-listings-and-ads'
				),
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

const getSelectedNames = ( selected, allCategories ) => {
	return selected
		.slice( 0, CATEGORIES_TO_SHOW_IN_TOOLTIP )
		.map( ( category ) => {
			return (
				allCategories.find( ( e ) => e.id.toString() === category )?.name ||
				sprintf(
					// translators: %d: number of categories.
					'Category ID %s (deleted)',
					[ category ],
					'google-listings-and-ads'
				)
			);
		} )
		.filter( ( x ) => x !== undefined )
		.join( SEPARATOR );
};

export default useCategoryTree;
