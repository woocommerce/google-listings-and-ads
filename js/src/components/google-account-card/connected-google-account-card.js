/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import Section from '.~/wcdl/section';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const [
		fetchGoogleDisconnect,
		{ loading: loadingGoogleDisconnect },
	] = useApiFetchCallback( {
		method: 'DELETE',
		path: `${ API_NAMESPACE }/google/connect`,
	} );
	const [
		fetchGoogleConnect,
		{ loading: loadingGoogleConnect, data: dataGoogleConnect },
	] = useGoogleAuthorization( pageName );

	const handleSwitch = async () => {
		let notice;

		try {
			const result = await createNotice(
				'info',
				__(
					'Disconnecting your current Google account, please wait…',
					'google-listings-and-ads'
				)
			);
			notice = result.notice;

			await fetchGoogleDisconnect();

			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			removeNotice( notice.id );
			createNotice(
				'error',
				__(
					'Unable to connect to a different Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			indicator={ <ConnectedIconLabel /> }
		>
			<Section.Card.Footer>
				<AppButton
					isLink
					disabled={
						loadingGoogleDisconnect ||
						loadingGoogleConnect ||
						dataGoogleConnect
					}
					text={ __(
						'Or, connect to a different Google account',
						'google-listings-and-ads'
					) }
					onClick={ handleSwitch }
				/>
			</Section.Card.Footer>
		</AccountCard>
	);
}
