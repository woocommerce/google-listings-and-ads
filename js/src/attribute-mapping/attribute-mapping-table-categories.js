/**
 * External dependencies
 */
import { _n, sprintf, _x, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTooltip from '.~/components/app-tooltip';

const DUMMY_CATEGORIES = [
	{ id: 1, name: 'Category 1' },
	{ id: 2, name: 'Category 2' },
	{ id: 3, name: 'Category 3' },
];

const SEPARATOR = _x(
	', ',
	'the separator for concatenating the categories where the Attribute mapping rule is applied.',
	'google-listings-and-ads'
);

const CATEGORIES_TO_SHOW = 5;

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
	if ( condition === 'ALL' ) {
		return __( 'All', 'google-listings-and-ads' );
	}

	const categoryArray = categories.split( ',' );
	const categoryNames = categoryArray
		.slice( 0, CATEGORIES_TO_SHOW )
		.map( ( category ) => {
			return DUMMY_CATEGORIES.find(
				( e ) => e.id === parseInt( category, 10 )
			).name;
		} )
		.join( SEPARATOR );

	const maybeDots =
		categoryArray.length > CATEGORIES_TO_SHOW
			? sprintf(
					// translators: %d: The number of categories.
					__( '+ %d more', 'google-listings-and-ads' ),
					categoryArray.length - CATEGORIES_TO_SHOW
			  )
			: '';

	return (
		<>
			<span>
				{ condition === 'ONLY'
					? __( 'Only in', 'google-listings-and-ads' )
					: __( 'All except', 'google-listings-and-ads' ) }
			</span>
			<AppTooltip
				text={
					<div className="gla-attribute-mapping__table-categories-tooltip">
						{ categoryNames }
						{ maybeDots && <span>{ maybeDots }</span> }
					</div>
				}
			>
				<div className="gla-attribute-mapping__table-categories-help">
					{ sprintf(
						// translators: %d: number of categories.
						_n(
							'%d category',
							'%d categories',
							categoryArray.length,
							'google-listings-and-ads'
						),
						categoryArray.length
					) }
				</div>
			</AppTooltip>
		</>
	);
};

export default AttributeMappingTableCategories;
