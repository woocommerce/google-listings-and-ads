/**
 * Internal dependencies
 */
import './index.scss';
import Faqs from './faqs';
import GetStartedCard from './get-started-card';
import exp from '../export.scss';

const GetStartedPage = () => {
	console.log( 'exp: ', exp );

	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<GetStartedCard />
			<Faqs></Faqs>
			<div
				style={ {
					color: exp.alertyellow,
					backgroundColor: exp.black,
					width: exp.gridunit60,
				} }
			>
				hello
			</div>
		</div>
	);
};

export default GetStartedPage;
