/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { STORE_KEY, API_NAMESPACE } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

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
 * @property {Function} applyIncentive Async function that posts an incentive ID to the API. Returns `true` on success or skip, `false` on error.
 */

/**
 * Custom hook to retrieve CYO incentives from the store.
 * The incentives resolver is only triggered when billing is approved;
 * otherwise the hook returns immediately with no data.
 *
 * @return {CYOIncentivesPayload} The CYO incentives payload.
 */
const useCYOIncentives = () => {
	const { createNotice } = useDispatchCoreNotices();

	const { data, hasFinishedResolution } = useSelect( ( select ) => {
		const {
			getGoogleAdsAccountBillingStatus,
			getCYOIncentives,
			hasFinishedResolution: hasFinished,
		} = select( STORE_KEY );

		const billingStatus = getGoogleAdsAccountBillingStatus();
		const isBillingCompleted =
			billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

		if ( ! isBillingCompleted ) {
			return {
				data: null,
				hasFinishedResolution: true,
			};
		}

		const incentivesData = getCYOIncentives();

		return {
			data: incentivesData,
			hasFinishedResolution: hasFinished( 'getCYOIncentives' ),
		};
	}, [] );

	const defaultIncentiveId =
		hasFinishedResolution && data?.length > 0
			? data.find( ( incentive ) => incentive.offer === 'medium' )?.id ||
			  data[ 0 ].id
			: null;

	const applyIncentive = useCallback(
		async ( incentiveId ) => {
			if ( ! incentiveId ) {
				return true;
			}

			try {
				await apiFetch( {
					path: `${ API_NAMESPACE }/ads/incentive`,
					method: 'POST',
					data: { id: incentiveId },
				} );
				return true;
			} catch ( error ) {
				createNotice(
					'error',
					__(
						'Unable to apply the selected ads credit offer.',
						'google-listings-and-ads'
					)
				);
				return false;
			}
		},
		[ createNotice ]
	);

	return { data, hasFinishedResolution, defaultIncentiveId, applyIncentive };
};

export default useCYOIncentives;
