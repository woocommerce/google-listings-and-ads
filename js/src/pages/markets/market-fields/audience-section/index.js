/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import MarketSelectControl from './market-select-control';
import AudienceSelectControl from './audience-select-control';

const AudienceSection = () => {
	const { adapter } = useAdaptiveFormContext();
	const { isEditing, isPrimaryMarket } = adapter;

	// Non-primary markets can't change which market they're editing — hide the selector.
	if ( isEditing && ! isPrimaryMarket ) {
		return null;
	}

	if ( isPrimaryMarket ) {
		return <AudienceSelectControl />;
	}

	return (
		<MarketSelectControl
			autoSelectFirstOption={ false }
			placeholderOption={ __( 'Select…', 'google-listings-and-ads' ) }
		/>
	);
};

export default AudienceSection;
