/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '.~/components/app-icon-button';
import './index.scss';

/**
 * Simple top bar with back button and title,
 * to be used when configuring a campaign during oboarding and later.
 *
 * @param {Object} props
 * @param {string} props.title Title to indicate where the user is at.
 * @param {string} props.backHref Href for the back button.
 * @param {Function} props.onBackButtonClick
 * @param {Function} props.onHelpButtonClick
 */
const TopBar = ( {
	title,
	backHref,
	onBackButtonClick,
	onHelpButtonClick,
} ) => {
	return (
		<div className="gla-stepper-top-bar">
			<Link
				className="back-button"
				href={ backHref }
				type="wc-admin"
				onClick={ onBackButtonClick }
			>
				<GridiconChevronLeft />
			</Link>
			<span className="title">{ title }</span>
			<div className="actions">
				{ /* TODO: click and navigate to where? */ }
				<AppIconButton
					icon={ <GridiconHelpOutline /> }
					text={ __( 'Help', 'google-listings-and-ads' ) }
					onClick={ onHelpButtonClick }
				/>
			</div>
		</div>
	);
};

export default TopBar;
