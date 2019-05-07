<?php
/**
 * Autoloader.
 *
 * @package YouTube
 * @since   1.0.0
 * @author  WP Perf <wpperf@gmail.com>
 */

/**
 * Autoload Classes
 *
 * Pattern: WP_Perf\YouTube_Channel_Sync\My_Module\My_Class_Name -> classes/my-module/class-my-class-name.php.
 *
 * @throws \Exception Function isn't callable.
 */
try {
	spl_autoload_register( function ( $class ) {
		$org_namespace     = 'WP_Perf';
		$project_namespace = 'YouTube_Channel_Sync';

		$base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;

		$allowed_file_prefixes = [
			'abstract',
			'class',
			'interface',
			'trait',
		];

		$tokens = explode( '\\', $class );

		// Check if the class is a member of this organization.
		if ( array_shift( $tokens ) !== $org_namespace ) {
			// Fail if not a member.
			return false;
		}

		// Check if the class is a member of this project.
		if ( array_shift( $tokens ) !== $project_namespace ) {
			// Fail if not a member.
			return false;
		}

		$tokens = array_map( function ( $token ) {
			$token = strtolower( $token );
			$token = str_replace( '_', '-', $token );

			return $token;
		}, $tokens );

		$file = array_pop( $tokens );

		$path = ( count( $tokens ) )
			? join( DIRECTORY_SEPARATOR, $tokens ) . DIRECTORY_SEPARATOR
			: '';

		foreach ( $allowed_file_prefixes as $file_prefix ) {
			$filepath = "${base_dir}${path}${file_prefix}-${file}.php";
			if ( file_exists( $filepath ) ) {
				require $filepath;
			}
		}

		return false;
	} );
} catch ( \Exception $e ) {
	die( $e->getMessage() ); // phpcs:ignore
}