/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import Text from '~/components/app-text';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import wpmlLogoURL from '~/images/logo/wpml-logo.png';
import './index.scss';

/**
 * @typedef {Object} Plugin
 * @property {string} id Plugin ID.
 * @property {string} title Plugin title.
 * @property {string} description Plugin description.
 * @property {string} link Plugin link.
 * @property {string} icon Plugin icon.
 */
const PLUGINS = [
	{
		id: 'wpml',
		title: 'WPML',
		description: __(
			'WooCommerce integration that handles multi-currency natively.',
			'google-listings-and-ads'
		),
		link: 'https://wpml.org/',
		icon: wpmlLogoURL,
	},
];

/**
 * Prompts the merchant to install a multilingual plugin.
 *
 * Renders null when a multilingual plugin is already active
 * (`glaData.isMultiLingualStore`) or when the shipping rate method
 * is not manual.
 */
const MultiLingualPluginPrompt = () => {
	const { settings } = useSettings();

	if (
		glaData.isMultiLingualStore ||
		settings?.shipping_rate !== SHIPPING_RATE_METHOD.MANUAL ||
		PLUGINS.length <= 0
	) {
		return null;
	}

	return (
		<div className="gla-multilingual-plugin-prompt">
			{ PLUGINS.map( ( plugin ) => (
				<Card
					className="gla-multilingual-plugin-prompt__plugin"
					key={ plugin.id }
					size="small"
				>
					<CardBody className="gla-multilingual-plugin-prompt__plugin-content">
						<img
							alt={ plugin.title }
							className="gla-multilingual-plugin-prompt__plugin-icon"
							height="32"
							src={ plugin.icon }
							width="32"
						/>
						<div className="gla-multilingual-plugin-prompt__plugin-info">
							<Text
								className="gla-multilingual-plugin-prompt__plugin-title"
								variant="subtitle-small"
							>
								{ plugin.title }
							</Text>
							<Text
								className="gla-multilingual-plugin-prompt__plugin-description"
								variant="body"
							>
								{ plugin.description }
							</Text>
						</div>
						<AppButton
							className="gla-multilingual-plugin-prompt__plugin-button"
							href={ plugin.link }
							rel="noreferrer"
							target="_blank"
							variant="secondary"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppButton>
					</CardBody>
				</Card>
			) ) }
		</div>
	);
};

export default MultiLingualPluginPrompt;
