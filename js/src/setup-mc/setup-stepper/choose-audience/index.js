/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContentHeader from '../components/step-content-header';
import './index.scss';

const ChooseAudience = () => {
	return (
		<div className="gla-choose-audience">
			<StepContentHeader
				step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
				title={ __(
					'Choose your audience',
					'google-listings-and-ads'
				) }
				description={ __(
					'Configure who sees your product listings on Google.',
					'google-listings-and-ads'
				) }
			/>
		</div>
	);
};

export default ChooseAudience;
