/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import { useEnableEnhancedConversions } from './useEnableEnhancedConversions';
import Section from '~/components/section';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';

const SetupEnhancedConversions = () => {
	const isEnabled = useEnableEnhancedConversions();
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
			// Silently fail because the error is handled in the hook.
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<Section
			title={ __(
				'Improve conversion accuracy',
				'google-listings-and-ads'
			) }
			description={
				<div>
					<p>
						{ __(
							'Enhanced Conversions is a feature designed to improve your measurement accuracy by collecting privacy-conscious data without the need for third-party cookies.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							href="https://support.google.com/google-ads/answer/9888656"
							context="setup-enhanced-conversions"
							linkId="enhanced-conversions-read-more"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
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
		</Section>
	);
};

export default SetupEnhancedConversions;
