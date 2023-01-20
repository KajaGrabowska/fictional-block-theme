<?php

add_action('rest_api_init', 'universityLikeRoutes');

function universityLikeRoutes()
{
	register_rest_route('university/v1', 'manageLike', array(
		'methods' => 'POST',
		'callback' => 'createLike' // callback is the function we want to run when a request is send to the url 
	));

	register_rest_route('university/v1', 'manageLike', array(
		'methods' => 'DELETE',
		'callback' => 'deleteLike'
	));
}

function createLike($data)
{
	if (is_user_logged_in()) {
		$professor = sanitize_text_field($data['professorId']);

		$existQuery = new WP_Query(array(
			'author' => get_current_user_id(),
			'post_type' => 'like',
			'meta_query' => array(
				array(
					'key' => 'liked_professor_id', //name of the custom field
					'compare' => '=', //match exactly
					'value' => $professor
				)
			)
		));

		if ($existQuery->found_posts == 0 AND get_post_type($professor) == 'professor') {
			return wp_insert_post(array(
				'post_type' => 'like',
				'post_status' => 'publish',
				'post_title' => '2nd PHP Test',
				'meta_input' => array(
					'liked_professor_id' => $professor
				)
			));
		} else {
			die("Invalid professor id");
		}

		
	} else {
		die("Only logged in users can add likes.");
	}
}

function deleteLike($data) {
	$likeId = sanitize_text_field($data['like']);
	//1st argument - the information you want about that post
	//2nd argument - the id of the post that you want information about 
	if (get_current_user_id() == get_post_field('post_author', $likeId) AND get_post_type($likeId) == 'like') {
		//1st argument - the ID of the post that you wanna delete
		//2nd argument - true completely deletes, false moves item to trash 
		wp_delete_post($likeId, true);
		return 'Congrats, like deleted.';
	} else {
		die("You do not have permission to delete that.");
	}
	
}
