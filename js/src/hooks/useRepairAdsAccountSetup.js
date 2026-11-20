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
 * Step that indicates a corrupted account creation state when reported for a
 * connected account with access granted. `set_id` is the step that creates the
 * account, so it cannot legitimately be incomplete once the account ID exists.
 * It is left pending when a concurrent request writes a stale copy of the
 * account creation state back.
 *
 * `conversion_action` is not included because the connected account cards
 * already resume setup at that step.
 */
const REPAIRABLE_STEP = 'set_id';

/**
 * Hook to repair a Google Ads account whose recorded creation state is
 * inconsistent with the account itself.
 *
 * When the account is connected and has access but the reported incomplete
 * step is `set_id`, this hook re-issues the account setup request once. The
 * backend marks `set_id` done for the already linked account and resumes setup
 * from there, so the recorded state converges back to reality.
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
			// Repair at most once per mount.
			lockedRef.current ||
			! adsAccountResolved ||
			! adsAccountStatusResolved ||
			! hasGoogleAdsConnection ||
			hasAccess !== true ||
			step !== REPAIRABLE_STEP
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
