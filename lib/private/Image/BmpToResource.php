<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Class for bitmap image conversion
 */

namespace OC\Image;

use OC\OCS\Exception;

class BmpToResource {
	const MAGIC = 19778; // ASCII BM
	const BITMAP_HEADER_SIZE_BYTES = 14;

	const DIB_BITMAPINFOHEADER_SIZE_BYTES = 40;

	const COMPRESSION_BI_RGB = 0;
	const COMPRESSION_BI_RLE8 = 1;
	const COMPRESSION_BI_RLE4 = 2;
	const COMPRESSION_BI_BITFIELDS = 3;

	/** @var string $fileName */
	private $fileName;

	/** @var \SplFileObject $file */
	private $file;

	/** @var array $header */
	private $header = [];

	/** @var array $palette */
	private $palette = [];

	/** @var string[] $pixelArray */
	private $pixelArray;

	/** @var resource $resource */
	private $resource;

	/** @var array $bytesPerDepth */
	private $bytesPerDepth = [
		1 => 1,
		4 => 1,
		8 => 1,
		16 => 2,
		24 => 3,
		32 => 3,
	];

	/**
	 * BmpToResource constructor.
	 *
	 * @param string $fileName
	 */
	public function __construct($fileName){
		$this->fileName = $fileName;
		$this->file = new \SplFileObject($this->fileName, 'rb');
	}

	/**
	 * @return resource
	 * @throws \Exception
	 */
	public function toResource(){
		try {
			$this->header = $this->readBitmapHeader();
			$this->header += $this->readDibHeader();
			if ($this->header['compression'] === self::COMPRESSION_BI_BITFIELDS) {
				$this->header += $this->readBitMasks();
			}
			// Color Table is mandatory for color depths <= 8 bits
			if ($this->header['bits'] <= 8) {
				$this->palette = $this->readColorTable($this->header['colors']);
			}

			$this->pixelArray = $this->readPixelArray();

			// create gd image
			$this->resource = imagecreatetruecolor($this->header['width'], $this->header['height']);
			if ($this->resource === false) {
				throw new \RuntimeException('imagecreatetruecolor failed for file ' . $this->fileName . '" with dimensions ' . $this->header['width'] . 'x' . $this->header['height']);
			}

			$this->pixelArrayToImage();
		} catch (\Exception $e) {
			$this->file = null;
			throw $e;
		}

		$this->file = null;
		return $this->resource;
	}

	/**
	 * @return array
	 */
	public function getHeader(){
		return $this->header;
	}

	/**
	 * https://en.wikipedia.org/wiki/BMP_file_format#Bitmap_file_header
	 * @return array
	 */
	private function readBitmapHeader(){
		$bitmapHeaderArray = @unpack('vtype/Vfilesize/Vreserved/Voffset', $this->readFile(self::BITMAP_HEADER_SIZE_BYTES));
		if (!isset($bitmapHeaderArray['type']) || $bitmapHeaderArray['type'] !== self::MAGIC) {
			throw new \DomainException('No valid bitmap signature found in ' . $this->fileName);
		}
		return [
			'filesize' => $bitmapHeaderArray['filesize'],
			'offset' =>  $bitmapHeaderArray['offset'],
		];
	}

	/**
	 * https://en.wikipedia.org/wiki/BMP_file_format#DIB_header_.28bitmap_information_header.29
	 * @return array
	 */
	private function readDibHeader(){
		$dibHeaderSizeArray = @unpack('Vheadersize', $this->readFile(4));
		if (!isset($dibHeaderSizeArray['headersize']) || $dibHeaderSizeArray['headersize'] < self::DIB_BITMAPINFOHEADER_SIZE_BYTES) {
			throw new \UnexpectedValueException('Unsupported DIB header version in ' . $this->fileName);
		}
		$rawDibHeader = $this->readFile($dibHeaderSizeArray['headersize'] - 4);
		$dibHeader = @unpack('Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', $rawDibHeader);

		// fixup colors
		$dibHeader['colors'] = $dibHeader['colors'] === 0 ? pow(2, $dibHeader['bits']) : $dibHeader['colors'];

		// fixup imagesize - it can be zero
		if ($dibHeader['imagesize'] < 1) {
			// No compression - calculate it in our own
			if ($dibHeader['compression'] === self::COMPRESSION_BI_RGB) {
				$bytesPerRow = intval(floor(($dibHeader['bits'] * $dibHeader['width'] + 31) / 32) * 4);
				$dibHeader['imagesize'] = $bytesPerRow * abs($dibHeader['height']);
			} else {
				$dibHeader['imagesize'] = @filesize($this->fileName) - $this->header['offset'];
			}
		}

		if ($dibHeader['imagesize'] < 1) {
			throw new \UnexpectedValueException('Can not obtain image size of ' . $this->fileName);
		}

		$validBitDepth = array_keys($this->bytesPerDepth);
		if (!in_array($dibHeader['bits'], $validBitDepth)) {
			throw new \UnexpectedValueException('Bit Depth ' . $dibHeader['bits'] . ' in ' . $this->fileName . ' is not supported');
		}

		return $dibHeader;
	}

	/**
	 * @return array
	 */
	private function readBitMasks(){
		return @unpack('VrMask/VgMask/VbMask', $this->readFile(12));
	}

	/**
	 * Read a color table
	 * http://www.dragonwins.com/domains/getteched/bmp/bmpfileformat.htm#The%20Color%20Table
	 * four bytes are ordered as follows:
	 * [ZERO][RED][GREEN][BLUE] Little Endian
	 * @param int $colors
	 * @return array
	 */
	private function readColorTable($colors){
		$palette = @unpack('V' . $colors, $this->readFile($colors * 4));
		return array_values($palette);
	}

	/**
	 * @return string[]
	 */
	private function readPixelArray(){
		// there is a gap a possible after the header
		$this->file->fseek($this->header['offset'], SEEK_SET);
		$pixelString = $this->readFile($this->header['imagesize']);
		// uncompress data
		switch ($this->header['compression']) {
			case self::COMPRESSION_BI_RLE8:
				$pixelString = $this->rle8_decode($pixelString, $this->header['width']);
				break;
			case self::COMPRESSION_BI_RLE4:
				$pixelString = $this->rle4_decode($pixelString, $this->header['width']);
				break;
		}

		$bytesPerRow = intval(floor(($this->header['bits'] * $this->header['width'] + 31) / 32) * 4);
		$plainPixelArray = str_split($pixelString, $bytesPerRow);

		$bytesPerColumn = $this->bytesPerDepth[$this->header['bits']];
		$pixelArray = [];
		foreach ($plainPixelArray as $pixelRow){
			$pixelArray[] = str_split($pixelRow, $bytesPerColumn);
		}

		return $pixelArray;
	}

	/**
	 * @return resource
	 */
	private function pixelArrayToImage(){
		// Positive height: Bottom row first.
		// Negative height: Upper row first
		// Do not reverse this? This section can be incomplete.
		$pixelArray = ($this->header['height']<0) ? array_reverse($this->pixelArray) : $this->pixelArray;

		$x = 0;
		$y = 0;
		foreach ($pixelArray as $pixelRow){
			foreach ($pixelRow as $column){
				$colors = $this->getColors($column);
				foreach ($colors as $color) {
					imagesetpixel($this->resource, $x, $y, $color);
					$x++;
					if ($x>=$this->header['width']){
						$x=0;
						break(2);
					}
				}
			}
			$y++;
			if ($y >= abs($this->header['height'])){
				break;
			}
		}
		return $this->resource;
	}

	/**
	 * Get a color(s) of the current pixel(s)
	 * @param string $raw
	 * @return array
	 */
	private function getColors($raw){
		$extra = chr(0); // used to complement an argument to word or double word
		$colors = [];
		switch ($this->header['bits']){
			case 32:
			case 24:
				$colors = @unpack('V', $raw . $extra);
				break;
			case 16:
				$colors = @unpack('v', $raw);
				if (!isset($this->header['rMask']) || $this->header['rMask'] != 0xf800) {
					$colors[1] = (($colors[1] & 0x7c00) >> 7) * 65536 + (($colors[1] & 0x03e0) >> 2) * 256 + (($colors[1] & 0x001f) << 3); // 555
				} else {
					$colors[1] = (($colors[1] & 0xf800) >> 8) * 65536 + (($colors[1] & 0x07e0) >> 3) * 256 + (($colors[1] & 0x001f) << 3); // 565
				}
				break;
			case 8:
			case 4:
			case 1:
				$colors = array_map(
					function ($i){
						return $this->palette[ $i ];
					},
					$this->splitByteIntoArray($raw, $this->header['bits'])
				);
				break;
		}
		$colors = array_values($colors);
		return $colors;
	}

	/**
	 * Split a byte into array of its binary digits
	 * @param string $byte a single char
	 * @param int $bitsPerPart how many bits should be in one part
	 * @return array
	 */
	private function splitByteIntoArray($byte, $bitsPerPart){
		$code = ord($byte);
		$stringOfBits = str_pad(decbin($code), 8, "0", \STR_PAD_LEFT);
		return str_split($stringOfBits, $bitsPerPart);
	}

	/**
	 * Decoder for RLE8 compression in windows bitmaps
	 * see https://msdn.microsoft.com/en-us/library/windows/desktop/dd183383(v=vs.85).aspx
	 *
	 * @param string  $str   Data to decode
	 * @param integer $width Image width
	 *
	 * @return string
	 */
	private function rle8_decode($str, $width){
		$lineWidth = $width + (3 - ($width-1) % 4);
		$out = '';
		$cnt = strlen($str);

		for ($i = 0; $i <$cnt; $i++) {
			$o = ord($str[$i]);
			switch ($o){
				case 0: // ESCAPE
					$i++;
					switch (ord($str[$i])){
						case 0: // NEW LINE
							$padCnt = $lineWidth - strlen($out)%$lineWidth;
							if ($padCnt<$lineWidth) {
								$out .= str_repeat(chr(0), $padCnt); // pad line
							}
							break;
						case 1: // END OF FILE
							$padCnt = $lineWidth - strlen($out)%$lineWidth;
							if ($padCnt<$lineWidth) {
								$out .= str_repeat(chr(0), $padCnt); // pad line
							}
							break 3;
						case 2: // DELTA
							$i += 2;
							break;
						default: // ABSOLUTE MODE
							$num = ord($str[$i]);
							for ($j = 0; $j < $num; $j++){
								$out .= $str[++$i];
							}
							if ($num % 2){
								$i++;
							}
					}
					break;
				default:
					$out .= str_repeat($str[++$i], $o);
			}
		}
		return $out;
	}

	/**
	 * Decoder for RLE4 compression in windows bitmaps
	 * see https://msdn.microsoft.com/en-us/library/windows/desktop/dd183383(v=vs.85).aspx
	 *
	 * @param string  $str   Data to decode
	 * @param integer $width Image width
	 *
	 * @return string
	 */
	private function rle4_decode($str, $width) {
		$w = floor($width/2) + ($width % 2);
		$lineWidth = $w + (3 - ( ($width-1) / 2) % 4);
		$pixels = array();
		$cnt = strlen($str);
		$c = 0;

		for ($i = 0; $i < $cnt; $i++) {
			$o = ord($str[$i]);
			switch ($o) {
				case 0: // ESCAPE
					$i++;
					switch (ord($str[$i])){
						case 0: // NEW LINE
							while (count($pixels)%$lineWidth != 0) {
								$pixels[] = 0;
							}
							break;
						case 1: // END OF FILE
							while (count($pixels)%$lineWidth != 0) {
								$pixels[] = 0;
							}
							break 3;
						case 2: // DELTA
							$i += 2;
							break;
						default: // ABSOLUTE MODE
							$num = ord($str[$i]);
							for ($j = 0; $j < $num; $j++) {
								if ($j%2 == 0) {
									$c = ord($str[++$i]);
									$pixels[] = ($c & 240)>>4;
								} else {
									$pixels[] = $c & 15;
								}
							}

							if ($num % 2 == 0) {
								$i++;
							}
					}
					break;
				default:
					$c = ord($str[++$i]);
					for ($j = 0; $j < $o; $j++) {
						$pixels[] = ($j%2==0 ? ($c & 240)>>4 : $c & 15);
					}
			}
		}

		$out = '';
		if (count($pixels)%2) {
			$pixels[] = 0;
		}

		$cnt = count($pixels)/2;

		for ($i = 0; $i < $cnt; $i++) {
			$out .= chr(16*$pixels[2*$i] + $pixels[2*$i+1]);
		}

		return $out;
	}

	/**
	 * @param string $bytesToRead
	 * @return string
	 */
	protected function readFile($bytesToRead){
		$data = $this->file->fread($bytesToRead);
		if ($data === false) {
			throw new \LengthException('Unexpected end of file. ' . $this->fileName);
		}
		return $data;
	}
}
