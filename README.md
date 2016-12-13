#### Description:

This is a Mautic Plugin that allow you to Import a List of Contacts Using a CSV file and any Form create.

#### Steps to test this Plugin:
1. Put this project in the plugins Folder.
2. Edit the config file of the plugin and add the folder of upload and folder of the processed files.
3. Create the folders that you configurated in config file, and give all permissions to that folders.
4. GO to Settings -> Import User By Form.
5. Create a json file with that format: 
```json
{
	"file": "file_name.CSV",
	"form_id": (int)form_id,
	"mautic_url": "http://urlof.your.mautic",
	"form": {
		"email": 0,
		"name": 1
	}
}
```
6. In this case, the CSV format is: 
```
jose@emailserver.com.br;José de Oliveira
```
maria@emailserver.com.br;Maria Helena
```

The field form of json file is a reference of the form created in mautic, where the key is the name of field of the form and the value is the index of the csv file column. Remember that the count always start with ZERO.
7. Verify if the csv and json files are in the folder that you configurated in plugin config file.
8. Enter in mautic/app folder and execute the command: php console eou:contacts:import-form-file --file import.json OR configure your crontab to execute this command.

