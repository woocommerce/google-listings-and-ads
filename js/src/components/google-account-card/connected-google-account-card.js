/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import useGoogleConnectFlow from './use-google-connect-flow';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	const [ handleConnect, { loading, data } ] = useGoogleConnectFlow();

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
