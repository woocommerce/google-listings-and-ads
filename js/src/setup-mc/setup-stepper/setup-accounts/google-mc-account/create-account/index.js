/**
 * Internal dependencies
 */
import CreateAccountCard from './create-account-card';
import CreatingCard from '../creating-card';
import ReclaimUrlCard from '../reclaim-url-card';
import useCreateMCAccount from '../useCreateMCAccount';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [
		handleCreateAccount,
		{ loading, error, response },
	] = useCreateMCAccount();

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
				onSwitchAccount={ onShowExisting }
			/>
		);
	}

	return (
		<CreateAccountCard
			allowShowExisting={ allowShowExisting }
			onShowExisting={ onShowExisting }
			onCreateAccount={ handleCreateAccount }
		/>
	);
};
export default CreateAccount;
