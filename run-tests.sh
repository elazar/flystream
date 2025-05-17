#!/bin/sh

# Define supported PHP versions
SUPPORTED_PHP_VERSIONS="81 82 83 84"

# Define supported compose commands in order of preference
SUPPORTED_COMPOSE_COMMANDS="podman-compose 'podman compose' 'docker compose' docker-compose"

# Set the error message using the supported versions
export ERROR_MSG="PHP_VERSION must be set (${SUPPORTED_PHP_VERSIONS// /, })"

# Default options
FORCE_REBUILD=0
CLEANUP_ONLY=0
VERBOSE=0

# Function to display help
show_help() {
    cat << EOF
Usage: $0 [OPTIONS] [PHP_VERSIONS...]

Run tests for specified PHP versions. If no versions are specified, tests all supported versions.

Options:
    -h, --help           Show this help message
    -f, --force-rebuild  Force rebuild of containers before testing
    -c, --cleanup        Clean up generated files only (no tests)
    -v, --verbose        Enable verbose output

Supported PHP versions: ${SUPPORTED_PHP_VERSIONS// /, }

Examples:
    $0                 # Run tests for all PHP versions
    $0 81 82           # Run tests for PHP 8.1 and 8.2
    $0 -f 83           # Force rebuild and test PHP 8.3
    $0 -c              # Clean up all generated files
EOF
}

# Function to find the first available compose command
find_compose_command() {
    # First try podman compose as a single command
    if command -v podman >/dev/null 2>&1 && podman compose --help >/dev/null 2>&1; then
        echo "podman compose"
        return 0
    fi

    # Then try docker compose as a single command
    if command -v docker >/dev/null 2>&1 && docker compose --help >/dev/null 2>&1; then
        echo "docker compose"
        return 0
    fi

    # Finally try the legacy commands
    for cmd in podman-compose docker-compose; do
        if command -v "$cmd" >/dev/null 2>&1; then
            echo "$cmd"
            return 0
        fi
    done

    echo "No supported compose command found. Please install podman-compose or docker-compose." >&2
    return 1
}

# Function to clean up generated files for a specific version
cleanup_version() {
    version=$1
    version_dir="docker/php${version}"

    echo "Cleaning up PHP ${version} generated files..."
    rm -rf "${version_dir}/.composer" \
           "${version_dir}/composer.lock" \
           "${version_dir}/test.log" \
           "${version_dir}/vendor"
}

# Function to run tests for a specific PHP version
run_version_tests() {
    version=$1
    version_dir="docker/php${version}"
    log_file="${version_dir}/test.log"

    printf "Running tests for PHP %s\n" "${version}"

    # Ensure required directories exist
    mkdir -p "${version_dir}"
    mkdir -p "${version_dir}/.composer/cache"
    mkdir -p "${version_dir}/vendor"
    mkdir -p "$(dirname "$log_file")"

    # Force rebuild if requested
    if [ $FORCE_REBUILD -eq 1 ]; then
        echo "Forcing rebuild of PHP ${version} container..."
        PHP_VERSION="${version}" $COMPOSE_CMD build --no-cache php
    fi

    # Create or reset composer.lock to valid empty JSON if it's empty or doesn't exist
    if [ ! -s "${version_dir}/composer.lock" ]; then
        echo "{}" > "${version_dir}/composer.lock"
    fi

    # Run the tests - output to terminal with colors and log file without colors
    mkfifo logpipe
    trap 'rm -f logpipe' EXIT
    sed 's/\x1b\[[0-9;]*m//g' < logpipe > "$log_file" &
    TERM="${TERM:-xterm-256color}" PHP_VERSION="${version}" $COMPOSE_CMD run --rm php 2>&1 | tee logpipe
    exit_code=$?
    rm logpipe

    if [ $exit_code -eq 0 ]; then
        printf "✓ PHP %s tests passed\n" "${version}"
    else
        printf "✗ PHP %s tests failed - see %s for details\n" "${version}" "$log_file"
    fi

    return $exit_code
}

# Parse command line arguments
while [ $# -gt 0 ]; do
    case "$1" in
        -h|--help)
            show_help
            exit 0
            ;;
        -f|--force-rebuild)
            FORCE_REBUILD=1
            shift
            ;;
        -c|--cleanup)
            CLEANUP_ONLY=1
            shift
            ;;
        -v|--verbose)
            VERBOSE=1
            shift
            ;;
        -*)
            echo "Unknown option: $1" >&2
            show_help
            exit 1
            ;;
        *)
            break
            ;;
    esac
done

# Get the compose command
COMPOSE_CMD="$(find_compose_command)" || exit 1
[ $VERBOSE -eq 1 ] && echo "Using ${COMPOSE_CMD}"

# Use provided versions or default to all supported versions
if [ $# -gt 0 ]; then
    # Validate provided versions
    for version in "$@"; do
        if ! echo "$SUPPORTED_PHP_VERSIONS" | grep -q -w "$version"; then
            echo "Error: PHP version $version is not supported"
            echo "Supported versions are: ${SUPPORTED_PHP_VERSIONS// /, }"
            exit 1
        fi
    done
    php_versions="$@"
else
    php_versions="$SUPPORTED_PHP_VERSIONS"
fi

# Handle cleanup-only mode
if [ $CLEANUP_ONLY -eq 1 ]; then
    for version in $php_versions; do
        cleanup_version "$version"
    done
    echo "Cleanup completed"
    exit 0
fi

# Run tests for each version
failed=0
for version in $php_versions; do
    # Clean up before testing if force rebuild is requested
    if [ $FORCE_REBUILD -eq 1 ]; then
        cleanup_version "$version"
    fi

    run_version_tests "$version"
    if [ $? -ne 0 ]; then
        failed=1
    fi
done

# Exit with appropriate status
if [ $failed -eq 0 ]; then
    echo "All tests completed successfully"
    exit 0
else
    echo "Some tests failed"
    exit 1
fi
