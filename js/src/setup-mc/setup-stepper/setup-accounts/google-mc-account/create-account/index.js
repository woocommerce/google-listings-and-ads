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
import CreatingCard from '../creating-card';
import ReclaimUrlCard from '../reclaim-url-card';
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
			await fetchCreateMCAccount( {
				data: error?.id && { id: error.id },
				parse: false,
			} );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( ! [ 403, 503 ].includes( e.status ) ) {
				const body = await e.json();
				const message =
					body.message ||
					__(
						'Unable to create Merchant Center account. Please try again later.',
						'google-listings-and-ads'
					);
				createNotice( 'error', message );
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
