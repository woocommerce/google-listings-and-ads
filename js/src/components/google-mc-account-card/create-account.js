/**
 * Internal dependencies
 */
import CreateAccountCard from './create-account-card';
import CreatingCard from './creating-card';
import ReclaimUrlCard from './reclaim-url-card';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';

/**
 * Create Account flow.
 *
 * During the account creation process, this will display the `CreatingCard`,
 * and if there is a reclaim URL error, it will display the `ReclaimUrlCard`.
 *
 * @param {Object} props Props
 * @param {Function} props.onSwitchAccount
 * Called when users click on "Switch account" button in the ReclaimUrlCard,
 * when there is a reclaim URL error during account creation process.
 */
const CreateAccount = ( props ) => {
	const { onSwitchAccount } = props;
	const [ handleCreateAccount, { loading, error, response } ] =
		useCreateMCAccount();

	if ( loading || ( response && response.status === 503 ) ) {
		return (
			<CreatingCard
				retryAfter={ error && error.retry_after }
				onRetry={ handleCreateAccount }
			/>
		);
	}

	if ( response && response.status === 403 ) {
		return (
			<ReclaimUrlCard
				id={ error.id }
				websiteUrl={ error.website_url }
				onSwitchAccount={ onSwitchAccount }
			/>
		);
	}

	return <CreateAccountCard onCreateAccount={ handleCreateAccount } />;
};
export default CreateAccount;
