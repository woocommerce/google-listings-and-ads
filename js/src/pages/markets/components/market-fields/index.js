/**
 * External dependencies
 */
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import AudienceSection from './audience-section';
import LocaleSection from './locale-section';
import ShippingSection from './shipping-section';
import './index.scss';

/**
 * Renders all market form fields for both the Add and Edit Market modals.
 * Returns null when `shipping_rate` is `manual` and the store is not multilingual,
 * unless the form is in edit mode (where audience and locale controls still apply).
 */
const MarketFields = () => {
	const { settings } = useSettings();
	const {
		adapter: { isEditing },
	} = useAdaptiveFormContext();

	if (
		settings?.shipping_rate === SHIPPING_RATE_METHOD.MANUAL &&
		! glaData.isMultiLingualStore &&
		! isEditing
	) {
		return null;
	}

	return (
		<Flex direction="column" gap={ 6 } className="gla-market-fields">
			<AudienceSection />
			<LocaleSection />
			<ShippingSection />
		</Flex>
	);
};

export default MarketFields;
