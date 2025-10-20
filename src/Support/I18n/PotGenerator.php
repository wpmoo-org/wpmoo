<?php
/**
 * Lightweight POT generator tailored for WPMoo projects.
 *
 * @package WPMoo\Support\I18n
 * @since 0.2.0
 */

namespace WPMoo\Support\I18n;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scans PHP sources for translation functions and writes a POT file.
 */
class PotGenerator {

	/**
	 * Text domain that should be captured.
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * Base path used to shorten file references.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Collected translation entries keyed by message signature.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected $entries = array();

	/**
	 * Supported translation functions metadata.
	 *
	 * @var array<string, array<string, int|string>>
	 */
	protected $function_meta = array(
		'__'            => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'_e'            => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'esc_html__'    => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'esc_attr__'    => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'esc_html_e'    => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'esc_attr_e'    => array(
			'type'          => 'single',
			'domain_index'  => 1,
		),
		'translate'     => array(
			'type'          => 'single',
		),
		'_x'            => array(
			'type'          => 'context',
			'context_index' => 1,
			'domain_index'  => 2,
		),
		'_ex'           => array(
			'type'          => 'context',
			'context_index' => 1,
			'domain_index'  => 2,
		),
		'esc_html_x'    => array(
			'type'          => 'context',
			'context_index' => 1,
			'domain_index'  => 2,
		),
		'esc_attr_x'    => array(
			'type'          => 'context',
			'context_index' => 1,
			'domain_index'  => 2,
		),
		'_n'            => array(
			'type'          => 'plural',
			'plural_index'  => 1,
			'domain_index'  => 3,
		),
		'_n_noop'       => array(
			'type'          => 'plural',
			'plural_index'  => 1,
			'domain_index'  => 2,
		),
		'_nx'           => array(
			'type'          => 'plural_context',
			'plural_index'  => 1,
			'context_index' => 2,
			'domain_index'  => 4,
		),
		'_nx_noop'      => array(
			'type'          => 'plural_context',
			'plural_index'  => 1,
			'context_index' => 2,
			'domain_index'  => 3,
		),
	);

	/**
	 * Constructor.
	 *
	 * @param string $domain    Text domain slug.
	 * @param string $base_path Base path used for relative references.
	 */
	public function __construct( $domain, $base_path ) {
		$this->domain    = $domain;
		$this->base_path = rtrim( $base_path, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Scan a source directory and write the POT file.
	 *
	 * @param string $source      Source directory (absolute or relative).
	 * @param string $destination Destination POT file path.
	 * @return bool
	 */
	public function generate( $source, $destination ) {
		$resolved_source = realpath( $source );

		if ( false === $resolved_source ) {
			return false;
		}

		$this->entries = array();
		$this->scan_directory( $resolved_source );

		return $this->write_pot( $destination );
	}

	/**
	 * Recursively scan the directory for PHP files.
	 *
	 * @param string $directory Absolute directory path.
	 * @return void
	 */
	protected function scan_directory( $directory ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$directory,
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
				$this->scan_file( $file->getPathname() );
			}
		}
	}

	/**
	 * Tokenize and analyse a PHP file.
	 *
	 * @param string $file_path Absolute file path.
	 * @return void
	 */
	protected function scan_file( $file_path ) {
		$contents = @file_get_contents( $file_path );

		if ( false === $contents ) {
			return;
		}

		$tokens                   = token_get_all( $contents );
		$token_count              = count( $tokens );
		$last_translator_comment  = null;

		for ( $index = 0; $index < $token_count; $index++ ) {
			$token = $tokens[ $index ];

			if ( is_array( $token ) ) {
				if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
					if ( false !== stripos( $token[1], 'translators:' ) ) {
						$last_translator_comment = $this->normalize_comment( $token[1] );
					}
					continue;
				}

				if ( T_STRING !== $token[0] ) {
					continue;
				}

				$function = $token[1];

				if ( ! isset( $this->function_meta[ $function ] ) ) {
					continue;
				}

				if ( $this->is_method_call( $tokens, $index ) && 'translate' !== $function ) {
					$last_translator_comment = null;
					continue;
				}

				$arguments = $this->extract_arguments( $tokens, $index );

				if ( empty( $arguments ) ) {
					$last_translator_comment = null;
					continue;
				}

				$meta = $this->function_meta[ $function ];

				if ( ! $this->domain_matches( $arguments, $meta ) ) {
					$last_translator_comment = null;
					continue;
				}

				$this->record_entry(
					$meta,
					$arguments,
					$file_path,
					$token[2],
					$last_translator_comment
				);

				$last_translator_comment = null;
			}
		}
	}

	/**
	 * Determine whether the current token is used as a method or static call.
	 *
	 * @param array<int, mixed> $tokens Token stream.
	 * @param int               $index  Current index.
	 * @return bool
	 */
	protected function is_method_call( array $tokens, $index ) {
		$previous = $this->previous_meaningful_token( $tokens, $index );

		if ( null === $previous ) {
			return false;
		}

		if ( is_string( $previous ) ) {
			return '->' === $previous || '::' === $previous;
		}

		return in_array(
			$previous[0],
			array( T_OBJECT_OPERATOR, T_DOUBLE_COLON ),
			true
		);
	}

	/**
	 * Get the previous non-whitespace token.
	 *
	 * @param array<int, mixed> $tokens Token stream.
	 * @param int               $index  Current index.
	 * @return mixed|null
	 */
	protected function previous_meaningful_token( array $tokens, $index ) {
		for ( $i = $index - 1; $i >= 0; $i-- ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}

				return $token;
			}

			if ( '' === trim( $token ) ) {
				continue;
			}

			return $token;
		}

		return null;
	}

	/**
	 * Extract the argument tokens for the current function call.
	 *
	 * @param array<int, mixed> $tokens Token stream.
	 * @param int               $index  Index positioned on the function name.
	 * @return array<int, array<int, mixed>>
	 */
	protected function extract_arguments( array &$tokens, &$index ) {
		$arguments    = array();
		$token_count  = count( $tokens );
		$position     = $index + 1;

		// Find the opening parenthesis.
		while ( $position < $token_count ) {
			$token = $tokens[ $position ];

			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$position++;
					continue;
				}

				return array();
			}

			if ( '(' === $token ) {
				$position++;
				break;
			}

			return array();
		}

		$depth      = 1;
		$current    = array();

		for ( ; $position < $token_count; $position++ ) {
			$token = $tokens[ $position ];

			if ( is_string( $token ) ) {
				if ( '(' === $token ) {
					$depth++;
					$current[] = $token;
					continue;
				}

				if ( ')' === $token ) {
					$depth--;

					if ( 0 === $depth ) {
						if ( ! empty( $current ) ) {
							$arguments[] = $current;
						}
						$index = $position;
						return $arguments;
					}

					$current[] = $token;
					continue;
				}

				if ( ',' === $token && 1 === $depth ) {
					$arguments[] = $current;
					$current     = array();
					continue;
				}

				$current[] = $token;
				continue;
			}

			$current[] = $token;
		}

		return array();
	}

	/**
	 * Check domain compatibility for a translation call.
	 *
	 * @param array<int, array<int, mixed>> $arguments Parsed argument tokens.
	 * @param array<string, mixed>          $meta      Function metadata.
	 * @return bool
	 */
	protected function domain_matches( array $arguments, array $meta ) {
		if ( ! isset( $meta['domain_index'] ) ) {
			return true;
		}

		$domain_index = (int) $meta['domain_index'];

		if ( ! isset( $arguments[ $domain_index ] ) ) {
			return true;
		}

		$domain = $this->extract_string( $arguments[ $domain_index ] );

		if ( null === $domain ) {
			return true;
		}

		return $domain === $this->domain;
	}

	/**
	 * Record a translation entry.
	 *
	 * @param array<string, mixed>          $meta      Function metadata.
	 * @param array<int, array<int, mixed>> $arguments Argument tokens.
	 * @param string                        $file_path Source file path.
	 * @param int                           $line      Line number.
	 * @param string|null                   $comment   Translator comment.
	 * @return void
	 */
	protected function record_entry( array $meta, array $arguments, $file_path, $line, $comment ) {
		$singular = $this->extract_string( $arguments[0] ?? array() );

		if ( null === $singular ) {
			return;
		}

		$plural  = null;
		$context = null;

		if ( isset( $meta['plural_index'] ) ) {
			$plural = $this->extract_string( $arguments[ (int) $meta['plural_index'] ] ?? array() );

			if ( null === $plural ) {
				return;
			}
		}

		if ( isset( $meta['context_index'] ) ) {
			$context = $this->extract_string( $arguments[ (int) $meta['context_index'] ] ?? array() );

			if ( null === $context ) {
				return;
			}
		}

		$this->add_entry(
			$singular,
			$plural,
			$context,
			$file_path,
			$line,
			$comment
		);
	}

	/**
	 * Extract a string literal (optionally concatenated) from tokens.
	 *
	 * @param array<int, mixed> $tokens Argument tokens.
	 * @return string|null
	 */
	protected function extract_string( array $tokens ) {
		if ( empty( $tokens ) ) {
			return null;
		}

		$value = '';

		foreach ( $tokens as $token ) {
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}

				if ( T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
					$segment = $this->interpret_literal( $token[1] );

					if ( null === $segment ) {
						return null;
					}

					$value .= $segment;
					continue;
				}

				return null;
			}

			if ( '.' === $token ) {
				continue;
			}

			if ( '(' === $token || ')' === $token ) {
				continue;
			}

			return null;
		}

		return '' === $value ? null : $value;
	}

	/**
	 * Decode a PHP string literal into its actual value.
	 *
	 * @param string $literal PHP literal, including quotes.
	 * @return string|null
	 */
	protected function interpret_literal( $literal ) {
		if ( '' === $literal ) {
			return null;
		}

		$quote = $literal[0];
		$body  = substr( $literal, 1, -1 );

		if ( '"' === $quote ) {
			if ( false !== strpos( $body, '$' ) ) {
				return null;
			}

			$body = str_replace( array( "\r\n", "\r" ), "\n", $body );

			return stripcslashes( $body );
		}

		if ( '\'' === $quote ) {
			$body = str_replace( array( '\\\\', '\\\'' ), array( '\\', '\'' ), $body );

			return $body;
		}

		return null;
	}

	/**
	 * Add or merge a translation entry.
	 *
	 * @param string      $singular Singular string.
	 * @param string|null $plural   Plural string (if any).
	 * @param string|null $context  Translation context.
	 * @param string      $file     Absolute file path.
	 * @param int         $line     Line number.
	 * @param string|null $comment  Translator comment.
	 * @return void
	 */
	protected function add_entry( $singular, $plural, $context, $file, $line, $comment ) {
		$key = implode(
			"\004",
			array(
				(string) $context,
				$singular,
				(string) $plural,
			)
		);

		if ( ! isset( $this->entries[ $key ] ) ) {
			$this->entries[ $key ] = array(
				'msgid'        => $singular,
				'msgid_plural' => $plural,
				'msgctxt'      => $context,
				'references'   => array(),
				'comments'     => array(),
			);
		}

		$reference = $this->relative_path( $file ) . ':' . $line;

		if ( ! in_array( $reference, $this->entries[ $key ]['references'], true ) ) {
			$this->entries[ $key ]['references'][] = $reference;
		}

		if ( $comment ) {
			$this->entries[ $key ]['comments'][ $comment ] = $comment;
		}
	}

	/**
	 * Write the accumulated entries to a POT file.
	 *
	 * @param string $destination Destination path.
	 * @return bool
	 */
	protected function write_pot( $destination ) {
		$handle = @fopen( $destination, 'w' );

		if ( false === $handle ) {
			return false;
		}

		$header_lines = array(
			'Project-Id-Version: ' . $this->domain,
			'Report-Msgid-Bugs-To: ',
			'POT-Creation-Date: ' . gmdate( 'Y-m-d H:i+0000' ),
			'PO-Revision-Date: YEAR-MO-DA HO:MI+0000',
			'Last-Translator: ',
			'Language-Team: ',
			'Language: ',
			'MIME-Version: 1.0',
			'Content-Type: text/plain; charset=UTF-8',
			'Content-Transfer-Encoding: 8bit',
			'X-Generator: WPMoo CLI',
			'X-Domain: ' . $this->domain,
		);

		$header = implode( "\n", $header_lines ) . "\n";

		fwrite( $handle, "msgid \"\"\n" );
		fwrite( $handle, "msgstr \"\"\n" );
		fwrite( $handle, $this->format_po_string( $header ) . "\n\n" );

		uksort(
			$this->entries,
			function ( $a, $b ) {
				return strcmp( $a, $b );
			}
		);

		foreach ( $this->entries as $entry ) {
			if ( ! empty( $entry['comments'] ) ) {
				foreach ( $entry['comments'] as $comment ) {
					fwrite( $handle, '#. ' . $comment . "\n" );
				}
			}

			if ( ! empty( $entry['references'] ) ) {
				fwrite(
					$handle,
					'#: ' . implode( ' ', $entry['references'] ) . "\n"
				);
			}

			if ( $entry['msgctxt'] ) {
				fwrite(
					$handle,
					'msgctxt ' . $this->format_po_string( $entry['msgctxt'] ) . "\n"
				);
			}

			fwrite(
				$handle,
				'msgid ' . $this->format_po_string( $entry['msgid'] ) . "\n"
			);

			if ( $entry['msgid_plural'] ) {
				fwrite(
					$handle,
					'msgid_plural ' . $this->format_po_string( $entry['msgid_plural'] ) . "\n"
				);
				fwrite( $handle, "msgstr[0] \"\"\n" );
				fwrite( $handle, "msgstr[1] \"\"\n\n" );
			} else {
				fwrite( $handle, "msgstr \"\"\n\n" );
			}
		}

		fclose( $handle );

		return true;
	}

	/**
	 * Format a string for PO output (handles escaping and newlines).
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	protected function format_po_string( $text ) {
		$text   = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$lines  = explode( "\n", $text );
		$output = array();

		foreach ( $lines as $index => $line ) {
			$escaped = str_replace(
				array( '\\', '"' ),
				array( '\\\\', '\\"' ),
				$line
			);

			$escaped = str_replace( "\t", '\\t', $escaped );

			if ( $index < count( $lines ) - 1 ) {
				$escaped .= '\\n';
			}

			$output[] = '"' . $escaped . '"';
		}

		if ( empty( $output ) ) {
			$output[] = '""';
		}

		return implode( "\n", $output );
	}

	/**
	 * Normalize a translator comment into a single line.
	 *
	 * @param string $comment Raw comment text.
	 * @return string
	 */
	protected function normalize_comment( $comment ) {
		$comment = str_replace( array( '/*', '*/' ), '', $comment );

		$lines = preg_split( '/\r\n|\r|\n/', $comment );
		$clean = array();

		foreach ( $lines as $line ) {
			$line = preg_replace( '/^\s*\/{2}/', '', $line );
			$line = preg_replace( '/^\s*\*/', '', $line );
			$line = preg_replace( '/^\s*#/', '', $line );
			$clean[] = trim( $line );
		}

		$text = trim( implode( ' ', $clean ) );
		$text = preg_replace( '/^translators:\s*/i', '', $text );

		return trim( $text );
	}

	/**
	 * Convert an absolute path into a path relative to the base directory.
	 *
	 * @param string $path Absolute path.
	 * @return string
	 */
	protected function relative_path( $path ) {
		$normalized_base = str_replace( '\\', '/', $this->base_path );
		$normalized_path = str_replace( '\\', '/', $path );

		if ( 0 === strpos( $normalized_path, $normalized_base ) ) {
			return ltrim(
				substr( $normalized_path, strlen( $normalized_base ) ),
				'/'
			);
		}

		return $path;
	}
}
