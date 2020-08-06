<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( trait_exists( 'pstuEnrolleeTrait' ) ) return;


trait pstuEnrolleeTrait {


	/**
	*	Выводит термин
	*/
	function num_decline( $number, $titles, $param2 = '', $param3 = '' ){
		if ( $param2 ) $titles = [ $titles, $param2, $param3 ];
		if ( is_string( $titles ) ) $titles = preg_split( '/, */', $titles );
		if ( empty( $titles[2] ) ) $titles[2] = $titles[1];
		$cases = [ 2, 0, 1, 1, 1, 2 ];
		$intnum = abs( intval( strip_tags( $number ) ) );
		return "$number ". $titles[ ( $intnum % 100 > 4 && $intnum % 100 < 20 ) ? 2 : $cases[ min( $intnum % 10, 5 ) ] ];
	}


	/**
	*
	*/
	function atts( $white, $atts ) {
		$atts = (array) $atts;
		$result  = array();
		foreach ( $white as $name => $default ) {
			if ( array_key_exists( $name, $atts ) ) {
				$result[ $name ] = $atts[ $name ];
			} else {
				$result[ $name ] = $default;
			}
		}
		return $result;
	}


	/**
	* Выводит шаблон для wp.template
	*/
	public function render_tmpl( $id, $path ) {
		if ( file_exists( $path ) ) printf(
			'<script type="text/html" id="tmpl-%1$s">%2$s</script>',
			$id,
			file_get_contents( $path )
		);
	}


	/**
	 *	Возвращает массив постоянных страницы
	 */
	function get_pages() {
		$pages = get_pages();
		$result = array();
		foreach ( $pages as $page ) {
			$result[ $page->ID ] = $page->post_title;
		}
		return $result;
	}


	/**
	 *
	*/
	function get_the_term_list( $post_id = false, $taxonomy = 'category' ) {
		if ( ! $post_id ) $post_id = get_the_ID();
		$result = array();
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$result[] = "<ul>";
			foreach ( $terms as $term ) $result[] = sprintf(
				'<li>%1$s %2$s</li>',
				apply_filters( 'single_term_title', $term->name ),
				( empty( trim( $term->description ) ) ) ? "" : "<small><i>({$term->description})</i></small>"
			);
			$result[] = "</ul>";
		} else {
			$result[] = "-";
		}
		return implode( "\r\n", $result );
	}


	function get_terms( $args = array() ) {
		$args = array_merge( array(
			'taxonomy'		=> 'category',
			'get'					=> 'all',
			'orderby'			=> 'id', 
			'order'				=> 'ASC',
			'hide_empty'	=> false, 
		), $args );
		$result = array( '' => '' );
		$terms = get_terms( $args );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			foreach ( $terms as $term )
				$result[ $term->term_id ] = $term->name;
		} else {
			$result = array( '-' => '-' );
		}
		return $result;
	}


	/**
		*
		*/
	function is_url( $uri ){
		if ( preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$uri ) ) {
			return $uri;
	    } else {
			return false;
		}
	}


	/**
	 *	Выводит настрокий
	 */
	function get_control( $args ) {
		$result = array();
		$options = get_option( $this->slug );
		switch ( $args[ 'type' ] ) {
			/**/
			case 'select':
				wp_enqueue_script( 'select2' );
				wp_enqueue_style( 'select2' );
				// wp_add_inline_script( "select2", "jQuery('#{$this->slug}_{$args['id']}').select2();", "after" );
				$result[] = sprintf(
					'<select id="%2$s_%1$s" name="%2$s[%1$s]"><option value="">%3$s</option>',
					$args[ 'id' ],
					$this->slug,
					__( 'Не выбрано', 'pstu-enrollee' )
				);
				foreach( $args[ 'vals' ] as $key => $value ) $result[] = sprintf(
					'<option value="%1$s" %3$s>%2$s</option>',
					$key,
					$value,
					selected( $key, $options[ $args[ 'id' ] ], false )
				);
				$result[] = '</select>';
				if ( ! empty( trim( $args[ 'desc' ] ) ) ) $result[] = "<p>{$args['desc']}</p>";
			break;
			/**/
		}
		echo implode( "\r\n", $result );
	}


	/**
		*
		*/
	static function get_translation_id( $post_id ) {
		$result = false;
		if ( defined( "POLYLANG_FILE" ) ) {
			if ( isset( $_GET[ 'from_post' ] ) ) {
				$result = $_GET[ 'from_post' ];
			}
		}
		return $result;
	}


	function ucfirst_utf8( $stri ) { 
		 if($stri{0}>="\xc3") 
		     return (($stri{1}>="\xa0")? 
		     ($stri{0}.chr(ord($stri{1})-32)): 
		     ($stri{0}.$stri{1})).substr($stri,2); 
		 else return ucfirst($stri); 
	}



	static function pstu_sprint_array( $relation, $items ) {
		switch ( $relation ) {
			case 'AND':
				return wp_printf( '%1$l', $items ); 
				break;
			case 'OR':
				return implode( __( ' или ', 'pstu-enrollee' ), $items );
				break;
			default:
				return implode( ", ", $items );
				break;
		}
	}


}