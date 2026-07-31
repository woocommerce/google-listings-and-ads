/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useUpsertAdsAccount from '~/hooks/useUpsertAdsAccount';
import useShouldCreateAdsAccount from './useShouldCreateAdsAccount';
import { CREATING_ADS_ACCOUNT } from '~/components/google-combo-account-card/constants';

/**
 * @typedef {Object} AutoCreateAdsAccountsData
 * @property {boolean} hasDetermined - Whether the checks to determine if accounts should be created are finished.
 * @property {('ads'|null)} creatingWhich - Which accounts are being created 'ads' or `null` if none.
 */

/**
 * useAutoCreateAdsAccount hook.
 * Creates Google Ads account if the user doesn't have any existing and connected account.
 * @return {AutoCreateAdsAccountsData} Object containing account creation data.
 */
const useAutoCreateAdsAccount = () => {
	const lockedRef = useRef( false );
	const shouldCreateAds = useShouldCreateAdsAccount();
	const [ creatingWhich, setCreatingWhich ] = useState( null );
	const [ hasDetermined, setDetermined ] = useState( false );
	const [ upsertAdsAccount ] = useUpsertAdsAccount();

	useEffect( () => {
		if (
			// Wait for all determinations to be ready
			shouldCreateAds === null ||
			// Avoid repeated calls
			lockedRef.current
		) {
			return;
		}

		let which = null;

		lockedRef.current = true;

		if ( shouldCreateAds ) {
			which = CREATING_ADS_ACCOUNT;
		}

		setCreatingWhich( which );
		setDetermined( true );

		if ( which ) {
			const handleCreateAccountCallback = async () => {
				await upsertAdsAccount();
				setCreatingWhich( null );
			};

			handleCreateAccountCallback();
		}
	}, [ shouldCreateAds, upsertAdsAccount ] );

	return {
		hasDetermined,
		creatingWhich,
	};
};

export default useAutoCreateAdsAccount;
