/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '.~/components/title-button-layout';
import CreateAccountButton from './create-account-button';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const CreateAccount = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { receiveAdsAccount } = useAppDispatch();
	const [ fetchCreateAdsAccount, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
	} );

	const handleCreateAccount = async () => {
		try {
			const data = await fetchCreateAdsAccount();
			receiveAdsAccount( data );
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to create Google Ads account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ __(
						'Create your Google Ads account',
						'google-listings-and-ads'
					) }
					button={
						<CreateAccountButton
							isSecondary
							loading={ loading }
							onCreateAccount={ handleCreateAccount }
						/>
					}
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default CreateAccount;
