<?

class BrownsonBase extends IPSModule
{

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
		$statusMessage = substr($statusMessage.'                                                            ', 0, 60);
		$memory = round(memory_get_usage() / 1024 / 1024, 2);
		$this->SendDebug("Resize", $statusMessage . ' UsedMemory='.$memory. " MB", 0);
	}

	// -------------------------------------------------------------------------
	protected function RegisterMedia ($ident, $name, $fileName) {
		$mediaId = @IPS_GetObjectIDByIdent($name, $this->InstanceID);
		if ($mediaId === false) {
			$mediaId	= IPS_CreateMedia(1);
			IPS_SetParent($mediaId, $this->InstanceID);
			IPS_SetIdent($mediaId, $ident);
			IPS_SetName($mediaId, $name);
			IPS_SetPosition($mediaId, 0);
			IPS_SetMediaFile($mediaId, $fileName, false);
		}
		return $MediaId;
	}


}

?>
