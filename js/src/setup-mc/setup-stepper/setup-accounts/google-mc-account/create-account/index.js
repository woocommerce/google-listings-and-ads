/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import CreateAccountCard from './create-account-card';
import CreatingCard from './creating-card';
import ReclaimUrlCard from '../reclaim-url-card';

const CreateAccount = () => {
	const { receiveMCAccount } = useAppDispatch();
	const [
		fetchCreateMCAccount,
		{ loading, error, response },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
	} );

	const handleCreateAccount = async () => {
		try {
			const data = await fetchCreateMCAccount();

			receiveMCAccount( data );
		} catch ( e ) {}
	};

	if ( loading || ( response && response.status === 503 ) ) {
		return (
			<CreatingCard
				retryAfter={ error && error.retry_after }
				onRetry={ handleCreateAccount }
			/>
		);
	}

	if ( response && response.status === 403 ) {
		return <ReclaimUrlCard />;
	}

	return <CreateAccountCard onCreateAccount={ handleCreateAccount } />;
};
export default CreateAccount;
