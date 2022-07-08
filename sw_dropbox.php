<?php 
/*
USAGE : 
$drop = new sw_dropbox();
$drop ->sendFile('my_file.jpg','./','/DROPBOX_FOMDER/');
*/

class sw_dropbox
{
	function __construct()
	{
		$this->key = 'YOUR_KEY';
		$this->secret = 'YOUR_SECRET_KEY';
		$this->refreshToken = 'REFRESH_TOKEN_GENERATED';
	}

	private function setSessionToken()
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$token = $this->getToken();
		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$_SESSION['dropboxToken'] = $token['access_token'];
		$_SESSION['dropboxExpires'] = $timestamp + ($token['expires_in'] - 100);
		return $token['access_token'];
	}

	private function getSessionToken()
	{
		if (isset($_SESSION['dropboxToken']) && !empty($_SESSION['dropboxToken'])) {

			$date = new DateTime();
			$timestamp = $date->getTimestamp();
			if ($timestamp < $_SESSION['dropboxExpires']) {
				return $_SESSION['dropboxToken'];
			}
			unset($_SESSION["dropboxToken"]);
			unset($_SESSION["dropboxExpires"]);
		}
		return $this->setSessionToken();
	}

	private function req($token_url, $headers, $request_data)
	{
		$ch = curl_init($token_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$exe = curl_exec($ch);
		curl_close($ch);
		return $exe;
	}

	private function getToken()
	{
		try {
			$token_url = "https://{$this->key}:{$this->secret}@api.dropbox.com/oauth2/token";
			$headers = array(
				"Authorization: Basic " . base64_encode("{$this->key}:{$this->secret}"),
				"Content-Type: application/x-www-form-urlencoded"
			);
			$request_data = array(
				"refresh_token" => $this->refreshToken,
				"grant_type" => "refresh_token" // Constant for this request
			);
			$res = $this->req($token_url, $headers, $request_data);
			if (!empty($res)) return json_decode($res, true);
			else return [];
		} catch (Exception $e) {
			echo ("[{$e->getCode()}] {$e->getMessage()}");
			return [];
		}
	}
	public function sendFile($fileName, $localPath = '/', $dropboxPath = '/')
	{
		$authorizationToken = $this->getSessionToken();
		$fp = fopen($localPath . $fileName, 'rb');
		$size = filesize($localPath . $fileName);
		$headers = array(
			'Authorization: Bearer ' . $authorizationToken,
			'Content-Type: application/octet-stream',
			'Dropbox-API-Arg: {"path":"' . $dropboxPath . $fileName . '", "mode":"add"}'
		);
		$token_url = "https://content.dropboxapi.com/2/files/upload";
		$ch = curl_init($token_url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$exe = curl_exec($ch);
		curl_close($ch);
		return $exe;
	}
};
?>
