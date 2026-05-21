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

/**
 * Renders language and currency select controls,
 * as well as a notice about multilingual support if the store is not multilingual.
 */
const LocaleSection = () => {
	const { settings } = useSettings();
	const {
		adapter: { isPrimaryMarket },
	} = useAdaptiveFormContext();

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
			className="gla-market-fields__locale-controls"
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
