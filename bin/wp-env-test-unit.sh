#!/usr/bin/env bash
#
# Proof-of-concept: run this plugin's PHPUnit suite locally against wp-env's
# "tests" environment, instead of bin/install-wp-tests.sh's native
# svn/MySQL install. See bin/wp-env-install-woocommerce.sh for why a real
# WooCommerce git checkout (not the wp-cli-installed release build) is used.
#
# Usage: composer test-unit-wp-env -- [phpunit args, e.g. --filter=Foo]

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

./bin/wp-env-install-woocommerce.sh

OVERRIDE_MARKER=".wp-env-wc-src"
if [ -f .wp-env.override.json ] && ! grep -q "${OVERRIDE_MARKER}" .wp-env.override.json; then
	echo "Refusing to overwrite existing .wp-env.override.json (doesn't look like ours)." >&2
	echo "Add \"./.wp-env-wc-src/plugins/woocommerce\" to its \"plugins\" array yourself, or remove it and re-run." >&2
	exit 1
fi

cat > .wp-env.override.json <<-EOF
	{
		"plugins": [
			"https://github.com/WP-API/Basic-Auth/archive/master.zip",
			"./tests/e2e/test-data",
			"./tests/e2e/test-snippets",
			"./${OVERRIDE_MARKER}/plugins/woocommerce",
			"."
		]
	}
EOF

PLUGIN_SLUG="$(basename "$PWD")"

./node_modules/.bin/wp-env run tests-cli \
	--env-cwd="wp-content/plugins/${PLUGIN_SLUG}" \
	-- bash -c "WP_CORE_DIR=/var/www/html vendor/bin/phpunit $*"
