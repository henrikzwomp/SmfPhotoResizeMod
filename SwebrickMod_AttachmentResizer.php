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


if(
	!empty($msgOptions['attachments']) && Count($msgOptions['attachments']) > 0 && // If new attachments are added
	isset($_REQUEST['SB_ResizePhotos']) && isset($_REQUEST['SB_MaxSize']) && // Needed form values are set (SB_ResizePhotos will not be set if not checked)
	isset($msgOptions) && isset($msgOptions['id']) // If we can find Id for post 
)
{
	$max_size = (int) $_REQUEST['SB_MaxSize'];
	
	// Get data for all new attachemnts from database
	$request = $smcFunc['db_query']('', '
		SELECT id_attach, id_thumb, filename, file_hash, width, height, mime_type
		FROM {db_prefix}attachments
		WHERE id_msg = {int:id_msg} 
		AND id_attach IN ({array_int:attachment_list})
		AND attachment_type = {int:attachment_type}
		AND mime_type IN ({array_string:mime_type})', 
		array(
			'id_msg' => $msgOptions['id'], 
			'attachment_list' => $msgOptions['attachments'],
			'attachment_type' => 0,
			'mime_type' => array('image/jpeg','image/png','image/gif')
		)
	);
	
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$resize_needed = false;
		$rotation_needed = false;
		
		// Get file path for file
		$file_path = getAttachmentFilename($row['filename'], $row['id_attach'], null, false, $row['file_hash']);
		
		// If JPEG, retreive Orientation from EXIF data
		$orientation = 0;
		if($row['mime_type'] == 'image/jpeg')
		{
			$exif = exif_read_data($file_path);
			if(!($exif===false))
			{
				if(isset($exif["Orientation"]))
				{
					$orientation = $exif["Orientation"];
				}
			}
		}

		$width = $row['width'];
		$height = $row['height'];

		// If resize is needed or not
		if($width > $max_size || $height > $max_size)
			$resize_needed = true;
		
		if($orientation > 1)
			$rotation_needed = true;

		// If anything is not needed to be done.
		if(!$resize_needed && !$rotation_needed)
			continue;
		
		$new_width = $width; // We might need these set later (for rotation).
		$new_height = $height;
		
		// Resize if needed
		if($resize_needed)
		{
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
			$temp_file_path = $file_path . '_2.jpg';
			resizeImageFile($file_path, $temp_file_path, $max_size, $max_size);
			unlink($file_path); // Delete file
			rename($temp_file_path, $file_path);
			
			// If picture needs to be rotated (and/or flipped)
			// (Image only needs to rotated if resized because EXIF data is not preserved in that process.)
			if($rotation_needed)
			{
				$degree = 0;
			
				if($Orientation == 1 || $orientation == 2)
					$degree = 0;
				elseif($orientation == 3 || $orientation == 4 )
					$degree = 180;
				elseif($orientation == 5 || $orientation == 6 )
					$degree = 270;
				elseif($orientation == 7 || $orientation == 8)
					$degree = 90;
			
				$source = imagecreatefromjpeg($file_path);
				$source = imagerotate($source, $degree, 0);
			
				if($orientation == 2 || $orientation == 4 || 
						$orientation == 5 || $orientation == 7)
					imageflip($source, IMG_FLIP_HORIZONTAL);
				
				imagejpeg($source, $file_path);
			}
		}
		
		// If there is a thumbnail, we might need to rotated it too.
		if($rotation_needed && $row['id_thumb'] > 0)
		{
			$thumb_file_path = getAttachmentFilename('value not used', $row['id_thumb'], null, false, '');
			
			$thumb_source = imagecreatefrompng($thumb_file_path);
			$thumb_source = imagerotate($thumb_source, $degree, 0);
	
			if($orientation == 2 || $orientation == 4 || 
					$orientation == 5 || $orientation == 7)
				imageflip($thumb_source, IMG_FLIP_HORIZONTAL);
		
			imagejpeg($thumb_source, $thumb_file_path);
		}
		
		// If needed, update width, height and file size in database for main picture
		// (Even if main picture hasn't been touched, we need to update width & height for correct popup-window size)
		if($resize_needed || $rotation_needed)
		{
			if($orientation == 5 || $orientation == 6 || 
						$orientation == 7 || $orientation == 8) {
					$a = $new_width;
					$b = $new_height;
					$new_width = $b;
					$new_height = $a;
				}
			
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
		
		// If rotated, thumb picture also needs* to have width, height and file size update in the database
		// (* Maybe not REALLY needed but it is nice when database values are correct :) )
		if($row['id_thumb'] > 0 && $rotation_needed)
		{
			
			$thumb_size = getimagesize($thumb_file_path);
			
			if(isset($thumb_size[0]) && isset($thumb_size[1]))
			{
			
				$smcFunc['db_query']('', '
						UPDATE {db_prefix}attachments
						SET
							width = {int:width},
							height = {int:height}, 
							size = {int:filesize}
						WHERE id_attach = {int:id_attach}',
						array(
							'width' => (int) $thumb_size[0], 
							'height' => (int) $thumb_size[1],
							'filesize' => (int) filesize($thumb_file_path),
							'id_attach' => $row['id_thumb']
						)
					);
				}
		}
	}
	
	$smcFunc['db_free_result']($request);
}
?>