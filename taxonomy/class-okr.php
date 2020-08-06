<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTaxonomyOKR' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuTaxonomyOKR {


	use pstuEnrolleeTrait;


	protected $name = 'okr';


	protected $slug = 'pstu_okr';


	protected $object_types = array( 'educational_program' );


	public function __construct() {
		add_action( 'init', array( $this, 'create_taxonomy' ) );
		add_filter( 'the_title', array( $this, 'post_title' ), 10, 3 );
		if ( is_admin() ) {
			add_action( 'save_post', array( $this, 'save' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );		
		}
	}


	public function create_taxonomy() {
		register_taxonomy(
			$this->name,
			$this->object_types,
			array(
				'label'                  => '',
				'labels'                 => array(
					'name'                 => __( 'ОКУ', 'pstu-enrollee' ),
					'singular_name'        => __( 'ОКУ', 'pstu-enrollee' ),
					'search_items'         => __( 'Найти запись', 'pstu-enrollee' ),
					'all_items'            => __( 'Все записи', 'pstu-enrollee' ),
					'view_item '           => __( 'Просмотр списка записей', 'pstu-enrollee' ),
					'parent_item'          => __( 'Родительская запись', 'pstu-enrollee' ),
					'parent_item_colon'    => __( 'Родительская запись:', 'pstu-enrollee' ),
					'edit_item'            => __( 'Редактировать запись', 'pstu-enrollee' ),
					'update_item'          => __( 'Обновить запись', 'pstu-enrollee' ),
					'add_new_item'         => __( 'Добавить новую запись', 'pstu-enrollee' ),
					'new_item_name'        => __( 'Добавить запись', 'pstu-enrollee' ),
					'menu_name'            => __( 'ОКУ', 'pstu-enrollee' ),
				),
				'description'            => '',
				'public'                 => true,
				'publicly_queryable'     => true,
				'show_ui'                => true,
				'show_in_menu'           => true,
				'show_in_nav_menus'      => false,
				'show_tagcloud'          => false,
				'show_in_rest'           => false,
				'rest_base'              => null,
				'hierarchical'           => false,
				'update_count_callback'  => '',
				'rewrite'                => true,
				//'query_var'              => $taxonomy, // название параметра запроса
				'capabilities'           => array(),
				// 'meta_box_cb'            => 'post_categories_meta_box',
				'meta_box_cb'            => false,
				'show_admin_column'      => true, // Позволить или нет авто-создание колонки таксономии в таблице ассоциированного типа записи. (с версии 3.5)
				'_builtin'               => false,
				'show_in_quick_edit'     => false, // по умолчанию значение show_ui
			)
		);
	}


	/**
	*	Сохранение поста
	*/
	public function save( $post_id ) {
		// проверяем существует ли nonce-поле, если нет - выходим
		if ( ! isset( $_POST[ "{$this->slug}_nonce" ] ) ) return;

		// проверяем значение nonce-поля, если не совпадает - выходим
		if ( ! wp_verify_nonce( $_POST[ "{$this->slug}_nonce" ], $this->slug ) ) {
			wp_nonce_ays();
			return;
		}

		// исключаем автосохранение и ревизии
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;	

		// проверяем права пользователя
		if ( in_array( $_POST[ 'post_type' ], $this->object_types ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_nonce_ays();
			return;
		}

		if ( isset( $_POST[ $this->slug ] ) ) {
			wp_set_object_terms( $post_id, intval( sanitize_key( $_POST[ $this->slug ] ) ), $this->name, false );
		} else {
			$okrs = get_terms( array(
				'taxonomy'    => $this->name,
				'object_ids'  => $post_id,
				'fields'      => 'ids',
			) );
			if ( is_array( $okrs ) && ! empty( $okrs ) ) {
				wp_remove_object_terms( $post_id, $okrs, $this->name );
			}
		}

	}


	/**
	* Регистрация метабокса
	*/
	public function add_meta_box( $post_type ) {
		if ( in_array( $post_type, $this->object_types ) ) add_meta_box(
			$this->slug,
			__( 'ОКУ', 'pstu-enrollee' ),
			array( $this, 'render_content' ),
			$this->object_types,
			'side',
			'high',
			null
		);
	}





	/**
	*	Вывод метабокса
	*/
	public function render_content( $post ) {
		wp_nonce_field( $this->slug, "{$this->slug}_nonce" );
		$okr = get_the_terms( $post, $this->name );
		$value = '';
		if ( is_array( $okr ) && ! empty( $okr ) ) {
			$okr = array_shift( $okr );
			$value = $okr->term_id;
		}
		echo $this->get_dropdown( array(
			'current' => $value,
			'style'   => 'width: 100%;'
		) );
	}


	/**
	*	Список ОКР
	*/
	public function get_dropdown( $atts ) {
		$result = array();
		$atts = $this->atts( array(
			'current' => '',
			'id'      => $this->slug,
			'name'    => $this->slug,
			'style'   => '',
			'empty'   => '',
			'class'   => '',
		), $atts );
		$terms = get_terms( array(
			'taxonomy'   => $this->name,
			'hide_empty' => false,
			'fields'     => 'id=>name',
		) );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $id => $name ) {
				$result[] = sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$id,
					selected( $id, $atts[ 'current' ], false ),
					$name
				);
			}
		}
		return ( empty( $result ) ) ? '' : sprintf(
			'<select id="%1$s" name="%2$s" class="%3$s" style="%4$s"> <option>%6$s</option> %5$s </select>',
			$atts[ 'id' ],
			$atts[ 'name' ],
			$atts[ 'class' ],
			$atts[ 'style' ],
			implode( "\r\n", $result ),
			$atts[ 'empty' ]
		);
	}


	/**
	* Фильтр заголовка посто
	*/
	public function post_title( $title, $post_id = '', $apply = true ) {
		if ( ( $apply && in_array( get_post_type( $post_id ), $this->object_types ) ) || is_admin() ) {
			$okr = get_the_terms( $post_id, $this->name );
			if ( is_array( $okr ) && ! empty( $okr ) ) {
				$okr = array_shift( $okr );
				 $title = sprintf(
					'%1$s (%2$s)',
					$title,
					$okr->name
				);
			}
		}
		return $title;
	}


}