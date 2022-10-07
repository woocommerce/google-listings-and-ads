/**
 * External dependencies
 */
import { _x } from '@wordpress/i18n';
import {
	useState,
	useCallback,
	useEffect,
	useImperativeHandle,
	forwardRef,
} from '@wordpress/element';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import CurrencyFactory from '@woocommerce/currency';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import useCountdown from '.~/hooks/useCountdown';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import AppSpinner from '.~/components/app-spinner';
import MockupShopping from './mockup-shopping';
import MockupYouTube from './mockup-youtube';
import MockupSearch from './mockup-search';
import MockupGmail from './mockup-gmail';
import MockupDisplay from './mockup-display';
import MockupMap from './mockup-map';
import productSampleImageURL from './images/product-sample-image.jpg';
import shopSampleLogoURL from './images/shop-sample-logo.png';
import { glaData } from '.~/constants';
import './campaign-preview.scss';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 *
 * @typedef {Object} CampaignPreviewHandler
 * @property {(step: number) => void} moveBy Move the currently displayed preview ad by how many steps, e.g. 1 for moving to the next and -1 for the previous.
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

const bestsellingQuery = {
	page: 1,
	per_page: 1,
	orderby: 'total_sales',
	order: 'desc',
};

const mockups = [
	MockupShopping,
	MockupYouTube,
	MockupSearch,
	MockupMap,
	MockupDisplay,
	MockupGmail,
];

function resolvePreviewProduct( products = [] ) {
	const currencyFactory = CurrencyFactory( getSetting( 'currency' ) );
	const [ product = {} ] = products;
	const { title, price, image_url: coverUrl } = product;
	const previewProduct = {
		title,
		coverUrl,
		price: currencyFactory.formatAmount( price ),
		shopName: getSetting( 'siteTitle' ),
		shopUrl: new URL( getSetting( 'homeUrl' ) ).host,
		shopLogoUrl: glaData.siteLogoUrl,
	};

	Object.entries( previewProduct ).forEach( ( [ key, val ] ) => {
		// All possible values won't be number 0, so here simply use falsy condition to do fallback.
		if ( ! val ) {
			previewProduct[ key ] = fallbackProduct[ key ];
		}
	} );

	return previewProduct;
}

/**
 * Renders a set of mockups to preview the paid ads shown on Google services.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.autoplay=true] Whether enable the autoplay.
 * @param {import('react').MutableRefObject<CampaignPreviewHandler>} ref React ref to be attached to the handler of this component.
 */
function CampaignPreview( { autoplay = true }, ref ) {
	const [ index, setIndex ] = useState( 0 );
	const { second, callCount, startCountdown } = useCountdown();
	const { hasFinishedResolution, data } = useAppSelectDispatch(
		'getMCProductFeed',
		bestsellingQuery
	);

	const shouldAutoplay = autoplay && hasFinishedResolution;

	const moveBy = useCallback( ( step ) => {
		setIndex( ( currentIndex ) => {
			return ( currentIndex + step + mockups.length ) % mockups.length;
		} );
	}, [] );

	useEffect( () => {
		if ( shouldAutoplay && second === 0 ) {
			if ( callCount > 0 ) {
				moveBy( 1 );
			}
			startCountdown( 5 );
		}
	}, [ shouldAutoplay, second, callCount, startCountdown, moveBy ] );

	useImperativeHandle( ref, () => ( {
		moveBy,
	} ) );

	if ( ! hasFinishedResolution ) {
		return (
			<div className="gla-ads-mockup">
				<AppSpinner />
			</div>
		);
	}

	const Mockup = mockups[ index ];
	const product = resolvePreviewProduct( data?.products );

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

export default forwardRef( CampaignPreview );
