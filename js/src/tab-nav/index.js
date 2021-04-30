/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import './index.scss';

const tabs = [
	{
		name: 'dashboard',
		title: __( 'Dashboard', 'google-listings-and-ads' ),
		className: 'tab-dashboard',
		path: '%2Fgoogle%2Fdashboard',
	},
	{
		name: 'reports',
		title: __( 'Reports', 'google-listings-and-ads' ),
		className: 'tab-reports',
		path: '%2Fgoogle%2Freports%2Fprograms',
	},
	{
		name: 'product-feed',
		title: __( 'Product Feed', 'google-listings-and-ads' ),
		className: 'tab-product-fee',
		path: '%2Fgoogle%2Fproduct-feed',
	},
	{
		name: 'settings',
		title: __( 'Settings', 'google-listings-and-ads' ),
		className: 'tab-settings',
		path: '%2Fgoogle%2Fsettings',
	},
];

const TabLink = ( { tabId, path, children, selected, ...rest } ) => {
	return (
		<Link
			role="tab"
			tabIndex={ selected ? null : -1 }
			aria-selected={ selected }
			id={ tabId }
			href={ 'admin.php?page=wc-admin&path=' + path }
			{ ...rest }
		>
			{ children }
		</Link>
	);
};

const TabNav = ( props ) => {
	const { initialName } = props;
	const activeClass = 'is-active';
	const { enableReports } = glaData;

	useEffect( () => {
		// Highlight the wp-admin dashboard menu
		const marketingMenu = document.querySelector(
			'#toplevel_page_woocommerce-marketing'
		);
		const dashboardLink = marketingMenu.querySelector(
			"a[href^='admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard']"
		);

		marketingMenu.classList.add( 'current', 'wp-has-current-submenu' );
		if ( dashboardLink ) {
			dashboardLink.parentElement.classList.add( 'current' );
		}
	}, [] );

	return (
		<div className="gla-tab-nav">
			<NavigableMenu
				role="tablist"
				orientation="horizontal"
				className="gla-tab-nav__tabs"
			>
				{ tabs.map( ( tab ) => {
					if ( ! enableReports && tab.name === 'reports' ) {
						return '';
					}
					return (
						<TabLink
							className={ classnames(
								'components-button',
								'gla-tab-nav__tabs-item',
								tab.className,
								{
									[ activeClass ]: tab.name === initialName,
								}
							) }
							tabId={ `${ tab.name }` }
							aria-controls={ `${ tab.name }-view` }
							selected={ tab.name === initialName }
							key={ tab.name }
							path={ tab.path }
						>
							{ tab.title }
						</TabLink>
					);
				} ) }
			</NavigableMenu>
		</div>
	);
};

export default TabNav;
