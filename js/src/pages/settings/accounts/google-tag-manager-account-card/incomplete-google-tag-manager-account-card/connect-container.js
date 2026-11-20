/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import SelectContainer from './select-container';
import NoContainerFound from './no-container-found';

/**
 * Renders the way to connect a container to the already-connected account: the container selector
 * (one or more containers exist), or the notice that none was found (the account has zero containers —
 * there's nothing to select). Both offer to create a container.
 *
 * @return {JSX.Element} The selector or the notice, or a loading spinner until the containers list has resolved.
 */
export default function ConnectContainer() {
	const { containers, hasFinishedResolution } =
		useGoogleTagManagerContainers();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	if ( containers?.length ) {
		return <SelectContainer />;
	}

	return <NoContainerFound />;
}
