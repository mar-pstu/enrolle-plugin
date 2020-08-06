<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTypeCompetitiveOffers' ) ) return;


class pstuTypeCompetitiveOffers {


	use pstuEnrolleeTrait;


	protected $name = 'competitive_offers';


	protected $slug = 'pstu_competitive_offers';


	protected $taxonomies = array();


	protected $supports = array( 'excerpt' );


	public function __construct() {
		add_action( 'init', array( $this, 'creatre_post_type' ) );
		add_filter( 'the_title', array( $this, 'post_title' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'admin_table_filters_handler' ), 10, 1 );
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 10, 0 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 1 );
			add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
			add_action( 'save_post', array( __CLASS__, 'name_change' ), 10, 1 );
			add_filter( "manage_{$this->name}_posts_columns", array( $this, 'add_views_column' ), 4 );
			add_action( "manage_{$this->name}_posts_custom_column", array( $this, 'fill_views_column' ), 5, 2 );
			add_action( 'restrict_manage_posts', array( $this, 'add_admin_table_filters' ), 10, 1 );
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	}



	/**
	*	Вывод предупреждений и сообщений в админке
	*/
	public function notice() {
		$post_type = get_post_type();
		if ( $post_type == $this->name ) {
			$entries = get_posts( array(
				'numberposts'   => -1,
				'post_type'     => $this->name,
				'meta_query'    => array(
					'relation'      => 'OR',
					array(
						'key'           => 'pstu_educational_program',
						'value'         => '',
						'compare'       => '=',
					),
					array(
						'key'           => 'pstu_educational_program',
						'compare'       => 'NOT EXISTS',
					),
				),
			) );
			if ( $entries && ! empty( $entries ) && ! is_wp_error( $entries ) ) {
				$found = array();
				foreach ( $entries as $entry ) {
					$found[] = sprintf(
						'<a href="%1$s">%2$s</a>',
						get_edit_post_link( $entry->ID ),
						$entry->post_title
					);
				}
				echo wp_sprintf(
					'<div class="notice notice-warning"><p><b>%1$s</b> %2$s %3$l</p></div>',
					__( 'Предупреждение:' ),
					__( 'найдены конкурсные предложения, которые не прикреплены к образовальной программе: ', 'pstu-enrollee' ),
					$found
				);
			}
		}
	}



	/**
	*	Добавление фильтра в талицу админки
	*/
	public function add_admin_table_filters( $post_type ) {
		if ( $post_type == $this->name ) {
			$current_educational_program = isset( $_GET[ 'pstu_educational_program' ] ) ? $_GET[ 'pstu_educational_program' ] : '';
			echo $this->get_dropdown_educational_program( array( 'current' => $current_educational_program ) );
		}
	}



	/**
	* Изменение запроса для фильтра по образовательным программам
	*/
	public function admin_table_filters_handler( $query ) {
		if( is_admin() && $query->is_main_query() ) {
			$screen = get_current_screen();
			if ( empty( $screen->post_type ) || $screen->post_type != $this->name || $screen->id != 'edit-' . $this->name ) return;
			$current_educational_program = isset( $_GET[ 'pstu_educational_program' ] ) ? $_GET[ 'pstu_educational_program' ] : '';
			if ( ! empty( $current_educational_program ) ) {
				$query->set( 'meta_query', array( [
					'key' => 'pstu_educational_program',
					'value'=> sanitize_key( $current_educational_program )
				] ) );
			}
		}
	}


	/**
	* Возвращает выпадающий список образовательных программ
	*/
	protected function get_dropdown_educational_program( $atts ) {
		$atts = $this->atts( array(
			'current' => '',
			'id'      => 'pstu_educational_program',
			'name'    => 'pstu_educational_program',
			'style'   => ''
		), $atts );
		$result = array();
		wp_reset_query();
		$educational_programs = get_posts( array(
			'numberposts' => -1,
			'orderby'     => 'name',
			'order'       => 'DESC',
			'post_type'   => 'educational_program',

		) );
		if ( $educational_programs && ! empty( $educational_programs ) && ! is_wp_error( $educational_programs ) ) {
			$result[] = sprintf(
				'<select name="%1$s" id="%2$s" style="%3$s"><option value="">-</option>',
				$atts[ 'name' ],
				$atts[ 'id' ],
				$atts[ 'style' ]
			);
			foreach ( $educational_programs as $educational_program ) {
				$result[] = sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$educational_program->ID,
					selected( $educational_program->ID, $atts[ 'current' ], false ),
					apply_filters( 'the_title', $educational_program->post_title, $educational_program->ID )
				);
			}
			$result[] = '</select>';
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'select2' );
			wp_add_inline_script( 'select2', "jQuery( document ).ready( function() { jQuery( '#{$atts[ 'id' ]}' ).select2(); } );", 'after' );
		} else {
			$result[] = __( 'Образовательные программы не найдены', 'pstu-enrollee' );
		}
		return implode( "\r\n", $result );
	}



	/**
	*	Добавление колонки на страницу списка постов в админке
	*/
	public function add_views_column( $columns ) {
		$columns[ 'pstu_course' ] = __( 'Курс', 'pstu-enrollee' );
		return $columns;
	}



	/**
	*	Заполняем созданные колонки
	*/
	public function fill_views_column( $colname, $post_id ) {
		switch ( $colname ) {
			case 'pstu_course':
				echo get_post_meta( $post_id, 'pstu_course', true );
				break;
		}
	}



	/**
	 *	Регистрация нового типа
	 */
	public function creatre_post_type() {
		register_post_type(
			$this->name,
			array(
				'labels'                          => array(
					'name'                          => __( 'Конкурсные предложения', 'pstu-enrollee' ),
					'singular_name'                 => __( 'Конкурсное предложение', 'pstu-enrollee' ),
					'add_new'                       => __( 'Добавить новую запись', 'pstu-enrollee' ),
					'add_new_item'                  => __( 'Новое Конкурсное предложение', 'pstu-enrollee' ),
					'edit_item'                     => __( 'Редактировать запись', 'pstu-enrollee' ),
					'new_item'                      => __( 'Новое конкурсное предложение', 'pstu-enrollee' ),
					'all_items'                     => __( 'Все конкурсные предложения', 'pstu-enrollee' ),
					'view_item'                     => __( 'Просмотр записей на сайте', 'pstu-enrollee' ),
					'search_items'                  => __( 'Искать в списке', 'pstu-enrollee' ),
					'not_found'                     => __( 'Записи не найдены', 'pstu-enrollee' ),
					'not_found_in_trash'            => __( 'В корзине нет записей', 'pstu-enrollee' ),
					'menu_name'                     => __( 'Конкурсные предложения', 'pstu-enrollee' ),
				),
				'public'                          => true,
				'publicly_queryable'              => false,
				'show_ui'                         => true,
				'show_in_nav_menus'               => false,
				'show_in_admin_bar'               => false,
				'exclude_from_search'             => true,
				'has_archive'                     => false,
				'show_in_rest'                    => false,
				'hierarchical'                    => false,
				'capability_type'                 => 'post',
				'menu_icon'                       => 'dashicons-excerpt-view',
				'menu_position'                   => '52',
				'supports'                        => $this->supports,
				'taxonomies'                      => $this->taxonomies,
			)
		);
	}


	/**
	*	Добавление метабокса
	*/
	public function add_meta_box( $post_type ) {
		if ( $post_type == $this->name ) add_meta_box(
			$this->slug . '_educational_program',
			__( 'Опции конкурсного предложения', 'pstu-next-theme' ),
			array( $this, 'render_meta_box_content' ),
			$post_type,
			'side',
			'high',
			null
		);
	}


	/**
	*	Вывод контента метабокса на странице педактирования конкурсного предложения
	*/
	public function render_meta_box_content( $post ) {
		wp_nonce_field( $this->slug, "{$this->slug}_nonce" );
		$translation_id = $this->get_translation_id( $post->ID );
		// название как в ЕДБО
		$edbo_name = get_post_meta( $post->ID, 'pstu_edbo_name', true );
		if ( $translation_id && empty( $edbo_name ) ) $edbo_name = get_post_meta( $translation_id, 'pstu_edbo_name', true );
		// образовательная программа
		$current_educational_program = get_post_meta( $post->ID, 'pstu_educational_program', true );
		if ( $translation_id && function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) ) {
			$current_educational_program = pll_get_post( get_post_meta( $translation_id, 'pstu_educational_program', true ), pll_current_language( 'slug' ) );
			if ( ! $current_educational_program ) $current_educational_program = '';
		} 
		// курс
		$course = get_post_meta( $post->ID, 'pstu_course', true );
		if ( $translation_id && empty( $course ) ) $course = get_post_meta( $translation_id, 'pstu_course', true );
		// время обучения
		$training_term = get_post_meta( $post->ID, 'pstu_training_term', true );
		if ( $translation_id && empty( $training_term ) ) $training_term = get_post_meta( $translation_id, 'pstu_training_term', true );
		if ( ! is_array( $training_term ) ) $training_term = array();
		foreach ( array( 'years', 'months' ) as $key ) {
			if ( ! isset( $training_term[ $key ] ) ) $training_term[ $key ] = '';
		}
		?>
			
			<label for="pstu_edbo_name" style="display: block; font-weight: bold; text-align: left;"><?php _e( 'Полное название (ЕГБО)', 'pstu-enrollee' ); ?></label>
			<textarea id="pstu_edbo_name" style="text-align: left; width: 100%;" type="text" name="pstu_edbo_name"><?php echo $edbo_name; ?></textarea>
			<label style="display: block; font-weight: bold; text-align: left; margin-top: 10px;" for="pstu_educational_program"><?php _e( 'Образовательная программа', 'pstu-enrollee' ); ?></label>
			<?php echo $this->get_dropdown_educational_program( array( 'current' => $current_educational_program, 'style' => 'width: 100%;' ) ); ?>
			<table style="display: block; width: 100%; margin-top: 10px; border: none;">
				<caption style="display: block; font-weight: bold; text-align: left;"><?php _e( 'Срок обучения', 'pstu-enrollee' ); ?></caption>
				<tr>
					<td style="width: 30%; text-align: right;"><label for="pstu_training_term_years"><?php _e( 'годы', 'pstu-enrollee' ); ?></label></td>
					<td><input id="pstu_training_term_years" type="text" min="0" max="9" style="text-align: center; width: 100%;" name="pstu_training_term[years]" value="<?php echo $training_term[ 'years' ]; ?>"></td>
				</tr>
				<tr>
					<td style="width: 30%; text-align: right;"><label for="pstu_training_term_months"><?php _e( 'месяцы', 'pstu-enrollee' ); ?></label></td>
					<td><input id="pstu_training_term_months" type="text" min="0" max="12" style="text-align: center; width: 100%;" name="pstu_training_term[months]" value="<?php echo $training_term[ 'months' ]; ?>"></td>
				</tr>
			</table>
			<!-- курс -->
			<div style="margin-top: 10px; font-weight: bold; text-align: left;"><?php _e( 'Курс', 'pstu-enrollee' ); ?></div>
			<?php for ( $i=1; $i < 4; $i++ ) : ?>
				<label for="pstu_course-<?php echo $i; ?>" style="display: inline-block; margin: 2px 15px 2px 0px;">
					<input id="pstu_course-<?php echo $i; ?>" type="radio" name="pstu_course" value="<?php echo $i; ?>" <?php checked( $i, $course, true ); ?>> <?php echo $i; ?>
				</label>
			<?php endfor; ?>

		<?
		wp_enqueue_script(
			'maskedinput',
			PSTU_ENROLLEE_URL . '/assets/scripts/jquery.maskedinput.js',
			array( 'jquery' ),
			false,
			false
		);
		wp_enqueue_script(
			'select2',
			PSTU_ENROLLEE_URL . '/assets/scripts/select2.js',
			array( 'jquery' ),
			false,
			false
		);
		wp_enqueue_style(
			'select2',
			PSTU_ENROLLEE_URL . 'assets/css/select2.css',
			array(),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/css/select2.css' ),
			'all'
		);
		wp_add_inline_script( 'maskedinput', 'jQuery(function(){ jQuery("#pstu_training_term_years").mask("9",{placeholder:" "});});', 'after' );
		wp_add_inline_script( 'maskedinput', 'jQuery(function(){ jQuery("#pstu_training_term_months").mask("9?9",{placeholder:" "});});', 'after' );
		wp_add_inline_script( 'select2', 'jQuery( document ).ready( function() { jQuery( "#pstu_educational_program" ).select2(); } );', 'after' );
	}


	/**
	*	Фильт для изменение имени поста при сохранении
	*/
	public static function name_change( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( get_post_type( $post_id ) != 'competitive_offers' ) return;
		remove_action( 'save_post', array( __CLASS__, 'name_change' ), 10, 1 );
		wp_update_post( array(
			'ID' => $post_id,
			'post_title' => ( string ) $post_id
		) );
		add_action( 'save_post', array( __CLASS__, 'name_change' ), 10, 1 );
	}


	/**
	* Сохранение поста
	*/
	public function save_post( $post_id ) {
		// проверяем существует ли nonce-поле, если нет - выходим
		if ( ! isset( $_POST[ "{$this->slug}_nonce" ] ) ) {
			// wp_nonce_ays();
			return;
		}

		// проверяем значение nonce-поля, если не совпадает - выходим
		if ( ! wp_verify_nonce( $_POST[ "{$this->slug}_nonce" ], $this->slug ) ) {
			wp_nonce_ays();
			return;
		}

		// исключаем автосохранение и ревизии
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;	

		// проверяем права пользователя
		if ( 'page' == $_POST[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_nonce_ays();
			return;
		}

		if ( isset( $_POST[ 'pstu_edbo_name' ] ) ) {
			update_post_meta( $post_id, 'pstu_edbo_name', strip_tags( sanitize_textarea_field( $_POST[ 'pstu_edbo_name' ] ) ) );
		} else {
			delete_post_meta( $post_id, 'pstu_edbo_name' );
		}

		if ( isset( $_POST[ 'pstu_educational_program' ] ) ) {
			update_post_meta( $post_id, 'pstu_educational_program', sanitize_key( $_POST[ 'pstu_educational_program' ] ) );
		} else {
			delete_post_meta( $post_id, 'pstu_educational_program' );
		}

		if ( isset( $_POST[ 'pstu_training_term' ] ) ) {
			update_post_meta( $post_id, 'pstu_training_term', array(
				'years' => ( isset( $_POST[ 'pstu_training_term' ][ 'years' ] ) ) ? sanitize_key( $_POST[ 'pstu_training_term' ][ 'years' ] ) : '',
				'months' => ( isset( $_POST[ 'pstu_training_term' ][ 'months' ] ) ) ? sanitize_key( $_POST[ 'pstu_training_term' ][ 'months' ] ) : ''
			) );
		} else {
			delete_post_meta( $post_id, 'pstu_training_term' );
		}

		if ( isset( $_POST[ 'pstu_course' ] ) ) {
			$course = intval( sanitize_text_field( $_POST[ 'pstu_course' ] ) );
			if ( $course > 4 && $course < 1 ) $course = 1;
			update_post_meta( $post_id, 'pstu_course', $course );
		} else {
			delete_post_meta( $post_id, 'pstu_course' );
		}
	}


	/**
	 *	Регистрация стилей и скриптов
	 */
	public function admin_enqueue() {
		wp_register_script(
			'maskedinput',
			PSTU_ENROLLEE_URL . 'assets/scripts/jquery.maskedinput.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/jquery.maskedinput.js' ),
			true
		);
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
	 *	Изменение заголовка поста
	 */
	public function post_title( $title, $post_id = false ) {
		if ( $post_id && get_post_type( $post_id ) == $this->name ) {
			if ( is_admin() && ( ! wp_doing_ajax() ) ) {
				$educational_program_id = get_post_meta( $post_id, 'pstu_educational_program', true );
				$title = sprintf(
					'%1$s - #%2$s %3$s',
					( empty( $educational_program_id ) ) ? __( 'Образовательная программа не прикреплена', 'pstu-enrollee' ) : esc_html( get_the_title( $educational_program_id ) ),
					$title,
					( has_excerpt( $post_id ) ) ? $this->ucfirst_utf8( strip_tags( get_the_excerpt( $post_id ) ) ) : ''
				);
			} else {
				$title = '';
				$course = get_post_meta( $post_id, 'pstu_course', true );
				$form_studies = get_terms( array(
					'taxonomy'      => 'form_study',
					'object_ids'    => $post_id,
					'fields'        => 'names',
				) );
				if ( ! empty( $course ) ) $title .= sprintf( __( '%s курс ', 'pstu-enrollee' ), $course );
				if ( $form_studies && ! empty( $form_studies ) && ! is_wp_error( $form_studies ) ) $title .= $this->ucfirst_utf8( wp_sprintf( '%l', $form_studies ) );
				if ( empty( $title ) ) $title = sprintf(
					__( 'Конкурсное предложение ID%1$s', 'sptu-enrollee' ),
					$post_id
				);
			}
		}
		return $title;
	}





}