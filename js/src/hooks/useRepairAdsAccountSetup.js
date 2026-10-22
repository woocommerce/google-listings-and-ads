/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleAdsAccountStatus from './useGoogleAdsAccountStatus';
import useUpsertAdsAccount from './useUpsertAdsAccount';

/**
 * Steps that indicate a corrupted account creation state when reported for a
 * connected account with access granted:
 *
 * - `set_id` cannot legitimately be incomplete once the account ID exists,
 *   because it is the step that creates the account.
 * - `conversion_action` has no endpoint that re-completes it, so once reverted
 *   it stays incomplete forever.
 *
 * Both occur when a concurrent request writes a stale copy of the account
 * creation state back, reverting completed steps to pending.
 */
const REPAIRABLE_STEPS = [ 'set_id', 'conversion_action' ];

/**
 * Hook to repair a Google Ads account whose recorded creation state is
 * inconsistent with the account itself.
 *
 * When the account is connected and has access but the reported incomplete
 * step is one that cannot legitimately be pending, this hook re-issues the
 * account setup request once. The backend setup resumes at the recorded step
 * and re-completes each one, marking `set_id` done because the account ID
 * already exists, so the recorded state converges back to reality.
 */
const useRepairAdsAccountSetup = () => {
	const lockedRef = useRef( false );
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: adsAccountResolved,
	} = useGoogleAdsAccount();
	const {
		hasAccess,
		step,
		hasFinishedResolution: adsAccountStatusResolved,
	} = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount ] = useUpsertAdsAccount();

	useEffect( () => {
		if (
			// Repair at most once per page load.
			lockedRef.current ||
			! adsAccountResolved ||
			! adsAccountStatusResolved ||
			! hasGoogleAdsConnection ||
			hasAccess !== true ||
			! REPAIRABLE_STEPS.includes( step )
		) {
			return;
		}

		lockedRef.current = true;
		upsertAdsAccount();
	}, [
		adsAccountResolved,
		adsAccountStatusResolved,
		hasGoogleAdsConnection,
		hasAccess,
		step,
		upsertAdsAccount,
	] );
};

export default useRepairAdsAccountSetup;
