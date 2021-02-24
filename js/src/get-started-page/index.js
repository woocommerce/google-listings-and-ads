/**
 * Internal dependencies
 */
import './index.scss';
import Faqs from './faqs';
import GetStartedCard from './get-started-card';
import FeaturesCard from './features-card';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<GetStartedCard />
			<FeaturesCard />
			<Faqs></Faqs>
		</div>
	);
};

export default GetStartedPage;
