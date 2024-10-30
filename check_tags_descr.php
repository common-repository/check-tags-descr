<?php
/**
 * Plugin Name: Check Tags Descr
 * Plugin URI: https://www.matriz.it/projects/check_tags_descr/
 * Description: It lists all tags that don't have description.
 * Version: 1.1.0
 * Requires at least: 3.0.0
 * Author: Mattia
 * Author URI: https://www.matriz.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

add_action('admin_menu', 'check_tags_descr_menu');
add_action('admin_head', 'check_tags_descr_javascript');
add_action('wp_ajax_check_tags_descr_save', 'check_tags_descr_save');

function check_tags_descr_menu() {
	add_submenu_page('edit.php', 'Check Tags Descr', 'Check Tags Descr', 'manage_categories', 'check_tags_descr', 'check_tags_descr_list');
}

function check_tags_descr_list() {
	if (!current_user_can('manage_categories'))  {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	$tags = get_tags(array(
		'hide_empty' => false
	));
	$counter = count($tags);
	for ($i = 0; $i < $counter; $i++) {
		if (!is_string($tags[$i]->description) || trim($tags[$i]->description) !== '') {
			unset($tags[$i]);
		}
	}
	?>
	<div class="wrap">
		<h2>Check Tags Descr</h2>
		<table class="widefat tag fixed">
			<thead>
				<tr>
					<th class="manage-column column-name" scope="col"><?php echo __('Name');?></th>
					<th class="manage-column column-description" scope="col"><?php echo __('Description');?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-name" scope="col"><?php echo __('Name');?></th>
					<th class="manage-column column-description" scope="col"><?php echo __('Description');?></th>
				</tr>
			</tfoot>
			<tbody id="the-list" class="list:tag">
				<?php if (empty($tags)) : ?>
					<tr><td colspan="2"><?php echo __('No tags found!');?></td></tr>
				<?php else : ?>
					<?php foreach ($tags as $tag) : ?>
						<?php $name = apply_filters('term_name', $tag->name);?>
						<tr id="list_tag_row_<?php echo $tag->term_id;?>">
							<td class="name column-name"><strong><a href="edit-tags.php?action=edit&amp;taxonomy=post_tag&amp;tag_ID=<?php echo $tag->term_id;?>" title="<?php echo esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $name));?>" class="row-title"><?php echo $name;?></a></strong></td>
							<td class="description column-description">
								<form action="#" method="post" class="check-tags-descr-form">
									<input type="hidden" name="term_id" value="<?php echo esc_attr($tag->term_id);?>">
									<textarea name="description" rows="3" cols="40"></textarea>
									<button type="submit" class="button"><?php echo __('Edit');?></button>
								</form>
							</td>
						</tr>
					<?php endforeach;?>
				<?php endif;?>
			</tbody>
		</table>
	</div>
	<?php
}

function check_tags_descr_javascript() {
?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.check-tags-descr-form').on('submit', function(e) {
		var f = jQuery(this), err = function(m) {
			alert(m || '<?php echo str_replace('\'', '\\\'', __('Item not updated.'));?>');
		};
		jQuery.ajax({
			'url': ajaxurl,
			'method': 'post',
			'data': {
				'action': 'check_tags_descr_save',
				'id': f.find(':input[name="term_id"]').val(),
				'descr': f.find(':input[name="description"]').val()
			},
			'dataType': 'json',
			'success': function(r) {
				if (r.ok) {
					f.closest('tr').remove();
				} else {
					err.call(null, r.msg);
				}
			},
			'error': function() {
				err.call();
			}
		});
		e.preventDefault();
	});
});
</script>
<?php
}

function check_tags_descr_save() {
	$json = array('ok' => 0);
	if (isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = intval($_POST['id']);
		$descr = isset($_POST['descr']) && is_string($_POST['descr']) ? trim($_POST['descr']) : '';
		if ($descr !== '') {
			$tax = get_taxonomy('post_tag');
			if (current_user_can($tax->cap->edit_terms)) {
				$res = wp_update_term($id, 'post_tag', array('description' => $descr));
				if (is_array($res) && isset($res['term_id']) && $res['term_id'] == $id) {
					$json['ok'] = 1;
				}
			} else {
				$json['msg'] = __('You are not allowed to edit this item.');
			}
		}
	}
	echo json_encode($json);
	die();
}