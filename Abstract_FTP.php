<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Abstract class for all FTP types (FTP, SFTP, ...)
 *
 * @author nounielbachir
 */
abstract class Abstract_FTP
{

    /**
     * Initialize preferences
     *
     * @access    public
     * @param    array
     * @return    void
     */
    function initialize($config = array())
    {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }

        // Prep the hostname
        $this->hostname = preg_replace('|.+?://|', '', $this->hostname);
    }

    /**
     * FTP Connect
     *
     * @access    public
     * @param    array     the connection values
     * @return    bool
     */
    abstract function connect($config = array());

    /**
     * FTP Login
     *
     * @access    private
     * @return    bool
     */
    abstract function _login();

    /**
     * Validates the connection ID
     *
     * @access    private
     * @return    bool
     */
    abstract function _is_conn();

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
    abstract function changedir($path = '', $supress_debug = FALSE);

    /**
     * Create a directory
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    abstract function mkdir($path = '', $permissions = NULL);

    /**
     * Upload a file to the server
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    string
     * @return    bool
     */
    abstract function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL);

    /**
     * Safe Upload a file to the server
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    string
     * @return    bool if the file is uploaded and checked in the ftp that exists.
     */
    function safe_upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
    {
        $uploaded = $this->upload($locpath, $rempath, $mode, $permissions);
        if ($uploaded) {
            try {
                $uploaded = $this->_filename_matchs_filesnames($locpath, $this->list_files(dirname($rempath)));
            } catch (Exception $e) {
                $uploaded = false;
            }
        }
        return $uploaded;
    }

    /**
     * Détermine est-ce que le fichier spécifié par le chemin $filepath existe dans la liste des fichiers
     * spécifient par leur name dans la liste $filesnames
     *
     * @param $filepath
     * @param array $filesnames
     * @return bool
     */
    protected function _filename_matchs_filesnames($filepath, array $filesnames)
    {
        if (!is_array($filesnames) OR !is_string($filepath)) return false;

        $filename = basename($filepath);
        $filtered_files = array_filter($filesnames, function ($item) use ($filename) {
            return strcmp($item, $filename) == 0;
        });
        return !empty($filtered_files) AND count($filtered_files) > 0;
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
    abstract function download($rempath, $locpath, $mode = 'auto');

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
    abstract function download_asynch($rempath, $locpath, $mode = 'auto');

    /**
     * Rename (or move) a file
     *
     * @access    public
     * @param    string
     * @param    string
     * @param    bool
     * @return    bool
     */
    abstract function rename($old_file, $new_file, $move = FALSE);

    /**
     * Move a file
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    bool
     */
    abstract function move($old_file, $new_file);

    /**
     * Rename (or move) a file
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    abstract function delete_file($filepath);

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    abstract function delete_dir($filepath);

    /**
     * Set file permissions
     *
     * @access    public
     * @param    string    the file path
     * @param    string    the permissions
     * @return    bool
     */
    abstract function chmod($path, $perm);

    /**
     * FTP List files in the specified directory
     *
     * @access    public
     * @return    array
     */
    abstract function list_files($path = '.');

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
    abstract function mirror($locpath, $rempath);

    /**
     * Close the connection
     *
     * @access    public
     * @param    string    path to source
     * @param    string    path to destination
     * @return    bool
     */
    abstract function close();

    /**
     * Retourne le nombre de fichier dans le dossier donné par le paramètre $rempath
     * si $deep est négatif il retourne le nombre de fichier total dans toute l'arborescence du dossier
     * @param $rempath
     * @param int $deep directory hiarchy deep
     * @throws Exception
     */
    function files_count($rempath = '.', $deep = -1)
    {
        $obj = $this;
        $count_function = function ($path, $count) use (&$count_function, $obj) {
            $files = $obj->list_files_details($path, true);
            foreach ($files as $key => $value) {
                if ($value['isdir']) $count = $count_function($path . DIRECTORY_SEPARATOR . $value['name'], $count);
                else $count++;
            }
            return $count;
        };
        return $count_function($rempath, 0);
    }

    /**
     * Permet de synchroniser le dossier $locpath avec le dossier $rempath
     * C'est l'opération inverse de la fonction mirror
     * @param string $rempath
     * @param string $locpath
     * @throws Exception
     */
    function synchronise($rempath, $locpath)
    {
        $this->_synchronise($rempath, $locpath, false);
    }

    /**
     * @param string $rempath
     * @param string $locpath
     * @param bool $create_local_dir si true on creer le dossier local s'il n'existe pas
     * @throws Exception
     */
    protected function _synchronise($rempath, $locpath, $create_local_dir = false)
    {
        $locpath = $this->_normalizePath($locpath);
        $rempath = $this->_normalizePath($rempath);

        //Verifier est-ce que les dossiers existent
        if (!$create_local_dir AND !is_dir($locpath))
            throw new \Exception(__FUNCTION__ . ' le path ' . $locpath . ' n existe pas');
        if (!$this->isdir($rempath))
            throw new \Exception(__FUNCTION__ . ' le remote path ' . $rempath . ' n existe pas');

        //Récuprérer le contenu du dossier distant
        $files = $this->list_files_details($rempath, true);
        //Sychroniser chaque dossier de façon récursive
        foreach ($files as $key => $file) {
            if ($file['isdir']) {
                $local_file_path = $locpath . DIRECTORY_SEPARATOR . $file['name'];
                if (!is_dir($local_file_path)) {
                    @mkdir($local_file_path, 0777, true);
                    if (!is_dir($local_file_path)) continue;
                }
                $this->_synchronise($rempath . DIRECTORY_SEPARATOR . $file['name'], $local_file_path, true);
            } else {
                $local_file = $locpath . DIRECTORY_SEPARATOR . $file['name'];
                if (!file_exists($local_file)) {
                    $this->download($rempath . DIRECTORY_SEPARATOR . $file['name'], $local_file);
                }
            }
        }
    }

    /**
     * Normaliser le chemin d'un dossier en :
     * - eliminant les DIRECTORY_SEPARATOR de la fin du path
     * - elmiminer les DIRECTORY_SEPARATOR multiple du path
     * @param string $path
     * @return string
     * @source @link(http://php.net/manual/en/function.realpath.php)
     */
    protected function _normalizePath($path = '')
    {
        if (!is_string($path) OR trim($path) === '') return $path;
        $parts = array();// Array to build a new path from the good parts
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);// Replace backslashes with DIRECTORY_SEPARATOR
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);// Replace forwardslashes with DIRECTORY_SEPARATOR
        $path = preg_replace('/\/+/', DIRECTORY_SEPARATOR, $path);// Combine multiple slashes into a single slash
        $path = preg_replace('/\\+/', DIRECTORY_SEPARATOR, $path);// Combine multiple slashes into a single slash
        $segments = explode(DIRECTORY_SEPARATOR, $path);// Collect path segments

        foreach ($segments as $segment) {
            if ($segment != '.') {
                $test = array_pop($parts);
                if (is_null($test))
                    $parts[] = $segment;
                else if ($segment == '..') {
                    if ($test == '..')
                        $parts[] = $test;

                    if ($test == '..' || $test == '')
                        $parts[] = $segment;
                } else {
                    $parts[] = $test;
                    $parts[] = $segment;
                }
            }
        }
        return implode(DIRECTORY_SEPARATOR, $parts);
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
    abstract function list_files_details($rempath = '.', $juste_dir_and_file = false);

    /**
     * Check if $rempath is a directory or not
     * @param $rempath
     * @return bool
     */
    abstract function isdir($rempath = '.');

    /**
     * Supprimer du FTP les fichiers qui existent dans le dossier local donné par $locpath.
     * Sachant que le dossier $locpath est synchronisé avec $rempath.
     * Cela util si on veut par supprimer les fichiers qu'on a téléchargé en local du serveur FTP pour libérer de l'espace disque
     * @param $rempath
     * @param $locpath
     * @param callable $predicat function($filepath){return false/true;}
     * @throws Exception
     */
    function delete_synchronised_files($rempath, $locpath, $predicat = NULL)
    {
        $locpath = $this->_normalizePath($locpath);
        $rempath = $this->_normalizePath($rempath);

        //Verifier est-ce que les dossiers existent
        if (!is_dir($locpath))
            throw new \Exception(__FUNCTION__ . ' le path ' . $locpath . ' n existe pas');
        if (!$this->isdir($rempath))
            throw new \Exception(__FUNCTION__ . ' le remote path ' . $rempath . ' n existe pas');

        //Récuprérer le contenu du dossier distant
        $files = $this->list_files_details($rempath, true);
        //Sychroniser chaque dossier de façon récursive
        foreach ($files as $key => $file) {
            if ($file['isdir']) {
                $this->delete_synchronised_files($rempath . DIRECTORY_SEPARATOR . $file['name'], $locpath . DIRECTORY_SEPARATOR . $file['name'], $predicat);
            } else {
                $local_file = $locpath . DIRECTORY_SEPARATOR . $file['name'];
                $rem_filepath = $rempath . DIRECTORY_SEPARATOR . $file['name'];
                if (file_exists($local_file)) {
                    if (is_null($predicat) OR (!is_null($predicat) AND is_callable($predicat) AND $predicat($rem_filepath))) {
                        $deleted = $this->delete_file($rem_filepath);
                        if ($deleted) continue; //Il faut peut être lancer une exception
                    }
                }
            }
        }
    }
}

?>
