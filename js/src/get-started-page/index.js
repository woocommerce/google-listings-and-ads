/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';
import useSyncableProductsCalculation from '.~/hooks/useSyncableProductsCalculation';
import BenefitsCard from './benefits-card';
import CustomerQuotesCard from './customer-quotes-card';
import Faqs from './faqs';
import FeaturesCard from './features-card';
import GetStartedCard from './get-started-card';
import GetStartedWithVideoCard from './get-started-with-video-card';
import UnsupportedNotices from './unsupported-notices';

const GetStartedPage = () => {
	const { request } = useSyncableProductsCalculation();

	// Trigger the calculation here for later use during the onboarding flow.
	useEffect( () => {
		request();
	}, [ request ] );

	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<UnsupportedNotices />
			<GetStartedWithVideoCard />
			<BenefitsCard />
			<FeaturesCard />
			<CustomerQuotesCard />
			<GetStartedCard />
			<Faqs />
		</div>
	);
};

export default GetStartedPage;
