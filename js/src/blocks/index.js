/**
 * External dependencies
 */
import { registerProductEditorBlockType } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import OnboardingPromptEdit from './product-onboarding-prompt/edit';
import onboardingPromptMetadata from './product-onboarding-prompt/block.json';
import ChannelVisibilityEdit from './product-channel-visibility/edit';
import channelVisibilityMetadata from './product-channel-visibility/block.json';
import DateTimeFieldEdit from './product-date-time-field/edit';
import dateTimeFieldMetadata from './product-date-time-field/block.json';
import SelectFieldEdit from './product-select-field/edit';
import selectFieldMetadata from './product-select-field/block.json';
import SelectWithTextFieldEdit from './product-select-with-text-field/edit';
import selectWithTextFieldMetadata from './product-select-with-text-field/block.json';

function registerProductEditorBlock( { name, ...metadata }, Edit ) {
	registerProductEditorBlockType( {
		name,
		metadata,
		settings: { edit: Edit },
	} );
}

registerProductEditorBlock( onboardingPromptMetadata, OnboardingPromptEdit );
registerProductEditorBlock( channelVisibilityMetadata, ChannelVisibilityEdit );
registerProductEditorBlock( dateTimeFieldMetadata, DateTimeFieldEdit );
registerProductEditorBlock( selectFieldMetadata, SelectFieldEdit );
registerProductEditorBlock(
	selectWithTextFieldMetadata,
	SelectWithTextFieldEdit
);
