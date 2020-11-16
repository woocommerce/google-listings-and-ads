/**
 * Internal dependencies
 */
import './index.scss';
import Faqs from './faqs';
import GetStartedCard from './get-started-card';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<GetStartedCard />
			<Faqs></Faqs>
		</div>
	);
};

export default GetStartedPage;
