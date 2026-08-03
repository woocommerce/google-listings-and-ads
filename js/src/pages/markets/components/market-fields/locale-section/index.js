/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import LanguageSelectControl from './language-select-control';
import CurrencySelectControl from './currency-select-control';
import './index.scss';

/**
 * Renders language and currency select controls,
 * as well as a notice about multilingual support if the store is not multilingual.
 */
const LocaleSection = () => {
	const { settings } = useSettings();
	const {
		adapter: { isPrimaryMarket },
	} = useAdaptiveFormContext();

	// Flat shipping is incompatible with multilingual feeds (the Markets
	// dashboard shows a warning for that combination), so the language and
	// currency controls are never offered for it; the backend fills in the
	// site language and store currency for markets saved without them.
	if (
		settings?.shipping_rate === SHIPPING_RATE_METHOD.FLAT ||
		( isPrimaryMarket && ! glaData.isMultiLingualStore )
	) {
		return null;
	}

	return (
		<Flex
			direction="column"
			gap={ 6 }
			className="gla-market-fields__locale-section"
		>
			{ ! glaData.isMultiLingualStore && (
				<Notice isDismissible={ false }>
					{ __(
						'Want to sell in multiple languages? Install a compatible multilingual plugin to add language and currency support to your markets.',
						'google-listings-and-ads'
					) }
				</Notice>
			) }

			<LanguageSelectControl />
			<CurrencySelectControl />
		</Flex>
	);
};

export default LocaleSection;
