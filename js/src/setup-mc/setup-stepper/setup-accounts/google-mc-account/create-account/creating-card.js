/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import ContentButtonLayout from '../../content-button-layout';
import Subsection from '.~/wcdl/subsection';
import AppButton from '.~/components/app-button';

const CreatingCard = ( props ) => {
	const { retryAfter, onRetry = () => {} } = props;

	useEffect( () => {
		if ( ! retryAfter ) {
			return;
		}

		const intervalID = setInterval( () => {
			onRetry();
		}, retryAfter * 1000 );

		return () => clearInterval( intervalID );
	}, [ retryAfter, onRetry ] );

	return (
		<Section.Card>
			<Section.Card.Body>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ __(
								'Create your Google Merchant Center account',
								'google-listings-and-ads'
							) }
						</Subsection.Title>
						<Subsection.HelperText>
							{ __(
								'This may take a few minutes, please wait a moment…',
								'google-listings-and-ads'
							) }
						</Subsection.HelperText>
					</div>
					<AppButton loading>
						{ __( 'Creating…', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default CreatingCard;
