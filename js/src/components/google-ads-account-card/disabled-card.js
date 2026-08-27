/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';

const DisabledCard = () => {
	return <AccountCard appearance={ APPEARANCE.GOOGLE_ADS } disabled />;
};

export default DisabledCard;
