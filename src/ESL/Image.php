<?php
/**
 * Create a thumbnail from an input image
 *
 * Wrapper around ImageMagick's convert-binary, offering a simplified interface into generating thumbnails with a predictable and optimized result
 * Currently supports;
 * - resizing (fit into box, crop to fit, extent to fit, strech to fit)
 * - adding a border
 * - rounding corners
 * - adding an overlay
 * - masking the image with transparency
 *
 * Known issues;
 * - An even border width (4, 8, 10, etc) is increased by one, except borderwidth 2. Internal IM bug
 * - trim() and rotate() are not available, because they would change the input width/height
 * - roundCorners are not visible on transparent PNG's, because the background- and cornercolor are shared
 *
 * @todo Move operations, like thumbnail, extend, border, into seperate classes. Minimizes this class and allows extending with custom operators
 *
 * @package Image
 * @version $Id: Image.php 770 2014-08-28 06:50:26Z fpruis $
 */
final class ESL_Image
{
	const PATH_CONVERT = 'convert';
	const PATH_OPTIPNG = 'optipng';
	const PATH_JPEGOPTIM = 'jpegoptim';

	// What part of canvas to crop, or where to add the overlay or mask
	const GRAVITY_CENTER    = 0;  // 0000
	const GRAVITY_NORTH     = 1;  // 0001
	const GRAVITY_NORTHEAST = 3;  // 0011
	const GRAVITY_EAST      = 2;  // 0010
	const GRAVITY_SOUTHEAST = 6;  // 0110
	const GRAVITY_SOUTH     = 4;  // 0100
	const GRAVITY_SOUTHWEST = 12; // 1100
	const GRAVITY_WEST      = 8;  // 1000
	const GRAVITY_NORTHWEST = 9;  // 1001

	// Internally used when resizing/cropping/extending
	const THUMBNAIL_SHRINK  = '>'; // Shrinks an image with dimension(s) larger than the corresponding width and/or height argument(s).
	const THUMBNAIL_STRETCH = '!'; // Width and height emphatically given, original aspect ratio ignored.
	const THUMBNAIL_MINIMUM = '^'; // Minimum values of width and height given, aspect ratio preserved.
	const THUMBNAIL_MAXIMUM = '';  // Maximum values of height and width given, aspect ratio preserved.

	// Pixel density factor
	const DENSITY_LDPI   = 0.75; // 120
	const DENSITY_MDPI   = 1;    // 160
	const DENSITY_TVDPI  = 1.33; // 213
	const DENSITY_HDPI   = 1.5;  // 240
	const DENSITY_XHDPI  = 2;    // 320
	const DENSITY_RETINA = self::DENSITY_XHDPI; // alias

	const DEFAULT_OUTPUT_FILENAME = '%wx%h/%p/%f-%u';
	const DEFAULT_OUTPUT_FOLDER = 'thumb';

	/**
	 * Basedir for input and output
	 * 
	 * @var string
	 */
	protected $sBasedir;

	/**
	 * Path to image, relative to basedir
	 *
	 * As given in constructor
	 * 
	 * @var string
	 */
	protected $sInputFile;

	/**
	 * Absolute path to image
	 * 
	 * @var string
	 */
	protected $sInputPath;

	/**
	 * Image width
	 *
	 * @var int
	 */
	protected $iInputWidth;

	/**
	 * Image height
	 * 
	 * @var int
	 */
	protected $iInputHeight;

	/**
	 * Image aspect ratio
	 * 
	 * @var float
	 */
	protected $fInputRatio;

	/**
	 * Directory path of image, relative to basedir
	 *
	 * Without leading and trailing directory seperators
	 *
	 * @var string
	 */
	protected $sInputDirname;

	/**
	 * Image basename
	 *
	 * Basename includes file-extension
	 * 
	 * @var string
	 */
	protected $sInputBasename;

	/**
	 * Image filename
	 *
	 * Filename excludes file-extension
	 * 
	 * @var string
	 */
	protected $sInputFilename;

	/**
	 * Image file-extension
	 * 
	 * @var string
	 */
	protected $sInputExtension;

	// Various operations and their parameters
	protected $bDoBackgroundColor = false;
	protected $sBackgroundColor = '#ffffff';

	protected $bDoThumbnail = false;
	protected $iThumbnailWidth = null;
	protected $iThumbnailHeight = null;
	protected $sThumbnailModifier = null;

	protected $bDoExtent = false;
	protected $iExtentWidth = null;
	protected $iExtentHeight = null;
	protected $sExtentGravity = null;

	protected $bDoBorder = false;
	protected $iBorderWidth = null;
	protected $sBorderColor = null;

	protected $bDoCorner = false;
	protected $iCornerRadius = null;

	protected $bDoOverlay = false;
	protected $sOverlayFile = null;
	protected $sOverlayGravity = null;

	protected $bDoMask = false;
	protected $sMaskFile = null;
	protected $sMaskGravity = null;

	/**
	 * Force generating thumb nonetheless the cache is valid
	 *
	 * @var bool
	 */
	protected $bRegenerateThumb = false;

	/**
	 * Automatically create smaller/larger thumbnails, for example to be displayed on retina displays
	 *
	 * @var float
	 */
	protected $fDensity = self::DENSITY_MDPI;

	/**
	 * Test whether file is locally accesible and return the absolute path to the given file
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param string Path to file $sPath
	 * @return string FQN
	 */
	static protected function assertLocalImage($sPath)
	{
		if (!($sResolvedPath = realpath($sPath))) {
			throw new InvalidArgumentException("File '$sPath' does not exist");
		}
		if (!is_readable($sResolvedPath)) {
			throw new InvalidArgumentException("File '$sResolvedPath' is not readable");
		}
		if (is_dir($sResolvedPath) || filesize($sResolvedPath) < 12) {
			throw new InvalidArgumentException("File '$sResolvedPath' is not an image");
		}

		return $sResolvedPath;
	}

	/**
	 * Test whether size is a positive int
	 *
	 * Used to test image dimensions and for example border widths.
	 * Not to be confused with filesize
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param int $iSize
	 * @return null
	 */
	static protected function assertSize($iSize)
	{
		if (!is_int($iSize) || $iSize <= 0) {
			throw new InvalidArgumentException("Size needs to be an integer larger than zero, '$iSize' given");
		}
	}

	/**
	 * Test for a valid color
	 *
	 * We only accept RGB in hex-notation. 
	 * Special case 'none' is also allowed
	 * Alpha-channel can not be given, as this gives unexpected results when we overlay mulitple layers, for example when creating rounded corners and borders, the opacity for
	 * the border would be multiplicated in the corners
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sColor
	 * @return null
	 */
	static protected function assertColor($sColor)
	{
		if ($sColor == 'none') {
			return;
		}

		if (preg_match('/^#([a-f0-9]){6}$/i', $sColor)) {
			return;
		}

		throw new InvalidArgumentException("Color '$sColor' is not a valid hex-color");
	}

	/**
	 * Test for a valid gravity
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param int $iGravity
	 * @return null
	 */
	static protected function assertGravity($iGravity)
	{
		$aValidValues = array(
			self::GRAVITY_CENTER,
			self::GRAVITY_NORTH,
			self::GRAVITY_NORTHEAST,
			self::GRAVITY_EAST,
			self::GRAVITY_SOUTHEAST,
			self::GRAVITY_SOUTH,
			self::GRAVITY_SOUTHWEST,
			self::GRAVITY_WEST,
			self::GRAVITY_NORTHWEST,
		);

		if (!in_array($iGravity, $aValidValues, true)) {
			throw new InvalidArgumentException('Invalid gravity');
		}
	}

	/**
	 * Read and return image dimensions
	 *
	 * Returns array with keys 'width' and 'height', or null on failure (if file does not exist, could not be read, is not an image, etc)
	 * 
	 * @param string $sPath Path to file
	 * @return array With keys 'width' and 'height'
	 */
	static public function readSize($sPath)
	{
		try {
			$sPathFqn = static::assertLocalImage($sPath);
		} catch (InvalidArgumentException $e) {
			// Invalid file
			return null;
		}

		if (($aSize = getimagesize($sPathFqn))) {
			// Fastest method to read image dimensions
			$iWidth = $aSize[0];
			$iHeight = $aSize[1];
		} else {
			// Try with imagemagick instead
			$aOutput = $iExitcode = null;
			$sIdentify = exec("identify -quiet -ping -format '%w %h' " . escapeshellarg($sPathFqn . '[0]'), $aOutput, $iExitcode);
			// Test and assign output to $iWidth & $iHeight
			if ($iExitcode !== 0 || sscanf($sIdentify, '%d %d', $iWidth, $iHeight) != 2) {
				// imagemagick failed too
				return null;
			}
		}

		return array(
			'width' => $iWidth,
			'height' => $iHeight
		);
	}

	/**
	 * Create an instance for an image to be manipulated. The image can be resized, have a border added, corners rounded, etcetera.
	 *
	 * Provide the image its width and height if these are already known to prevent them having to be read from file
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sBasedir Base directory to read images from and write thumb to
	 * @param string $sFile Path to the input image relative to basedir
	 * @param int $iSourceWidth Width of input image in pixels, always use together with height, omit if unknown or unsure
	 * @param int $iSourceHeight Height of input image in pixels, always use together with width, omit if unknown or unsure
	 */
	public function __construct($sBasedir, $sFile, $iSourceWidth = null, $iSourceHeight = null)
	{
		if (!is_dir($sBasedir) || !($this->sBasedir = realpath($sBasedir))) {
			throw new InvalidArgumentException("Invalid basedir '$sBasedir'.");
		}
		if (null !== $iSourceWidth) {
			static::assertSize($iSourceWidth);
		}
		if (null !== $iSourceHeight) {
			static::assertSize($iSourceHeight);
		}

		$this->sInputPath = static::assertLocalImage($this->sBasedir . DIRECTORY_SEPARATOR . $sFile);
		$this->sInputFile = $sFile;

		if ($iSourceWidth && $iSourceHeight) {
			// Use provided dimensions
			$this->iInputWidth = $iSourceWidth;
			$this->iInputHeight = $iSourceHeight;
		} else {
			// Read image dimensions
			if (!($aSize = static::readSize($this->getRealPath()))) {
				throw new InvalidArgumentException("File '" . $this->getRealPath() . "' is not an image");
			}
			$this->iInputWidth = $aSize['width'];
			$this->iInputHeight = $aSize['height'];
		}

		$this->fInputRatio = $this->iInputWidth / $this->iInputHeight;

		$aSourceInfo = pathinfo($this->getFile());
		$this->sInputDirname = ($aSourceInfo['dirname'] != '.') ? trim($aSourceInfo['dirname'], DIRECTORY_SEPARATOR) : '';
		$this->sInputBasename = $aSourceInfo['basename'];
		$this->sInputFilename = $aSourceInfo['filename'];
		$this->sInputExtension = isset($aSourceInfo['extension']) ? strtolower($aSourceInfo['extension']) : '';
	}

	/**
	 * Build imagemagick command
	 *
	 * @todo Split up in seperate methods, to ease extending
	 *
	 * @param string $sFileInput Input filename
	 * @param string $sFileOutput Output filename
	 * @return string command
	 */
	protected function buildCommand($sFileInput, $sFileOutput)
	{
		$iOutputWidth = round($this->generateOutputWidth() * $this->fDensity);
		$iOutputHeight = round($this->generateOutputHeight() * $this->fDensity);

		$aArgumentsInput = array(
			'-auto-orient'
		);
		$aArgumentsOutput = array(
			'-colorspace sRGB',
			'-quality 85'
		);

		switch ($this->getExtension()) {
			case 'gif':
				$aArgumentsOutput[] = '-coalesce';
				break;

			case 'pdf':
			case 'psd':
				// Take first page/frame instead of handling each as seperate input
				$sFileInput = $sFileInput . '[0]';
				// no break; intended to do the svg and png rules too

			case 'svg':
				$aArgumentsInput[] = '-density 300';
				// no break; intended to do the png rule too

			case 'png':
				$this->bDoBackgroundColor = true;
				break;
		}

		if ($this->bDoBackgroundColor) {
			$aArgumentsOutput[] = sprintf('-background %s', escapeshellarg($this->sBackgroundColor));
		}

		if ($this->bDoThumbnail) {
			$iThumbWidth = round($this->iThumbnailWidth * $this->fDensity);
			$iThumbHeight = round($this->iThumbnailHeight * $this->fDensity);
			if ($this->getExtension() == 'jpg') {
				$aArgumentsInput[] = sprintf("-define 'jpeg:size=%dx%d'", min($iThumbWidth * 2, $this->getWidth()), min($iThumbHeight * 2, $this->getHeight()));
			}
			$aArgumentsOutput[] = sprintf("-thumbnail '%dx%d%s'", $iThumbWidth, $iThumbHeight, $this->sThumbnailModifier);
		}

		$aArgumentsOutput[] = '-strip';

		if ($this->bDoExtent) {
			$iExtentWidth = round($this->iExtentWidth * $this->fDensity);
			$iExtentHeight = round($this->iExtentHeight * $this->fDensity);
			$aArgumentsOutput[] = sprintf("-gravity %s -extent '%dx%d'", escapeshellarg($this->sExtentGravity), $iExtentWidth, $iExtentHeight);
		}

		if ($this->bDoOverlay) {
			if ($this->sOverlayGravity) {
				$aArgumentsOutput[] = sprintf('\( %s -gravity %s \) -composite', escapeshellarg($this->sOverlayFile), escapeshellarg($this->sOverlayGravity));
			} else {
				$aArgumentsOutput[] = sprintf("\( -resize '%dx%d!' %s -gravity 'center' \) -composite", $iOutputWidth, $iOutputHeight, escapeshellarg($this->sOverlayFile));
			}
		}

		if ($this->bDoCorner || $this->bDoBorder) {
			$fOffset = (($this->iBorderWidth * $this->fDensity) / 2);

			if ($this->bDoCorner) {
				// Draw rectangle with rounded corners, then copy the opaque corners into our image, discarding the rectangle, making our corners opaque

				$aArgumentsOutput[] = sprintf(
					'\( -size %dx%d xc:none -fill white	-draw \'roundRectangle %3$.1f,%3$.1f %4$.1f,%5$.1f %6$.1f,%6$.1f\' -compose %7$s \) -composite',
					$iOutputWidth,
					$iOutputHeight,
					$fOffset,
					$iOutputWidth - $fOffset - 1,
					$iOutputHeight - $fOffset - 1,
					$this->iCornerRadius * $this->fDensity,
					($this->sBackgroundColor == 'none') ? 'Dst_In' : 'Dst_Atop' // Prevent transparent pixels becoming opaque (black) when merging
				);

				if ($this->bDoBorder) {
					$aArgumentsOutput[] = sprintf(
						'\( -size %dx%d xc:none -fill none -stroke %s -strokewidth %.1f -draw "roundRectangle %5$.1f,%5$.1f %6$.1f,%7$.1f %8$.1f,%8$.1f" -compose Over \) -composite',
						$iOutputWidth,
						$iOutputHeight,
						escapeshellarg($this->sBorderColor),
						$this->iBorderWidth * $this->fDensity,
						$fOffset,
						$iOutputWidth - $fOffset - 1,
						$iOutputHeight - $fOffset - 1,
						$this->iCornerRadius * $this->fDensity
					);
				}
			} elseif ($this->bDoBorder) {
				// Shave gives problems with transparent PNG input images and changed background color. Therefor we draw a rectangle instead
				$aArgumentsOutput[] = sprintf(
					'\( -size %dx%d xc:none -fill none -stroke %s -strokewidth %.1f -draw "rectangle %5$.1f,%5$.1f %6$.1f,%7$.1f" -compose Over \) -composite',
					$iOutputWidth,
					$iOutputHeight,
					escapeshellarg($this->sBorderColor),
					$this->iBorderWidth * $this->fDensity,
					$fOffset,
					$iOutputWidth - $fOffset - 1,
					$iOutputHeight - $fOffset - 1
				);
			}
		}

		if ($this->bDoBackgroundColor) {
			$aArgumentsOutput[] = sprintf('-compose Over -background %s', escapeshellarg($this->sBackgroundColor));
		}

		if ($this->getExtension() != 'gif') {
			// Do not flatten gif. It would break animated gifs
			$aArgumentsOutput[] = '-flatten';
		}

		// Aplly mask to have transparency in masked areas, instead of background color
		if ($this->bDoMask) {
			if ($this->sMaskGravity) {
				$aArgumentsOutput[] = sprintf('\( %s -gravity %s +clone -compose CopyOpacity \) -composite', escapeshellarg($this->sMaskFile), escapeshellarg($this->sMaskGravity));
			} else {
				$aArgumentsOutput[] = sprintf("\( -resize '%dx%d!' %s -gravity 'center' +clone -compose CopyOpacity \) -composite", $iOutputWidth, $iOutputHeight, escapeshellarg($this->sMaskFile));
			}
		}

		// Input file
		$aArgumentsInput[] = escapeshellarg($sFileInput);
		// Output file
		$aArgumentsOutput[] = escapeshellarg($sFileOutput);

		// Build command
		return static::PATH_CONVERT . ' -quiet ' . implode(' ', $aArgumentsInput) . ' ' . implode(' ', $aArgumentsOutput);
	}

	/**
	 * Get width of output file
	 *
	 * Is only accurate if called during save()
	 * Has to be processed with fDensity for image operations. Use as-is in for example output filename
	 * 
	 * @return int
	 */
	protected function generateOutputWidth()
	{
		if ($this->bDoExtent) {
			$iCanvasWidth = $this->iExtentWidth;
		} elseif ($this->bDoThumbnail) {
			$iCanvasWidth = $this->iThumbnailWidth;
		} else {
			$iCanvasWidth = $this->getWidth();
		}

		return $iCanvasWidth;
	}

	/**
	 * Get height of output file
	 *
	 * Is only accurate if called during save()
	 * Has to be processed with fDensity for image operations. Use as-is in for example output filename
	 * 
	 * @return int
	 */
	protected function generateOutputHeight()
	{
		if ($this->bDoExtent) {
			$iCanvasHeight = $this->iExtentHeight;
		} elseif ($this->bDoThumbnail) {
			$iCanvasHeight = $this->iThumbnailHeight;
		} else {
			$iCanvasHeight = $this->getHeight();
		}

		return $iCanvasHeight;
	}

	/**
	 * Generate a hash unique for the currently set operations
	 *
	 * @return string
	 */
	protected function generateFilenameHash()
	{
		$sUnique = $this->getRealPath() . 'w' . $this->generateOutputWidth() . 'h' . $this->generateOutputHeight() . 'r' . $this->fDensity;

		if ($this->bDoBackgroundColor) {
			$sUnique .= 'bg' . $this->sBackgroundColor;
		}
		if ($this->bDoThumbnail) {
			$sUnique .= 't' . $this->iThumbnailWidth . 'x' . $this->iThumbnailHeight . $this->sThumbnailModifier;
		}
		if ($this->bDoExtent) {
			$sUnique .= 'e' . $this->sExtentGravity . 'w' . $this->iExtentWidth . 'h' . $this->iExtentHeight;
		}
		if ($this->bDoCorner) {
			$sUnique .= 'c' . $this->iCornerRadius;
		}
		if ($this->bDoBorder) {
			$sUnique .= 'b' . $this->iBorderWidth . $this->sBorderColor;
		}
		if ($this->bDoOverlay) {
			$sUnique .= 'o' . $this->sOverlayFile . 'g' . $this->sOverlayGravity;
		}
		if ($this->bDoMask) {
			$sUnique .= 'm' . $this->sMaskFile . 'g' . $this->sMaskGravity;
		}

		return md5($sUnique);
	}

	/**
	 * Generate extension to be used for output file
	 * 
	 * @return string
	 */
	protected function generateOutputExtension()
	{
		switch (true) {
			case ($this->bDoBackgroundColor && $this->sBackgroundColor == 'none'):
			case ($this->bDoMask):
			case ($this->getExtension() == 'png'):
			case ($this->getExtension() == 'pdf'):
			case ($this->getExtension() == 'svg'):
				$sExtension = 'png';
				break;

			case ($this->getExtension() == 'gif'):
				$sExtension = 'gif';
				break;

			default:
				$sExtension = 'jpg';
		}
		return $sExtension;
	}

	/**
	 * Replace special placeholders with actual values
	 *
	 * @internal Can become a closure in PHP>5.4, when $this can be used inside anonymous functions
	 *
	 * @param type $aMatch
	 * @return type
	 */
	protected function parseFilenamePlaceholders($aMatch)
	{
		switch ($aMatch[1]) {
			case 'w':
				return $this->generateOutputWidth();
			case 'h':
				return $this->generateOutputHeight();
			case 'f':
				return $this->getFilename();
			case 'u':
				return $this->generateFilenameHash();
			case 'p':
				// Path; with slashes
				return str_replace(DIRECTORY_SEPARATOR, '/', $this->getPath());
			case 'd':
				// Path; with dashes
				return str_replace(DIRECTORY_SEPARATOR, '-', $this->getPath());
			default:
				// Leave it be
				return $aMatch[1];
		}
	}

	/**
	 * Generate path where output file should be saved
	 *
	 * @param string $sFilename
	 * @return string
	 */
	protected function generateOutputPath($sFilename)
	{
		$sResolvedFilename = preg_replace_callback('/(?<!%)%(\w)/', array($this, 'parseFilenamePlaceholders'), $sFilename);

		// Remove duplicate seperators, in case a placeholder turned up empty
		$sFile = preg_replace('#([/-])\\1+#', '$1', $sResolvedFilename);

		if ($this->fDensity != 1) {
			$sFile .= '@' . $this->fDensity . 'x';
		}

		return $sFile . '.' . $this->generateOutputExtension();
	}

	/**
	 * Perform all operations on input file and save output with the given filename
	 *
	 * The given filename might contain one or more of the folowing placeholders:
	 *  %w output width
	 *  %h output height
	 *  %f input filename
	 *  %u unique hash for given commands
	 *  %p path to input file, relative to basedir, directory structure intact
	 *  %d path to input file, relative to basedir, directory seperators replaced with dashes
	 *
	 * The file-extension is always automatically suffixed and can not be manipulated
	 * If the pixel density is changed, a '@0x' suffix is also automatically added
	 * Folder is relative to basedir, and prefixed to filename
	 *
	 * Default filename: %wx%h/%p/%f-%u
	 * Default folder: thumb
	 * Example ouput filename: /thumb/400x300/files/Banners/promotie2013-faa7299ba52140eac2563f159f389535.jpg
	 * 
	 * @throws RuntimeException
	 *
	 * @param string $sFilename Filename, without file extension, possibly with dynamic placeholders
	 * @param string $sFolder Relative to basedir. Defaults to 'thumb'
	 * @return ESL_Image
	 */
	public function save($sFilename = null, $sFolder = null)
	{
		if ($sFilename === null) {
			$sFilename = static::DEFAULT_OUTPUT_FILENAME;
		}
		if ($sFolder === null) {
			$sFolder = static::DEFAULT_OUTPUT_FOLDER;
		}

		$sOutputFile = $this->generateOutputPath($sFilename);
		if ($sFolder) {
			$sOutputFile = trim($sFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sOutputFile;
		}

		$sOutputPath = $this->sBasedir . DIRECTORY_SEPARATOR . $sOutputFile;

		if ($this->bRegenerateThumb) {
			$bGenerateThumb = true;
		} elseif (!file_exists($sOutputPath)) {
			$bGenerateThumb = true;
		} else {
			$iOutputStamp = filemtime($sOutputPath);
			if (filemtime($this->getRealPath()) > $iOutputStamp) {
				$bGenerateThumb = true;
			} elseif ($this->bDoOverlay && filemtime($this->sOverlayFile) > $iOutputStamp) {
				$bGenerateThumb = true;
			} elseif ($this->bDoMask && filemtime($this->sMaskFile) > $iOutputStamp) {
				$bGenerateThumb = true;
			} else {
				$bGenerateThumb = false;
			}
		}

		if ($bGenerateThumb) {
			$sOutputDir = dirname($sOutputPath);
			// Check for output directory
			if (!is_dir($sOutputDir) && !mkdir($sOutputDir, 0777, true)) {
				throw new RuntimeException("Could not create output directory '$sOutputDir'.");
			}

			// Create temporary thumbnail file
			if (!touch($sOutputPath)) {
				throw new RuntimeException("Could not create thumbnail placeholder '$sOutputPath'.");
			}

			$sCmdConvert = $this->buildCommand($this->getRealPath(), $sOutputPath);

			// Execute
			$aOutput = $iExitcode = null;
			exec('nice ' . $sCmdConvert . ' 2>&1 >/dev/null', $aOutput, $iExitcode);
			if ($iExitcode !== 0) {
				throw new RuntimeException(implode("\n", $aOutput), $iExitcode, new RuntimeException("Command '$sCmdConvert' failed."));
			}

			// Optimise
			$aOutput = $iExitcode = null;
			switch ($this->generateOutputExtension()) {
				case 'png':
					$sOutput = exec('nice ' . static::PATH_OPTIPNG . ' -q ' . escapeshellarg($sOutputPath) . ' 2>&1', $aOutput, $iExit);
					break;
				case 'jpg':
					$sOutput = exec('nice ' . static::PATH_JPEGOPTIM . ' -q --all-progressive ' . escapeshellarg($sOutputPath) . ' 2>&1', $aOutput, $iExit);
					break;
				default:
					$iExit = 0;
			}

			if ($iExit !== 0) {
				// optimiser is missing on server. Report error
				trigger_error('Optimiser for "' . $this->generateOutputExtension() . '" in ESL_Image failed (#' . $iExit . '); ' . $sOutput, E_USER_WARNING);
			}
		}

		// Return resulting image
		return new static($this->sBasedir, $sOutputFile, $this->generateOutputWidth(), $this->generateOutputHeight());
	}

	/**
	 * Return path to image, relative to basedir
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->getFile();
	}

	/**
	 * Path to source image, relative to basedir
	 * 
	 * @return string
	 */
	public function getFile()
	{
		return $this->sInputFile;
	}

	/**
	 * Absolute path to source image
	 *
	 * @return string
	 */
	public function getRealPath()
	{
		return $this->sInputPath;
	}

	/**
	 * Width in pixels of source image
	 *
	 * @return int
	 */
	public function getWidth()
	{
		return $this->iInputWidth;
	}

	/**
	 * Height in pixels of source image
	 *
	 * @return int
	 */
	public function getHeight()
	{
		return $this->iInputHeight;
	}

	/**
	 * Aspect ratio of source image
	 *
	 * @return float
	 */
	public function getRatio()
	{
		return $this->fInputRatio;
	}

	/**
	 * Directory part of the source, relative to the basedir
	 *
	 * This excludes the source image basename, and returns just the directory part, without leading and trailing directory seperators
	 * Use dirname() on ESL_Image->getRealPath() if you need the FQN
	 *
	 * @see $this->getRealPath()
	 * @return string
	 */
	public function getPath()
	{
		return $this->sInputDirname;
	}

	/**
	 * Basename of source image
	 *
	 * Without path info, with file extension. The filename
	 *
	 * @see $this->getFilename()
	 *
	 * @return string
	 */
	public function getBasename()
	{
		return $this->sInputBasename;
	}

	/**
	 * Filename of source image
	 *
	 * Without path info, without file extension
	 *
	 * @see $this->getBasename()
	 * @see $this->getExtension()
	 * @return string
	 */
	public function getFilename()
	{
		return $this->sInputFilename;
	}

	/**
	 * File extension of source image
	 *
	 * Empty if file had no extension. If file had multiple extensions, only the last one is returned
	 * Always in lowercase.
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->sInputExtension;
	}

	/**
	 * Resize
	 * 
	 * ImageMagick supports severals modifiers for more control over the resize command;
	 *  scale%             Height and width both scaled by specified percentage.
	 *  scale-x%xscale-y%  Height and width individually scaled by specified percentages. (Only one % symbol needed.)
	 *  width	             Width given, height automagically selected to preserve aspect ratio.
	 *  xheight            Height given, width automagically selected to preserve aspect ratio.
	 *  widthxheight
	 *  widthxheight^
	 *  widthxheight!
	 *  widthxheight>
	 *  widthxheight<      Enlarges an image with dimension(s) smaller than the corresponding width and/or height argument(s).
	 *  area@              Resize image to have specified area in pixels. Aspect ratio is preserved.
	 * 
	 * @param int $iWidth
	 * @param int $iHeight
	 * @param string $sModifier
	 * @return null
	 */
	protected function thumbnail($iWidth, $iHeight, $sModifier = self::THUMBNAIL_MAXIMUM)
	{
		$this->bDoThumbnail = true;
		$this->iThumbnailWidth = $iWidth;
		$this->iThumbnailHeight = $iHeight;
		$this->sThumbnailModifier = $sModifier;
	}

	/**
	 * Crop and extend
	 *
	 * @param int $iWidth
	 * @param int $iHeight
	 * @param int $iGravity
	 * @return null
	 */
	protected function extent($iWidth, $iHeight, $iGravity)
	{
		$this->bDoExtent = true;
		$this->iExtentWidth = $iWidth;
		$this->iExtentHeight = $iHeight;
		$this->sExtentGravity = $this->toGravity($iGravity);

		if ($iWidth > $this->getWidth() || $iHeight > $this->getHeight()) {
			$this->bDoBackgroundColor = true;
		}
	}

	/**
	 * Get string representation for gravity, to be used with ImageMagick-command
	 * 
	 * @param string $iGravity
	 * @return string
	 */
	protected function toGravity($iGravity)
	{
		if ($iGravity === null) {
			return null;
		} elseif ($iGravity == self::GRAVITY_CENTER) {
			$sGravity = 'center';
		} else {
			if ($iGravity & self::GRAVITY_NORTH) {
				$sGravity = 'north';
			} elseif ($iGravity & self::GRAVITY_SOUTH) {
				$sGravity = 'south';
			} else {
				$sGravity = '';
			}

			if ($iGravity & self::GRAVITY_EAST) {
				$sGravity .= 'east';
			} elseif ($iGravity & self::GRAVITY_WEST) {
				$sGravity .= 'west';
			}
		}

		return $sGravity;
	}

	/**
	 * Resize to fit inside given box
	 *
	 * Entire image is preserved and scaled untill it fits inside of the given box. Depending on the aspect ratio of the source and the requested dimensions, the resulting image
	 * will either exactly fit the box, be smaller on either one of the sides, or be smaller on both dimensions if the source image is smaller than the requested size.
	 *
	 * You can use a zero value for one of either dimensions to have it automatically determined based on aspect ratio.
	 *
	 * Use ESL_Image->resizeExtend() to perform the same scaling, and then extend the canvas to the requested dimensions using a background color
	 * 
	 * @see $this->resizeCrop Resize to be framed in given box
	 * @see $this->resizeExtend Resize to fit inside given box, extendind canvas to match requested dimensions.
	 * @see $this->resizeStretch Resize to fix inside given box, ignoring aspect ratio and streching and shrinking sides independently
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param int $iWidth Maximum width
	 * @param int $iHeight Maximum height
	 * @return \ESL_Image $this
	 */
	public function resizeBox($iWidth, $iHeight)
	{
		if ($iWidth !== 0) {
			static::assertSize($iWidth);
		}
		if ($iHeight !== 0) {
			static::assertSize($iHeight);
		}
		if (!$iWidth && !$iHeight) {
			throw new InvalidArgumentException("Width and height can not both be zero (auto)");
		}

		$fInputRatio = $this->getRatio();

		if ($iWidth === 0) {
			// Calculate width based on height and known ratio
			$iWidth = (int) round($iHeight * $fInputRatio);
		} elseif ($iHeight === 0) {
			// Calculate height based on width and known ratio
			$iHeight = (int) round($iWidth / $fInputRatio);
		}

		// Preserve aspect ratio. ImageMagick actually does this itself, but we need this to be set for the rounded border to be positioned correctly
		if ($fInputRatio >= $iWidth / $iHeight) {
			if ($iWidth > $this->getWidth()) {
				$iWidth = $this->getWidth();
			}
			$iHeight = (int) round($iWidth / $fInputRatio);
		} else {
			if ($iHeight > $this->getHeight()) {
				$iHeight = $this->getHeight();
			}
			$iWidth = (int) round($iHeight * $fInputRatio);
		}

		$this->thumbnail($iWidth, $iHeight, self::THUMBNAIL_SHRINK);

		return $this;
	}

	/**
	 * Resize to be framed in given box
	 *
	 * Parts of image are chopped off to make the image fit in the requested box. Use the gravity to control which part is cut off.
	 * Aspect ratio of source is preserved.
	 *
	 * @see $this->resizeBox Resize to fit inside given box
	 * @see $this->resizeExtend Resize to fit inside given box, extendind canvas to match requested dimensions.
	 * @see $this->resizeStretch Resize to fix inside given box, ignoring aspect ratio and streching and shrinking sides independently
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param int $iWidth Width
	 * @param int $iHeight Height
	 * @param int $iGravity One of the ESL_Image::GRAVITY-constants where to extend the canvas. Defaults to GRAVITY_CENTER
	 * @return \ESL_Image $this
	 */
	public function resizeCrop($iWidth, $iHeight, $iGravity = null)
	{
		static::assertSize($iWidth);
		static::assertSize($iHeight);

		if ($iGravity === null) {
			$iGravity = self::GRAVITY_CENTER;
		} else {
			static::assertGravity($iGravity);
		}

		if ($iWidth < $this->getWidth() && $iHeight < $this->getHeight()) {
			// Scale to minimal dimensions; either or both side match the dimension, the other or neither is larger
			$this->thumbnail($iWidth, $iHeight, self::THUMBNAIL_MINIMUM);
		} else {
			// Source is too small; extext canvas
			if ($iGravity && $iWidth > $this->getWidth()) {
				// Source is too narrow. Unset horizontal gravity
				$iGravity ^= ($iGravity & (self::GRAVITY_EAST | self::GRAVITY_WEST));
			}
			if ($iGravity && $iHeight > $this->getHeight()) {
				// Source is too low. Unset vertical gravity
				$iGravity ^= ($iGravity & (self::GRAVITY_NORTH | self::GRAVITY_SOUTH));
			}
		}

		// Resize canvas
		$this->extent($iWidth, $iHeight, $iGravity);

		return $this;
	}

	/**
	 * Resize to fit inside given box, extendind canvas to match requested dimensions.
	 *
	 * Performs the same method of scaling as ESL_Image->resizeBox(), and afterwards extends the canvas to the requested dimensions using the set background color.
	 *
	 * @see $this->resizeBox Resize to fit inside given box
	 * @see $this->resizeCrop Resize to be framed in given box
	 * @see $this->resizeStretch Resize to fix inside given box, ignoring aspect ratio and streching and shrinking sides independently
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param int $iWidth Width
	 * @param int $iHeight Height
	 * @param int $iGravity One of the ESL_Image::GRAVITY-constants where to extend the canvas. Defaults to GRAVITY_CENTER
	 * @return \ESL_Image $this
	 */
	public function resizeExtend($iWidth, $iHeight, $iGravity = null)
	{
		static::assertSize($iWidth);
		static::assertSize($iHeight);

		if ($iGravity === null) {
			$iGravity = self::GRAVITY_CENTER;
		} else {
			static::assertGravity($iGravity);
		}

		// Scale to fit
		$this->thumbnail($iWidth, $iHeight, self::THUMBNAIL_SHRINK);

		// Extent canvas
		$this->extent($iWidth, $iHeight, $iGravity);

		return $this;
	}

	/**
	 * Resize to fix inside given box, ignoring aspect ratio and streching and shrinking sides independently
	 *
	 * @see $this->resizeBox Resize to fit inside given box
	 * @see $this->resizeCrop Resize to be framed in given box
	 * @see $this->resizeExtend Resize to fit inside given box, extendind canvas to match requested dimensions.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param int $iWidth Width
	 * @param int $iHeight Height
	 * @return \ESL_Image $this
	 */
	public function resizeStretch($iWidth, $iHeight)
	{
		static::assertSize($iWidth);
		static::assertSize($iHeight);

		$this->thumbnail($iWidth, $iHeight, self::THUMBNAIL_STRETCH);

		return $this;
	}

	/**
	 * Set the background color to be used when extending and creating rounded corners
	 *
	 * @param string $sColor In hex-notation (#ff66aa) or "none" for transparency
	 * @return \ESL_Image $this
	 */
	public function backgroundColor($sColor)
	{
		static::assertColor($sColor);

		$this->sBackgroundColor = $sColor;
		//$this->bDoBackgroundColor = true;

		return $this;
	}

	/**
	 * Add a border surrounding the canvas
	 *
	 * If the canvas was extended using resizeExtend, the border surrounds the entire canvas, not the image itself
	 * If you want to border the image and have the canvas extend outside of the border, you will have to use $this->overlay() to fake a border
	 *
	 * While the color 'none' will be accepted as parameter, it won't have any effect. It will draw an invisible border, making the image underneath remaining visibile
	 * If you want a transparent border surrounding the image use ESL_Image->mask() instead
	 *
	 * @param int $iWidth Border width in pixels
	 * @param string $sColor Border color in hex-notation, for exampe '#ff66aa'
	 * @return \ESL_Image $this
	 */
	public function border($iWidth, $sColor)
	{
		static::assertSize($iWidth);
		static::assertColor($sColor);

		$this->bDoBorder = true;
		$this->iBorderWidth = $iWidth;
		$this->sBorderColor = $sColor;

		return $this;
	}

	/**
	 * Round the four corners
	 *
	 * Similair to border-radius in CSS
	 * 
	 * @param int $iRadius Border radius in pixels
	 * @return \ESL_Image $this
	 */
	public function roundCorners($iRadius)
	{
		static::assertSize($iRadius);

		$this->bDoCorner = true;
		$this->bDoBackgroundColor = true;
		$this->iCornerRadius = $iRadius;

		return $this;
	}

	/**
	 * Overlay an image over the source image
	 *
	 * Usually used with PNG overlays with a transparent background to create for example watermarks, but can be used for anything.
	 *
	 * If no gravity is given the overlay will be streched to overlay the entire source image. Otherwise the overlay maintains its dimensions and is positioned accordingly
	 *
	 * @param string $sFile Image to use as overlay. Unrelated to basedir. Preferably provide an absolute path
	 * @param int $iGravity One of the ESL_Image::GRAVITY-constants where to position the overlay. Default null to stretch the overlay to fit
	 * @return \ESL_Image $this
	 */
	public function overlay($sPathImage, $iGravity = null)
	{
		$sFqnPath = static::assertLocalImage($sPathImage);
		if (null !== $iGravity) {
			static::assertGravity($iGravity);
		}

		$this->bDoOverlay = true;
		$this->sOverlayFile = $sFqnPath;
		$this->sOverlayGravity = $this->toGravity($iGravity);

		return $this;
	}

	/**
	 * Mask image with transparency
	 *
	 * Copy the transparency from the given mask into the source image. Any non-transparent regions are ignored, and therefor not modified in the source
	 *
	 * If no gravity is given the mask will be streched to overlay the entire source image. Otherwise the mask maintains its dimensions and is positioned accordingly
	 * 
	 * @param string $sFile Image to use as mask. Unrelated to basedir. Preferably provide an absolute path
	 * @param int $iGravity One of the ESL_Image::GRAVITY-constants where to position the mask. Default null to stretch the mask to fit
	 * @return \ESL_Image $this
	 */
	public function mask($sPathImage, $iGravity = null)
	{
		$sFqnPath = static::assertLocalImage($sPathImage);
		if (null !== $iGravity) {
			static::assertGravity($iGravity);
		}

		$this->bDoMask = true;
		$this->sMaskFile = $sFqnPath;
		$this->sMaskGravity = $this->toGravity($iGravity);
		// Make sure the backgroundcolor is applied
		$this->bDoBackgroundColor = true;

		return $this;
	}

	/**
	 * Ignore cached version of thumbnail and force to generate new file
	 *
	 * Use while debugging. Has a big performance impact because the cache will be ignored
	 * 
	 * @return \ESL_Image $this
	 */
	public function regenerate()
	{
		$this->bRegenerateThumb = true;
		return $this;
	}

	/**
	 * Change image density
	 *
	 * Affects the size of the outputted image. Usable for example to create a variant of a thumbnail to display on a retina display, which is required to be twice the size.
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param float $fDensity One of the ESL_Image::DENSITY-constants, or a custom value
	 * @return \ESL_Image $this
	 */
	public function density($fDensity)
	{
		if ((!is_int($fDensity) && !is_float($fDensity)) || $fDensity <= 0) {
			throw new InvalidArgumentException('Density is invalid');
		}

		$this->fDensity = $fDensity;
		return $this;
	}
}
?>