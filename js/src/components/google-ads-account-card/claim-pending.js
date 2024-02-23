/**
 * Internal dependencies
 */
import useAutoCheckAdsAccountStatus from '.~/hooks/useAutoCheckAdsAccountStatus';

const ClaimPending = () => {
	useAutoCheckAdsAccountStatus();

	return null;
};

export default ClaimPending;
