/**
 * External dependencies
 */
import { css } from '@emotion/css';

/**
 * Internal dependencies
 */
import breakpoints from '../../utils/breakpoint-values';

export const cardClass = css`
	.components-flex {
		flex-direction: column;
		align-items: flex-end;

		@media ( min-width: ${ breakpoints.large } ) {
			flex-direction: row;
			align-items: center;
		}

		.motivation-text {
			margin: calc( var( --main-gap ) * 2 );

			.title {
				margin-bottom: calc( var( --main-gap ) / 2 );
			}

			.description {
				margin-bottom: var( --main-gap );
			}
		}

		.motivation-image {
			text-align: right;
			margin-left: calc( var( --main-gap ) * 2 );
			margin-bottom: -4px;

			@media ( min-width: ${ breakpoints.large } ) {
				margin-top: calc( var( --main-gap ) * 1.5 );
			}

			svg {
				width: 100%;
				height: 100%;
			}
		}
	}
`;
