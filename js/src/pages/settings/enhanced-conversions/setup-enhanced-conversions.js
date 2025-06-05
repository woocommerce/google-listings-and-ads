/**
 * External dependencies
 */
import { CheckboxControl, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import { useEnableEnhancedConversions } from './useEnableEnhancedConversions';

/**
 * Internal dependencies
 */
import Section from '~/components/section';

const SetupEnhancedConversions = () => {
	const [ isEnabled, , toggleEnhancedConversions ] =
		useEnableEnhancedConversions();

	const [ isNoticeVisible, setIsNoticeVisible ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );

	const onChangeECState = async () => {
		setIsSaving( true );
		await toggleEnhancedConversions();
		setIsNoticeVisible( true );
		setIsSaving( false );
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
			<Section.Card className="gla-settings-enhanced-conversions">
				<Section.Card.Body>
					<CheckboxControl
						label={ __(
							'Send Enhanced Conversions data to Google Ads',
							'google-listings-and-ads'
						) }
						checked={ isEnabled && ! isSaving }
						disabled={ isSaving }
						onChange={ onChangeECState }
						value={ isEnabled }
						help={ __(
							'Please make sure to follow the documentation to enable Enhanced Conversions. The feature needs to be enabled both here on WooCommerce and on your Google Ads account.',
							'google-listings-and-ads'
						) }
					/>
					{ isNoticeVisible && (
						<Notice
							status="success"
							isDismissible={ true }
							onRemove={ () => setIsNoticeVisible( false ) }
						>
							{ __(
								'Enhanced Conversions status updated successfully.',
								'google-listings-and-ads'
							) }
						</Notice>
					) }
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default SetupEnhancedConversions;
