<?php
if (!defined('SMF'))
	die('Hacking attempt...');

/*
SMF Dependencies - Variable
	$sourcedir - Global variable. Directory for SMF source code files.
	$smcFunc - Global variable. For Database functions 
	$msgOptions - Includes variables for the post. 
		'id' - ID for new/edited post.
		'attachments' - Array with Ids for new attachments. Does not list previously added ones.

SMF Dependencies - Files
	$sourcedir . '/Subs-Graphics.php' - For image resize (function resizeImageFile)
	$sourcedir . '/Subs.php' - For getting path to attached file (function getAttachmentFilename)

*/

global $sourcedir, $smcFunc; // line probably not needed.

require_once($sourcedir . '/Subs.php');
require_once($sourcedir . '/Subs-Graphics.php');

/*
SB_ResizePhotos is set
SB_ResizePhotos is not empty
SB_MaxSize is set
*/

if(
	!empty($msgOptions['attachments']) && Count($msgOptions['attachments']) > 0 && // If new attachments are added
	isset($_REQUEST['SB_ResizePhotos']) && isset($_REQUEST['SB_MaxSize']) && // Needed form values are set
	isset($msgOptions) && isset($msgOptions['id']) // If we can find Id for post 
)
{
	$max_size = (int) $_REQUEST['SB_MaxSize'];
	
	// Get data for all new attachemnts from database
	$request = $smcFunc['db_query']('', '
		SELECT id_attach, filename, file_hash, id_folder, width, height
		FROM {db_prefix}attachments
		WHERE id_msg = {int:id_msg} 
		AND id_attach IN ({array_int:attachment_list})
		AND attachment_type = {int:attachment_type}
		AND mime_type = {string:mime_type}', 
		array(
			'id_msg' => $msgOptions['id'], 
			'attachment_list' => $msgOptions['attachments'],
			'attachment_type' => 0,
			'mime_type' => 'image/jpeg'
		)
	);
	
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$width = $row['width'];
		$height = $row['height'];
		
		// If resize is needed or not
		if($width < $max_size && $height < $max_size)
			continue;
		
		// Calculate new size (used for Update query but not by resize method)
		if($height > $width)
		{
			$new_width = floor($width * $max_size / $height);
			$new_height = $max_size;
		}
		else
		{
			$new_width = $max_size;
			$new_height = floor($height * $max_size / $width);
		}
		
		// Create a new smaller image file and replace to current one.
		$file_path = getAttachmentFilename($row['filename'], $row['id_attach'], $row['id_folder'], false, $row['file_hash']);
		$temp_file_path = $file_path . '_2.jpg';
		resizeImageFile($file_path, $temp_file_path, $max_size, $max_size);
		unlink($file_path); // Delete file
		rename($temp_file_path, $file_path);
		
		// Update width, height and file size in database
		$smcFunc['db_query']('', '
				UPDATE {db_prefix}attachments
				SET
					width = {int:width},
					height = {int:height}, 
					size = {int:filesize}
				WHERE id_attach = {int:id_attach}',
				array(
					'width' => (int) $new_width, 
					'height' => (int) $new_height,
					'filesize' => (int) filesize($file_path),
					'id_attach' => $row['id_attach']
				)
			);
	}
	
	$smcFunc['db_free_result']($request);
}

// echo '<a href="http://linuxwebserver01/smf/index.php?topic=' . $topicOptions['id'] . '.0" >Return to topic</a><br><br>';
// die('Debug done :)');

?>