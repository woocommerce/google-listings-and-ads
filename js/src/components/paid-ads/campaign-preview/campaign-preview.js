/**
 * External dependencies
 */
import { _x } from '@wordpress/i18n';
import { useState, useCallback, useEffect } from '@wordpress/element';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

/**
 * Internal dependencies
 */
import useCountdown from '.~/hooks/useCountdown';
import MockupShopping from './mockup-shopping';
import MockupYouTube from './mockup-youtube';
import MockupSearch from './mockup-search';
import MockupGmail from './mockup-gmail';
import MockupDisplay from './mockup-display';
import MockupMap from './mockup-map';
import productSampleImageURL from './images/product-sample-image.jpg';
import shopSampleLogoURL from './images/shop-sample-logo.png';
import './campaign-preview.scss';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * @type {AdPreviewData}
 */
const fallbackProduct = {
	title: _x(
		'White tee',
		'A sample product title for demonstrating the paid ads shown on Google services.',
		'google-listings-and-ads'
	),
	price: _x(
		'$10.00',
		'A sample product price for demonstrating the paid ads shown on Google services.',
		'google-listings-and-ads'
	),
	shopName: _x(
		`Colleen's Tee Store`,
		'A sample name of an online shop for demonstrating the paid ads shown on Google services.',
		'google-listings-and-ads'
	),
	coverUrl: productSampleImageURL,
	shopLogoUrl: shopSampleLogoURL,
	shopUrl: 'colleensteestore.com',
};

const mockups = [
	MockupShopping,
	MockupYouTube,
	MockupSearch,
	MockupMap,
	MockupDisplay,
	MockupGmail,
];

export default function CampaignPreview() {
	const [ index, setIndex ] = useState( 0 );
	const { second, callCount, startCountdown } = useCountdown();

	// TODO: Fetch required data from API.
	const product = fallbackProduct;

	const moveBy = useCallback( ( step ) => {
		setIndex( ( currentIndex ) => {
			return ( currentIndex + step + mockups.length ) % mockups.length;
		} );
	}, [] );

	useEffect( () => {
		if ( second === 0 ) {
			if ( callCount > 0 ) {
				moveBy( 1 );
			}
			startCountdown( 5 );
		}
	}, [ second, callCount, startCountdown, moveBy ] );

	const Mockup = mockups[ index ];

	return (
		<TransitionGroup className="gla-campaign-preview">
			<CSSTransition
				key={ index }
				classNames="gla-campaign-preview__transition-blur"
				timeout={ 500 }
			>
				<Mockup product={ product } />
			</CSSTransition>
		</TransitionGroup>
	);
}
