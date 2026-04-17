/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';

const selectorName = 'getCYOIncentives';
const PREFERRED_INCENTIVE_TIER = 'medium';

/**
 * @typedef {Object} CYOIncentiveAmount
 * @property {string} currencyCode The ISO 4217 currency code. Example: 'USD'.
 * @property {string} units The amount in the major unit of the currency. Example: '1800'.
 */

/**
 * @typedef {Object} CYOIncentiveSpend
 * @property {CYOIncentiveAmount} awardAmount The ad credit amount awarded upon meeting the spend requirement.
 * @property {CYOIncentiveAmount} requiredAmount The minimum spend amount required to qualify for the incentive.
 */

/**
 * @typedef {Object} CYOIncentiveRequirement
 * @property {CYOIncentiveSpend} spend The spend details including the award and required amounts.
 */

/**
 * @typedef {Object} CYOIncentive
 * @property {string} id The unique identifier for the incentive.
 * @property {string} type The incentive type. Example: 'ACQUISITION'.
 * @property {'high'|'medium'|'low'} offer The offer tier.
 * @property {string} termsAndConditionsUrl URL to the terms and conditions for this incentive.
 * @property {CYOIncentiveRequirement} requirement The spend requirement to qualify for this incentive.
 */

/**
 * @typedef {Object} CYOIncentivesPayload
 * @property {CYOIncentive[]|null} data The list of CYO incentives, or `null` if not yet fetched.
 * @property {boolean} hasFinishedResolution Whether the data fetching has finished.
 * @property {string|null} defaultIncentiveId The ID of the default incentive to pre-select, or `null` if not available.
 */

/**
 * Custom hook to retrieve CYO incentives from the store.
 * The incentives resolver is only triggered when billing is approved;
 * otherwise the hook returns immediately with no data.
 *
 * @return {CYOIncentivesPayload} The CYO incentives payload.
 */
const useCYOIncentives = () => {
	const { billingStatus, hasFinishedResolution: hasResolvedBillingStatus } =
		useGoogleAdsAccountBillingStatus();

	return useSelect(
		( select ) => {
			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			if ( ! isBillingCompleted ) {
				return {
					data: null,
					defaultIncentiveId: null,
					hasFinishedResolution: hasResolvedBillingStatus,
				};
			}

			const selector = select( STORE_KEY );
			const incentives = selector[ selectorName ]();
			const hasResolvedIncentives =
				selector.hasFinishedResolution( selectorName );

			return {
				data: incentives,
				defaultIncentiveId:
					incentives?.find(
						( incentive ) =>
							incentive.offer === PREFERRED_INCENTIVE_TIER
					)?.id ||
					incentives?.[ 0 ]?.id ||
					null,
				hasFinishedResolution: hasResolvedIncentives,
			};
		},
		[ billingStatus, hasResolvedBillingStatus ]
	);
};

export default useCYOIncentives;
