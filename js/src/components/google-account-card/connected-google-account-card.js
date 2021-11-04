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
import useSwitchGoogleAccount from './useSwitchGoogleAccount';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	const [ handleSwitch, { loading } ] = useSwitchGoogleAccount();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			indicator={ <ConnectedIconLabel /> }
		>
			<Section.Card.Footer>
				<AppButton
					isLink
					disabled={ loading }
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
