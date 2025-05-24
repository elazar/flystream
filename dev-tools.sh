#!/bin/sh

# Define supported PHP versions
SUPPORTED_PHP_VERSIONS="81 82 83 84"

# Define supported compose commands in order of preference
SUPPORTED_COMPOSE_COMMANDS="podman-compose 'podman compose' 'docker compose' docker-compose"

# Set the error message using the supported versions
export ERROR_MSG="PHP_VERSION must be set (${SUPPORTED_PHP_VERSIONS// /, })"

# Default options
FORCE_REBUILD=0
VERBOSE=0

# Function to display help
show_help() {
    cat << EOF
Usage: $0 <COMMAND> [OPTIONS] [PHP_VERSIONS...]

Development tools for running tests and code style checks across multiple PHP versions.

COMMANDS:
    test                 Run tests for specified PHP versions
    cs                   Run code style fixer for specified PHP versions
    cleanup              Clean up generated files only (no operations)

Options:
    -h, --help           Show this help message
    -f, --force-rebuild  Force rebuild of containers before operation
    -v, --verbose        Enable verbose output

Supported PHP versions: ${SUPPORTED_PHP_VERSIONS// /, }

Examples:
    $0 test                          # Run tests for all PHP versions
    $0 test 81 82                    # Run tests for PHP 8.1 and 8.2
    $0 cs -f 83                      # Force rebuild and run CS fixer for PHP 8.3
    $0 cs                            # Run CS fixer for all PHP versions
    $0 cleanup                       # Clean up all generated files
    $0 cleanup 81                    # Clean up files for PHP 8.1 only
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
           "${version_dir}/cs.log" \
           "${version_dir}/vendor"
}

# Function to setup version environment
setup_version_environment() {
    version=$1
    version_dir="docker/php${version}"

    # Ensure required directories exist
    mkdir -p "${version_dir}"
    mkdir -p "${version_dir}/.composer/cache"
    mkdir -p "${version_dir}/vendor"

    # Force rebuild if requested
    if [ $FORCE_REBUILD -eq 1 ]; then
        echo "Forcing rebuild of PHP ${version} container..."
        PHP_VERSION="${version}" $COMPOSE_CMD build --no-cache php
    fi

    # Create or reset composer.lock to valid empty JSON if it's empty or doesn't exist
    if [ ! -s "${version_dir}/composer.lock" ]; then
        echo "{}" > "${version_dir}/composer.lock"
    fi
}

# Function to run tests for a specific PHP version
run_version_tests() {
    version=$1
    version_dir="docker/php${version}"
    log_file="${version_dir}/test.log"

    printf "Running tests for PHP %s\n" "${version}"

    setup_version_environment "$version"

    # Run the tests - output to terminal with colors and log file without colors
    mkdir -p "$(dirname "$log_file")"
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

# Function to run code style fixer for a specific PHP version
run_version_cs() {
    version=$1
    version_dir="docker/php${version}"
    log_file="${version_dir}/cs.log"

    printf "Running code style fixer for PHP %s\n" "${version}"

    setup_version_environment "$version"

    # Run CS fixer - output to terminal with colors and log file without colors
    mkdir -p "$(dirname "$log_file")"
    TERM="${TERM:-xterm-256color}" PHP_VERSION="${version}" COMPOSER_COMMAND=cs $COMPOSE_CMD run --rm php 2>&1 | tee "$log_file"
    exit_code=$?

    if [ $exit_code -eq 0 ]; then
        printf "✓ PHP %s code style check passed\n" "${version}"
    else
        printf "✗ PHP %s code style check failed - see %s for details\n" "${version}" "$log_file"
    fi

    return $exit_code
}

# Function to validate PHP versions
validate_php_versions() {
    for version in "$@"; do
        if ! echo "$SUPPORTED_PHP_VERSIONS" | grep -q -w "$version"; then
            echo "Error: PHP version $version is not supported"
            echo "Supported versions are: ${SUPPORTED_PHP_VERSIONS// /, }"
            exit 1
        fi
    done
}

# Function to determine PHP versions to use
get_php_versions() {
    if [ $# -gt 0 ]; then
        validate_php_versions "$@"
        echo "$@"
    else
        echo "$SUPPORTED_PHP_VERSIONS"
    fi
}

# Function to handle cleanup command
handle_cleanup() {
    shift # Remove 'cleanup' from arguments

    # Parse cleanup-specific options
    while [ $# -gt 0 ]; do
        case "$1" in
            -h|--help)
                show_help
                exit 0
                ;;
            -v|--verbose)
                VERBOSE=1
                shift
                ;;
            -*)
                echo "Unknown option for cleanup: $1" >&2
                show_help
                exit 1
                ;;
            *)
                break
                ;;
        esac
    done

    php_versions=$(get_php_versions "$@")

    for version in $php_versions; do
        cleanup_version "$version"
    done
    echo "Cleanup completed"
    exit 0
}

# Function to handle test command
handle_test() {
    shift # Remove 'test' from arguments

    # Parse test-specific options
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
            -v|--verbose)
                VERBOSE=1
                shift
                ;;
            -*)
                echo "Unknown option for test: $1" >&2
                show_help
                exit 1
                ;;
            *)
                break
                ;;
        esac
    done

    php_versions=$(get_php_versions "$@")

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
}

# Function to handle cs command
handle_cs() {
    shift # Remove 'cs' from arguments

    # Parse cs-specific options
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
            -v|--verbose)
                VERBOSE=1
                shift
                ;;
            -*)
                echo "Unknown option for cs: $1" >&2
                show_help
                exit 1
                ;;
            *)
                break
                ;;
        esac
    done

    php_versions=$(get_php_versions "$@")

    # Run CS fixer for each version
    failed=0
    for version in $php_versions; do
        # Clean up before CS check if force rebuild is requested
        if [ $FORCE_REBUILD -eq 1 ]; then
            cleanup_version "$version"
        fi

        run_version_cs "$version"
        if [ $? -ne 0 ]; then
            failed=1
        fi
    done

    # Exit with appropriate status
    if [ $failed -eq 0 ]; then
        echo "All code style checks completed successfully"
        exit 0
    else
        echo "Some code style checks failed"
        exit 1
    fi
}

# Main script logic
if [ $# -eq 0 ]; then
    # No arguments, show help
    show_help
    exit 0
fi

# Get the compose command early
COMPOSE_CMD="$(find_compose_command)" || exit 1
[ $VERBOSE -eq 1 ] && echo "Using ${COMPOSE_CMD}"

# Check if first argument is a command
case "$1" in
    test)
        handle_test "$@"
        ;;
    cs)
        handle_cs "$@"
        ;;
    cleanup)
        handle_cleanup "$@"
        ;;
    -h|--help)
        show_help
        exit 0
        ;;
    *)
        echo "Unknown command: $1" >&2
        echo "Use '$0 --help' for usage information." >&2
        exit 1
        ;;
esac