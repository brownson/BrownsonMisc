<?

class BrownsonBase extends IPSModule
{
	protected $showMemoryUsage = false;

	public function Create()
	{
		parent::Create();
	}

	public function ApplyChanges()
	{
		parent::ApplyChanges();
	}

	################## PRIVATE  ##################

	// -------------------------------------------------------------------------
	protected function IsInstancePropertiesValid()
	{		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];

		return ($status == 102 || $status == 104);
	}
	
	// -------------------------------------------------------------------------
	protected function ShowMemoryUsage($statusMessage) {
		if ($this->showMemoryUsage) {
			$memory = round(memory_get_usage() / 1024 / 1024, 2);
			$this->SendDebug("ShowMemoryUsage", $statusMessage . ', UsedMemory='.$memory. " MB", 0);
		}
	}

	// -------------------------------------------------------------------------
	protected function ShowMemoryAvailable($statusMessage) {
		if ($this->showMemoryUsage) {
			$memory = ini_get('memory_limit');
			$this->SendDebug("ShowMemoryAvailable", $statusMessage . ', AvailableMemory='.$memory. " MB", 0);
		}
	}

	// -------------------------------------------------------------------------
	protected function RegisterMedia ($ident, $name, $fileName) {
		$mediaId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if ($mediaId === false) {
			$mediaId	= IPS_CreateMedia(1);
			IPS_SetParent($mediaId, $this->InstanceID);
			IPS_SetIdent($mediaId, $ident);
			IPS_SetName($mediaId, $name);
			IPS_SetPosition($mediaId, 0);
			IPS_SetMediaFile($mediaId, $fileName, false);
		}
		return $mediaId;
	}


}

?>
