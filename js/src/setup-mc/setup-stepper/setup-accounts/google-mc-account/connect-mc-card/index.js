/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import SwitchUrlCard from '../switch-url-card';
import ReclaimUrlCard from '../reclaim-url-card';

const ConnectMCCard = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const [
		fetchMCAccounts,
		{ loading, error, response, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { invalidateResolution } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		await fetchMCAccounts();
		invalidateResolution( 'getGoogleMCAccount', [] );
	};

	const handleSelectAnotherAccount = () => {
		reset();
	};

	if ( response && response.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ error.id }
				message={ error.message }
				claimedUrl={ error.claimed_url }
				newUrl={ error.new_url }
				onSelectAnotherAccount={ handleSelectAnotherAccount }
			/>
		);
	}

	if ( response && response.status === 403 ) {
		return <ReclaimUrlCard websiteUrl={ error.website_url } />;
	}

	return (
		<Section.Card>
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'You have existing Merchant Center accounts',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<ContentButtonLayout>
					<MerchantCenterSelectControl
						value={ value }
						onChange={ setValue }
					/>
					<AppButton
						isSecondary
						loading={ loading }
						disabled={ ! value }
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<Button disabled={ loading } isLink onClick={ onCreateNew }>
					{ __(
						'Or, create a new Merchant Center account',
						'google-listings-and-ads'
					) }
				</Button>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default ConnectMCCard;
