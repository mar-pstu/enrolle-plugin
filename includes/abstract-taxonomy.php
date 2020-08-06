<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuAbstractTaxonomy' ) ) return;


/**
	*	
	*/
abstract class pstuAbstractTaxonomy {


	use pstuEnrolleeTrait;


	protected $slug = 'pstu_taxonomy';


	protected $name = 'pstu_taxonomy';


	protected $object_type = array();


	protected $fields = array();


	public function __construct() {
		add_action( 'admin_enqueue_scripts',                             array( $this, 'admin_enqueue' ),       10, 0 );
		add_action( 'wp_enqueue_scripts',                                array( $this, 'wp_enqueue' ),          10, 0 );
		add_action( 'pre_get_terms',                                     array( $this, 'column_views' ),        10, 1 );
		add_action( "{$this->name}_add_form_fields",                     array( $this, 'add_fields' ),          10, 1 );
		add_action( "{$this->name}_edit_form_fields",                    array( $this, 'edit_fields' ),         10, 2 );
		add_action( "created_{$this->name}",                             array( $this, 'save_taxonomy' ),       10, 2 );
		add_action( "edited_{$this->name}",                              array( $this, 'save_taxonomy' ),       10, 2 );
		add_filter( "manage_edit-{$this->name}_columns",                 array( $this, 'columns' ),             10, 1 );
		add_filter( "manage_{$this->name}_custom_column",                array( $this, 'custom_column' ),       10, 3 );
		add_filter( "manage_edit-{$this->name}_sortable_columns",        array( $this, 'sortable_columns' ),    10, 1 );
	}


	/**
	 *
	*/
	public function get( $name = 'name' ) {
		return ( in_array( $name, array( 'slug', 'name', 'object_type', 'fields' ) ) ) ? $this->$name : false;
	}


	/**
	 *	Метабокс в виде выпадающего списка
	*/
	public function meta_box_select( $post, $box ) {
		$terms = get_terms( array(
			'taxonomy'      => $this->name,
			'orderby'       => 'name', 
			'order'         => 'ASC',
			'get'           => 'all',
		) );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$result[] = sprintf( '<div id="taxonomy-%1$s" class="selectdiv">', $this->name );
			$result[] = sprintf( '  <select name="tax_input[%1$s][]" class="widefat"><option value="0"></option>', $this->name );
			foreach ( $terms as $term ) $result[] = sprintf(
				'<option id="taxonomy_term_%1$s" value="%2$s" %3$s>%4$s</option>',
				$term->term_id,
				$term->slug,
				selected( is_object_in_term( $post->ID, $this->name, $term->term_id ), true, false ),
				$term->name
			);
			$result[] = '  </select>';
			$result[] = '</div>';
		} else {
			$result[] = __( 'Термины не найдены', 'pstu-enrollee' );
		}
		echo implode( "\r\n", $result );
	}


	/**
	 *	Список checkbox без возможности редактировать
	*/
	public function meta_box_checkbox( $post, $box ) {
		// wp_enqueue_script( 'blocksit' );
		// wp_add_inline_script( "blocksit", "jQuery( '#taxonomy-{$this->name}' ).BlocksIt( { numOfCol: 3, offsetX: 5, offsetY: 5, blockElement: '.checkboxlist-{$this->name}-item'} )", "after" );
		$result = array();
		$terms = get_terms( array(
			'taxonomy'      => $this->name,
			'orderby'       => 'name', 
			'order'         => 'ASC',
			'get'           => 'all',
		) );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$result[] = sprintf( '<ul class="categorychecklist form-no-clear" id="%1$schecklist" data-wp-lists="list:%1$s"><input type="hidden" name="tax_input[%1$s][]" value="0" checked="checked">', $this->name );
			foreach ( $terms as $term ) $result[] = sprintf(
				'<li style="margin: 5px; display: inline-block;" id="%6$s-%1$s"><label class="selectit" for="in-%6$s-%1$s"><input type="checkbox" name="tax_input[%6$s][]" id="in-%6$s-%1$s" value="%2$s" %3$s> %4$s %5$s</label></li>',
				$term->term_id,
				$term->slug,
				checked( is_object_in_term( $post->ID, $this->name, $term->term_id ), true, false ),
				$term->name,
				( empty( trim( $term->description ) ) ) ? '' : '<i>(' . $term->description . ')</i>',
				$this->name
			);
			$result[] = '</ul>';
		} else {
			$result[] = __( 'Термины не найдены', 'pstu-enrollee' );
		}
		echo implode( "\r\n", $result );
	}


	/**
		*	Регистраиця стилей и скриптов для админки
		*/
	public function admin_enqueue() {
		// wp_register_script(
		// 	'blocksit',
		// 	PSTU_ENROLLEE_URL . 'assets/scripts/blocksit.js',
		// 	array( 'jquery' ),
		// 	filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/blocksit.js' ),
		// 	true
		// );
		wp_register_script(
			'select2',
			PSTU_ENROLLEE_URL . 'assets/scripts/select2.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/select2.js' ),
			true
		);
		wp_register_style(
			'select2',
			PSTU_ENROLLEE_URL . 'assets/css/select2.css',
			array(),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/css/select2.css' ),
			'all'
		);
	}


	/**
	 *
	 */
	public function wp_enqueue() {}	


	/**
		*	Добавление пустого поля при создании таксономии
		*/
	public function add_fields() {
		$result = array();
		foreach ( $this->fields as $field ) $result[] = sprintf(
			'<div class="form-field term-group-wrap %1$s">%2$s%3$s</div>',
			( $field->get( 'required' ) ) ? 'form-required' : '',
			$field->get_label(),
			$field->get_control()
		);
		echo implode( "\r\n", $result );
	}


	/**
	 *	Добавление поля при редактировании
	 */
	public function edit_fields( $term, $taxonomy ) {
		$result = array();
		foreach ( $this->fields as $field ) {
			$field->set( 'value', get_term_meta( $term->term_id, $field->get( 'name' ), true ) );
			$result[] = sprintf(
				'<tr class="form-field term-group-wrap %1$s"><th scope="row">%2$s</th><td>%3$s</td></tr>',
				( $field->get( 'required' ) ) ? 'form-required' : '',
				$field->get_label(),
				$field->get_control()
			);
		}
		echo implode( "\r\n", $result );
	}


	/**
	 *	Сохранение полей
	 */
	public function save_taxonomy( $term_id, $tt_id ) {
		foreach ( $this->fields as $field ) {
			$key = $field->get( 'name' );
			if ( ! empty( $_POST[ $key ] ) ) {
				update_term_meta( $term_id, $key, $this->validate( $key, $field->validate( $_POST[ $key ] ) ) );
			} elseif ( $field->get( 'required' ) ) {
				update_term_meta( $term_id, $key, $field->get( 'default' ) );
			} else {
				delete_term_meta( $term_id, $key );
			}
		}
	}


	/**
		*	Валидация настроек
		*/
	private function validate( $name, $value ) {
		return $value;
	}


	/**
	 *	Добавлние шапки столбца в таблице постов админки
	 */
	public function columns( $columns ) {
		foreach ( $this->fields as $field )
			if ( $field->get( 'column' ) ) {
				$columns[ $field->get( 'name' ) ] = $field->get( 'label' );
			}
		return $columns;
	}


	/**
	 *	Заполнение ячеек в таблице таксономии админки
	 */
	public function custom_column( $out, $column_name, $term_id ) {
		foreach ( $this->fields as $field ) {
			if ( $column_name == $field->get( 'name' ) ) $out = get_term_meta( $term_id, $column_name, true );
		}
		return $out;
	}


	/**
		*	Добавление возможности сортировки
		*/
	public function sortable_columns( $sortable_columns ) {
		foreach ( $this->fields as $field )
			if ( $field->get( 'sortable' ) ) {
				$sortable_columns[ $field->get( 'name' ) ] = $field->get( 'name' );
			}
		return $sortable_columns;
	}


	/**
		*	Изменения запроса для возможности сортировки
		*/
	public function column_views( $query ) {
		if( ! is_admin() ) return;
		foreach ( $this->fields as $field )
			if ( $field->get( 'sortable' ) ) {
				if ( isset( $_GET[ 'orderby' ] ) ) {
					$key = $field->get( 'name' );
					if( $key == $_GET[ 'orderby' ] ) $query->query_vars[ 'orderby' ] = $key;
				}
			}
	}



	/**
	* Фильтр заголовка
	*/
	public function post_title( $title, $post_id = '' ) {
		if ( empty( $post_id ) ) $post_id = get_the_ID();
		if ( get_post_type( $post_id ) == $this->name ) {
			$code = get_post_meta( $post_id, "{$this->slug}_code", true );
			if ( empty( $code ) ) {
				$specialties = get_the_terms( $post_id, 'specialties' );
				if ( is_array( $specialties ) ) {
					$code = get_term_meta( $specialties[ 0 ]->term_id, "pstu_specialties_code", true );
					if ( $code ) {
						add_post_meta( $post_id, "{$this->slug}_code", $code );
					}
				}
			}
			$title = sprintf(
				'%1$s %2$s',
				$code,
				$title
			);
		}
		return $title;
	}



}