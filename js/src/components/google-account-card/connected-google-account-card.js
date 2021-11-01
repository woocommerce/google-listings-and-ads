/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import Section from '.~/wcdl/section';
import AppButton from '../app-button';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useGoogleAuthorization(
		pageName
	);

	const handleConnect = async () => {
		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
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
					disabled={ loading || data }
					text={ __(
						'Or, connect to a different Google account',
						'google-listings-and-ads'
					) }
					onClick={ handleConnect }
				/>
			</Section.Card.Footer>
		</AccountCard>
	);
}
