<?php
namespace Serie;
use \PERP\Controller as Controller;

class Serie extends Controller
{
	var $id = null;
	public static $series_types = array(1 => 'Factura', 'Chitanta', 'Proforma', 'Aviz', 'NIR', 'Bon consum', 'Transfer');
	
	function __construct()
	{
		controller::__construct();
		
		if(!$this->is_logged || !$this->is_admin)
			$this->redirect('/');
		
		$this->setParam('page_title','Serii documente');
		$this->setParam('series_types',self::$series_types);
		$this->module = __NAMESPACE__;
		$this->id = $this->f3->get('PARAMS.id');
	}
	
	function get()
	{
		$items_list =  \R::getAll('SELECT id, name, type, LPAD(fnumber, 4, \'0\') AS fnumber, LPAD(cnumber, 4, \'0\') AS cnumber, isprimary, description, COUNT(serieid) AS nrdocs
								   FROM serie 
								   LEFT JOIN seriedoc ON seriedoc.serieid = serie.id
								   GROUP BY id
								   ORDER BY id DESC');

		if($items_list && count($items_list))
			$this->setParam('items_list', $items_list);
	}
	
	function post()
	{
		$data['type'] = (isset($_POST['type']) && $data['type'] = abs(intval($_POST['type'])))?$data['type']:null;
		$data['name']	 = (isset($_POST['name']) && $data['name'] = trim($_POST['name']))?$data['name']:null;
		$data['fnumber'] = (isset($_POST['fnumber']) && $data['fnumber'] = abs(intval($_POST['fnumber'])))?$data['fnumber']:null;
		$data['description'] = (isset($_POST['description']) && $data['description'] = trim($_POST['description']))?$data['description']:'';
		
		if($data['type'] && $data['name'] && $data['fnumber'])
		{
			if(!\R::getCell('SELECT id FROM serie WHERE type = :type AND name = :name', array('type' => $data['type'], 'name' => $data['name'])))
			{
				$data['cnumber'] = $data['fnumber'];
				
				$data['isprimary'] = (\R::getCell('SELECT id FROM serie WHERE type = :type AND isprimary = 1', array('type' => $data['type'])))?'0':1;
				
				$obj = \R::dispense('serie');
				$obj->import($data);
				
				if(!$obj_id = \R::store($obj))
					die('Salvare esuata!');
					
				die((string)$obj_id);
			}
		}
		
		die('Seria exista deja in baza de date! Nu se pot adauga doua serii cu aceeasi denumire pentru acelasi tip de document!');
	}
	
	public static function gestiuni_list()
	{
		$items_list =  \R::getAll('SELECT id, name FROM gestiune ORDER BY name ASC');
		
		if($items_list && count($items_list))
		{
			foreach($items_list as $item)
				$gestiuni_list[$item['id']] = $item['name'];
				
			return $gestiuni_list;
		}
		
		return null;
	}
	
	function delete()
	{
		if($this->id)
			\R::exec('DELETE FROM serie WHERE id = :id', array('id' => $this->id));
		
		$this->redirect('/serie');
	}
	
	function setPrimary()
	{
		if($this->id)
		{
			if($type = \R::getCell('SELECT type FROM serie WHERE id = :id', array('id' => $this->id)))
			{
				\R::exec('UPDATE serie SET isprimary = 0 WHERE type = :type', array('type' => $type));
				\R::exec('UPDATE serie SET isprimary = 1 WHERE id = :id', array('id' => $this->id));
			}
		}
		exit;
	}
	
	public static function setDoc($docdata = array())
	{
		$required = array('docid', 'serieid', 'doctype', 'serienr', 'isdraft');
		
		if(count($docdata) && count(array_intersect_key(array_flip($required), $docdata)) === count($required))
			\R::exec('INSERT IGNORE INTO seriedoc (docid, serieid, doctype, serienr, isdraft) VALUES(:docid, :serieid, :doctype, :serienr, :isdraft)', $docdata);
			
	}
	
	public static function setNoDraft($docid = null,  $doctype = null)
	{
		if($docid && $doctype)
			\R::exec('UPDATE seriedoc SET isdraft = 0 WHERE docid = :docid AND doctype = :doctype', array('docid' => $docid, 'doctype' => $doctype));
	}
	
	public static function resetDrafts($serieid = null, $doctype = null, $oldserie = null, $newserie = null)
	{
		if($serieid && $doctype && $oldserie && $newserie)
			\R::exec('UPDATE seriedoc SET serienr = :newserie 
					  WHERE doctype = :doctype 
					  AND serieid = :serieid 
					  AND serienr = :oldserie 
					  AND isdraft = 1', array('newserie' => $newserie, 'doctype' => $doctype, 'serieid' => $serieid, 'oldserie' => $oldserie));
	}
	
	public static function increaseCNumber($seriedid = null)
	{
		if($seriedid)
		{
			\R::exec('UPDATE serie SET cnumber = cnumber+1 WHERE id = :id', array('id' => $seriedid));
			
			return \R::getCell('SELECT CONCAT(name,\'\', LPAD(cnumber, 4, \'0\')) AS serienr
								FROM serie 
								WHERE id = :id', array('id' => $seriedid));;
		}
	}
	
	public static function getDoc($docid = null, $doctype = null)
	{
		if($docid && $doctype)
			return \R::getCell('SELECT serienr FROM seriedoc WHERE docid = :docid AND doctype = :doctype',array('docid' => $docid, 'doctype' => $doctype));
		
		return null;
	}
	
	public static function getSerie($doctype = null)
	{
		if($doctype)
			return \R::getAll('SELECT id AS serieid, name, LPAD(cnumber, 4, \'0\') AS fnumber, isprimary 
							   FROM serie 
							   WHERE type = :doctype', array('doctype' => $doctype));
		
		return null;
	}
	
	public static function getDocNumberBySerieId($seriedid = null)
	{
		if($seriedid)
			return \R::getCell('SELECT CONCAT(name,\'\', LPAD(cnumber, 4, \'0\')) AS serienr
								FROM serie 
								WHERE id = :seriedid', array('seriedid' => $seriedid));
		
		return null;
	}
	
	public static function returnModal($doctype = null)
	{
		global $smarty;
		
		if($doctype)
		{
			$smarty->assign('reqdoctype', $doctype);
			$smarty->assign('series_types',self::$series_types);
			return $smarty->fetch('serie/modal_serie.tpl');
		}
		return null;
	}
}
?>