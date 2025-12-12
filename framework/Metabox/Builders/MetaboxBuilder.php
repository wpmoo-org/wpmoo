use WPMoo\Metabox\Interfaces\MetaboxInterface;

/**
 * Metabox builder.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class MetaboxBuilder implements MetaboxInterface {

	/**
	 * Metabox ID.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Metabox title.
	 *
	 * @var string
	 */	private string $title;

	/**
	 * Screen(s) on which to show the metabox.
	 *
	 * @var string|string[]
	 */
	private $screen;

	/**
	 * Context within the screen where the metabox should display.
	 *
	 * @var string
	 */
	private string $context = 'advanced'; // Default to 'advanced'.

	/**
	 * Priority within the context where the metabox should display.
	 *
	 * @var string
	 */
	private string $priority = 'default'; // Default to 'default'.

	/**
	 * Content (fields or other components) of the metabox.
	 *
	 * @var array<mixed>
	 */
	private array $content = array();

	/**
	 * MetaboxBuilder constructor.
	 *
	 * @param string $id Metabox ID.
	 * @param string $title Metabox title.
	 */
	public function __construct( string $id, string $title ) {

		$this->id = $id;

		$this->title = $title;
	}

	/**
	 * Set the ID of the metabox.
	 *
	 * @param string $id Metabox ID.
	 * @return self
	 */
	public function id( string $id ): self {

		$this->id = $id;

		return $this;
	}

	/**
	 * Set the title of the metabox.
	 *
	 * @param string $title Metabox title.
	 * @return self
	 */
	public function title( string $title ): self {

		$this->title = $title;

		return $this;
	}

	/**
	 * Set the screen(s) on which to show the metabox.
	 *
	 * @param string|string[] $screen Screen(s) on which to show the metabox.
	 * @return self
	 */
	public function screen( $screen ): self {

		$this->screen = $screen;

		return $this;
	}

	/**
	 * Set the context within the screen where the metabox should display.
	 *
	 * @param string $context Context within the screen where the metabox should display.
	 * @return self
	 */
	public function context( string $context ): self {

		$this->context = $context;

		return $this;
	}

	/**
	 * Set the priority within the context where the metabox should display.
	 *
	 * @param string $priority Priority within the context where the metabox should display.
	 * @return self
	 */
	public function priority( string $priority ): self {

		$this->priority = $priority;

		return $this;
	}

	/**
	 * Add content (fields or other components) to the metabox.
	 *
	 * @param array<mixed> $content Content (fields or other components) of the metabox.
	 * @return self
	 */
	public function content( array $content ): self {

		$this->content = $content;

		return $this;
	}

	/**
	 * Get the ID of the metabox.
	 *
	 * @return string
	 */
	public function get_id(): string {

		return $this->id;
	}

	/**
	 * Get the title of the metabox.
	 *
	 * @return string
	 */
	public function get_title(): string {

		return $this->title;
	}

	/**
	 * Get the screen(s) of the metabox.
	 *
	 * @return string|string[]
	 */
	public function get_screen() {

		return $this->screen;
	}

	/**
	 * Get the context of the metabox.
	 *
	 * @return string
	 */
	public function get_context(): string {

		return $this->context;
	}

	/**
	 * Get the priority of the metabox.
	 *
	 * @return string
	 */
	public function get_priority(): string {

		return $this->priority;
	}

	/**
	 * Get the content (fields or other components) of the metabox.
	 *
	 * @return array<mixed>
	 */
	public function get_content(): array {

		return $this->content;
	}
}
