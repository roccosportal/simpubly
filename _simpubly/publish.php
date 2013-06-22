<?php


// CHANGE THESE ONES //

const PROJECT_ZIP_URL = 'https://github.com/roccosportal/blog-roccosportal-com/archive/master.zip';
const INNER_ZIP_PATH = 'blog-roccosportal-com-master'; // set the inner zip folder path that sould be published; set to null to publish complete content
const PROJECT_DIR = '../'; // relative from current dir
const DO_BACKUP = true;
const AUTHORIZATION_KEY = 'test';  // set to null for no authorization

$ignorePathsForCopy = array(
	'Application/Configs/Config.php'
);

$ignorePathsForBackup = array(
    '_simpubly'
);


// YOU PROBABLY DO NOT HAVE TO CHANGE THESE ONES //

const SIMPUBLY_WORKING_DIR = './'; // relative to this file
const TMP_FOLDER = 'tmp';
const ZIP_FILE = 'zip.zip';
const BACKUP_FOLDER = 'backups';
const BACKUP_FILE_NAME = 'backup';
const ZIP_EXTRACTED_FOLDER = 'zip_extracted';


// AT THIS POINT YOU SHOULD NOT CHANGE ANYTHING //

// declare helper functions
function startsWith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}
class Logger {
    protected $newline = null;

    protected static $instance = null;

    public function __construct(){
        $isCLI = ( php_sapi_name() == 'cli' );
        if($isCLI){
            $this->newline = "\r\n";
        }
        else {
            $this->newline = '<br />';
        }
    }

    public function log($message, $type = 'info'){
        echo '[' . $type . '] ' .$message . $this->newline;
    }

    public static function get(){
        if(self::$instance == null){
            self::$instance = new Logger();
        }
        return self::$instance;
    }
}
class Startup {
    public function checkIfAuthorized(){
        $isAuthorized = false;
        if(AUTHORIZATION_KEY == null){
            $isAuthorized = true;
        }
        else {
            if((php_sapi_name() == 'cli' ) == true){ // always allow cli
                $isAuthorized = true;
            }
            else if(isset($_REQUEST['key']) && $_REQUEST['key'] == AUTHORIZATION_KEY){
                $isAuthorized = true;
            }
        }
        if($isAuthorized){
            Logger::get()->log('authorization accepted');
        }
        else {
            Logger::get()->log('not authorized!');
            die();
        }
    }
    public function start(){
        $this->makeTmpDir();
    }
    public function clean(){
        $this->cleanTmpDir();
    }
    protected function cleanTmpDir(){
        // clear tmp space
        if(is_dir(SIMPUBLY_WORKING_DIR . TMP_FOLDER)){
            $this->rmdirRecursive(SIMPUBLY_WORKING_DIR . TMP_FOLDER);
        }
    }
    protected function rmdirRecursive($dir){
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
          (is_dir("$dir/$file")) ? $this->rmdirRecursive("$dir/$file") : unlink("$dir/$file"); 
        } 
        return rmdir($dir); 
    }
    protected function makeTmpDir(){
        mkdir(SIMPUBLY_WORKING_DIR . TMP_FOLDER, 0777);
    }
}
class Backup {
    protected $backupBasePath = null;

    protected $ignorePaths = array();

    public function start(){
        Logger::get()->log('start backup...');
        @mkdir(SIMPUBLY_WORKING_DIR . BACKUP_FOLDER, 0777);
        $now = date('Y-m-d_H_i_s');
        $backup_file = SIMPUBLY_WORKING_DIR . BACKUP_FOLDER . DIRECTORY_SEPARATOR . BACKUP_FILE_NAME . '-' . $now . '.zip';
        $path = PROJECT_DIR;
        $this->backupBasePath = str_replace('\\', '/', realpath($path));
        $this->zip($path, $backup_file);
        Logger::get()->log('backup finished');
    }

    public function setIgnorePaths($ignorePaths){
        $this->ignorePaths = $ignorePaths;
    }

    public function isIgnorePath($path){
        $path = str_replace($this->backupBasePath . DIRECTORY_SEPARATOR, '', $path);
        foreach ($this->ignorePaths as $ignorePath) {
            if(startsWith($path, $ignorePath)){
                Logger::get()->log('ignoring path: ' . $path);
                return true;
            }
        }
        return false;
    }


    protected function zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }
        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($source) === true)
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file)
            {
                $file = str_replace('\\', '/', $file);

                // Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..')) )
                    continue;

                $file = realpath($file);

                if (is_dir($file) === true )
                {
                    if(!$this->isIgnorePath($file)){
                        $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                    }
                }
                else if (is_file($file) === true)
                {
                    if(!$this->isIgnorePath($file)){
                        $zip->addFromString(str_replace($source . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
                    }
                }
            }
        }
        else if (is_file($source) === true)
        {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }
}
class Downloader {
    public function start(){
        Logger::get()->log('start downloading...');
        $this->download(PROJECT_ZIP_URL, SIMPUBLY_WORKING_DIR  . TMP_FOLDER . DIRECTORY_SEPARATOR . ZIP_FILE);
        Logger::get()->log('download finished');
    }

    protected function download($from, $to){
        file_put_contents($to, fopen($from, 'r'));
    }
}
class Unzipper {
    public function start(){
        Logger::get()->log('start unzipping...');
        // try to unzip
        $zip = new ZipArchive;
        $res = $zip->open(SIMPUBLY_WORKING_DIR  . TMP_FOLDER . DIRECTORY_SEPARATOR . ZIP_FILE);
        if ($res !== TRUE) {
            Logger::get()->log('error while unziping');
            die();
        }

        $zip->extractTo(SIMPUBLY_WORKING_DIR. TMP_FOLDER . DIRECTORY_SEPARATOR . ZIP_EXTRACTED_FOLDER);
        $zip->close();

        Logger::get()->log('unzipping finished');
    }
}
class Copier {
    protected $zipCopyPath = null;

    public function start(){
        Logger::get()->log('start copying...');
        $this->zipCopyPath =  SIMPUBLY_WORKING_DIR. TMP_FOLDER . DIRECTORY_SEPARATOR . ZIP_EXTRACTED_FOLDER;

        if(INNER_ZIP_PATH != null){
            $this->zipCopyPath .= DIRECTORY_SEPARATOR . INNER_ZIP_PATH;
        }
        Logger::get()->log('from: ' . realpath($this->zipCopyPath));
        Logger::get()->log('to: ' . realpath(PROJECT_DIR));

        $this->recurseCopy($this->zipCopyPath, PROJECT_DIR);
        Logger::get()->log('copying finished');
    }

    public function setIgnorePaths($ignorePaths){
        $this->ignorePaths = $ignorePaths;
    }

    public function isIgnorePath($path){
        $path = str_replace($this->zipCopyPath . DIRECTORY_SEPARATOR, '', $path);
        foreach ($this->ignorePaths as $ignorePath) {
            if(startsWith($path, $ignorePath)){
                Logger::get()->log('ignoring path: ' . $path);
                return true;
            }
        }
        return false;
    }

    protected function recurseCopy($src,$dst){
        $dir = opendir($src); 
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) { 
                    if(!$this->isIgnorePath($src . DIRECTORY_SEPARATOR . $file)){
                        $this->recurseCopy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
                    }
                } 
                else if(!$this->isIgnorePath($src . DIRECTORY_SEPARATOR . $file)){
                    copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
                } 
            } 
        } 
        closedir($dir); 
    }
}

Logger::get()->log('start simpubly...');
// THE PROGRAM STARS HERE //
$startUp = new StartUp();
$startUp->checkIfAuthorized();
$startUp->clean();
$startUp->start();

if(DO_BACKUP){
	$backup = new Backup();
    $backup->setIgnorePaths($ignorePathsForBackup);
    $backup->start();
}

$downloader = new Downloader();
$downloader->start();

$unzipper = new Unzipper();
$unzipper->start();

$copier = new Copier();
$copier->setIgnorePaths($ignorePathsForCopy);
$copier->start();

$startUp->clean();

Logger::get()->log('everything finished');