<?php
/**
* @copyright   2016 EOU/MRM, Inc. All rights reserved
* @author      Bruno de Abreu
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\ImportUserByFormBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;

class ImportController extends CommonController
{
    public function indexAction()
    {
    	$import_folder = $this->get('mautic.helper.core_parameters')->getParameter('import_folder');
    	$process_folder = $this->get('mautic.helper.core_parameters')->getParameter('process_folder');
    	$json_file = (file_exists($import_folder . 'import.json') ? $import_folder . 'import.json' : false);
    	$json = (($json_file) ? json_decode(file_get_contents($json_file), true) : []);
		    	
    	$items = [
    		'import_folder' => [
				'folder' => $import_folder,    				
    			'is_writable' => (is_writable($import_folder) ? true : false)
    		],
    		'process_folder' => [
    			'folder' => $process_folder,
    			'is_writable' => (is_writable($process_folder) ? true : false)
    		],
    		'json_file' => [
    			'file' => $json_file,
    			'json' => $json
    		],
    		'lock_file' => (file_exists($import_folder . 'import.lock'))
    	];
    	
    	
    	return $this->delegateView(
    			[
    					'viewParameters' => [
    							'items' => $items,
    					],
    					'contentTemplate' => 'ImportUserByFormBundle:Import:list.html.php',
    			]
    			);
    }
    
    public function importAction() 
    {
    	$import_folder = $this->get('mautic.helper.core_parameters')->getParameter('import_folder');
    	
    	$valid = $this->verifyJsonFile($_FILES['json_config']['tmp_name']);
    	
		if($valid) {
			move_uploaded_file($_FILES['json_config']['tmp_name'], $import_folder . '/' . $_FILES['json_config']['name']);
    		chmod($import_folder . '/' . $_FILES['json_config']['name'], 0755);
    		
    		move_uploaded_file($_FILES['csv_file']['tmp_name'], $import_folder . '/' . $_FILES['csv_file']['name']);
    		chmod($import_folder . '/' . $_FILES['csv_file']['name'], 0755);
	    	
    		return $this->redirect('/s/importuserbyform');
		} else {
			echo 'Error: Invalid JSON! <a href="/s/importuserbyform"> Try Again </a>';
			exit;
		}
    }
    
    private function isJson($string) 
    {
    	return ((is_string($string) &&
    			(is_object(json_decode($string)) ||
    					is_array(json_decode($string))))) ? true : false;
    }
    
    private function verifyJsonFile($file) 
    {
    	$valid = false;
    	
    	$json = file_get_contents($file);
    	
    	if($this->isJson($json)) {
			$json_d = json_decode($json, true);
    		
			if(isset($json_d['file']) && isset($json_d['form_id']) && isset($json_d['mautic_url']) && isset($json_d['form'])) {
				$valid = true;
			}
    	}
    	
    	return $valid;
    }
}
