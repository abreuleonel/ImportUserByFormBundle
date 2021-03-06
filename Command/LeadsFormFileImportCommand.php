<?php

namespace MauticPlugin\ImportUserByFormBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class LeadsFormFileImportCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('eou:contacts:import-form-file')
            ->setDescription('Import leads into a specific segment.')
            ->addOption('--file', '', InputOption::VALUE_REQUIRED, 'File to import')
            ->addOption('--test', '', InputOption::VALUE_OPTIONAL, 'Test Values')
        	->addOption('--startline', '', InputOption::VALUE_OPTIONAL, 'Start Line')
        	->addOption('--view', '', InputOption::VALUE_OPTIONAL, 'View Results');
			
        parent::configure();
    }

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dir = $this->getContainer()->get('mautic.helper.core_parameters')->getParameter('import_folder');
		$config_file    = $dir . $input->getOption('file');
		
		if (!file_exists($config_file)) {
			echo "File doesn't exist";
			return false;
		}
		
		if(file_exists($dir . "import.lock")) {
			echo "Another process is already running";
			return false;
		}
		
		$json = file_get_contents($config_file);
		$config = json_decode($json);
		
		if(isset($config->form)) {
			$this->importByForm($config, $dir, $input, $output);
		} elseif(isset($config->leadFields)) {
			$this->importDirectly($config, $dir, $input, $output);
		} else {
			exit('ERRO LINE 63');
		}
	}

	private function importByForm($config, $dir, InputInterface $input, OutputInterface $output) 
	{
		$file = $dir . $config->file;
		$form_id = $config->form_id;
		$uri = $config->mautic_url;
		$teste = (null !== $input->getOption('test') ? $input->getOption('test') : false);
		$inicio = (null !== $input->getOption('startline') ? $input->getOption('startline') : 0);
		$count = 0;
		$view_results = (null !== $input->getOption('view') ? $input->getOption('view') : false);
		
		$handle = fopen($file, "r");
		$fp = fopen($dir . "erros.txt", "a");
		$lock = fopen($dir . "import.lock", "a");
		$processed = fopen($dir . $config->file . "-PROCESSED", "a");
		
		$totalRows = shell_exec("wc -l < " . $file);
		
		$progress = new ProgressBar($output, (int)$totalRows);
		
		while (($data = fgetcsv($handle, 1000, $config->separator)) !== FALSE) {
			$start = microtime(true);
			$postData = $this->getPostData($config->form, $data);
			if($teste) {
				echo $count . ': ' . $data[0] . ' - ' . $data[1] . "\n";
				$count++;
			} else {
				if($count >= $inicio) {
					$responsePost = $this->postMauticForm($postData, $form_id, null, $uri);
					$isJson = $this->isJson($responsePost);
					$end = microtime(true);
					$progress->advance();
					if($isJson) {
						fwrite($processed, 'PROCESSED: ' . json_encode($postData)  . "\n");
						if($view_results) {
							echo $count . ': ' . json_encode($postData) . ' - in ' . ($start-$end) ."\n";
						}
					} else {
						fwrite($fp, 'ERRO: ' .  json_encode($postData)  . "\n");
					}
				}
				$count++;
			}
		}
		
		$progress->finish();
		
		fclose ($handle);
		fclose ($fp);
		fclose ($processed);
		$this->moveFiles($config, $dir, $input->getOption('file'));
		unlink($dir . "import.lock");
		
		if (!$this->checkRunStatus($input, $output)) {
			return 0;
		}
		
		$this->completeRun();
		
		return 0;
	}
	
	private function importDirectly($config, $dir, InputInterface $input, OutputInterface $output) 
	{
		$file    = $dir . $config->file;
		$row = 0;
		
		$fp = fopen($dir . "erros.txt", "a");
		$lock = fopen($dir . "import.lock", "a");
		$processed = fopen($dir . $config->file . "-PROCESSED", "a");
		
		if (file_exists($file)) {
			$handle = fopen($file, "r");
		
			$totalRows = shell_exec("wc -l < " . $file);
		
			$progress = new ProgressBar($output, (int)$totalRows);
		
			while (($data = fgetcsv($handle, 1000, $config->separator)) !== FALSE) {
				$postData = $this->getPostData($config->leadFields, $data);
				try {
					$responsePost = $this->addContact($postData, $config->segmentAlias);
					fwrite($processed, 'PROCESSED: ' . json_encode($postData)  . "\n");
				} catch (\Exception $e) {
					fwrite($fp, 'ERRO: ' .  json_encode($postData)  . "\n");
				}
				$progress->advance();
				$row++;
			}
		
			$progress->finish();
			
			fclose($fp);
			fclose($handle);
			fclose($processed);
			fclose($lock);
			
			$this->moveFiles($config, $dir, $input->getOption('file'));
			
			unlink($dir . "import.lock");
		} else {
			exit('erro');
		}
		
		if (!$this->checkRunStatus($input, $output)) {
			return 0;
		}
		
		$this->completeRun();
		
		return 0;
	}
	
	private function addContact(array $leadFields, string $segmentAlias)
	{
		$container  = $this->getContainer();
		$factory    = $container->get('mautic.factory');
		$doctrine 	= $container->get('doctrine');
		
		$consumer_id = (isset($leadFields['consumer_id']) ? $leadFields['consumer_id'] : null);
		$email = (isset($leadFields['email']) ? $leadFields['email'] : null);
		
		if(null === $consumer_id && null === $email) {
			echo 'Erro: Sem Consumer_id e Email';
			exit;
		}
		
		$q = $doctrine->getConnection()->createQueryBuilder();
		$q->select('t.id')
		->from(MAUTIC_TABLE_PREFIX.'leads', 't')
		->where('t.consumer_id = ' . $consumer_id);
		 
		$qLead = $q->execute()->fetch();
		$lead_id = (isset($qLead['id']) && !empty($qLead['id'])) ? $qLead['id'] : null;
		
		$leadModel = $factory->getModel('Lead');
		
		if($lead_id) {
			$qb = $doctrine->getConnection()->createQueryBuilder();
			$qb->update(MAUTIC_TABLE_PREFIX.'leads', 't');
			
			foreach($leadFields as $lk => $lv) {
				$qb->set('t.' . $lk,  '"' . $lv . '"');
			} 
			
			$qb->where('t.id = ' . $lead_id);
			$qb->execute();
		} else {
			$lead = new Lead();
			
			$leadModel->setFieldValues($lead, $leadFields, true);
			$leadModel->saveEntity($lead);
			$lead_id = $lead->getId();
		}
		
		$modelList  = $factory->getModel('lead.list');
		
		$list = $factory->getEntityManager()->getRepository('MauticLeadBundle:LeadList')->getLists(false,$segmentAlias);
		$list_id = key($list);

		$leadModel->addToLists($lead_id, [$list_id]);
	}
	
	private function moveFiles($config, $dir, $config_file) 
	{
		$filename = explode('.', $config->file)[0];
		$new_dir = $this->getContainer()->get('mautic.helper.core_parameters')->getParameter('process_folder') . $filename . '/';
		mkdir($new_dir, 0755, true);
		
		$csv_file = $dir . $config->file;
		$import_file = $dir . $config_file;
		$processed_file = $dir . $config->file . '-PROCESSED';
		$error_file = $dir . 'erros.txt';
		
		rename($csv_file, $new_dir . $config->file);
		rename($import_file, $new_dir . $config_file);
		rename($error_file, $new_dir . 'erros.txt');
		rename($processed_file, $new_dir . $config->file . '-PROCESSED');
	}
	
	private function getPostData($form, array $data) 
	{
		$postData = [];
		
		foreach($form as $k => $v) {
			$postData[$k] = utf8_encode($data[$v]);
		}
		
		return $postData;
	}
	
	private function postMauticForm($data, $formId, $ip = null, $uri)
	{
	    // Get IP from $_SERVER
	    if (!$ip) {
	        $ipHolders = array(
	            'HTTP_CLIENT_IP',
	            'HTTP_X_FORWARDED_FOR',
	            'HTTP_X_FORWARDED',
	            'HTTP_X_CLUSTER_CLIENT_IP',
	            'HTTP_FORWARDED_FOR',
	            'HTTP_FORWARDED',
	            'REMOTE_ADDR'
	        );
	
	        foreach ($ipHolders as $key) {
	            if (!empty($_SERVER[$key])) {
	                $ip = $_SERVER[$key];
	
	                if (strpos($ip, ',') !== false) {
	                    // Multiple IPs are present so use the last IP which should be the most reliable IP that last connected to the proxy
	                    $ips = explode(',', $ip);
	                    array_walk($ips, create_function('&$val', '$val = trim($val);'));
	
	                    $ip = end($ips);
	                }
	
	                $ip = trim($ip);
	                break;
	            }
	        }
	    }
	    
	    $data['formId'] = $formId;
	
	    // return has to be part of the form data array
	    if (!isset($data['return'])) {
	        $data['return'] = '.';
	    }
	
	    $data = array('mauticform' => $data);
	
	    $formUrl =  $uri . '/form/submit?ajax=true&formId=' . $formId;
	
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $formUrl);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Forwarded-For: $ip"));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	    $response = curl_exec($ch);
	
	    curl_close($ch);
	
	    return $response;     
	}
	
	private function isJson($string) {
	    return ((is_string($string) &&
	            (is_object(json_decode($string)) ||
	            is_array(json_decode($string))))) ? true : false;
	}
}
