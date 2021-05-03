/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import CreateAccountCard from './create-account-card';
import CreatingCard from './creating-card';
import BetaReclaimUrlCard from '../beta-reclaim-url-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchCreateMCAccount,
		{ loading, error, response },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
	} );

	const handleCreateAccount = async () => {
		try {
			await fetchCreateMCAccount( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( e.status === 406 ) {
				const body = await e.json();
				createNotice( 'error', body.message );
			} else if ( ! [ 403, 503 ].includes( e.status ) ) {
				createNotice(
					'error',
					__(
						'Unable to create Merchant Center account. Please try again later.',
						'google-listings-and-ads'
					)
				);
			}
		}
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
		// TODO: Use the BetaReclaimUrlCard for beta testing purpose only.
		// To switch back to ReclaimUrlCard for production roll out.
		// return <ReclaimUrlCard websiteUrl={ error.website_url } />;
		return <BetaReclaimUrlCard websiteUrl={ error.website_url } />;
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
