/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTooltip from '.~/components/app-tooltip';

const DUMMY_CATEGORIES = [
	{ id: 1, name: 'Category 1' },
	{ id: 2, name: 'Category 2' },
	{ id: 3, name: 'Category 3' },
];

const AttributeMappingTableCategories = ( { categories } ) => {
	const categoryNames = categories
		.slice( 0, 5 )
		.map( ( category ) => {
			return DUMMY_CATEGORIES.find(
				( e ) => e.id === parseInt( category, 10 )
			).name;
		} )
		.join( ', ' );

	const maybeDots = categories.length > 5 ? '...' : '';

	return (
		<AppTooltip text={ `${ categoryNames }${ maybeDots }` }>
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
		</AppTooltip>
	);
};

export default AttributeMappingTableCategories;
