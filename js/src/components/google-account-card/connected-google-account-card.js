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

/**
 * Renders a Google account card UI with connected account information.
 * It also provides a switch button that lets user connect with another Google account.
 *
 * @param {Object} props React props.
 * @param {{ email: string }} props.googleAccount A data payload object contains user's Google account email.
 * @param {JSX.Element} [props.helper] Helper content below the Google account email.
 */
export default function ConnectedGoogleAccountCard( {
	googleAccount,
	helper,
} ) {
	const [ handleSwitch, { loading } ] = useSwitchGoogleAccount();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			helper={ helper }
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
