<?php

/**
 * Created by PhpStorm.
 * User: nounielbachir
 * Date: 18/12/14
 * Time: 20:41
 */
class LocalFakeFTP extends Abstract_FTP
{

    /**
     * @var string
     */
    protected $root_dir;
    /**
     * @var string
     */
    protected $current_dir;

    /**
     * @var bool
     */
    protected $opened;

    function __construct($local_root_dir = '')
    {
        $this->root_dir = $local_root_dir;
        $this->current_dir = $this->root_dir;
        $this->opened = false;
    }


    /**
     * FTP Connect
     *
     * @access    public
     * @param    array     the connection values
     * @return    bool
     */
    function connect($config = array())
    {
        $this->opened = $this->_login();
        return $this->opened;
    }

    /**
     * FTP Login
     *
     * @access    private
     * @return    bool
     */
    function _login()
    {
        return $this->_check_dir_exists($this->root_dir);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function _check_dir_exists($path = '')
    {
        if (file_exists($path) AND is_dir($path)) return true;
        else return false;
    }

    /**
     * Validates the connection ID
     *
     * @access    private
     * @return    bool
     */
    function _is_conn()
    {
        return $this->opened;
    }

    /**
     * Change directory
     *
     * The second parameter lets us momentarily turn off debugging so that
     * this function can be used to test for the existence of a folder
     * without throwing an error.  There's no FTP equivalent to is_dir()
     * so we do it by trying to change to a particular directory.
     * Internally, this parameter is only used by the "mirror" function below.
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return    bool
     */
    function changedir($path = '', $supress_debug = FALSE)
    {
        $this->current_dir = $this->_make_path($path);
        return $this->_check_dir_exists($this->current_dir);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function _make_path($path = '')
    {
        if (empty($path) OR $path === '' OR !is_string($path)) $path = $this->current_dir;
        if ($path{0} == DIRECTORY_SEPARATOR) $path = $this->root_dir . $path;
        elseif ($path{0} == '.') {
            if (strlen($path) > 1) $path = $this->current_dir . DIRECTORY_SEPARATOR . substr($path, 1, strlen($path));
            else $path = $this->current_dir;
        } else {
            $path = $this->current_dir . DIRECTORY_SEPARATOR . $path;
        }

        return $this->_normalizePath($path);
    }

    /**
     * Create a directory
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function mkdir($path = '', $permissions = NULL)
    {
        $path = $this->_make_path($path);
        if ($this->_check_dir_exists($path)) {
            return @mkdir($path, $permissions);
        }
        return false;
    }

    /**
     * Upload a file to the server
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    string
     * @return    bool
     */
    function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
    {
        $ftp_rempath = $this->_make_path(dirname($rempath));
        if (file_exists($locpath) AND file_exists($ftp_rempath) AND is_dir($ftp_rempath)) {
            return copy($locpath, $ftp_rempath . DIRECTORY_SEPARATOR . basename($rempath));
        }
        return false;
    }

    /**
     * Download a file from a remote server to the local server
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    string
     * @return    bool
     */
    function download($rempath, $locpath, $mode = 'auto')
    {
        $remote_filename = basename($rempath);
        $remote_dirname = dirname($rempath);
        $ftp_remote_path = $this->_make_path($remote_dirname);
        $rempath = $ftp_remote_path . DIRECTORY_SEPARATOR . $remote_filename;
        if (file_exists($rempath)) {
            $locpath_path = dirname($locpath);
            if (file_exists($locpath_path) AND is_dir($locpath_path)) {
                return copy($rempath, $locpath);
            }
        }
        return false;
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
        return $this->download($rempath, $locpath, $mode);
    }

    /**
     * Rename (or move) a file
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    bool
     * @return    bool
     */
    function rename($old_file, $new_file, $move = FALSE)
    {
        // TODO: Implement rename() method.
    }

    /**
     * Move a file
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    bool
     */
    function move($old_file, $new_file)
    {
        // TODO: Implement move() method.
    }

    /**
     * Rename (or move) a file
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function delete_file($filepath)
    {
        $path = $this->_make_path(dirname($filepath));
        $this->_check_dir_exists($path);
        return @unlink($path . DIRECTORY_SEPARATOR . basename($filepath));
    }

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function delete_dir($filepath)
    {
        // TODO: Implement delete_dir() method.
    }

    /**
     * Set file permissions
     *
     * @access    public
     * @param    string    the file path
     * @param    string    the permissions
     * @return    bool
     */
    function chmod($path, $perm)
    {
        // TODO: Implement chmod() method.
    }

    /**
     * FTP List files in the specified directory
     *
     * @access    public
     * @return    array
     */
    function list_files($path = '.')
    {
        $path = $this->_make_path($path);
        if (!$this->_check_dir_exists($path)) return array();
        return scandir($path);
    }

    /**
     * Read a directory and recreate it remotely
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a mirror via FTP based on it.  Whatever the directory structure
     * of the original file path will be recreated on the server.
     *
     * @access    public
     * @param    string    path to source with trailing slash
     * @param    string    path to destination - include the base folder with trailing slash
     * @return    bool
     */
    function mirror($locpath, $rempath)
    {
        // TODO: Implement mirror() method.
    }

    /**
     * Close the connection
     *
     * @access    public
     * @param    string    path to source
     * @param    string    path to destination
     * @return    bool
     */
    function close()
    {
        $this->opened = false;
        return $this->opened;
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
        $path = $this->_make_path($rempath);
        if (!$this->_check_dir_exists($path)) return array();
        $return = shell_exec("ls -al $path");
        if ($return) {
            $parsed = array();
            $res_arr = explode("\n", $return);
            $i = 0;
            foreach ($res_arr as $current) {
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
                if ($split[0] != "total" AND
                    $juste_dir_and_file AND
                    in_array($split[0]{0}, array('d', '-')) AND
                    !in_array($split[8], array('.', '..'))
                ) {
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
        } else return array();
    }

    /**
     * Check if $rempath is a directory or not
     * @param $rempath
     * @return bool
     */
    function isdir($rempath = '.')
    {
        $path = $this->_make_path($rempath);
        return $this->_check_dir_exists($path);
    }
}