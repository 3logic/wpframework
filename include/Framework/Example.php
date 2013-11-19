<?php
namespace Framework;

use \TFramework\CustomizablePostType;

class Example extends CustomizablePostType{

	const CUSTOM_TYPE_NAME = 'example';

	const TEST_THUMBNAIL = 'example-thumb';
    
    /**
     * Costruttore. Wrap su un wordpress post, 
     * utilizza la funzione TFramework\CustomizablePostType::setCustomFieldProperties per gestire i custom post type creati con ACF
     * @param object $post Wordpress post instance
 	*/
	public function __construct($post) {
		parent::__construct($post);
		parent::setCustomFieldProperties( array( 'example_field' ) );
	}

	/**
	 * Registra il custom post type utilizzando TFramework\CustomizablePostType::_registerCustomType
	 * @param object $options Wordpress post instance
	*/
	public static function registerCustomPostType($options = null) {
        $lang_context = isset($options['context']) ? $options['context'] : null;
		$args = array(

		'label' => 'Examples',
		'labels' => array(
			'name' => 'Examples',
			'singular_name' =>  'Example',
			'add_new' => _x('Add', $lang_context).' Example',
			'add_new_item' => __('Add New',$lang_context).' Example',
			'edit_item' => __('Edit',$lang_context).' Examples',
			'new_item' => __('New',$lang_context).' Example',
			'all_items' => __('All',$lang_context).' Examples',
			'view_item' => __('View',$lang_context).' Example',
			'search_items' => __('Search',$lang_context).' Examples',
			'not_found' =>  sprintf(__('No "%s" found',$lang_context),'Example'),
			'not_found_in_trash' => sprintf(__('No "%s" found in Trash',$lang_context),'Example'), 
			'parent_item_colon' => '',
			'menu_name' =>  'Examples'

			  ),
			'supports' => array(
				'title',
				'editor',
				//'author',
				'thumbnail',
				//'revisions',
				'excerpt',
				//'trackbacks',
			),
			'taxonomies' => array(),	
		);
		return parent::_registerCustomType(self::CUSTOM_TYPE_NAME, $args);
	}
	
	/**
	 * Override of registerACFFields
	 * Viene fatto override per registrare campi custom di acf senza passare dal pannello di opzioni.
	 * In questo esempio un campo di testo per gestire link esterni. Quersta funzione è chiamata automaticamente durante l'inizializzazione (onInit) del tema.
	 * Dovrebbe essere sempre controllato se ACF è installato e attivato
	 */
	protected static function registerACFFields($options){
		$lang_context = isset($options['context']) ? $options['context'] : null;
		
		if(function_exists('register_field_group')){
			register_field_group(array (
					'id' => 'acf_ferrari-moments-link-fields',
					'title' => 'Ferrari Moment Link',
					'fields' => array (
						array (
							'key' => 'field_51910c9a02741',
							'label' => __('Link',$lang_context),
							'instructions' => __('External links must be set starting with protocol, e.g. http://www.externalsite.net',$lang_context),
							'name' => 'link',
							'type' => 'text',
							'default_value' => '',
							'formatting' => 'none',
						),
					),
					'location' => array (
						'rules' => array (
							array (
								'param' => 'post_type',
								'operator' => '==',
								'value' => self::getPostTypeName(),
								'order_no' => 0,
							),
						),
						'allorany' => 'all',
					),
					'options' => array (
						'position' => 'normal',
						'layout' => 'default',
						'hide_on_screen' => array (
						),
					),
				'menu_order' => 1,
				)
			);
		}
	}

    /**
    * Code to be executed on theme init
    * @param Object $options Option passed to theme setup
    */
    public static function onInit($options = null){
        parent::onInit($options);
    }

    /**
	* Override of onThemeSetup
	* E' di solito utilizzata per registrare dimensioni di immagini custom o per oeperazioni di solito eseguite nel setup del tema
	* @param array $options Opzioni passate dal tema
	*/
	public static function onThemeSetup($options = null){
		//Register some image sizes
		add_image_size( self::TEST_THUMBNAIL, 560,320, true );
	}
	
	/**
	 * Esempio di funzione di istanza. il link definito in ACF può essere richiamato come una funzione di istanza $this->link()
	 */
	public function get_the_link(){
		return sprintf('<a href="%s">%s</a>',$this->link(),$this->link_description());
	}
	
	
	/**
	 * Esempio di funzione statica. Restituisce gli ultimi esempi caricati sul sistema
	 * @param Number $num Default 3
	 */
	static public function getLatestMoments($num = 3){
		
		$latests = array();

		if($num > 0){
			$fuser = FUser::getFUserFromCurrentUser();
			//$metaquery = $fuser->getMetaQueryFilter();
			$args = array (
				'posts_per_page' => $num,
				'post_type' => self::getPostTypeName(),
				'post_status' => 'publish',
				'order' => 'ASC',
			);
			$posts = get_posts($args);

			$latests = array_map(function($post){
				return new Example($post);
			}, $posts);
		}
		return $latests;
	}
}
?>
