/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import SwitchAccountButton from './switch-account-button';

/**
 * Renders a Google account card UI with connected account information.
 * It also provides a switch button that lets user connect with another Google account.
 *
 * @param {Object} props React props.
 * @param {{ email: string }} props.googleAccount A data payload object containing the user's Google account email.
 * @param {JSX.Element} [props.helper] Helper content below the Google account email.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 * @param {Object} props.restProps Props to be forwarded to AccountCard.
 */
const ConnectedGoogleAccountCard = ( {
	googleAccount,
	helper,
	hideAccountSwitch = false,
	...restProps
} ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			helper={ helper }
			indicator={ <ConnectedIconLabel /> }
			actions={ ! hideAccountSwitch && <SwitchAccountButton /> }
			{ ...restProps }
		/>
	);
};

export default ConnectedGoogleAccountCard;
