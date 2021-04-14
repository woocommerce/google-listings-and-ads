/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import isNaN from '.~/utils/isNaN';
import round from '.~/utils/round';

const mapToData = ( primary, secondary ) => {
	return Object.keys( primary ).reduce( ( acc, key ) => {
		const value = primary[ key ];
		const base = secondary[ key ];
		const percent = ( ( value - base ) / base ) * 100;
		const delta = isNaN( percent ) ? null : round( percent );
		return {
			...acc,
			[ key ]: { value, delta },
		};
	}, {} );
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
