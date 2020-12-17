/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '../../components/app-icon-button';
import './index.scss';

const TopBar = () => {
	return (
		<div className="gla-setup-mc-top-bar">
			<Link
				className="back-button"
				href={ getNewPath( {}, '/google/dashboard' ) }
				type="wc-admin"
			>
				<GridiconChevronLeft />
			</Link>
			<span className="title">
				{ __(
					'Get started with Google Listings & Ads',
					'google-listings-and-ads'
				) }
			</span>
			<AppIconButton
				className="help-button"
				icon={ <GridiconHelpOutline /> }
				text={ __( 'Help', 'google-listings-and-ads' ) }
			/>
		</div>
	);
};

export default TopBar;
