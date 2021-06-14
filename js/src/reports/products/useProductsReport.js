/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { mapReportFieldsToPerformance } from '.~/data/utils';
import useUrlQuery from '.~/hooks/useUrlQuery';

const category = 'products';
const emptyData = {
	products: [],
	intervals: [],
	totals: {},
};

/**
 * Get products report data by source of program type.
 * Query parameters will be parsed from the URL by this hook.
 *
 * @param  {string} type Data source of program type, 'free' or 'paid'.
 * @return {ProductsReportSchema} The fetched products report data and its status.
 */
export default function useProductsReport( type ) {
	const query = useUrlQuery();

	return useSelect(
		( select ) => {
			const { getReport } = select( STORE_KEY );

			const primary = getReport( category, type, query, 'primary' );
			const secondary = getReport( category, type, query, 'secondary' );
			const loaded = primary.loaded && secondary.loaded;

			let data = emptyData;

			if ( loaded && primary.data && secondary.data ) {
				data = {
					products: primary.data.products || emptyData.products,
					intervals: primary.data.intervals || emptyData.intervals,
					totals: mapReportFieldsToPerformance(
						primary.data.totals,
						secondary.data.totals,
						primary.reportQuery.fields
					),
				};
			}

			return { data, loaded };
		},
		[ type, query ]
	);
}

/**
 * @typedef { import("../index.js").ProductsReportSchema } ProductsReportSchema
 */
