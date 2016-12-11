<?
require_once(__DIR__ . DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."BrownsonBase.php"); 

class CamSnapshot extends BrownsonBase
{
	
	// -------------------------------------------------------------------------
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyString("SnapshotURL", '');

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
			
			$filenameLarge = IPS_GetKernelDir().'media\CamSnapshot_'.$this->InstanceID.'_Large.jpg';
			$mediaIDLarge  = $this->RegisterMedia ('imagelarge', 'ImageLarge', $filenameLarge);
   
			if ($this->WriteMediaContent('imagelarge', $data)===false) {
				$this->SetStatus(203); //Write failed
				return;
			}
			//if ($this->WriteMediaFileResized($filenameLarge, $filenameSmall)===false) {
			//	$this->SetStatus(203); //Write failed
			//	return;
			//}
			if ($this->WriteMediaContentResized('imagelarge', 'imageSmall')===false) {
				$this->SetStatus(203); //Write failed
				return;
			}
			
			$this->ShowMemoryUsage('finished:');
		} else {
			$this->SendDebug("Refresh", "InstanceProperties NOT Valid, ignore Refresh ...", 0);		
		}
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------
	private function WriteMediaFile ($fileName, $fileContent) {
		$this->SendDebug("WriteMediaFile", 'Write ImageData to '.$fileName, 0);
		$result = file_put_contents($fileName, $fileContent);
		if ($result===false) {
			$this->SendDebug("WriteMediaFile", 'Error writing ImageData to '.$fileName.' --> Retry ...', 0);
			IPS_Sleep(1000);
			$result = file_put_contents($fileName, $fileContent);
		}
		if ($result===false) {
			$this->SendDebug("WriteMediaFile", 'Error writing ImageData to '.$fileName, 0);
			return false;
		}
		return true;
	}

	// -------------------------------------------------------------------------
	protected function IsInstancePropertiesValid()
	{		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];

		return ($status == 102 || $status == 104 || $status == 202  || $status == 203 );
	}
	
	// -------------------------------------------------------------------------
	private function WriteMediaFileResized ($fileNameLarge, $fileNameSmall) {
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
	private function WriteMediaContent ($ident, $data) {
		$this->SendDebug("WriteMediaContent", 'Write ImageData to Object with Ident='.$ident, 0);
		$mediaId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		IPS_SetMediaCached($mediaId, true);
		$result = IPS_SetMediaContent($mediaId, base64_encode($data));
		if ($result===false) {
			$this->SendDebug("WriteMediaContent", 'Error writing ImageData to '.$ident, 0);
			return false;
		}
		return true;
	}

	// -------------------------------------------------------------------------
	private function WriteMediaContentResized ($identLarge, $identSmall) {
		$createSmall     = $this->ReadPropertyBoolean('CreateSmall');
		if ($createSmall) {
			$filenameSmall   = IPS_GetKernelDir().'media\CamSnapshot_'.$this->InstanceID.'_Small.jpg';
			$mediaIDSmall    = $this->RegisterMedia ($identSmall, 'ImageSmall', $filenameSmall);

			$ratioSmall      = $this->ReadPropertyFloat('RatioSmall');
			$mediaIdLarge    = @IPS_GetObjectIDByIdent($identLarge, $this->InstanceID);
			$mediaLarge      = @IPS_GetMedia($mediaIdLarge);
			$mediaIdSmall    = @IPS_GetObjectIDByIdent($identSmall, $this->InstanceID);
			$dataLargeBase64 = IPS_GetMediaContent($mediaIdLarge);
			$dataLarge       = base64_decode($dataLargeBase64);
			$source          = imagecreatefromstring($dataLarge);
			list($width, $height, $type, $attr) = getimagesize(IPS_GetKernelDir().$mediaLarge['MediaFile']);
						
			$thumb = imagecreatetruecolor($width / $ratioSmall,
										  $height / $ratioSmall);
										  
			imagecopyresized($thumb, 
			                 $source, 
							 0, 
							 0, 
							 0, 
							 0,
	                         $width / $ratioSmall,
						     $height / $ratioSmall,
						     $width, 
						     $height);
						 
			ob_start();
			imagepng($thumb);
			$data = ob_get_clean();
						
			IPS_SetMediaCached($mediaIdSmall, false);
			$result = IPS_SetMediaContent($mediaIdSmall, base64_encode($data));
			
			return $result;
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
			IPS_LogMessage(__file__, 'File "'.$snapshotURL.'" could NOT be found on the Server !!!');
			return false;
		}
		$this->SendDebug("GetSnapshot", "Loaded SnapshotImage from URL", 0);

		return $fileContent;
	}
	
	
}

?>
