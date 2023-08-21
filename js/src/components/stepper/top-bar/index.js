/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
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
 * @param {string} props.title Title to indicate where the user is at.
 * @param {import(".~/components/app-button").default} props.helpButton Help button
 * @param {string} props.backHref Href for the back button.
 * @param {Function} props.onBackButtonClick
 */
const TopBar = ( { title, backHref, helpButton, onBackButtonClick } ) => {
	return (
		<div className="gla-stepper-top-bar">
			<Link
				className="components-button gla-stepper-top-bar__back-button"
				href={ backHref }
				type="wc-admin"
				onClick={ onBackButtonClick }
			>
				<GridiconChevronLeft />
			</Link>
			<span className="gla-stepper-top-bar__title">{ title }</span>
			{ helpButton }
		</div>
	);
};

export default TopBar;
