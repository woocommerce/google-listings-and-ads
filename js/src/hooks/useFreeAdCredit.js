/**
 * External dependencies
 */
import { getDateDifferenceInDays } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

/**
 * Returns a Boolean value indicating whether the user is eligible for free ad credit.
 *
 * This will retrieve the user's Google Ads account.
 *
 * To be eligible for the free ad credit, the account must be a sub-account
 * that is newly created within the last 60 days.
 * If users disconnected the account and reconnect again,
 * it will not be seen as a newly created sub-account
 * and hence won't be eligible for the free ad credit.
 *
 * @return {boolean} Value indicating whether the user is eligible for free ad credit.
 */
const useFreeAdCredit = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		googleAdsAccount &&
		googleAdsAccount.sub_account &&
		getDateDifferenceInDays(
			new Date(),
			new Date( googleAdsAccount.created_timestamp * 1000 )
		) < 60
	);
};

export default useFreeAdCredit;
