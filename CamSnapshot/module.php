<?
require_once(__DIR__ . DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."BrownsonBase.php"); 

class CamSnapshot extends BrownsonBase
{
	
	// -------------------------------------------------------------------------
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyString("SnapshotURL", 0);

		$this->RegisterPropertyBoolean("CreateSmall", false);		
		$this->RegisterPropertyFloat("RatioSmall", 2.0);

		$this->RegisterPropertyBoolean("AutoRefresh", true);		
		$this->RegisterTimer("RefreshTimer", 0, 'CamSnapshot_Refresh($_IPS[\'TARGET\']);');
		$this->RegisterPropertyInteger("Interval", 300);
		
		$this->RegisterScript('refresh', 'Refresh', '<?\n\n CamSnapshot_Refresh($_IPS[\'TARGET\']); \n\n?>', 0);
	}

	// -------------------------------------------------------------------------
	public function RequestAction($Ident, $Value)
	{
		switch($Ident) {
			default:
				throw new Exception("Invalid ident");
		}
	}
	
	// -------------------------------------------------------------------------
	public function ApplyChanges()
	{
		parent::ApplyChanges();
		
		$snapshotURL      = $this->ReadPropertyString('SnapshotURL');
		$autoRefresh      = $this->ReadPropertyBoolean('AutoRefresh');
		
		if ($snapshotURL=="") {
			$this->SetStatus(201); //No URL specified
		} else if (!$autoRefresh) {
			$this->SetStatus(104); //Instanz ist inaktiv
		} else {
			$this->SetStatus(102); //Instanz ist aktiv
		}
		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];
		if ($status==102 && $autoRefresh) {
			$this->SetTimerInterval("RefreshTimer", $this->ReadPropertyInteger("Interval")*1000);
		} else {
			$this->SetTimerInterval("RefreshTimer", 0);
		}
	}
	
	// -------------------------------------------------------------------------
	public function Refresh()
	{
		$this->SendDebug("Refresh", "Execute Refresh of SnapshotImage:", 0);
		$this->SendDebug("Refresh", "Available Memory: ".ini_get('memory_limit'), 0);
		
		if ($this->IsInstancePropertiesValid()) {
			$snapshotURL      = $this->ReadPropertyString('SnapshotURL');
			
			// Download Images
			$this->ShowMemoryUsage('Startup: ');
			$data = $this->GetSnapshotImageFromURL($snapshotURL);

			if ($data===false) {
				$this->SetStatus(202); //Download failed
				return;
			}
			
			$filenameLarge = IPS_GetKernelDir().'media/CamSnapshot_'.$this->InstanceID.'_Large.jpg';
			$filenameSmall = IPS_GetKernelDir().'media/CamSnapshot_'.$this->InstanceID.'_Small.jpg';
			$mediaIDLarge  = RegisterMedia ('imagelarge', 'ImageLarge', $filenameLarge);
			$mediaIDSmall  = RegisterMedia ('imagesmall', 'ImageSmall', $filenameSmall);
   
			if ($this->WriteMediaImage($filenameLarge, $data)===false) {
				$this->SetStatus(203); //Write failed
				return;
			}
			if ($this->WriteMediaImageResized($filenameLarge, $filenameSmall)===false) {
				$this->SetStatus(203); //Write failed
				return;
			}
			
			$this->ShowMemoryUsage('finished:');
		}
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------
	private function WriteMediaImageResized ($fileNameLarge, $fileNameSmall) {
		$createSmall      = $this->ReadPropertyBoolean('CreateSmall');
		$ratioSmall      = $this->ReadPropertyFloat('RatioSmall');
		if ($createSmall) {
			list($width, $height, $type, $attr) = getimagesize($fileNameLarge);
						
			$thumb = imagecreatetruecolor($width/$ratioSmall,
										  $height/$ratioSmall);
			$source = imagecreatefromjpeg($filenameLarge);
			imagecopyresized($thumb, $source, 0, 0, 0, 0,
	                         $width/$ratioSmall,
						     $height/$ratioSmall,
						     $width, 
						     $height);
						 
			imagejpeg($thumb, $filenameSmall);
		}

		return true;
	}
	
	// -------------------------------------------------------------------------
	private function WriteMediaImage ($fileName, $fileContent) {
		$this->SendDebug("WriteMediaImage", 'Write ImageData to '.$fileName);
		$result = file_put_contents($fileName, $fileContent);
		if ($result===false) {
			$this->SendDebug("Refresh", 'Error writing ImageData to '.$fileName.' --> Retry ...');
			IPS_Sleep(1000);
			$result = file_put_contents($fileName, $fileContent);
		}
		if ($result===false) {
			$this->SendDebug("Refresh", 'Error writing ImageData to '.$fileName);
			return false;
		}
		return true;
	}
	
	// -------------------------------------------------------------------------
	private function GetSnapshotImageFromURL($snapshotURL) {
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $snapshotURL);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
	
		$fileContent = curl_exec($curl_handle);
		curl_close($curl_handle);

		if ($fileContent===false) {
			IPS_LogMessage(__file__, 'File "'.CAM_IMAGE_URL.'" could NOT be found on the Server !!!');
			return false;
		}
		$this->SendDebug("GetSnapshot", "Loaded SnapshotImage from URL", 0);

		return $fileContent;
	}
	
	
}

?>
