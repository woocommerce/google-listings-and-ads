/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import { STORE_KEY } from '.~/data/constants';

/**
 * @typedef {Object} StoreAddressSyncedData
 * @property {string[]} missingRequiredFields The missing required fields of the store address.
 * @property {boolean|null} addressSynced Returns `true` if the store address matches the GMC account address, otherwise, returns `false`. If the MC account is not connected or if the state is not yet determined, returns `null`.
 */

/**
 * Checks if the store address is synchronized with the Merchant Center (GMC) account address.
 *
 * @return {StoreAddressSyncedData} The store address synced data.
 */
export default function useStoreAddressSynced() {
	const { isReady } = useGoogleMCAccount();

	return useSelect(
		( select ) => {
			if ( ! isReady ) {
				return {
					missingRequiredFields: [],
					addressSynced: null,
				};
			}

			const { getGoogleMCContactInformation } = select( STORE_KEY );
			const contact = getGoogleMCContactInformation();

			if ( ! contact ) {
				return {
					missingRequiredFields: [],
					addressSynced: null,
				};
			}

			const {
				is_mc_address_different: isMCAddressDifferent,
				wc_address_errors: missingRequiredFields,
			} = contact;

			return {
				missingRequiredFields,
				addressSynced:
					! Boolean( isMCAddressDifferent ) &&
					! missingRequiredFields.length,
			};
		},
		[ isReady ]
	);
}
