/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const DisabledCard = () => {
	return <AccountCard disabled appearance={ APPEARANCE.GOOGLE_ADS } />;
};

export default DisabledCard;
