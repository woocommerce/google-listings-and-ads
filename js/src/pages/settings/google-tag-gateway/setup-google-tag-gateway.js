/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCallback,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { Card, CardBody, CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppDocumentationLink from '~/components/app-documentation-link';
import AppSpinner from '~/components/app-spinner';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEnableGoogleTagGateway from '~/hooks/useEnableGoogleTagGateway';

/**
 * Renders the settings section for Google Tag Gateway setup.
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-google-tag-gateway', link_id: 'google-tag-gateway-read-more', href: 'https://support.google.com/google-ads/answer/9888656' }`
 */
const SetupGoogleTagGateway = () => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const {
		isEnabled,
		hasFinishedResolution: hasResolvedEnableGoogleTagGateway,
	} = useEnableGoogleTagGateway();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateGoogleTagGatewayStatus } = useAppDispatch();

	const toggleGoogleTagGateway = useCallback( async () => {
		await updateGoogleTagGatewayStatus( ! isEnabled );
	}, [ updateGoogleTagGatewayStatus, isEnabled ] );

	const handleOnChange = async () => {
		try {
			setIsSaving( true );
			await toggleGoogleTagGateway();

			createNotice(
				'success',
				__(
					'Google Tag Gateway status updated successfully.',
					'google-listings-and-ads'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateGoogleTagGatewayStatus` action.
		} finally {
			setIsSaving( false );
		}
	};

	const loaded =
		hasResolvedGoogleAdsAccount && hasResolvedEnableGoogleTagGateway;
	const disabledCheckbox = ! hasGoogleAdsConnection || isSaving;

	if ( ! loaded ) {
		return <AppSpinner />;
	}

	return (
		<CheckboxControl
			label={ __(
				'Enable Google tag gateway for advertisers',
				'google-listings-and-ads'
			) }
			checked={ isEnabled }
			disabled={ disabledCheckbox }
			onChange={ handleOnChange }
			help={ createInterpolateElement(
				__(
					'Google Tag Gateway for Advertisers helps businesses strengthen first-party data and improve measurement accuracy while enhancing privacy and customer insights. <readMoreLink>Read more</readMoreLink>',
					'google-listings-and-ads'
				),
				{
					readMoreLink: (
						<AppDocumentationLink
							href="https://support.google.com/google-ads/answer/9841530"
							context={ 'setup-google-tag-gateway' }
							linkId="google-tag-gateway-read-more"
						/>
					),
				}
			) }
		/>
	);
};

export default SetupGoogleTagGateway;
