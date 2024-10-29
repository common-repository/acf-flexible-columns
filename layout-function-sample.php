<?php
/*
Add new content blocks within the Advanced Custom Fields - Flexible Columns editor by adding a new layout within the row width(s) you want it available in. Then copy or include this file into your functions.php and add your layout types in the function below
*/
function acffcp_custom_layouts($type){
	if( $type == 'custom_layout_name' ):
		$field = get_sub_field('field_name');
        $layout = $field;
	endif;
    return $layout;
}
add_filter( 'flexible_layout', 'acffcp_custom_layouts');