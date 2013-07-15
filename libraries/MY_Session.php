<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CGIT Native Sessions
 *
 * Allows the use of native PHP sessions in CodeIgniter
 *
 * Copyright (C) 2013 Castlegate IT Ltd <info@castlegateit.co.uk>
 *
 * Description: A native session class for the CodeIgniter framework, CGIT Native Sessions extends the existing session class and provides the same interface
 * and functions. Sessions ids are automatically regenerated each second to prevent session highjacking and user agent is checked too.
 *
 * Released: 12/06/2013
 * Requirements: PHP5 or above and Codeigniter 2.0+
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free 
 * Software Foundation; either version 3 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for 
 * more details. You should have received a copy of the GNU General Public License along with this program. If not, see 
 * <http://opensource.org/licenses/gpl-license.php>
 *
 * @package CGIT Native PHP Sessions
 * @author  Andy Reading - Castlegate IT Ltd <andy@castlegateit.co.uk>
 * @link    https://github.com/castlegateit/cgit_session
 * @version 1.4
 */

class MY_Session extends CI_Session {

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Flashdata prefix
     * @var string
     */
    public $flashdata_key = '__flash';

    /**
     * Should the session cookie be HTTP Only to prevent XSS attacks.
     * @var boolean
     */
    private $_httponly;

    /**
     * Should the cookie be secure?
     * @var boolean
     */
    private $_secure;

    /**
     * Session expiry in seconds
     * @var integer
     */
    private $_expiry;

    /**
     * Session regeneration time in seconds
     * @var integer
     */
    private $_regenerate;

    /**
     * The session name references the name of the session, which is used in cookies and URLs (e.g. PHPSESSID).
     * @var string
     */
    private $_session_name;

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     *
     * Starts or resumes the session, validates the session against the user agents and regenerates the session id. Also sweeps and removes old flashdata
     *
     * @access  public
     * @param   array
     * @return  void
     */
    public function __construct($params = array())
    {
        // Load configuration
        $CI =& get_instance();
        $CI->config->load('session');

        // Set configuration values
        $this->_session_name         = $CI->config->item('cgit_sess_session_name');
        $this->_expiry               = $CI->config->item('cgit_sess_expiry');
        $this->_secure               = $CI->config->item('cgit_sess_secure');
        $this->_httponly             = $CI->config->item('cgit_sess_httponly');
        $this->_regenerate           = $CI->config->item('cgit_sess_regenerate');
        $this->_session_key          = $CI->config->item('cgit_sess_session_key');
        
        // Create/resume the session
        $this->sess_create();

        // Validate the session
        if ($this->validate_sess())
        {
            // Regenerate the session ID
            $this->regenerate_id();
        }
        else
        {
            // Session is invalid, destroy it and start again
            $this->sess_destroy();
            $this->sess_create();
        }

        // Delete 'old' flashdata (from last request)
        $this->_flashdata_sweep();

        // Mark all new flashdata as old (data will be deleted before next request)
        $this->_flashdata_mark();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Session create
     *
     * Creates a new session or resumes an existing one. If new, the user agent will be recorded
     *
     * @access  public
     * @return  void
     */
    public function sess_create()
    {
        // Configure the session cookie
        session_set_cookie_params($this->_expiry, '/', $_SERVER['HTTP_HOST'], $this->_secure, $this->_httponly);

        // Configure session name
        session_name($this->_session_name);

        // Start
        session_start();

        // Update the expiry time of the session if its not set
        if (!isset($_SESSION[$this->_session_key]['__expiry']))
        {
            $this->update_expiry();
        }

        // Update the user agent of the session if its not set
        if(!isset($_SESSION[$this->_session_key]['__agent']))
        {
            $this->update_agent();
        }

        // Update the regeneration time of the session if its not set
        if(!isset($_SESSION[$this->_session_key]['__regeneration']))
        {
            $this->update_regeneration();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Session destroy
     *
     * Destorys the current session, unsets the $_SESSION superglobal
     *
     * @access  public
     * @return  void
     */
    public function sess_destroy()
    {
        // Destroy!
        session_unset();
        session_destroy();
        $_SESSION = array();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate session
     *
     * Checks the current user agent against that sorted on session creation
     *
     * @access  public
     * @return  boolean
     */
    public function validate_sess()
    {
        // Check the expiry time has not exceeded or the user agent has not changed
        if (!isset($_SESSION[$this->_session_key]['__agent']) || $_SESSION[$this->_session_key]['__agent'] != $_SERVER['HTTP_USER_AGENT'] 
            || $_SESSION[$this->_session_key]['__expiry'] < time())
        {
            return FALSE;
        }

        // If successful then the expiry time should be updated
        $this->update_expiry();

        return TRUE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Regenerate id
     *
     * Regenerates the session id. Old sessions are not removed, to prevent issues where the a user may "double refresh" and not recieve the new sessions ID
     * so they lose their session completely. Instead, the old session is allowed to remain, however the expiry time is reduced to ensure lots of old 
     * sessions are not left open.
     *
     * @access  public
     * @return  void
     */
    public function regenerate_id()
    {
        // Check its time for regeneration
        if ($_SESSION[$this->_session_key]['__regeneration'] < time())
        {
            // Update the regeneration time
            $this->update_regeneration();

            // Regenerate
            session_regenerate_id(TRUE);

            // Update the new expiry time
            $this->update_expiry();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Update expiry
     *
     * Updates the expiry time of the session
     *
     * @access  public
     * @return  void
     */
    public function update_expiry()
    {
        $_SESSION[$this->_session_key]['__expiry'] = time() + $this->_expiry;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Update agent
     *
     * Updates the user agent associated with the session
     *
     * @access  public
     * @return  void
     */
    public function update_agent()
    {
        $_SESSION[$this->_session_key]['__agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Update regeneration
     *
     * Updates the session ID regeneration date
     *
     * @access  public
     * @return  void
     */
    public function update_regeneration()
    {
        $_SESSION[$this->_session_key]['__regeneration'] = time() + $this->_regenerate;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Session ID
     *
     * Returns the current session ID
     *
     * @access  public
     * @return  string
     */
    public function session_id()
    {
        return session_id();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Userdata
     *
     * Returns a specific session variable, or FALSE if it does not exist
     *
     * @access  public
     * @return  mixed
     */
    public function userdata($item)
    {
        return (!isset($_SESSION[$item])) ? FALSE : $_SESSION[$item];
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * All userdata
     *
     * Returns all session data as an array
     *
     * @access  public
     * @return  array
     */
    public function all_userdata()
    {
        return $_SESSION;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set userdata
     *
     * Sets session data as key, value or as an associate array
     *
     * @access  public
     * @return  void
     */
    public function set_userdata($newdata = array(), $newval = '')
    {
        if (is_string($newdata))
        {
            $newdata = array($newdata => $newval);
        }

        if (count($newdata) > 0)
        {
            foreach ($newdata as $key => $val)
            {
                $_SESSION[$key] = $val;
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Unset userdata
     *
     * Removes a specific session array key or an array of keys
     *
     * @access  public
     * @return  void
     */
    public function unset_userdata($newdata = array())
    {
        if (is_string($newdata))
        {
            $newdata = array($newdata => '');
        }

        if (count($newdata) > 0)
        {
            foreach ($newdata as $key => $val)
            {
                unset($_SESSION[$key]);
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set flashdata
     *
     * Sets data as temporary session items that are removed when accessed
     *
     * @access  public
     * @return  void
     */
    public function set_flashdata($newdata = array(), $newval = '')
    {
        if (is_string($newdata))
        {
            $newdata = array($newdata => $newval);
        }

        if (count($newdata) > 0)
        {
            foreach ($newdata as $key => $val)
            {
                $flashdata_key = $this->flashdata_key.':new:'.$key;
                $this->set_userdata($flashdata_key, $val);
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Keep flashdata
     *
     * Marks a particular flashdata item to be kept instead of automatically being deleted
     *
     * @access  public
     * @return  void
     */
    public function keep_flashdata($key)
    {
        // 'old' flashdata gets removed.  Here we mark all
        // flashdata as 'new' to preserve it from _flashdata_sweep()
        // Note the function will return FALSE if the $key
        // provided cannot be found
        $old_flashdata_key = $this->flashdata_key.':old:'.$key;
        $value = $this->userdata($old_flashdata_key);

        $new_flashdata_key = $this->flashdata_key.':new:'.$key;
        $this->set_userdata($new_flashdata_key, $value);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Flashdata
     *
     * Retrieves a flash data time from the session
     *
     * @access  public
     * @return  void
     */
    public function flashdata($key)
    {
        $flashdata_key = $this->flashdata_key.':old:'.$key;
        return $this->userdata($flashdata_key);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Flashdata mark
     *
     * Marks flashdata to being "old" and ready for deletion
     *
     * @access  public
     * @return  void
     */
    public function _flashdata_mark()
    {
        $userdata = $this->all_userdata();
        foreach ($userdata as $name => $value)
        {
            $parts = explode(':new:', $name);
            if (is_array($parts) && count($parts) === 2)
            {
                $new_name = $this->flashdata_key.':old:'.$parts[1];
                $this->set_userdata($new_name, $value);
                $this->unset_userdata($name);
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Flashdata sweep
     *
     * Deletes old flashdata
     *
     * @access  public
     * @return  void
     */
    public function _flashdata_sweep()
    {
        $userdata = $this->all_userdata();
        foreach ($userdata as $key => $value)
        {
            if (strpos($key, ':old:'))
            {
                $this->unset_userdata($key);
            }
        }

    }

}

/* End of file MY_Session.php */
/* Location: ./application/libraries/MY_Session.php */