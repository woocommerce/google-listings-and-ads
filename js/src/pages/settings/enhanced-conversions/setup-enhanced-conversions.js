/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import SpinnerCard from '~/components/spinner-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEnableEnhancedConversions from './useEnableEnhancedConversions';

/**
 * Renders the settings section for Enhanced Conversions setup.
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-enhanced-conversions', link_id: 'enhanced-conversions-read-more', href: 'https://support.google.com/google-ads/answer/9888656' }`
 */
const SetupEnhancedConversions = () => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const {
		isEnabled,
		hasFinishedResolution: hasResolvedEnableEnhancedConversion,
	} = useEnableEnhancedConversions();
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

	let helpText = __(
		'Please make sure to follow the documentation to enable Enhanced Conversions. The feature needs to be enabled both here on WooCommerce and on your Google Ads account.',
		'google-listings-and-ads'
	);

	if ( ! hasGoogleAdsConnection ) {
		helpText = __(
			'Please connect your Google Ads account in order to use Enhanced Conversions data.',
			'google-listings-and-ads'
		);
	}

	const loaded =
		hasResolvedGoogleAdsAccount && hasResolvedEnableEnhancedConversion;
	const disabledCheckbox = ! hasGoogleAdsConnection || isSaving;

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
			{ ! loaded && <SpinnerCard /> }

			{ loaded && (
				<Section.Card>
					<Section.Card.Body>
						<ToggleControl
							label={ __(
								'Send Enhanced Conversions data to Google Ads',
								'google-listings-and-ads'
							) }
							checked={ isEnabled }
							disabled={ disabledCheckbox }
							onChange={ handleOnChange }
							help={ helpText }
						/>
					</Section.Card.Body>
				</Section.Card>
			) }
		</Section>
	);
};

export default SetupEnhancedConversions;
