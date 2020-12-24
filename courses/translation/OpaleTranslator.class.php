<?php
class OpaleTranslator
{
    public $codelangfrom;
    public $codelangto;
    public $apicode;
    public $baseUrlApi = "https://translation.googleapis.com/language/translate/v2?key=";

    public function OpaleTranslator($codelangfrom, $codelangto, $apicode)
    {
        $this->codelangfrom = $codelangfrom;
        $this->codelangto = $codelangto;
        $this->apicode = $apicode;
    }

    /**
     * Verify if file is .scar
     * @param mixed $archive Path to archive
     * @param mixed $destination Path to extract in
     * @return $filename Name of file or false if error
     */
    public function extract($archive, $destination)
    {
        $filename = false;
        $ext = pathinfo($archive, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'scar':
                $filename = $this->extractZipArchive($archive, $destination);
                break;
            default:
                $GLOBALS['status'] = array('error' => "Please input a .scar file .\n");
                break;
        }
        return $filename;
    }

    /**
     * Extract the archive in destination
     * @param mixed $archive Path to archive
     * @param mixed $destination Path to extract in
     * @return $filename Name of file or false if error
     */
    public function extractZipArchive($archive, $destination)
    {
        $zip = new ZipArchive;
        if ($zip->open($archive) === TRUE) {
            // Check if destination is writable
            if (!file_exists($destination)){
                mkdir($destination);
            }
            if (is_writeable($destination)) {
                $zip->extractTo($destination);
                $filename = explode('/', $zip->filename);
                $filename = $filename[count($filename) - 1];
                $zip->close();
                $GLOBALS['status'] = array('success' => "Files unzipped successfully \n");
                return $filename;
            } else {
                $GLOBALS['status'] = array('error' => "Directory not writeable by webserver.\n");
                return false;
            }
        } else {
            $GLOBALS['status'] = array('error' => "Cannot read .zip archive.\n");
            return false;
        }
    }

    /**
     * Rename root folders of archive
     * @param mixed $folderroot Folder root archive
     * @param mixed $oldname Old name folder
     * @param mixed $newname New name folder
     * @return $filename true or false if error
     */
    public function renameFolders($folderroot, $oldname, $newname)
    {
        $oldfile = $folderroot . '/' . $oldname;
        $newfile = $folderroot . '/' . $newname;
        if (!rename($oldfile, $newfile)) {
            $GLOBALS['status'] = array('error' => "Error on renaming folder" . $oldfile . "\n");
            return false;
        }
        $oldfile = $folderroot . '/' . $newname . '/' . $oldname;
        $newfile = $folderroot . '/' . $newname . '/' . $newname;
        if (!rename($oldfile, $newfile)) {
            $GLOBALS['status'] = array('error' => "Error on renaming folder" . $oldfile . "\n");
            return false;
        }
        $GLOBALS['status'] = array('success' => "Folders renamed successfully \n");
        return true;
    }

    /**
     * Create a scar archive and remove temporary files
     * @param mixed $folderroot Folder root to zip
     * @param mixed $zipname Zip Name
     * @param mixed $destination Where to zip
     * @return $filename true or false if error
     */
    public function outputZipArchive($foldertozip, $zipname, $destination)
    {
        $rootPath = realpath($foldertozip);
        $zip = new ZipArchive;
        $zip->open($destination . $zipname . ".scar", ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        if (!$zip->close()) {
            $GLOBALS['status'] = array('error' => "Files impossible to zip \n");
            return true;
        }
        exec('rm -r ' . $foldertozip);
        $GLOBALS['status'] = array('success' => "Files zipped successfully \n");
        return true;
    }

    public function editXML($xmlfile)
    {
        $dom = new DOMDocument();
        $dom->load($xmlfile);
        $xml = $dom->documentElement;
        if (!$xml) {
            $GLOBALS['status'] = array('error' => 'Error on xml File');
        } else {
            $GLOBALS['status'] = array('success' => 'Xml oppened successfully');
        }
        $this->translateCollectionType($xml,'title');
        $this->translateCollectionType($xml,'para');

        $dom->save($xmlfile);

    }

    private function translateCollectionType($xml,$collectiontype){
        $collection = $xml->getElementsByTagName($collectiontype);
        if($collectiontype == 'para'){
            for ($i=0; $i < $collection->length; $i++) {
                $tmp = $collection->item($i)->nodeValue."0";
                $collection->item($i)->nodeValue = $tmp;
            }
        }else{
            for ($i=0; $i < $collection->length; $i++) {
                $stringstotranslate[] = urlencode($collection->item($i)->nodeValue);
            }
            $translatedstring = $this->translate($stringstotranslate);
            if ($translatedstring) {
                for ($i=0; $i < $collection->length; $i++) {
                    $collection->item($i)->nodeValue = $stringstotranslate[$i];
                }
            }else{
                for ($i=0; $i < $collection->length; $i++) {
                    $collection->item($i)->nodeValue = "";
                }
            }
        }
    }
    public function translate($stringtotranslate)
    {
        $url = $this->baseUrlApi.$this->apicode;
        foreach($stringtotranslate as $string){
            $url.="&q=".$string;
        }
        $url.="&source=".urlencode($this->codelangfrom)."&target=".urlencode($this->codelangto);
        //print $url;
        if (! function_exists("curl_init"))
		{
		      print "Error, your PHP does not support curl functions.\n";
		      die();
        }
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "Mozilla");
		$body = curl_exec($ch);
		curl_close($ch);
        $json = json_decode($body, true);
        $translatedstring="";
        print var_dump($json);
        if (!empty($json['error']))
		{
		    print "Error: ".$json['error']['code']." ".$url."\n";
			$translatedstring = "";
        }else{
            $translations = $json['data']['translations'];
            $stringtotranslate = array();
            foreach ($translations as $translation) {
                $translatedstring[] = $translation['translatedText'];
            }
        }
        return $translatedstring;
    }
}
