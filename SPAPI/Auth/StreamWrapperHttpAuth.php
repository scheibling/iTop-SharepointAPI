<?php
namespace SPAPI\Auth;
/**
 *    SoapClientAuth for accessing Web Services protected by HTTP authentication
 *    Author: tc
 *    Last Modified: 04/08/2011
 *    Update: 14/03/2012 - Fixed issue with CURLAUTH_ANY not authenticating to NTLM servers
 *    Download from: http://tcsoftware.net/blog/
 *
 *    Copyright (C) 2011  tc software (http://tcsoftware.net)
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class streamWrapperHttpAuth
 * @author tc
 * @copyright Copyright (C) 2011 tc software
 *
 * @package SPAPI\Auth
 */
class StreamWrapperHttpAuth {

	public static $Username = NULL;
	public static $Password = NULL;

	private $path = NULL;
	private $position = 0;
	private $buffer = NULL;
	private $curlHandle = NULL;

	public function stream_close() {
		if ($this->curlHandle) {
			curl_close($this->curlHandle);
		}
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->path = $path;
		$response = $this->postRequest($this->path);
		$this->buffer = ($response !== FALSE ? $response : NULL);
		$this->position = 0;

		return $response !== FALSE;
	}

	public function stream_eof() {
		return $this->position > strlen($this->buffer);
	}

	public function stream_flush() {
		$this->position = 0;
		$this->buffer = NULL;
	}

	public function stream_read($count) {
		if ($this->buffer) {
			$data = substr($this->buffer, $this->position, $count);
			$this->position += $count;

			return $data;
		}

		return FALSE;
	}

	public function stream_write($data) {
		return ($this->buffer ? TRUE : FALSE);
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		switch ($whence) {
			case SEEK_SET:
				$this->position = $offset;
				break;
			case SEEK_CUR:
				$this->position += $offset;
				break;
			case SEEK_END:
				$this->position = strlen($this->buffer) + $offset;
				break;
		}

		return TRUE;
	}

	public function stream_tell() {
		return $this->position;
	}

	public function stream_stat() {
		return array('size' => strlen($this->buffer));
	}

	public function url_stat($path, $flags) {
		$response = $this->postRequest($path);

		return array('size' => strlen($response));
	}

	protected function postRequest($path, $authType = CURLAUTH_ANY) {
		$this->curlHandle = curl_init($path);
		curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, TRUE);
		if (streamWrapperHttpAuth::$Username) {
			curl_setopt($this->curlHandle, CURLOPT_HTTPAUTH, $authType);
			curl_setopt(
				$this->curlHandle,
				CURLOPT_USERPWD,
				streamWrapperHttpAuth::$Username . ':' . streamWrapperHttpAuth::$Password
			);
		}

		curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, 2);

		$response = curl_exec($this->curlHandle);

		if (($info = curl_getinfo($this->curlHandle)) && $info['http_code'] == 200) {
			if (curl_errno($this->curlHandle) == 0) {
				return $response;
			}
			else {
				throw new \Exception(curl_error($this->curlHandle), curl_errno($this->curlHandle));
			}
		}
		else {
			if ($info['http_code'] == 401) { // Attempt NTLM Auth only, CURLAUTH_ANY does not work with NTML
				if ($authType != CURLAUTH_NTLM) {
					return $this->postRequest($path, CURLAUTH_NTLM);
				}
				else {
					throw new \Exception ('Access Denied', 401);
				}
			}
			else {
				if (curl_errno($this->curlHandle) != 0) {
					throw new \Exception(curl_error($this->curlHandle), curl_errno($this->curlHandle));
				}
				else {
					throw new \Exception('Error', $info['http_code']);
				}
			}
		}

		return FALSE;
	}
}