/**
 * A set of helper functions to retrieve Products related data
 * to be used in Products ReportFilter
 * Copied from
 * https://github.com/woocommerce/woocommerce-admin/blob/main/client/lib/async-requests/index.js
 */

/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { identity } from 'lodash';
import { getIdsFromQuery } from '@woocommerce/navigation';
import { NAMESPACE } from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @return {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function ( queryString = '', query ) {
		const pathString = typeof path === 'function' ? path( query ) : path;
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = {
			include: idList.join( ',' ),
			per_page: idList.length,
		};
		return apiFetch( {
			path: addQueryArgs( pathString, payload ),
		} ).then( ( data ) => data.map( handleData ) );
	};
}

export const getProductLabels = getRequestByIdString(
	NAMESPACE + '/products',
	( product ) => ( {
		key: product.id,
		label: product.name,
		// State here that the product is variable, so we could show the other filter is needed.
		type: product.type,
	} )
);

/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {Object} variation - variation returned by the api
 * @param {Array} variation.attributes - attribute objects, with option property.
 * @param {string} variation.name - name of variation.
 * @return {string} - formatted variation name
 */
export function getVariationName( { attributes, name } ) {
	const separator = getSetting( 'variationTitleAttributesSeparator', ' - ' );

	if ( name.indexOf( separator ) > -1 ) {
		return name;
	}

	const attributeList = attributes
		.map( ( { option } ) => option )
		.join( ', ' );

	return attributeList ? name + separator + attributeList : name;
}

export const getVariationLabels = getRequestByIdString(
	( { products } ) => {
		// If a product was specified, get just its variations.
		if ( products ) {
			return NAMESPACE + `/products/${ products }/variations`;
		}

		return NAMESPACE + '/variations';
	},
	( variation ) => {
		return {
			key: variation.id,
			label: getVariationName( variation ),
		};
	}
);
