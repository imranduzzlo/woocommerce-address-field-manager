<?php
/**
 * GitHub Plugin Updater
 * Enables automatic updates from GitHub repository
 * Uses WordPress standard update system with proper hooks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WAFM_GitHub_Updater {

	private $file;
	private $plugin_slug;
	private $plugin_basename;
	private $github_user;
	private $github_repo;
	private $github_url;
	private $plugin_data;
	private $github_token;

	public function __construct( $file ) {
		$this->file = $file;
		$this->plugin_basename = plugin_basename( $file );
		
		// Load plugin data
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$this->plugin_data = get_plugin_data( $file );
		
		// Parse GitHub URL from Plugin URI
		$plugin_uri = $this->plugin_data['PluginURI'] ?? '';
		if ( $plugin_uri && strpos( $plugin_uri, 'github.com' ) !== false ) {
			$path = parse_url( $plugin_uri, PHP_URL_PATH );
			$parts = explode( '/', trim( $path, '/' ) );
			if ( count( $parts ) >= 2 ) {
				$this->github_user = $parts[0];
				$this->github_repo = $parts[1];
				$this->github_url = "https://github.com/{$this->github_user}/{$this->github_repo}";
				$this->plugin_slug = dirname( $this->plugin_basename );
			}
		}
		
		// Optional: Load GitHub token for private repos or higher rate limits
		$token_file = dirname( $file ) . '/.kiro/github-token.txt';
		if ( file_exists( $token_file ) ) {
			$this->github_token = trim( file_get_contents( $token_file ) );
		}
		
		$this->init();
	}

	private function init() {
		if ( ! $this->github_user || ! $this->github_repo ) {
			return;
		}

		// Hook into WordPress update system
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		
		// Plugin information for the modal
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 20, 3 );
		
		// Fix directory name after update
		add_filter( 'upgrader_source_selection', array( $this, 'fix_plugin_directory' ), 10, 4 );
		
		// Clear cache after update
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
	}

	/**
	 * Check for updates
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get latest release from GitHub
		$release = $this->get_latest_release();
		
		if ( ! $release ) {
			return $transient;
		}

		$current_version = $this->plugin_data['Version'] ?? '0';
		$latest_version = ltrim( $release['tag_name'], 'v' );

		// Only add update if newer version available
		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$plugin_data = array(
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $latest_version,
				'url'           => $this->github_url,
				'package'       => $release['zipball_url'],
				'tested'        => '6.7',
				'requires'      => '6.0',
				'requires_php'  => '7.4',
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Get latest release from GitHub API
	 */
	private function get_latest_release() {
		$transient_key = 'wafm_github_release_' . md5( $this->github_user . $this->github_repo );
		$cached = get_transient( $transient_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		
		$args = array(
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
			),
			'timeout' => 15,
		);
		
		// Add authorization if token is available
		if ( $this->github_token ) {
			$args['headers']['Authorization'] = 'token ' . $this->github_token;
		}
		
		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			// Cache failure for 5 minutes to avoid hammering API
			set_transient( $transient_key, false, 5 * MINUTE_IN_SECONDS );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			set_transient( $transient_key, false, 5 * MINUTE_IN_SECONDS );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( ! isset( $release['tag_name'] ) ) {
			set_transient( $transient_key, false, 5 * MINUTE_IN_SECONDS );
			return false;
		}

		// Cache for 6 hours
		set_transient( $transient_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Plugin information for the details modal
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		
		if ( ! $release ) {
			return $result;
		}

		$latest_version = ltrim( $release['tag_name'], 'v' );

		$plugin_info = new stdClass();
		$plugin_info->name = $this->plugin_data['Name'];
		$plugin_info->slug = $this->plugin_slug;
		$plugin_info->version = $latest_version;
		$plugin_info->author = $this->plugin_data['Author'];
		$plugin_info->homepage = $this->github_url;
		$plugin_info->download_link = $release['zipball_url'];
		$plugin_info->requires = '6.0';
		$plugin_info->requires_php = '7.4';
		$plugin_info->tested = '6.7';
		$plugin_info->last_updated = $release['published_at'];
		$plugin_info->sections = array(
			'description' => $this->plugin_data['Description'],
			'changelog'   => $this->format_changelog( $release['body'] ?? '' ),
		);
		$plugin_info->banners = array();
		$plugin_info->icons = array();

		return $plugin_info;
	}

	/**
	 * Format changelog from GitHub release notes
	 */
	private function format_changelog( $body ) {
		if ( empty( $body ) ) {
			return '<p>No changelog available.</p>';
		}

		// Convert markdown headers
		$body = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $body );
		$body = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $body );
		$body = preg_replace( '/^# (.+)$/m', '<h2>$1</h2>', $body );
		
		// Convert markdown lists
		$body = preg_replace( '/^\- (.+)$/m', '<li>$1</li>', $body );
		$body = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $body );
		
		// Convert bold
		$body = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $body );
		
		// Convert line breaks
		$body = wpautop( $body );
		
		return wp_kses_post( $body );
	}

	/**
	 * Fix plugin directory name after update
	 * GitHub zipballs extract to {user}-{repo}-{commit} format
	 */
	public function fix_plugin_directory( $source, $remote_source, $upgrader, $hook_extra = null ) {
		global $wp_filesystem;

		// Only process our plugin
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		// Expected directory name
		$correct_dir = $this->plugin_slug;
		
		// Get the actual directory name from source
		$source_files = $wp_filesystem->dirlist( $remote_source );
		if ( ! $source_files ) {
			return $source;
		}
		
		// GitHub creates directory like: username-reponame-commit
		$source_dir = key( $source_files );
		
		// If already correct, return
		if ( $source_dir === $correct_dir ) {
			return $source;
		}

		// Rename to correct directory
		$new_source = trailingslashit( $remote_source ) . $correct_dir;
		
		if ( $wp_filesystem->move( $source, $new_source ) ) {
			return $new_source;
		}

		return new WP_Error( 'rename_failed', __( 'Could not rename plugin directory.', 'woocommerce-address-field-manager' ) );
	}

	/**
	 * Clear cache after update
	 */
	public function clear_cache( $upgrader, $options ) {
		if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
			return;
		}

		if ( ! isset( $options['plugins'] ) ) {
			return;
		}

		foreach ( $options['plugins'] as $plugin ) {
			if ( $plugin === $this->plugin_basename ) {
				$transient_key = 'wafm_github_release_' . md5( $this->github_user . $this->github_repo );
				delete_transient( $transient_key );
				break;
			}
		}
	}
}
