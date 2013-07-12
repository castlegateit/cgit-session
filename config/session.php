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


/**
 * Session key
 *
 * Index to use for storing session specific data in $_SESSION super global array.
 *
 * Default: '__cgit_session_'
 */
$config['cgit_sess_session_key'] = '__cgit_session_';


/**
 * HTTP only
 *
 * Should the session cookie be HTTP Only to prevent XSS attacks. http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly
 */
$config['cgit_sess_httponly'] = TRUE;


/**
 * Secure
 *
 * Should the cookie be secure? Set to TRUE when using SSL.
 */
$config['cgit_sess_secure'] = FALSE;


/**
 * Expiry
 *
 * Session expiry time in seconds.
 *
 * Default: 7200 (2 hours)
 */
$config['cgit_sess_expiry'] = 7200;


/**
 * Regeneration time
 *
 * Session regeneration time in seconds
 *
 * Default: 300 (5 minutes)
 */
$config['cgit_sess_regenerate'] = 300;


/**
 * Session name
 *
 * The session name references the name of the session, which is used in cookies and URLs (e.g. PHPSESSID). It should contain only alphanumeric 
 * characters; If name is specified, the name of the current session is changed to its value. 
 *
 * Default: session
 */
$config['cgit_sess_session_name'] = 'session';


/* End of file session.php */
/* Location: ./application/config/session.php */