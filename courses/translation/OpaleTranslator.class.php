<?php
class OpaleTranslator
{
    public $codelangfrom;
    public $codelangto;
    public $apicode;
    public $baseUrlApi = "https://translation.googleapis.com/language/translate/v2?key=";
    public $resultfilenames = array();

    /**
     * Constructor of OpaleTranslator class
     * @param mixed $codelangfrom The language code of the base archive
     * @param mixed $codelangto The language code of the archive to create
     * @param mixed $apicode The apicode for google translate api
     */
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
            if (!file_exists($destination)) {
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
        if (!is_dir($oldfile) || !rename($oldfile, $newfile)) {
            $GLOBALS['status'] = array('error' => "Error on renaming folder" . $oldfile . "\n");
            return false;
        }
        $oldfile = $folderroot . '/' . $newname . '/' . $oldname;
        $newfile = $folderroot . '/' . $newname . '/' . $newname;
        if (!is_dir($oldfile) || !rename($oldfile, $newfile)) {
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
        $GLOBALS['status'] = array('success' => "Files zipped successfully \n");
        return true;
    }
    /**
     * Edit xml files and quiz files
     * @param mixed $foldertozip Folder root to zip
     * @return false if error
     */
    public function editXML($foldertozip)
    {
        $this->resultfilenames = array();
        $this->glob_recur($foldertozip,"Cours.xml");
        if(!$this->translateXML()){
            return false;
        }
        $this->resultfilenames = array();
        $this->glob_recur($foldertozip,"AccesExterne.xml");
        $this->translateXML();

        $this->resultfilenames = array();
        $this->glob_recur($foldertozip,"GrainSécurité.xml");
        $this->translateXML();

        $this->resultfilenames = array();
        $this->glob_recur($foldertozip, ".quiz");
        foreach ($this->resultfilenames as $filename) {
            $this->translateQuiz($filename);
        }
        return true;
    }
    /**
     * Translate Quiz files
     * @param mixed $quizpath a path to an .quiz file
     * @return false if error true else
     */
    public function translateQuiz($quizpath)
    {
        $filename = explode('/', $quizpath);
        $filename = $filename[count($filename) - 1];
        print "***** Translation " . $filename . " *****\n";
        $dom = new DOMDocument();
        if (!$dom->load($quizpath)) {
            $GLOBALS['status'] = array('error' => 'Error on quiz ' . $filename . " File\n");
            return false;
        } else {
            print "***** " . $filename . " oppened successfully *****\n";
        }
        $xml = $dom->documentElement;
        $this->translateCollectionType($xml, 'para');
        $dom->save($quizpath);
        print "***** " . $filename . " translated *****\n";
        return true;
    }

    /**
     * Translate Xml files
     * @return false if error true else
     */
    public function translateXML()
    {
        $xmlfile = $this->resultfilenames[0];
        $filename = explode('/', $xmlfile);
        $filename = $filename[count($filename) - 1];
        $dom = new DOMDocument();
        if (!$dom->load($xmlfile)) {
            $GLOBALS['status'] = array('error' => 'Error on xml File\n');
            return false;
        } else {
            print "***** " . $filename . " oppened successfully *****\n";
        }
        $xml = $dom->documentElement;
        print "***** Translation titles *****\n";
        $this->translateCollectionType($xml, 'title');
        print "***** Titles translated *****\n";
        print "***** Translation paragraphes *****\n";
        $this->translateCollectionType($xml, 'para');
        print "***** Paragraphs translated *****\n";
        $dom->save($xmlfile);
        return true;
    }

    /**
     * Translate a type of an xml file
     * @param mixed $xml a Node collection of an xml file
     * @param mixed $collectiontype a type in xml file
     */
    private function translateCollectionType($xml, $collectiontype)
    {
        $collection = $xml->getElementsByTagName($collectiontype);
        $i = 0;
        do {
            $node = $collection->item($i);
            $this->translate_rec($node);
            $i++;
        } while ($collection->item($i));
    }

    /**
     * Translate a Node and all his childNodes
     * @param mixed $node The node to translate
     */
    private function  translate_rec($node)
    {
        $childnodes = $node->childNodes;
        if (!$childnodes) {
            if (!$this->startsWith($node->nodeValue, "https://") && !$this->startsWith($node->nodeValue, "http://") && !$this->startsWith($node->nodeName, "sp:title")) {
                $stringtoprint = $node->nodeValue;
                $translatedstring = $this->translate(array($node->nodeValue));
                $node->nodeValue = html_entity_decode($translatedstring)." ";
                $stringtoprint .= " => ".$node->nodeValue;
                print $stringtoprint."\n";
            }
        } else {
            foreach ($childnodes as $childnode) {
                $this->translate_rec($childnode);
            }
        }
    }

    /**
     * Translate a string with google traduction api
     * @param mixed $stringtotranslate the string to translate
     * @return $translatedstring the strign tranlated
     */
    public function translate($stringtotranslate)
    {
        $tmp = explode('_', $this->codelangfrom);
        if (!empty($tmp[1])) $this->codelangfrom = $tmp[0];
        $tmp = explode('_', $this->codelangto);
        if (!empty($tmp[1])) $this->codelangto = $tmp[0];

        $src_text_to_translate = preg_replace('/%s/', 'SSSSS', implode('', $stringtotranslate));
        $src_text_to_translate = preg_replace('/' . preg_quote('\n\n') . '/', ' NNNNN ', $src_text_to_translate);

        $url = $this->baseUrlApi . $this->apicode;
        $url .= "&q=" . urlencode($src_text_to_translate);
        $url .= "&source=" . $this->codelangfrom . "&target=" . $this->codelangto;

        if (!function_exists("curl_init")) {
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
        $translatedstring = "";

        if (!empty($json['error'])) {
            print "Error: " . $json['error']['code'] . " " . $url . "\n";
            $translatedstring = "";
        } else {
            $translatedstring = $json['data']['translations'][0]['translatedText'];
        }
        return $translatedstring;
    }

    /**
     * Find files with $needle patern
     * @param mixed $folderroot the folder root
     * @param mixed $needle the patern of the file to find
     */
    private function glob_recur($folderroot, $needle)
    {
        $filenames = glob($folderroot . "/*");
        foreach ($filenames as $filename) {
            if (is_dir($filename)) {
                $this->glob_recur($filename, $needle);
            } else {
                if ($this->endsWith($filename, $needle)) {
                    $this->resultfilenames[] =  $filename;
                }
            }
        }
    }

    /**
     * Verify if $haystack ends with $needle
     * @param mixed $haystack the string to verfy
     * @param mixed $needle the needle used to verify
     * @return true if $haystack ends with $needle false else
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Verify if $haystack starts with $needle
     * @param mixed $haystack the string to verfy
     * @param mixed $needle the needle used to verify
     * @return true if $haystack starts with $needle false else
     */
    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    /**
     * Delete the temporary file used to do the archive
     * @param mixed $foldertozip the folder to delete
     */
    public function delTmp($foldertozip)
    {
        exec('rm -r ' . $foldertozip);
    }
}
