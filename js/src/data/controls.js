/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import TYPES from './action-types';

export const fetchWithHeaders = ( options ) => {
	return {
		type: 'FETCH_WITH_HEADERS',
		options,
	};
};

export const awaitPromise = ( promise ) => {
	return {
		type: 'GLA_AWAIT_PROMISE',
		promise,
	};
};

export const recordGlaDataEvent = ( actionType, dataOrResponse ) => {
	const isResponse = dataOrResponse instanceof Response;
	return {
		type: 'GLA_RECORD_DATA_EVENT',
		actionType,
		data: isResponse ? null : dataOrResponse,
		response: isResponse ? dataOrResponse.clone() : null,
	};
};

/**
 * Received the data of budget recommendations.
 *
 * @event gla_ads_budget_recommendations_received
 * @property {string} source The data source of the budget recommendations, e.g. 'google-ads-api', 'fallback-database'.
 * @property {number} recommended_budget The recommended daily budget displayed to merchants regardless of the final amount they choose.
 * @property {string} metrics_availability The availability of the forecast metrics for the budget recommendations, e.g. 'all', 'partial', 'none'.
 */

/**
 * Received the data of budget metrics.
 *
 * @event gla_ads_budget_metrics_received
 * @property {number} budget The budget amount.
 * @property {string} currency The currency of the budget.
 * @property {string} country The country code of the budget.
 * @property {boolean} available Whether the budget metrics are available or not.
 */

/**
 * @fires gla_ads_budget_recommendations_received
 * @fires gla_ads_budget_metrics_received
 */
function recordGlaDataEventControl( { actionType, data, response } ) {
	switch ( actionType ) {
		case TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS: {
			recordGlaEvent(
				'gla_ads_budget_recommendations_received',
				data.eventProps
			);
			break;
		}

		case TYPES.RECEIVE_ADS_BUDGET_METRICS: {
			Promise.resolve( data || response.json() ).then(
				( { budget, currency, country, country_codes, metrics } ) => {
					recordGlaEvent( 'gla_ads_budget_metrics_received', {
						budget,
						currency,
						country: country || country_codes.at( 0 ),
						available: Boolean( metrics ),
					} );
				}
			);
			break;
		}
	}
}

export const controls = {
	...dataControls,
	FETCH_WITH_HEADERS( { options } ) {
		return apiFetch( { ...options, parse: false } )
			.then( ( response ) => {
				return Promise.all( [
					response.headers,
					response.status,
					response.json(),
				] );
			} )
			.then( ( [ headers, status, data ] ) => ( {
				headers,
				status,
				data,
			} ) );
	},
	GLA_AWAIT_PROMISE: ( { promise } ) => promise,
	GLA_RECORD_DATA_EVENT: recordGlaDataEventControl,
};
