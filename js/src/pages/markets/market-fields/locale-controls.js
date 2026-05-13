/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import LanguageSelectControl from './language-select-control';
import CurrencySelectControl from './currency-select-control';
import './locale-controls.scss';

/**
 * Renders language and currency select controls,
 * as well as a notice about multilingual support if the store is not multilingual.
 */
const LocaleControls = () => {
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

export default LocaleControls;
