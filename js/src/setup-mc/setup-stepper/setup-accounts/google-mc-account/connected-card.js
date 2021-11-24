/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [
		fetchGoogleMCDisconnect,
		{ loading: loadingGoogleMCDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/mc/connection`,
		method: 'DELETE',
	} );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Disconnecting your Google Merchant Center account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchGoogleMCDisconnect();
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to disconnect your Google Merchant Center account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}

		removeNotice( notice.id );
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ toAccountText( googleMCAccount.id ) }
			indicator={ <ConnectedIconLabel /> }
		>
			<Section.Card.Footer>
				<AppButton
					isLink
					disabled={ loadingGoogleMCDisconnect }
					text={ __(
						'Or, connect to a different Google Merchant Center account',
						'google-listings-and-ads'
					) }
					onClick={ handleSwitch }
				/>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectedCard;
