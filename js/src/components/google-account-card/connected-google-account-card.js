/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import SwitchAccountButton from './switch-account-button';

/**
 * Renders a Google account card UI with connected account information.
 * It also provides a switch button that lets user connect with another Google account.
 *
 * @param {Object} props React props.
 * @param {{ email: string }} props.googleAccount A data payload object containing the user's Google account email.
 * @param {JSX.Element} [props.helper] Helper content below the Google account email.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 */
const ConnectedGoogleAccountCard = ( {
	googleAccount,
	helper,
	hideAccountSwitch = false,
} ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			helper={ helper }
			indicator={ <ConnectedIconLabel /> }
		>
			{ ! hideAccountSwitch && (
				<Section.Card.Footer>
					<SwitchAccountButton />
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default ConnectedGoogleAccountCard;
