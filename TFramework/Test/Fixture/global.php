<?php

function get_field($field_name, $post = null){
	return 'Field_value for '.$field_name.' on post ' . ( $post ? $post : 'null' );
}

function register_field_group(){
	return true;
}