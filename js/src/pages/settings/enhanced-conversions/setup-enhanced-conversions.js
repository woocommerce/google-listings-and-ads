/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, Tip } from '@wordpress/components';
import {
	useCallback,
	useState,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppDocumentationLink from '~/components/app-documentation-link';
import AppSpinner from '~/components/app-spinner';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEnableEnhancedConversions from './useEnableEnhancedConversions';
import './setup-enhanced-conversions.scss';

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

	const loaded =
		hasResolvedGoogleAdsAccount && hasResolvedEnableEnhancedConversion;
	const disabledCheckbox = ! hasGoogleAdsConnection || isSaving;

	if ( ! loaded ) {
		return <AppSpinner />;
	}

	let helpText = createInterpolateElement(
		__(
			'Enhanced Conversions is a feature designed to improve your measurement accuracy by collecting privacy-conscious data without the need for third-party cookies. <readMoreLink>Read more</readMoreLink>.',
			'google-listings-and-ads'
		),
		{
			readMoreLink: (
				<AppDocumentationLink
					href="https://support.google.com/google-ads/answer/9888656"
					context="setup-enhanced-conversions"
					linkId="enhanced-conversions-read-more"
				/>
			),
		}
	);

	if ( ! hasGoogleAdsConnection ) {
		helpText = __(
			'Please connect your Google Ads account in order to use Enhanced Conversions data.',
			'google-listings-and-ads'
		);
	}

	return (
		<div className="gla-settings-enhanced-conversions">
			<CheckboxControl
				label={ __(
					'Send Enhanced Conversions data to Google Ads',
					'google-listings-and-ads'
				) }
				checked={ isEnabled }
				disabled={ disabledCheckbox }
				onChange={ handleOnChange }
				help={ helpText }
			/>

			<Tip>
				{ __(
					'Please make sure to follow the documentation to enable Enhanced Conversions. The feature needs to be enabled both here on WooCommerce and on your Google Ads account.',
					'google-listings-and-ads'
				) }
			</Tip>
		</div>
	);
};

export default SetupEnhancedConversions;
