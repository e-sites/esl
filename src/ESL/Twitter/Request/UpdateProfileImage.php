<?php
/**
 * Updates the authenticating user's profile image.
 *
 * This method asynchronously processes the uploaded file before updating the user's profile image URL. You can either update your local cache the next time
 * you request the user's information.
 *
 * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile_image
 *
 * @package Twitter
 * @version $Id: UpdateProfileImage.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Request_UpdateProfileImage extends ESL_Twitter_Request
{
	const REQUEST_PATH = 'account/update_profile_image';
	const REQUEST_METHOD = 'POST';

	/**
	 * The avatar image for the profile, base64-encoded. Must be a valid GIF, JPG, or PNG image of less than 700 kilobytes in size. Images with width larger than 500 pixels
	 * will be scaled down. Animated GIFs will be converted to a static GIF of the first frame, removing the animation.
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sImageFile
	 */
	public function __construct($sImageFile)
	{
		if (!file_exists($sImageFile)) {
			throw new InvalidArgumentException("File '$sImageFile' does not exist.");
		}
		if (filesize($sImageFile) > 716800) { // 700kb
			throw new InvalidArgumentException("File '$sImageFile' is too large (700kb).");
		}

		$this->aParameters['image'] = base64_encode(file_get_contents($sImageFile));
	}
}
?>