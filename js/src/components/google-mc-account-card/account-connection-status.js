/**
 * Internal dependencies
 */
import SwitchUrlCard from './switch-url-card';
import ReclaimUrlCard from './reclaim-url-card';
import CreatingCard from './creating-card';

/**
 * Displays different status cards based on account connection results.
 *
 * This component handles the display of:
 * - A switch URL card.
 * - A reclaim URL card.
 * - A creating card when the account is being created.
 *
 * @param {Object} props - The component props.
 * @param {Object} props.resultConnectMC - The result object for connecting the MC account.
 * @param {Object} props.resultCreateAccount - The result object for creating a new account.
 * @param {Function} props.onRetry - The function to call when the user wants to retry account creation.
 */
const AccountConnectionStatus = ( {
	resultConnectMC,
	resultCreateAccount,
	onRetry,
} ) => {
	if ( resultConnectMC.response?.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ resultConnectMC.error.id }
				message={ resultConnectMC.error.message }
				claimedUrl={ resultConnectMC.error.claimed_url }
				newUrl={ resultConnectMC.error.new_url }
				onSelectAnotherAccount={ resultConnectMC.reset }
			/>
		);
	}

	if (
		resultConnectMC.response?.status === 403 ||
		resultCreateAccount.response?.status === 403
	) {
		return (
			<ReclaimUrlCard
				id={
					resultConnectMC.error?.id || resultCreateAccount.error?.id
				}
				websiteUrl={
					resultConnectMC.error?.website_url ||
					resultCreateAccount.error?.website_url
				}
				onSwitchAccount={ () => {
					resultConnectMC.reset();
					resultCreateAccount.reset();
				} }
			/>
		);
	}

	if (
		resultCreateAccount.loading ||
		resultCreateAccount.response?.status === 503
	) {
		return (
			<CreatingCard
				retryAfter={ resultCreateAccount.error?.retry_after }
				onRetry={ onRetry }
			/>
		);
	}

	return null;
};

export default AccountConnectionStatus;
