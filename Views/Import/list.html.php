<?php
/**
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('ImportUserByFormBundle:Import:index.html.php');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.importuserbyform.title'));
?>

<?php 

$disable = '';
if(!($items['import_folder']['is_writable']) || !($items['process_folder']['is_writable'])): 
	$disable = 'disabled="disabled"';
	foreach($items as $k => $v):
		if(!$v['is_writable']):
?>
			<div class="alert alert-danger">
			  <strong>Error!</strong> The folder <?= $v['folder'] ?> is NOT Writable!
			</div>	

		<?php endif;?>
	<?php endforeach; ?>
<?php  endif; ?>

<?php  
	if($items['json_file']['file'] && !($items['lock_file'])):
		$disable = 'disabled="disabled"';
?>
		<div id="process_queue" class="alert alert-info">
		  <h3>There is a Process in the Queue </h3> <br />
		  <b> CSV Name: </b> <?= $items['json_file']['json']['file'] ?> <br />
		  <b> Form: </b> <?= $items['json_file']['json']['form_id'] ?> <br />
		  <b>Status: </b> Not started yet 
		</div>	
<?php 
	endif;
?>

<div id="ajax_result" class="alert alert-info" style="display: none">
	<div class="content">
		<h3></h3> <br />
		
		<b>CSV Name: </b> <span id="csv_name"></span> <br />
		<b>Form: </b> <span id="form_id"></span> <br />
 		<b>CSV Total Rows: </b> <span id="csv_total_rows"></span> <br />
		<b>Imported Rows: </b> <span id="imported_rows"></span> <br />
 	</div>
</div>

<div class="row">
	<div class="col-sm-offset-3 col-sm-6">
        <div class="ml-lg mr-lg mt-md pa-lg">
			<div class="panel panel-info">
				<div class="panel-heading">
		        	<div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.import.userbyform.instructions'); ?></div>
		        </div>
	        	<div class="panel-body">
	            	<form role="form" name="form_import" method="post" action="/s/importuserbyform/import" enctype="multipart/form-data">                    
	                	<div class="input-group well mt-lg" style="width: 100%">
	                    	<label for="json_import" style="color: black; font-weight: bold;"> JSON FILE </label>
	                    	<input <?= $disable ?> type="file" id="json_import" name="json_config" required="required" accept=".json" class="form-control" autocomplete="false"  >                        
	                    </div>
	                    
	                    <div class="input-group well mt-lg" style="width: 100%">
	                    	<label for="csv_import" style="color: black; font-weight: bold;"> CSV FILE </label>
	                    	<input <?= $disable ?> type="file" id="csv_import" name="csv_file" required="required" accept=".csv" class="form-control" autocomplete="false" placeholder="CSV File" />                        
	                    </div>
	                    
	                    <button <?= $disable?> type="submit" id="import_start" name="lead_import[start]" class="btn btn-primary" >
	        				<i class="fa fa-upload "></i>
	        				Upload Files
	        			</button>      
	                 </form>
	       		</div>
			</div>
		</div>
	</div>
</div>


<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', [
        'id'     => 'MonitoringPreviewModal',
        'header' => false,
    ]);
?>
<?php echo $view['assets']->includeScript('plugins/ImportUserByFormBundle/Assets/js/import.js', 'loadAjaxEvent', 'loadAjaxEvent'); ?>


