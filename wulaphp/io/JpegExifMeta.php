<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\io;

define("EXIF_OBJECT_NAME", "005");
define("EXIF_EDIT_STATUS", "007");
define("EXIF_PRIORITY", "010");
define("EXIF_CATEGORY", "015");
define("EXIF_SUPPLEMENTAL_CATEGORY", "020");
define("EXIF_FIXTURE_IDENTIFIER", "022");
define("EXIF_KEYWORDS", "025");
define("EXIF_RELEASE_DATE", "030");
define("EXIF_RELEASE_TIME", "035");
define("EXIF_SPECIAL_INSTRUCTIONS", "040");
define("EXIF_REFERENCE_SERVICE", "045");
define("EXIF_REFERENCE_DATE", "047");
define("EXIF_REFERENCE_NUMBER", "050");
define("EXIF_CREATED_DATE", "055");
define("EXIF_CREATED_TIME", "060");
define("EXIF_ORIGINATING_PROGRAM", "065");
define("EXIF_PROGRAM_VERSION", "070");
define("EXIF_OBJECT_CYCLE", "075");
define("EXIF_BYLINE", "080");
define("EXIF_BYLINE_TITLE", "085");
define("EXIF_CITY", "090");
define("EXIF_PROVINCE_STATE", "095");
define("EXIF_COUNTRY_CODE", "100");
define("EXIF_COUNTRY", "101");
define("EXIF_ORIGINAL_TRANSMISSION_REFERENCE", "103");
define("EXIF_HEADLINE", "105");
define("EXIF_CREDIT", "110");
define("EXIF_SOURCE", "115");
define("EXIF_COPYRIGHT_STRING", "116");
define("EXIF_CAPTION", "120");
define("EXIF_LOCAL_CAPTION", "121");

class JpegExifMeta {
    private $meta = [];
    private $file = null;

    public function __construct($filename) {
        $info = null;
        getimagesize($filename, $info);
        if (isset($info["APP13"])) $this->meta = iptcparse($info["APP13"]);
        $this->file = $filename;
    }

    public function getValue($tag) {
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : "";
    }

    public function setValue($tag, $data) {
        $this->meta["2#$tag"] = [$data];

        $this->write();
    }

    private function write() {
        $content = iptcembed($this->binary(), $this->file, 0);

        return file_put_contents($this->file, $content);
    }

    private function binary() {
        $data = [];

        foreach (array_keys($this->meta) as $key) {
            $tag     = str_replace("2#", "", $key);
            $data [] = $this->iptc_maketag(2, $tag, $this->meta[ $key ][0]);
        }

        return implode('', $data);
    }

    private function iptc_maketag($rec, $data, $value) {
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);

        if ($length < 0x8000) {
            $retval .= chr($length >> 8) . chr($length & 0xFF);
        } else {
            $retval .= chr(0x80) . chr(0x04) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        return $retval . $value;
    }

    public function dump() {
        echo "<pre>";
        print_r($this->meta);
        echo "</pre>";
    }

    public function removeAllTags() {
        $this->meta = [];
        $img        = imagecreatefromstring(implode(file($this->file)));
        if (file_exists($this->file)) unlink($this->file);

        return imagejpeg($img, $this->file, 100);
    }
}