<?php
/**
 * Minimal HTML to Markdown converter for llms-full.txt.
 *
 * @link       https://techbysh.com
 * @since      2.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal HTML to Markdown converter.
 *
 * Covers the elements that appear in post content: headings, paragraphs,
 * lists, links, emphasis, quotes, code, images and simple tables. Falls back
 * to plain text when the PHP DOM extension is unavailable.
 *
 * @since      2.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Markdown {

	/**
	 * Elements whose content is never useful as text.
	 *
	 * @since    2.0.0
	 * @var      array
	 */
	private static $skip_tags = array( 'script', 'style', 'noscript', 'iframe', 'form', 'button', 'input', 'select', 'textarea', 'svg', 'canvas', 'template' );

	/**
	 * Address relative links are resolved against during a conversion.
	 *
	 * @since    2.0.0
	 * @var      string
	 */
	private static $base_url = '';

	/**
	 * Convert an HTML fragment to Markdown.
	 *
	 * @since    2.0.0
	 * @param    string    $html        HTML fragment.
	 * @param    string    $base_url    Address of the page the HTML comes from, for relative
	 *                                  links such as "page-2". Defaults to the home URL.
	 * @return   string
	 */
	public static function from_html( $html, $base_url = '' ) {
		$html = trim( (string) $html );
		if ( '' === $html ) {
			return '';
		}

		self::$base_url = '' !== $base_url ? (string) $base_url : home_url( '/' );

		if ( ! class_exists( 'DOMDocument' ) ) {
			return self::plain_text( $html );
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();
		$loaded   = $dom->loadHTML( '<?xml encoding="utf-8" ?><div id="tavc-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return self::plain_text( $html );
		}

		$root = null;
		foreach ( $dom->childNodes as $node ) {
			if ( $node instanceof DOMElement ) {
				$root = $node;
				break;
			}
		}

		if ( ! $root ) {
			return self::plain_text( $html );
		}

		return self::tidy( self::render_children( $root ) );
	}

	/**
	 * Render all child nodes.
	 *
	 * @since    2.0.0
	 * @param    DOMNode    $node    Parent.
	 * @return   string
	 */
	private static function render_children( $node ) {
		$output = '';
		foreach ( $node->childNodes as $child ) {
			$output .= self::render_node( $child );
		}

		return $output;
	}

	/**
	 * Render one node.
	 *
	 * @since    2.0.0
	 * @param    DOMNode    $node    Node.
	 * @return   string
	 */
	private static function render_node( $node ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			return preg_replace( '/\s+/u', ' ', $node->nodeValue );
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag = strtolower( $node->nodeName );

		if ( in_array( $tag, self::$skip_tags, true ) ) {
			return '';
		}

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				// Post titles are level 2 in llms-full.txt, so content headings start at level 3.
				$level = min( 6, intval( substr( $tag, 1 ) ) + 2 );
				$text  = trim( self::inline_text( $node ) );
				return '' === $text ? '' : "\n\n" . str_repeat( '#', $level ) . ' ' . $text . "\n\n";

			case 'p':
			case 'figcaption':
				return "\n\n" . trim( self::render_children( $node ) ) . "\n\n";

			case 'br':
				return "\n";

			case 'hr':
				return "\n\n---\n\n";

			case 'strong':
			case 'b':
				$text = trim( self::render_children( $node ) );
				return '' === $text ? '' : '**' . $text . '**';

			case 'em':
			case 'i':
				$text = trim( self::render_children( $node ) );
				return '' === $text ? '' : '*' . $text . '*';

			case 'code':
				if ( $node->parentNode && 'pre' === strtolower( $node->parentNode->nodeName ) ) {
					return $node->textContent;
				}
				return '`' . trim( $node->textContent ) . '`';

			case 'pre':
				return "\n\n```\n" . rtrim( $node->textContent ) . "\n```\n\n";

			case 'a':
				$text = trim( self::render_children( $node ) );
				$href = self::absolute_url( $node->getAttribute( 'href' ) );
				if ( '' === $href || 0 === strpos( $href, '#' ) ) {
					return $text;
				}
				return '' === $text ? '' : '[' . self::escape_brackets( $text ) . '](' . $href . ')';

			case 'img':
				$src = self::absolute_url( $node->getAttribute( 'src' ) );
				if ( '' === $src ) {
					return '';
				}
				return '![' . self::escape_brackets( trim( $node->getAttribute( 'alt' ) ) ) . '](' . $src . ')';

			case 'ul':
			case 'ol':
				return "\n\n" . self::render_list( $node, 'ol' === $tag ) . "\n\n";

			case 'blockquote':
				$inner = self::tidy( self::render_children( $node ) );
				$lines = explode( "\n", $inner );
				return "\n\n" . implode( "\n", array_map( array( __CLASS__, 'quote_line' ), $lines ) ) . "\n\n";

			case 'table':
				return "\n\n" . self::render_table( $node ) . "\n\n";

			default:
				$inner = self::render_children( $node );
				// Block-level containers get their own paragraph.
				if ( in_array( $tag, array( 'div', 'section', 'article', 'figure', 'header', 'footer', 'aside', 'main', 'details', 'summary', 'dl', 'dt', 'dd' ), true ) ) {
					return "\n\n" . $inner . "\n\n";
				}
				return $inner;
		}
	}

	/**
	 * Render a list.
	 *
	 * @since    2.0.0
	 * @param    DOMElement    $list       ul or ol element.
	 * @param    bool          $ordered    Whether the list is ordered.
	 * @param    int           $depth      Nesting depth.
	 * @return   string
	 */
	private static function render_list( $list, $ordered, $depth = 0 ) {
		$lines  = array();
		$number = 1;
		$indent = str_repeat( '  ', $depth );

		foreach ( $list->childNodes as $item ) {
			if ( XML_ELEMENT_NODE !== $item->nodeType || 'li' !== strtolower( $item->nodeName ) ) {
				continue;
			}

			$text   = '';
			$nested = array();
			foreach ( $item->childNodes as $child ) {
				$child_tag = XML_ELEMENT_NODE === $child->nodeType ? strtolower( $child->nodeName ) : '';
				if ( 'ul' === $child_tag || 'ol' === $child_tag ) {
					$nested[] = self::render_list( $child, 'ol' === $child_tag, $depth + 1 );
				} else {
					$text .= self::render_node( $child );
				}
			}

			$marker  = $ordered ? $number . '.' : '-';
			$lines[] = $indent . $marker . ' ' . trim( preg_replace( '/\s+/u', ' ', $text ) );
			foreach ( $nested as $nested_list ) {
				$lines[] = $nested_list;
			}
			$number++;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Render a table as a Markdown pipe table.
	 *
	 * @since    2.0.0
	 * @param    DOMElement    $table    Table element.
	 * @return   string
	 */
	private static function render_table( $table ) {
		$rows = array();
		foreach ( $table->getElementsByTagName( 'tr' ) as $row ) {
			$cells = array();
			foreach ( $row->childNodes as $cell ) {
				if ( XML_ELEMENT_NODE === $cell->nodeType && in_array( strtolower( $cell->nodeName ), array( 'td', 'th' ), true ) ) {
					$cells[] = str_replace( '|', '\|', trim( preg_replace( '/\s+/u', ' ', self::render_children( $cell ) ) ) );
				}
			}
			if ( ! empty( $cells ) ) {
				$rows[] = $cells;
			}
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$columns = max( array_map( 'count', $rows ) );
		$lines   = array();
		foreach ( $rows as $index => $cells ) {
			$cells   = array_pad( $cells, $columns, '' );
			$lines[] = '| ' . implode( ' | ', $cells ) . ' |';
			if ( 0 === $index ) {
				$lines[] = '|' . str_repeat( ' --- |', $columns );
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Text of a node with inline formatting removed.
	 *
	 * @since    2.0.0
	 * @param    DOMNode    $node    Node.
	 * @return   string
	 */
	private static function inline_text( $node ) {
		return preg_replace( '/\s+/u', ' ', $node->textContent );
	}

	/**
	 * Prefix a line for a block quote.
	 *
	 * @since    2.0.0
	 * @param    string    $line    Line.
	 * @return   string
	 */
	private static function quote_line( $line ) {
		return '' === $line ? '>' : '> ' . $line;
	}

	/**
	 * Escape square brackets inside link text.
	 *
	 * @since    2.0.0
	 * @param    string    $text    Text.
	 * @return   string
	 */
	public static function escape_brackets( $text ) {
		return str_replace( array( '[', ']' ), array( '\[', '\]' ), $text );
	}

	/**
	 * Make a link absolute and safe; returns '' for unsupported schemes.
	 *
	 * @since    2.0.0
	 * @param    string    $url    URL from an attribute.
	 * @return   string
	 */
	private static function absolute_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( 0 === strpos( $url, '#' ) ) {
			return $url;
		}
		if ( 0 === strpos( $url, '//' ) ) {
			$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
		} elseif ( 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		} elseif ( ! preg_match( '/^[a-z][a-z0-9+.\-]*:/i', $url ) ) {
			// Relative to the page ("page-2", "?p=5"): resolve against its address,
			// otherwise esc_url_raw() would turn "page-2" into "http://page-2".
			$base = '' !== self::$base_url ? self::$base_url : home_url( '/' );
			if ( 0 === strpos( $url, '?' ) ) {
				$query_position = strpos( $base, '?' );
				$url            = ( false !== $query_position ? substr( $base, 0, $query_position ) : $base ) . $url;
			} else {
				$base = preg_replace( '/[?#].*$/', '', $base );
				$url  = substr( $base, 0, strrpos( $base, '/' ) + 1 ) . $url;
			}
		}

		$url = esc_url_raw( $url, array( 'http', 'https', 'mailto' ) );

		// Parentheses would end the Markdown link early.
		return str_replace( array( '(', ')', ' ' ), array( '%28', '%29', '%20' ), $url );
	}

	/**
	 * Collapse blank lines and trailing spaces.
	 *
	 * @since    2.0.0
	 * @param    string    $text    Text.
	 * @return   string
	 */
	private static function tidy( $text ) {
		$text = preg_replace( '/[ \t]+\n/u', "\n", $text );
		$text = preg_replace( '/\n[ \t]+(?=[^ \t\-\d>|])/u', "\n", $text );
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		return trim( $text );
	}

	/**
	 * Plain-text fallback.
	 *
	 * @since    2.0.0
	 * @param    string    $html    HTML.
	 * @return   string
	 */
	private static function plain_text( $html ) {
		$text = wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br/>', '<br />' ), "\n\n", $html ) );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		return self::tidy( preg_replace( '/[ \t]+/u', ' ', $text ) );
	}
}
