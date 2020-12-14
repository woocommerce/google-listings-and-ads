/**
 * External dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';

const SettingsCardLayout = ( props ) => {
	const { title, description, children } = props;

	return (
		<section className="settings-card-layout">
			<header>
				<h1>{ title }</h1>
				<p>{ description }</p>
			</header>
			<Card className="settings-card">{ children }</Card>
		</section>
	);
};

export default SettingsCardLayout;
