/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {Object} CYOIncentiveAmount
 * @property {string} currencyCode The ISO 4217 currency code. Example: 'USD'.
 * @property {string} units The amount in the major unit of the currency. Example: '1800'.
 */

/**
 * @typedef {Object} CYOIncentiveRequirements
 * @property {{ awardAmount: CYOIncentiveAmount }} spend The spend requirement, including the award amount earned upon meeting it.
 * @property {CYOIncentiveAmount} requiredAmount The minimum spend amount required to qualify for the incentive.
 */

/**
 * @typedef {Object} CYOIncentive
 * @property {number} id The unique identifier for the incentive.
 * @property {string} type The incentive type. Example: 'ACQUISITION'.
 * @property {'high'|'medium'|'low'} offer The offer tier.
 * @property {string} termsAndConditionsUrl URL to the terms and conditions for this incentive.
 * @property {CYOIncentiveRequirements} requirements The spend requirements to qualify for this incentive.
 */

/**
 * @typedef {Object} CYOIncentivesPayload
 * @property {CYOIncentive[]|null} data The list of CYO incentives, or `null` if not yet fetched.
 * @property {boolean} hasFinishedResolution Whether the data fetching has finished.
 */

/**
 * Custom hook to retrieve CYO incentives from the store.
 *
 * @return {CYOIncentivesPayload} The CYO incentives payload.
 */
const useCYOIncentives = () => {
	return useSelect( ( select ) => {
		const { getCYOIncentives, hasFinishedResolution } = select( STORE_KEY );
		const data = getCYOIncentives();

		return {
			data,
			hasFinishedResolution: hasFinishedResolution( 'getCYOIncentives' ),
		};
	} );
};

export default useCYOIncentives;
