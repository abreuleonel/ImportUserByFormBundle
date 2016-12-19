<?php
/**
 * @copyright   2014 EOU/MRM. All rights reserved
 * @author      BRuno de Abreu
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'menu' => [
        'admin' => [
            'mautic.importuserbyform.title' => [
                    'route'     => 'importuserbyform_index',
                    'iconClass' => 'fa-terminal',
            ],
        ],
    ],

    'routes' => [
        'main' => [
            'importuserbyform_index' => [
                'path'       => '/importuserbyform',
                'controller' => 'ImportUserByFormBundle:Import:index',
            ],
        	'importuserbyform_import' => [
        		'path'       => '/importuserbyform/import',
        		'controller' => 'ImportUserByFormBundle:Import:import',
        	],
        ],
    ],
		
	'parameters' => [
		'import_folder' => '/tmp/lead-import/',
		'process_folder' => '/tmp/lead-import/',
		'execute_immediately' => false,
	]
];
