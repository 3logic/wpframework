<?php
require_once('CustomizablePostType.php');

abstract class ClonablePostType extends CustomizablePostType{

	public $properties;
	
	protected function __construct($post){
		parent::__construct($post);
	}
	
	protected function setCustomFieldProperties( $property_names = array() ){
		$this->properties= $property_names;
		parent::setCustomFieldProperties($property_names);
	}
	
	private static function _setJsonHeaders(){
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
	}
/**
* Code to be executed on theme init
* @param Object $options Option passed to theme setup
*/
	public static function onInit($options = null){
		parent::onInit($options);
		
		// Clonazione del post-type
		if(is_post_type_hierarchical(static::getPostTypeName()))
			$prefix= 'page';
		else
			$prefix='post';

		$currentclass = get_called_class();
		
		add_filter($prefix.'_row_actions', $currentclass.'::setCloneRowActions', 10, 2);
		add_action('wp_ajax_clone', $currentclass.'::doClone');
		add_filter('admin_head', array($currentclass, 'do_admin_head'));
		
	}

	
	public static function setCloneRowActions($actions, $post){
		if($post->post_type == static::getPostTypeName()){
		    $actions['clone'] = '<a href="#" class="clone-event" data-postid="'.$post->ID.'">'.__('Clone','fnmedia').'</a>';
		}
		return $actions;
	}

	
	public static function doClone(){
		global $acf;
		//header('Content-type: text/html');
		self::_setJsonHeaders();
		if(empty($_GET['pid']) || !current_user_can('edit_posts')) die(json_encode(false));
		
		$post = get_post(intval($_GET['pid']));
		if(!$post || is_wp_error($post)) 
			die(json_encode(false));

		$classname = get_called_class();

		$blogs = get_blog_list();

		$postItem = new $classname($post);
		
		$fields_to_clone = $postItem->properties;

		$supports = get_all_post_type_supports(static::getPostTypeName());

		$new_post = array(
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => get_current_user_id(),
			'post_status' => 'draft',
			'post_type' => $postItem->post_type
		);
		
		if(isset($supports['title']) && $supports['title'])
			$new_post['post_title'] = $postItem->post_title;
		
		if(isset($supports['editor']) && $supports['editor'])
			$new_post['post_content'] = $postItem->post_content;
		
		if(isset($supports['thumbnail']) && $supports['thumbnail']){
			$attach_id = get_post_thumbnail_id($postItem->ID);
		}
			
		if(isset($supports['excerpt']) && $supports['excerpt'])
			$new_post['post_excerpt'] = $postItem->post_excerpt;
		
		$ftc_keys= array();
		
		foreach($fields_to_clone as $fk=>$ftc){
			$field = get_field_object($ftc,$postItem->ID);
			$ftc_keys[$field['key']] = $postItem->{$ftc}();
		}
		
		global $blog_id;
		$clones_done = array();
		
		foreach($blogs as $bid=>$b){
			
			if($b['blog_id'] == $blog_id)
				continue;
			
			if(isset($attach_id)){
				//	add_post_meta($new_post_id, '_thumbnail_id', $attach_id, true);
				require_once( 'AttachmentData.php' );
				$upload_dir = wp_upload_dir();
				$attachment_data = array();
				$attachment_data['thumbnail'] = @AttachmentData::from_attachment_id( $attach_id, $upload_dir);
				$filestr  = $attachment_data['thumbnail']->file_metadata['file'];
			}
			
			switch_to_blog($b['blog_id']);	
			// da qui lavoriamo sul blog $b --------------
			
			$blogdetails = get_blog_details($bid);
			$clones_done[] = array('blogname'=>$blogdetails->blogname);
			
			$new_post_id = wp_insert_post($new_post);
			
			if(isset($attach_id)){
				$existingid = self::get_attachment_id_from_metadata_file($filestr);
				if($existingid){
					$new_attachment_id = $existingid;
					$clones_done[sizeof($clones_done)-1]['foundimage'] = true;
				}
				else {
					$new_attachment_id = self::copy_attachment( $attachment_data['thumbnail'], $new_post_id );
				}
				if ( $new_attachment_id !== false )
					update_post_meta( $new_post_id, '_thumbnail_id', $new_attachment_id );
			}
			if(!$new_post_id || is_wp_error($new_post_id)) 
				die(json_encode(false));
	
			foreach($ftc_keys as $field_key => $field_value){
				update_field($field_key, $field_value, $new_post_id);
			}
			
			restore_current_blog();
			// torniamo al blog corrente --------------
		}
		ob_clean();
		die(json_encode($clones_done));
	}

	static function get_attachment_id_from_metadata_file ($filestr) {
		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND guid LIKE '%$filestr'";
		$id = $wpdb->get_var($query);
		return $id;
	}
	
	static function copy_attachment($attachment_data, $post_id)
	{
		// Copy the file to the blog's upload directory
		$upload_dir = wp_upload_dir();
		if ( ! file_exists( $attachment_data->filename_path ) )
			return false;

		copy( $attachment_data->filename_path, $upload_dir['path'] . '/' . $attachment_data->filename_base );
		
		// And now create the attachment stuff.
		// This is taken almost directly from http://codex.wordpress.org/Function_Reference/wp_insert_attachment
		$wp_filetype = wp_check_filetype( $attachment_data->filename_base, null );
		$attachment = array(
			'guid' => $upload_dir['url'] . '/' . $attachment_data->filename_base,
			'menu_order' => $attachment_data->menu_order,
			'post_excerpt' => $attachment_data->post_excerpt,
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => $attachment_data->post_title,
			'post_content' => '',
			'post_status' => 'inherit',
		);
		$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $attachment_data->filename_base, $post_id );
		
		// Now to handle the metadata.
		// 1. Create new metadata for this attachment.
		require_once(ABSPATH . "wp-admin" . '/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['path'] . '/' . $attachment_data->filename_base );
		
		// 2. Write the old metadata first.
		foreach( $attachment_data->post_custom as $key => $value )
		{
			$value = reset( $value );
			$value = maybe_unserialize( $value );
			switch( $key )
			{
				// Some values need to handle completely different upload paths (from different months, for example).
				case '_wp_attached_file':
					$value = $attach_data[ 'file' ];
					break;
			}
			update_post_meta( $attach_id, $key, $value );
		}
		
		// 3. Overwrite the metadata that needs to be overwritten with fresh data. 
		wp_update_attachment_metadata( $attach_id,  $attach_data );

		return $attach_id;
	}


	public static function do_admin_head(){
		$ajaxurl= admin_url( 'admin-ajax.php'); ?>
		<script type="text/javascript">
		    (function($, window){
		        $(document).ready(function(){
		            $('.row-actions a.clone-event').click(function(){
		                var postId = $(this).data('postid');
		           
		                $.ajax({
		                    'url': '<?php echo $ajaxurl ?>',
		                    'data' : {
		                    	action:'clone',
		                    	pid: postId
		                    },
		                    'success': function(data){
		                        if(data){
		                        	var str = '<?php _e('OK, cloned to: ','fnmedia'); ?>'; 
		                            for (var i=0; i<data.length; i++){
		                            	if(i>0)
		                            		str+=', ';
		                            	str+=data[i]['blogname'];
		                            	if(data[i]['foundimage'])
		                            		str+=' (<?php _e ('image already existing','fnmedia'); ?>)';
		                            }
		                            alert(str);
		                        } else {
		                            alert('<?php _e('Unable to complete your request, retry later or contact the administrator.','fnmedia'); ?>');
		                        }
		                    },
		                    'error': function(){
		                        alert('<?php _e('Unable to complete your request, retry later or contact the administrator.','fnmedia'); ?>');
		                    }
		                });	
		              
		                return false;
		            });
		        });
		    })(jQuery, window);
		</script>
		<?php
	}
	

}
?>