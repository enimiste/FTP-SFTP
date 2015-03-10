<?php

require_once 'Abstract_FTP.php';
/**
 * Description of SFTP
 *
 * @author nounielbachir
 */
class SFTP extends Abstract_FTP {
    var $hostname	= '';
    var $username	= '';
    var $password	= '';
    var $port = 22;
    var $debug		= TRUE;
    var $conn_sftp = FALSE;
    
    /**
     * Constructor - Sets Preferences
     *
     * The constructor can be passed an array of config values
     */
    public function __construct($config = array()) {
        $this->initialize($config);
    }

    /**
     * SFTP Connect
     *
     * @access public
     * @param array the connection values
     * @return bool
     */
    function connect($config = array()) {
        if (count($config) > 0) {
            $this->initialize($config);
        }

        // Open up SSH connection to server with supplied credetials.
        $this->conn_sftp = new Net_SFTP($this->hostname, $this->port);
        
        // Try and login...
        if (!$this->_login()) {
            if ($this->debug == TRUE) {
                $this->_error('sftp_unable_to_login_to_ssh');
            }
            return FALSE;
        }

        return TRUE;
    }

// --------------------------------------------------------------------

    /**
     * SFTP Login
     *
     * @access private
     * @return bool
     */
    function _login() {
       return $this->conn_sftp->login($this->username, $this->password);
    }

// --------------------------------------------------------------------

    /**
     * Validates the connection ID
     *
     * @access private
     * @return bool
     */
    function _is_conn() {
        /*if (!is_resource($this->conn_sftp)) {
            if ($this->debug == TRUE) {
                $this->_error('sftp_no_connection');
            }
            return FALSE;
        }*/
        return TRUE;
    }

    // --------------------------------------------------------------------


    /**
     * Create a directory
     *
     * @access public
     * @param string $path
     * @param null $permissions
     * @throws Exception
     * @internal param $string
     * @return bool
     */
    function mkdir($path = '', $permissions = NULL) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::mkdir');
    }

// --------------------------------------------------------------------

    /**
     * Upload a file to the server
     *
     * @access public
     * @param $locpath
     * @param $rempath
     * @param string $mode
     * @param null $permissions
     * @throws Exception
     * @internal param $string
     * @internal param $string
     * @return bool
     */
    function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::upload');
    }

// --------------------------------------------------------------------

    /**
     * Download a file to the server
     *
     * @access public
     * @param string
     * @param string
     * @return bool
     */
    function download($rempath, $locpath, $mode = 'auto') {
        if (!$this->_is_conn()) {
            return FALSE;
        }
      
        $result = $this->conn_sftp->get($rempath, $locpath);

        if ($result === FALSE) {
            if ($this->debug == TRUE) {
                $this->_error('sftp_unable_to_download');
            }
            return FALSE;
        }
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Rename a file
     *
     * @access public
     * @param string
     * @param string
     * @param bool
     * @return bool
     */
    function rename($old_file, $new_file, $move = FALSE) {
        $this->conn_sftp->rename($old_file, $new_file);
    }

// --------------------------------------------------------------------

    /**
     * Delete a file
     *
     * @access public
     * @param $filepath
     * @throws Exception
     * @internal param $string
     * @return bool
     */
    function delete_file($filepath) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::delete_file');
    }

// --------------------------------------------------------------------

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @access public
     * @param $filepath
     * @throws Exception
     * @internal param $string
     * @return bool
     */
    function delete_dir($filepath) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::delete_dir');
    }

// --------------------------------------------------------------------

    /**
     * FTP List files in the specified directory
     *
     * @access public
     * @param string
     * @param bool
     * @return array
     */
    function list_files($path = '.') {
        if (!$this->_is_conn()) {
            return FALSE;
        }

        $res = $this->conn_sftp->nlist($path);
        if($res === FALSE) $this->_error ($this->conn_sftp->getSFTPErrors());
        return $res;
    }

// ------------------------------------------------------------------------

    /**
     * Upload data from a variable
     *
     * @access private
     * @param $data_to_send
     * @param $rempath
     * @throws Exception
     * @internal param $string
     * @internal param $string
     * @return bool
     */
    function upload_from_var($data_to_send, $rempath) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::upload_from_var');
    }

// ------------------------------------------------------------------------

    /**
     *
     *
     * @access private
     * @param $line
     * @throws Exception
     * @internal param $string
     * @return bool
     */
    function _error($line) {
        $errors = $this->conn_sftp->getSFTPErrors();
        if(is_array($errors)) $errors = implode(', ', $errors);
        throw new Exception($line . '[' . $errors .']');
    }

    public function changedir($path = '', $supress_debug = FALSE) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::changdir');
    }

    public function chmod($path, $perm) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::chmod');
    }

    public function close() {
        return TRUE;
    }

    public function mirror($locpath, $rempath) {
        throw new Exception('Not implemented : ' . __CLASS__ . '::mirror');
    }

    public function move($old_file, $new_file) {
        return $this->rename($old_file, $new_file, TRUE);
    }

    /**
     * Check if $rempath is a directory or not
     * @param string $rempath
     * @throws Exception
     * @return bool
     */
    function isdir($rempath='.')
    {
        throw new Exception('Not implemented : ' . __CLASS__ . '::isdir');
    }

    /**
     * Fait une liste détaillée des fichiers d'un dossier
     * return array of type :
     * ['isdir'=>true/false, 'perms'=>, 'owner'=>, 'group'=>, 'size'=>taille en octets, 'month'=>, 'day'=>, 'time/year'=>, 'name'=>]
     * isdir, name, size are mondatory
     * @access    public
     * @param string $rempath
     * @param bool $juste_dir_and_file si true on return seulement les dossiers et fichiers
     * @return    array
     */
    function list_files_details($rempath = '.', $juste_dir_and_file = false)
    {
        $parsed = array();
        $list = $this->conn_sftp->rawlist($rempath);
        $i = 0;
        foreach ($list as $current) {
            $split = preg_split("[ ]", $current, 9, PREG_SPLIT_NO_EMPTY);
            /*
             * $split[0]{0} :
             *   “-” pour un fichier normal (ex: /etc/cups/command.types)
             *   “d” pour un répertoire (ex: /etc/cups)
             *   “c” pour un périphérique “caractère” (ex: un modem comme /dev/rtc)
             *   “b” pour un périphérique “bloc” (ex: un disque comme /dev/hda ou /dev/sda)
             *   “l” pour un lien symbolique (ex: /boot/vmlinuz)
             *   “s” pour une socket locale (ex:/dev/log)
             *   “p” pour un tube nommé (ex: /dev/bootplash ou /dev/xconsole)
             */
            if ($split[0] != "total" AND $juste_dir_and_file AND in_array($split[0]{0}, array('d', '-'))) {
                $parsed[$i]['isdir'] = $split[0]{0} === "d";
                $parsed[$i]['perms'] = $split[0];
                //$parsed[$i]['number'] = $split[1];//(sans intérêt courant: il s'agit d'un comptage de liaisons)
                $parsed[$i]['owner'] = $split[2];
                $parsed[$i]['group'] = $split[3];
                $parsed[$i]['size'] = $split[4];
                $parsed[$i]['month'] = $split[5];
                $parsed[$i]['day'] = $split[6];
                $parsed[$i]['time/year'] = $split[7];
                $parsed[$i]['name'] = $split[8];
                $i++;
            }
        }
        return $parsed;
    }

    /**
     * Download a file from a remote server to the local server asynchronously
     *
     * @access    public
     * @param $rempath
     * @param $locpath
     * @param string $mode
     * @throws Exception
     * @internal param $string
     * @internal param $string
     * @internal param $string
     * @return    bool
     */
    function download_asynch($rempath, $locpath, $mode = 'auto')
    {
        throw new \Exception(__FUNCTION__ . ' Not implemented');
    }
}

?>
