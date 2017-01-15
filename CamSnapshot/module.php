<?
require_once(__DIR__ . DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."BrownsonBase.php"); 

class CamSnapshot extends BrownsonBase
{
	
	// -------------------------------------------------------------------------
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyString("SnapshotURL", '');
		$this->RegisterPropertyBoolean("UseMediaCache", false);		

		$this->RegisterPropertyBoolean("CreateSmall", false);		
		$this->RegisterPropertyFloat("RatioSmall", 2.0);

		$this->RegisterPropertyBoolean("AutoRefresh", true);		
		$this->RegisterTimer("RefreshTimer", 0, 'CamSnapshot_Refresh($_IPS[\'TARGET\']);');
		$this->RegisterPropertyInteger("Interval", 300);
		
		$this->RegisterScript('refresh', 'Refresh', '<?  CamSnapshot_Refresh(IPS_GetParent($_IPS["SELF"])); ?>', 0);
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
		$this->ShowMemoryAvailable("Refresh");
		
		if ($this->IsInstancePropertiesValid()) {
			$snapshotURL      = $this->ReadPropertyString('SnapshotURL');
			$snapshotURL      = trim($snapshotURL, ' ');
			$snapshotURL      = str_replace(' ', '%20', $snapshotURL);
			
			// Download Images
			$this->ShowMemoryUsage('Start Refresh of Images');
			$data = $this->GetSnapshotImageFromURL($snapshotURL);

			if ($data===false) {
				$this->SetStatus(202); //Download failed
				return;
			}
			   
			if ($this->WriteMediaContent('imagelarge', 'ImageLarge', $data)===false) {
				$this->SetStatus(203); //Write failed
				return;
			}
			if ($this->WriteMediaContentResized('imagesmall', 'ImageSmall', 'imagelarge')===false) {
				$this->SetStatus(203); //Write failed
				return;
			}

			$autoRefresh      = $this->ReadPropertyBoolean('AutoRefresh');
			$instance = IPS_GetInstance($this->InstanceID);
			$status   = $instance['InstanceStatus'];
			if (!$autoRefresh) {
				$this->SetStatus(104); //Instanz ist inaktiv
			} else {
				$this->SetStatus(102); //Instanz ist aktiv
			}
			
			$this->ShowMemoryUsage('Finished Refresh');
		} else {
			$this->SendDebug("Refresh", "InstanceProperties NOT Valid, ignore Refresh ...", 0);		
		}
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------
	protected function IsInstancePropertiesValid()
	{		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];

		return ($status == 102 || $status == 104 || $status == 202  || $status == 203 );
	}
	
	// -------------------------------------------------------------------------
	private function WriteMediaContent ($ident, $name, $data) {
		$this->SendDebug("WriteMediaContent", 'Write ImageData to Object with Ident='.$ident, 0);

		$filenameLarge   = IPS_GetKernelDir().'media\CamSnapshot_'.$this->InstanceID.'_Large.jpg';
		$mediaId         = $this->RegisterMedia ($ident, $name, $filenameLarge);
		$useMediaCache   = $this->ReadPropertyBoolean('UseMediaCache');

		IPS_SetMediaCached($mediaId, $useMediaCache);
		$result = IPS_SetMediaContent($mediaId, base64_encode($data));
		if ($result===false) {
			$this->SendDebug("WriteMediaContent", 'Error writing ImageData to '.$ident, 0);
			return false;
		}

		$this->SendDebug("WriteMediaContent", "SetMediaImage for downloaded Image", 0);
		$this->ShowMemoryUsage('Created Image');

		return true;
	}

	// -------------------------------------------------------------------------
	private function WriteMediaContentResized ($identSmall, $name, $identLarge) {
		$createSmall     = $this->ReadPropertyBoolean('CreateSmall');
		if ($createSmall) {
			$filenameSmall   = IPS_GetKernelDir().'media\CamSnapshot_'.$this->InstanceID.'_Small.jpg';
			$mediaIDSmall    = $this->RegisterMedia ($identSmall, $name, $filenameSmall);
		    $useMediaCache   = $this->ReadPropertyBoolean('UseMediaCache');

			$ratioSmall      = $this->ReadPropertyFloat('RatioSmall');
			$mediaIdLarge    = @IPS_GetObjectIDByIdent($identLarge, $this->InstanceID);
			$mediaLarge      = @IPS_GetMedia($mediaIdLarge);
			$mediaIdSmall    = @IPS_GetObjectIDByIdent($identSmall, $this->InstanceID);
			$dataLargeBase64 = IPS_GetMediaContent($mediaIdLarge);
			$dataLarge       = base64_decode($dataLargeBase64);
			$source          = imagecreatefromstring($dataLarge);

			$this->SendDebug("WriteMediaContentResized", "Build resized Image with Ratio=".$ratioSmall , 0);
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
			imagejpeg($thumb);
			$data = ob_get_clean();
						
			IPS_SetMediaCached($mediaIdSmall, $useMediaCache);
			$result = IPS_SetMediaContent($mediaIdSmall, base64_encode($data));
			
			$this->SendDebug("WriteMediaContentResized", "SetMediaImage for resized Image", 0);
			$this->ShowMemoryUsage('Created resized Image');

			return $result;
		}

		return true;
	}
		
	// -------------------------------------------------------------------------
	private function GetSnapshotImageFromURL($snapshotURL) {
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_HEADER, false);
		curl_setopt($curl_handle, CURLOPT_URL, $snapshotURL);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
		//curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11');
		$fileContent = curl_exec($curl_handle);
		$rescode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

		if ($fileContent===false) {
			$this->SendDebug("GetSnapshot", 'File "'.$snapshotURL.'" could NOT be found on the Server !!!', 0);
			$this->SendDebug("GetSnapshot", 'Curl-Error: '.curl_error($curl_handle), 0);
			$this->SendDebug("GetSnapshot", 'ResultCode: '.$rescode, 0);
			curl_close($curl_handle);
			return false;
		}
		$this->SendDebug("GetSnapshot", "Loaded SnapshotImage from URL", 0);
		$this->ShowMemoryUsage('Loaded SnapshotImage');

		curl_close($curl_handle);

		return $fileContent;
	}
	
	
}

?>
