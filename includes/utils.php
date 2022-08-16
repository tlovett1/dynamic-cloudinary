<?php
/**
 * Utility functions
 *
 * @package DynamicCloudinary
 */

namespace DynamicCloudinary\Utils;

/**
 * Get allowed extensions we want to serve from Cloudinary
 *
 * @return array;
 */
function get_allowed_file_extensions() {
	$allowed_extensions = apply_filters(
		'dc_allowed_file_types',
		[
			'image' => [
				'jpg',
				'jpeg',
				'gif',
				'svg',
				'pdf',
				'png',
				'heic',
				'webp',
				'bmp',
			],
			'video' => [
				'mpeg',
				'mp4',
				'mov',
				'avi',
				'flv',
				'mkv',
				'webm',
				'wmv',
			],
		]
	);

	return $allowed_extensions;
}

