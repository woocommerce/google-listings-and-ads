/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Simple top bar with back button and title,
 * to be used when configuring a campaign during oboarding and later.
 *
 * @param {Object} props
 * @param {Function} props.onBackButtonClick
 * @param {Function} props.children Additional elements to be put in the top bar.
 * @param {string} props.backHref Href for the back button.
 */
const TopBar = ( { onBackButtonClick, children, backHref } ) => {
	return (
		<div className="gla-setup-mc-top-bar">
			<Link
				className="back-button"
				href={ backHref }
				type="wc-admin"
				onClick={ onBackButtonClick }
			>
				<GridiconChevronLeft />
			</Link>
			<span className="title">
				{ __(
					'Get started with Google Listings & Ads',
					'google-listings-and-ads'
				) }
			</span>
			{ children }
		</div>
	);
};

export default TopBar;
