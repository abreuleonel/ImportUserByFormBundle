<?php
/**
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\ImportUserByFormBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getJobDataAction(Request $request)
    {
    	$import_folder = $this->get('mautic.helper.core_parameters')->getParameter('import_folder');
    	$lock_file = (file_exists($import_folder . 'import.lock') ? true : false);
    	
		if(!$lock_file) {
			$response = [];
			$success = true;
			$process_started = false;
			return $this->sendJsonResponse(['message' => $response, 'success' => $success, 'process_started' => $process_started]);
		}
		
		$json_file = $import_folder . 'import.json';
		$json = json_decode(file_get_contents($json_file), true);
		$csv_file = $import_folder . $json['file'];		
		$processed_file = $import_folder . $json['file'] . '-PROCESSED';
		
 		$total_rows = shell_exec("wc -l < " . $csv_file);
		$total_rows_processed = shell_exec("wc -l < " . $processed_file);
		
		$json['total_rows'] = $total_rows;
		$json['total_rows_processed'] = $total_rows_processed;
		
    	$success = true;
    	$process_started = true;

        return $this->sendJsonResponse(['message' => $json, 'success' => $success, 'process_started' => $process_started]);
    }
}
