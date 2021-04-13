/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';
import { getCurrentDates } from '@woocommerce/date';
import mapValues from 'lodash/mapValues';
import isNaN from 'lodash/isNaN';
import round from 'lodash/round';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const mapToData = ( primary, secondary ) => {
	return mapValues( primary, ( value, key ) => {
		const base = secondary[ key ];
		const percent = ( ( value - base ) / base ) * 100;
		const delta = isNaN( percent ) ? null : round( percent, 2 );
		return { value, delta };
	} );
};

export default function usePerformance( type ) {
	const query = getQuery();
	const currentDate = getCurrentDates( query );
	const deps = [
		type,
		currentDate.primary.range,
		currentDate.secondary.range,
	];

	return useSelect( ( select ) => {
		const { getDashboardPerformance, isResolving } = select( STORE_KEY );
		const primaryArgs = [ type, query, 'primary' ];
		const secondaryArgs = [ type, query, 'secondary' ];
		const primary = getDashboardPerformance( ...primaryArgs );
		const secondary = getDashboardPerformance( ...secondaryArgs );

		let data = {};
		let loading =
			isResolving( 'getDashboardPerformance', primaryArgs ) ||
			isResolving( 'getDashboardPerformance', secondaryArgs );

		if ( primary && secondary ) {
			data = mapToData( primary, secondary );
		} else {
			loading = true;
		}

		return {
			loading,
			data,
		};
	}, deps );
}
