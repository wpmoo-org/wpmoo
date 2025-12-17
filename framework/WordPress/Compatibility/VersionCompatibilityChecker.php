<?php

namespace WPMoo\WordPress\Compatibility;

/**
 * Version compatibility checker for WPMoo framework.
 *
 * @package WPMoo\WordPress\Compatibility
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class VersionCompatibilityChecker {

	/**
	 * Check if a plugin's required framework version is compatible with the current framework version.
	 *
	 * @param string $required_version The version required by the plugin (e.g., '1.0.0', '^1.0', '~1.0.0', '>=1.0 <2.0.0').
	 * @param string $current_version The current framework version.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	public static function is_compatible( string $required_version, string $current_version ): array {
		// If the required version is a simple version string (e.g., '1.0.0').
		if ( ! preg_match( '/[~^*<>!=\[\]]/', $required_version ) ) {
			$compatible = version_compare( $current_version, $required_version, '>=' );
			$message = $compatible
				? "Current framework version ({$current_version}) meets required version ({$required_version})"
				: "Current framework version ({$current_version}) does not meet required version ({$required_version})";

			return array(
				'compatible' => $compatible,
				'message' => $message,
			);
		}

		// Handle multiple constraints (e.g., ">=1.0.0 <2.0.0").
		if ( strpos( $required_version, ' ' ) !== false ) {
			return self::check_multiple_constraints( $required_version, $current_version );
		}

		// Handle Composer-style version constraints.
		$result = self::check_version_constraint( $required_version, $current_version );

		return array(
			'compatible' => $result['compatible'],
			'message' => $result['message'],
		);
	}

	/**
	 * Check multiple version constraints.
	 *
	 * @param string $constraints The version constraints (e.g., '>=1.0.0 <2.0.0').
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_multiple_constraints( string $constraints, string $version ): array {
		// Split the constraints by spaces, but preserve the operators with the versions.
		$parts = preg_split( '/\s+/', trim( $constraints ) );
		$all_compatible = true;
		$messages = array();

		// Process pairs of operator and version.
		$parts_count = count( $parts );
		for ( $i = 0; $i < $parts_count; $i += 2 ) {
			if ( $i + 1 >= $parts_count ) {
				// If there's an odd number of parts, the last one might be incomplete.
				break;
			}

			$operator = $parts[ $i ];
			$required_version = $parts[ $i + 1 ];

			// Validate that the first part is an operator.
			if ( ! preg_match( '/^[<>=!]/', $operator ) ) {
				// If not an operator, try combining with previous.
				if ( $i > 0 ) {
					$operator = $parts[ $i - 1 ] . ' ' . $parts[ $i ];
					$required_version = $parts[ $i + 1 ];
					$i--; // Adjust the counter.
				} else {
					// Skip this pair if it doesn't start with an operator.
					continue;
				}
			}

			$result = self::check_direct_comparison( $operator, $required_version, $version );
			$all_compatible = $all_compatible && $result['compatible'];
			$messages[] = $result['message'];
		}

		return array(
			'compatible' => $all_compatible,
			'message' => implode( '; ', $messages ),
		);
	}

	/**
	 * Check version against Composer-style constraints.
	 *
	 * @param string $constraint The version constraint (e.g., '^1.0', '~1.0.0', '>=1.0.0').
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_version_constraint( string $constraint, string $version ): array {
		// Remove any whitespace.
		$constraint = trim( $constraint );

		// Handle wildcard versions (e.g., 1.x, 1.*, 1.x).
		if ( preg_match( '/^(\d+)\.x\.x$|^(\d+)\.x$|^(\d+)\.\*$/', $constraint, $matches ) ) {
			$major = $matches[1] ?? ( $matches[2] ?? $matches[3] );
			return self::check_wildcard_constraint( $major, $version );
		} elseif ( preg_match( '/^(\d+)\.(\d+)\.x$|^(\d+)\.(\d+)\.\*$/', $constraint, $matches ) ) {
			$major = $matches[1] ?? $matches[3];
			$minor = $matches[2] ?? $matches[4];
			return self::check_wildcard_minor_constraint( $major, $minor, $version );
		}

		// Handle different constraint types.
		if ( str_starts_with( $constraint, '^' ) ) {
			// Caret operator: allows patch-level changes if a minor version is specified.
			// ^1.2.3 is equivalent to >=1.2.3 <2.0.0.
			$required = substr( $constraint, 1 );
			return self::check_caret_constraint( $required, $version );
		} elseif ( str_starts_with( $constraint, '~' ) ) {
			// Tilde operator: allows patch-level changes.
			// ~1.2.3 is equivalent to >=1.2.3 <1.3.0.
			$required = substr( $constraint, 1 );
			return self::check_tilde_constraint( $required, $version );
		} elseif ( preg_match( '/^([<>=!]+)\s*(.+)$/', $constraint, $matches ) ) {
			// Direct comparison operators (>=, <=, >, <, !=).
			$operator = $matches[1];
			$required = $matches[2];
			return self::check_direct_comparison( $operator, $required, $version );
		} else {
			// Handle pre-release versions (e.g., 1.0.0-alpha, 1.0.0-beta.2).
			if ( strpos( $constraint, '-' ) !== false ) {
				return self::check_pre_release_constraint( $constraint, $version );
			}

			// Fallback to simple comparison.
			$compatible = version_compare( $version, $constraint, '>=' );
			return array(
				'compatible' => $compatible,
				'message' => $compatible
					? "Version {$version} satisfies constraint {$constraint}"
					: "Version {$version} does not satisfy constraint {$constraint}",
			);
		}
	}

	/**
	 * Check wildcard constraint for major version (e.g., 1.x.x, 1.*).
	 *
	 * @param string $major The major version.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_wildcard_constraint( string $major, string $version ): array {
		$version_parts = explode( '.', $version );
		$version_major = $version_parts[0] ?? 0;

		$compatible = $major == $version_major;

		return array(
			'compatible' => $compatible,
			'message' => $compatible
				? "Version {$version} matches major version constraint {$major}.x.x"
				: "Version {$version} does not match major version constraint {$major}.x.x",
		);
	}

	/**
	 * Check wildcard constraint for minor version (e.g., 1.2.x, 1.2.*).
	 *
	 * @param string $major The major version.
	 * @param string $minor The minor version.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_wildcard_minor_constraint( string $major, string $minor, string $version ): array {
		$version_parts = explode( '.', $version );
		$version_major = $version_parts[0] ?? 0;
		$version_minor = $version_parts[1] ?? 0;

		$compatible = ( $major == $version_major ) && ( $minor == $version_minor );

		return array(
			'compatible' => $compatible,
			'message' => $compatible
				? "Version {$version} matches version constraint {$major}.{$minor}.x"
				: "Version {$version} does not match version constraint {$major}.{$minor}.x",
		);
	}

	/**
	 * Check pre-release version constraint.
	 *
	 * @param string $constraint The constraint including pre-release info.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_pre_release_constraint( string $constraint, string $version ): array {
		// Split version and pre-release part.
		$parts = explode( '-', $constraint, 2 );
		$base_version = $parts[0];
		$pre_release_constraint = $parts[1] ?? '';

		// Split version being checked.
		$version_parts = explode( '-', $version, 2 );
		$version_base = $version_parts[0];
		$version_pre_release = $version_parts[1] ?? '';

		// First check base version compatibility.
		$base_compatible = version_compare( $version_base, $base_version, '>=' );

		// If base versions don't match, the constraint fails.
		if ( ! $base_compatible ) {
			return array(
				'compatible' => false,
				'message' => "Version {$version} does not satisfy base version constraint {$base_version} in {$constraint}",
			);
		}

		// If constraint has a pre-release part but the version doesn't, it's only compatible if the version is stable.
		if ( '' !== $pre_release_constraint && '' === $version_pre_release ) {
			// Stable versions are considered greater than pre-release versions.
			return array(
				'compatible' => true,
				'message' => "Stable version {$version} satisfies pre-release constraint {$constraint}",
			);
		}

		// If both have pre-release parts, compare them.
		if ( '' !== $pre_release_constraint && '' !== $version_pre_release ) {
			// For pre-release comparison, we'll consider them equal for basic compatibility.
			// This could be enhanced to follow SemVer pre-release rules more strictly.
			return array(
				'compatible' => true,
				'message' => "Version {$version} satisfies pre-release constraint {$constraint}",
			);
		}

		// If constraint is for a pre-release but version is stable, it's not compatible.
		if ( '' !== $pre_release_constraint && '' === $version_pre_release ) {
			return array(
				'compatible' => false,
				'message' => "Stable version {$version} does not satisfy pre-release constraint {$constraint}",
			);
		}

		// If constraint is for stable but version has pre-release, it's not compatible.
		if ( '' === $pre_release_constraint && '' !== $version_pre_release ) {
			return array(
				'compatible' => false,
				'message' => "Pre-release version {$version} does not satisfy stable constraint {$constraint}",
			);
		}

		return array(
			'compatible' => true,
			'message' => "Version {$version} satisfies constraint {$constraint}",
		);
	}

	/**
	 * Check caret constraint (^ operator).
	 *
	 * @param string $required The required version after caret.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_caret_constraint( string $required, string $version ): array {
		$required_parts = explode( '.', $required );
		$version_parts = explode( '.', $version );

		// Pad arrays to same length with zeros.
		$required_parts_count = count( $required_parts );
		while ( $required_parts_count < 3 ) {
			$required_parts[] = '0';
			$required_parts_count = count( $required_parts );
		}
		$version_parts_count = count( $version_parts );
		while ( $version_parts_count < 3 ) {
			$version_parts[] = '0';
			$version_parts_count = count( $version_parts );
		}

		// Parse version numbers.
		$required_major = intval( $required_parts[0] );
		$required_minor = intval( $required_parts[1] ?? 0 );
		$required_patch = intval( $required_parts[2] ?? 0 );

		$version_major = intval( $version_parts[0] );
		$version_minor = intval( $version_parts[1] );
		$version_patch = intval( $version_parts[2] );

		// Check if version is greater than or equal to required.
		$gte = version_compare( $version, $required, '>=' );

		// Check if version is less than next major version.
		$next_major = ( $required_major + 1 ) . '.0.0';
		$lt_next_major = version_compare( $version, $next_major, '<' );

		$compatible = $gte && $lt_next_major;

		return array(
			'compatible' => $compatible,
			'message' => $compatible
				? "Version {$version} satisfies caret constraint ^{$required} (>= {$required} and < {$next_major})"
				: "Version {$version} does not satisfy caret constraint ^{$required} (>= {$required} and < {$next_major})",
		);
	}

	/**
	 * Check tilde constraint (~ operator).
	 *
	 * @param string $required The required version after tilde.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_tilde_constraint( string $required, string $version ): array {
		$required_parts = explode( '.', $required );
		$version_parts = explode( '.', $version );

		// Pad arrays to same length with zeros.
		$required_parts_count = count( $required_parts );
		while ( $required_parts_count < 3 ) {
			$required_parts[] = '0';
			$required_parts_count = count( $required_parts );
		}
		$version_parts_count = count( $version_parts );
		while ( $version_parts_count < 3 ) {
			$version_parts[] = '0';
			$version_parts_count = count( $version_parts );
		}

		// Parse version numbers.
		$required_major = intval( $required_parts[0] );
		$required_minor = intval( $required_parts[1] ?? 0 );
		$required_patch = intval( $required_parts[2] ?? 0 );

		$version_major = intval( $version_parts[0] );
		$version_minor = intval( $version_parts[1] );
		$version_patch = intval( $version_parts[2] );

		// Check if version is greater than or equal to required.
		$gte = version_compare( $version, $required, '>=' );

		// Check if version is less than next minor version.
		$next_minor = $required_major . '.' . ( $required_minor + 1 ) . '.0';
		$lt_next_minor = version_compare( $version, $next_minor, '<' );

		$compatible = $gte && $lt_next_minor;

		return array(
			'compatible' => $compatible,
			'message' => $compatible
				? "Version {$version} satisfies tilde constraint ~{$required} (>= {$required} and < {$next_minor})"
				: "Version {$version} does not satisfy tilde constraint ~{$required} (>= {$required} and < {$next_minor})",
		);
	}

	/**
	 * Check direct comparison operators.
	 *
	 * @param string $operator The comparison operator.
	 * @param string $required The required version.
	 * @param string $version The version to check.
	 * @return array{compatible:bool, message:string} ['compatible' => bool, 'message' => string]
	 */
	private static function check_direct_comparison( string $operator, string $required, string $version ): array {
		$compatible = version_compare( $version, $required, $operator );

		return array(
			'compatible' => $compatible,
			'message' => $compatible
				? "Version {$version} satisfies constraint {$operator}{$required}"
				: "Version {$version} does not satisfy constraint {$operator}{$required}",
		);
	}
}
