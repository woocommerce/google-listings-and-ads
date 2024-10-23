/**
 * Internal dependencies
 */
import useGoogleMCAccount from './useGoogleMCAccount';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

const useGoogleMCAccountReady = () => {
	const { googleMCAccount } = useGoogleMCAccount();

	return (
		googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
		( googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE &&
			googleMCAccount?.step === 'link_ads' )
	);
};

export default useGoogleMCAccountReady;
