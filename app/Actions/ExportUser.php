<?php

namespace App\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Actions\AbstractAction;
use App\Report;

abstract class ExportData {
	protected $exportTo; // Set in constructor to one of 'browser', 'file', 'string'
	protected $stringData; // stringData so far, used if export string mode
	protected $tempFile; // handle to temp file (for export file mode)
	protected $tempFilename; // temp file name and path (for export file mode)

	public $filename; // file mode: the output file name; browser mode: file name for download; string mode: not used

	public function __construct($exportTo = "browser", $filename = "exportdata") {
		if(!in_array($exportTo, array('browser','file','string') )) {
			throw new Exception("$exportTo is not a valid ExportData export type");
		}
		$this->exportTo = $exportTo;
		$this->filename = $filename;
	}
	
	public function initialize() {
		
		switch($this->exportTo) {
			case 'browser':
				$this->sendHttpHeaders();
				break;
			case 'string':
				$this->stringData = '';
				break;
			case 'file':
				$this->tempFilename = tempnam(sys_get_temp_dir(), 'exportdata');
				$this->tempFile = fopen($this->tempFilename, "w");
				break;
		}
		
		$this->write($this->generateHeader());
	}
	
	public function addRow($row) {
		$this->write($this->generateRow($row));
	}
	
	public function finalize() {
		
		$this->write($this->generateFooter());
		
		switch($this->exportTo) {
			case 'browser':
				flush();
				break;
			case 'string':
				// do nothing
				break;
			case 'file':
				// close temp file and move it to correct location
				fclose($this->tempFile);
				rename($this->tempFilename, $this->filename);
				break;
		}
	}
	
	public function getString() {
		return $this->stringData;
	}
	
	abstract public function sendHttpHeaders();
	
	protected function write($data) {
		switch($this->exportTo) {
			case 'browser':
				echo $data;
				break;
			case 'string':
				$this->stringData .= $data;
				break;
			case 'file':
				fwrite($this->tempFile, $data);
				break;
		}
	}
	
	protected function generateHeader() {
		// can be overridden by subclass to return any data that goes at the top of the exported file
	}
	
	protected function generateFooter() {
		// can be overridden by subclass to return any data that goes at the bottom of the exported file		
	}
	
	// In subclasses generateRow will take $row array and return string of it formatted for export type
	abstract protected function generateRow($row);
	
}

/**
 * ExportDataTSV - Exports to TSV (tab separated value) format.
 */
class ExportDataTSV extends ExportData {
	
	function generateRow($row) {
		foreach ($row as $key => $value) {
			// Escape inner quotes and wrap all contents in new quotes.
			// Note that we are using \" to escape double quote not ""
			$row[$key] = '"'. str_replace('"', '\"', $value) .'"';
		}
		return implode("\t", $row) . "\n";
	}
	
	function sendHttpHeaders() {
		header("Content-type: text/tab-separated-values");
    header("Content-Disposition: attachment; filename=".basename($this->filename));
	}
}

/**
 * ExportDataCSV - Exports to CSV (comma separated value) format.
 */
class ExportDataCSV extends ExportData {
	
	function generateRow($row) {
		foreach ($row as $key => $value) {
			// Escape inner quotes and wrap all contents in new quotes.
			// Note that we are using \" to escape double quote not ""
			$row[$key] = '"'. str_replace('"', '\"', $value) .'"';
		}
		return implode(",", $row) . "\n";
	}
	
	function sendHttpHeaders() {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".basename($this->filename));
	}
}


/**
 * ExportDataExcel exports data into an XML format  (spreadsheetML) that can be 
 * read by MS Excel 2003 and newer as well as OpenOffice
 * 
 * Creates a workbook with a single worksheet (title specified by
 * $title).
 * 
 * Note that using .XML is the "correct" file extension for these files, but it
 * generally isn't associated with Excel. Using .XLS is tempting, but Excel 2007 will
 * throw a scary warning that the extension doesn't match the file type.
 * 
 * Based on Excel XML code from Excel_XML (http://github.com/oliverschwarz/php-excel)
 *  by Oliver Schwarz
 */
class ExportDataExcel extends ExportData {
	
	const XmlHeader = "<?xml version=\"1.0\" encoding=\"%s\"?\>\n<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">";
	const XmlFooter = "</Workbook>";
	
	public $encoding = 'UTF-8'; // encoding type to specify in file. 
	// Note that you're on your own for making sure your data is actually encoded to this encoding
	
	public $title = 'Sheet1'; // title for Worksheet 
	
	function generateHeader() {
		
		// workbook header
		$output = stripslashes(sprintf(self::XmlHeader, $this->encoding)) . "\n";
		
		// Set up styles
		$output .= "<Styles>\n";
		$output .= "<Style ss:ID=\"sDT\"><NumberFormat ss:Format=\"Short Date\"/></Style>\n";
		$output .= "</Styles>\n";
		
		// worksheet header
		$output .= sprintf("<Worksheet ss:Name=\"%s\">\n    <Table>\n", htmlentities($this->title));
		
		return $output;
	}
	
	function generateFooter() {
		$output = '';
		
		// worksheet footer
		$output .= "    </Table>\n</Worksheet>\n";
		
		// workbook footer
		$output .= self::XmlFooter;
		
		return $output;
	}
	
	function generateRow($row) {
		$output = '';
		$output .= "        <Row>\n";
		foreach ($row as $k => $v) {
			$output .= $this->generateCell($v);
		}
		$output .= "        </Row>\n";
		return $output;
	}
	
	private function generateCell($item) {
		$output = '';
		$style = '';
		
		// Tell Excel to treat as a number. Note that Excel only stores roughly 15 digits, so keep 
		// as text if number is longer than that.
		if(preg_match("/^-?\d+(?:[.,]\d+)?$/",$item) && (strlen($item) < 15)) {
			$type = 'Number';
		}
		// Sniff for valid dates; should look something like 2010-07-14 or 7/14/2010 etc. Can
		// also have an optional time after the date.
		//
		// Note we want to be very strict in what we consider a date. There is the possibility
		// of really screwing up the data if we try to reformat a string that was not actually 
		// intended to represent a date.
		elseif(preg_match("/^(\d{1,2}|\d{4})[\/\-]\d{1,2}[\/\-](\d{1,2}|\d{4})([^\d].+)?$/",$item) &&
					($timestamp = strtotime($item)) &&
					($timestamp > 0) &&
					($timestamp < strtotime('+500 years'))) {
			$type = 'DateTime';
			$item = strftime("%Y-%m-%dT%H:%M:%S",$timestamp);
			$style = 'sDT'; // defined in header; tells excel to format date for display
		}
		else {
			$type = 'String';
		}
				
		$item = str_replace('&#039;', '&apos;', htmlspecialchars($item, ENT_QUOTES));
		$output .= "            ";
		$output .= $style ? "<Cell ss:StyleID=\"$style\">" : "<Cell>";
		$output .= sprintf("<Data ss:Type=\"%s\">%s</Data>", $type, $item);
		$output .= "</Cell>\n";
		
		return $output;
	}
	
	function sendHttpHeaders() {
		header("Content-Type: application/vnd.ms-excel; charset=" . $this->encoding);
		header("Content-Disposition: inline; filename=\"" . basename($this->filename) . "\"");
	}

}
class ExportUser extends AbstractAction
{
    public function getTitle()
    {
        return 'Descargar reporte';
    }

    public function getIcon()
    {
        return "voyager-receipt";
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-warning',
        ];
    }

    public function getDefaultRoute()
    {
        return '';
    }

    public function shouldActionDisplayOnDataType()
    {
        return true;
    }

    public function massAction($ids, $comingFrom)
    {
        $profile = Auth::user();
        switch (true) {
            case str_contains($this->dataType->model_name, 'User'):
                $data = app($this->dataType->model_name)->all();
                if ($profile->role_id != 1) {
                    $data = $data->where('center_id', Auth::user()->center_id);
                    $data->all();
                }
				$displayColumns = DB::select("SELECT display_name FROM data_rows where data_type_id=?; ",[1],);
				$idToSearch = 1;
                break;
            case str_contains($this->dataType->model_name, 'Vehicle'):
                $data = app($this->dataType->model_name)->all();
                if ($profile->role_id != 1) {
                    $data = $data->where('center_id', Auth::user()->center_id);
                    $data->all();
                }
                $displayColumns = DB::select("SELECT display_name FROM data_rows where data_type_id=?;",[5],);
                $idToSearch = 5;
				break;
                case str_contains($this->dataType->model_name, 'Center'):
                    $data = app($this->dataType->model_name)->all();
                    if ($profile->role_id != 1) {
                        $data = $data->where('id', Auth::user()->center_id);
                        $data->all();
                    }
					$displayColumns = DB::select("SELECT display_name FROM data_rows where data_type_id=?;",[4],);
					$idToSearch = 4;
                    break;    
            case str_contains($this->dataType->model_name, 'Report'):
                $data = $profile->role_id == 1 ? app($this->dataType->model_name)->all() : DB::select(
                    'select distinct * from reports as r
                     inner join  users as u on r.sign_id= u.id
                     where u.center_id=?;',
                    [$profile->center_id],
                );
				$idToSearch = 6;


					
				//$displayColumnsJoins= array_merge($displayColumns, $displayColumnsUser);
				//$displayColumnsJoins =array_unique($displayColumnsJoins);

                break;

        }

        if (sizeof($data) === 0) return dd('No data to export!');

		$displayColumns = DB::select("SELECT field, display_name FROM data_rows where data_type_id=?;",[$idToSearch],);
		$displayColumnsUser = DB::select("SELECT field, display_name FROM data_rows where data_type_id=?;",[1],);
		$displayColumnsJoins = array();
			foreach ($displayColumns as $column) {
				array_push($displayColumnsJoins, [$column->field,$column->display_name]);
			}

			foreach ($displayColumnsUser as $column) {
				array_push($displayColumnsJoins, [$column->field,$column->display_name]);
			}

			
        if (!str_contains($this->dataType->model_name, 'Report')) {
        	$columns =  array_keys($data->first()->toArray());
			} else {
				$dataColumns = collect($data[0]);
				$data=collect($data);
				$columns = array_keys($dataColumns->toArray());
			}
		//if (str_contains($this->dataType->model_name, 'Report')) {


			// TODOS LOS TITULOS DE LA TABLA
			foreach ($columns as &$field) {
				foreach ($displayColumnsJoins as $field_display) {
					if ($field_display[0] === $field) {
						$field = $field_display[1];
						break; // Rompe el bucle interno una vez que se encuentra la coincidencia
					}
				}
			}
		//}





        if (sizeof($data) === 0) return dd('No data to export!');
        
		
		$callback = function () use ($data, $columns,$displayColumns) {			
		
			$columnsDisplayName = array();
            foreach ($displayColumns as $column) {
               array_push($columnsDisplayName, $column->display_name);
            }

			/////////////////////////////////////////////////
			/*
			foreach ($data as $data_col) {
				$datas = var_export($data_col, true);
				$nombreArchivo = "displayColumns.txt";
				file_put_contents($nombreArchivo, $datas, FILE_APPEND | LOCK_EX);
				header("Content-Type: text/plain");
				header("Content-Disposition: attachment; filename=$nombreArchivo");
				readfile($nombreArchivo);
				unlink($nombreArchivo);
			}
			*/
			
			/////////////////////////////////////////////////

			$exporter = new ExportDataExcel('browser', "{$this->dataType->slug}.xls");
			$exporter->initialize();
			
			if (str_contains($this->dataType->model_name, 'Report')) {
				$exporter->addRow($columns);
			}
			else {$exporter->addRow($columns);}
			foreach ($data as $data_col) {
                if(str_contains($this->dataType->model_name, 'Report')){
                    $values = json_decode(json_encode($data_col), true);
                    $values=array_values($values);
                }
				else{
                 $values = array_values($data_col->toArray());
                }	
				$valors = array ();
				for ($i = 0; $i < sizeof($values); $i++) {
                    if (is_array($values[$i])) 
					$values[$i] = json_encode($values[$i]);
					array_push($valors, $values[$i]);
                }			
				$exporter->addRow($valors);			
			}

			

			
			$exporter->finalize();

        };
		return response()->stream($callback, 200);

    }

}
