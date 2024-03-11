/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const DisabledCard = () => {
	return (
		<AccountCard
			disabled
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
		/>
	);
};

export default DisabledCard;
