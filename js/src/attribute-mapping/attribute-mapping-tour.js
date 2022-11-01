/**
 * External dependencies
 */
import Tour from '@automattic/tour-kit';
import { useState } from '@wordpress/element';

const AttributeMappingTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.gla-attribute-mapping__table',
				},
				meta: {
					description: 'Lorem ipsum dolor sit amet.',
				},
			},
		],
		renderers: {
			tourStep: ( {
				steps,
				currentStepIndex,
				setInitialFocusedElement,
				onNext,
				onPrevious,
				onDismiss,
			} ) => {
				return (
					<>
						<button onClick={ onPrevious }>Previous</button>
						<button
							onClick={ onNext }
							ref={ setInitialFocusedElement }
						>
							Next
						</button>
						<button onClick={ onDismiss( 'close-btn' ) }>
							Close
						</button>
						<p>{ steps[ currentStepIndex ].meta.description }</p>
					</>
				);
			},
			tourMinimized: <div />,
		},
		closeHandler: () => setShowTour( false ),
		options: {},
	};

	return showTour && <Tour config={ config } />;
};

export default AttributeMappingTour;
