/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { Fragment, useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import Section from '~/components/section';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEnableEnhancedConversions from './useEnableEnhancedConversions';

/**
 * Renders the settings section for Enhanced Conversions setup.
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-enhanced-conversions', link_id: 'enhanced-conversions-read-more', href: 'https://support.google.com/google-ads/answer/9888656' }`
 */
const ActiveEnhancedConversionCard = () => {
	const { isEnabled, hasFinishedResolution } = useEnableEnhancedConversions();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateEnhancedConversionsStatus } = useAppDispatch();

	const toggleEnhancedConversions = useCallback( async () => {
		await updateEnhancedConversionsStatus( ! isEnabled );
	}, [ updateEnhancedConversionsStatus, isEnabled ] );

	const handleOnChange = async () => {
		try {
			setIsSaving( true );
			await toggleEnhancedConversions();

			createNotice(
				'success',
				__(
					'Enhanced Conversions status updated successfully.',
					'google-listings-and-ads'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateEnhancedConversionsStatus` action.
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! hasFinishedResolution ) {
		return null;
	}

	return (
		<Fragment>
			{ hasFinishedResolution && (
				<Section.Card>
					<Section.Card.Body>
						<CheckboxControl
							label={ __(
								'Send Enhanced Conversions data to Google Ads',
								'google-listings-and-ads'
							) }
							checked={ isEnabled }
							disabled={ isSaving }
							onChange={ handleOnChange }
							help={ __(
								'Please make sure to follow the documentation to enable Enhanced Conversions. The feature needs to be enabled both here on WooCommerce and on your Google Ads account.',
								'google-listings-and-ads'
							) }
						/>
					</Section.Card.Body>
				</Section.Card>
			) }
		</Fragment>
	);
};

export default ActiveEnhancedConversionCard;
