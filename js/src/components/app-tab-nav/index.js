/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const TabLink = ( { tabId, href, children, selected, ...rest } ) => {
	return (
		<Link
			aria-selected={ selected }
			href={ href }
			id={ tabId }
			role="tab"
			tabIndex={ selected ? null : -1 }
			{ ...rest }
		>
			{ children }
		</Link>
	);
};

const AppTabNav = ( props ) => {
	const { selectedKey, tabs } = props;

	return (
		<div className="app-tab-nav">
			<NavigableMenu
				className="app-tab-nav__tabs"
				orientation="horizontal"
				role="tablist"
			>
				{ tabs.map( ( tab ) => (
					<TabLink
						aria-controls={ `${ tab.key }-view` }
						className={ classnames(
							'components-button',
							'app-tab-nav__tabs-item',
							{
								'is-active': tab.key === selectedKey,
							}
						) }
						href={ tab.href }
						key={ tab.key }
						selected={ tab.key === selectedKey }
						tabId={ `${ tab.key }` }
					>
						<span className="app-tab-nav__tabs-item-label">
							{ tab.title }
						</span>
					</TabLink>
				) ) }
			</NavigableMenu>
		</div>
	);
};

export default AppTabNav;
