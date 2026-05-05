/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';

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
		settings?.shipping_rate !== SHIPPING_RATE_METHOD.MANUAL
	) {
		return null;
	}

	return (
		<Flex
			className="gla-multilingual-plugin-prompt"
			direction="column"
			gap={ 4 }
		>
			{ PLUGINS.map( ( { id, title, description, link, icon } ) => (
				<Card
					className="gla-multilingual-plugin-prompt__plugin"
					size="small"
					key={ id }
				>
					<CardBody>
						<Flex
							direction="column"
							className="gla-multilingual-plugin-prompt__plugin-content"
							gap={ 4 }
						>
							<FlexBlock size={ 1 }>
								<Flex align="center" justify="start" gap={ 4 }>
									<FlexItem className="gla-multilingual-plugin-prompt__plugin-icon">
										<img
											alt={ title }
											height="32"
											src={ icon }
											width="32"
										/>
									</FlexItem>
									<FlexItem>
										<Text
											className="gla-multilingual-plugin-prompt__plugin-title"
											variant="subtitle-small"
										>
											{ title }
										</Text>
										<Text
											className="gla-multilingual-plugin-prompt__plugin-description"
											variant="body"
										>
											{ description }
										</Text>
									</FlexItem>
								</Flex>
							</FlexBlock>
							<FlexItem>
								<AppButton
									className="gla-multilingual-plugin-prompt__plugin-button"
									href={ link }
									rel="noreferrer"
									target="_blank"
									variant="secondary"
								>
									{ __(
										'Learn more',
										'google-listings-and-ads'
									) }
								</AppButton>
							</FlexItem>
						</Flex>
					</CardBody>
				</Card>
			) ) }
		</Flex>
	);
};

export default MultiLingualPluginPrompt;
