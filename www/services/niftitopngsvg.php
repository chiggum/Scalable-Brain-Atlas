<?php
$info = json_decode(
<<<SITEMAP
{
  "path": "Scalable Brain Atlas|services|nifti to png/svg",
  "title": "Converts nifti to png/svg",
  "description": "Returns the png/svg slices"
}
SITEMAP
,TRUE);

ini_set('display_errors',1);
require_once('../shared-lib/sitemap.php');
require_once('../shared-lib/applet.php');
$siteMap = new siteMap_class($info);
$applet = new applet_class();

/* Create form fields for this applet */
$attrs0= array('size'=>3);
$attrs1 = array('size'=>3);
$attrs2 = array('size'=>40);
$comment0 = 'comment on download files';
$applet->addFormField('comment0',new commentField_class($comment0));
$f = new selectField_class('Download Type');
$f->setChoices(array('  ', 'png', 'svg', 'both'),NULL);
$applet->addFormField('output',$f);
$comment1 = 'comment on curve tolerance';
$applet->addFormField('comment1',new commentField_class($comment1));
$applet->addFormField('curve_tol',new numField_class('Curve Tolerance',$attrs0, 0, 999));
$comment2 = 'comment on line tolerance';
$applet->addFormField('comment2',new commentField_class($comment2));
$applet->addFormField('line_tol',new numField_class('Line Tolerance',$attrs1, 0, 999));
$comment3 = 'comment on background color';
$applet->addFormField('comment3',new commentField_class($comment3));
$applet->addFormField('bg_col',new textField_class('Background Color',$attrs2));
$comment4 = 'comment on nifti file';
$applet->addFormField('comment4',new commentField_class($comment4));
$file0 = new fileField_class('nifti file', 'nii_file', 'nii');
$applet->addFormField('nii_file',$file0);
$comment5 = 'comment on colormap json file';
$applet->addFormField('comment5',new commentField_class($comment5));
$file1 = new fileField_class('colormap json file', 'json_file', 'json');
$applet->addFormField('json_file',$file1);

$errors = $applet->parseAndValidateInputs($_REQUEST);
$retType = @$_REQUEST['output'];

if (!$retType) {
	/*
     * Interactive mode
     */
	echo '<html><head>';
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
	echo '<script type="text/javascript" src="../shared-js/browser.js"></script>';
	echo $siteMap->windowTitle();
	echo $siteMap->clientScript();
	echo $applet->clientScript();
	echo '</head><body>';
	echo $siteMap->navigationBar();
	echo $siteMap->pageTitle();
	echo $siteMap->pageDescription();
	echo '<p>';
	echo $applet->uploadFormHtml('Process and Download', 'dummyIFrame');	//IFrame not required hence a dummy Iframe is used
	echo '</body></html>';
	exit;
} elseif (count($errors)) {
    echo '<html>'.$applet->errorReport($errors).'</html>';
    exit;
}

/*
 * On submit
 */

	//get arguments ready
	$curveTolerance = $_REQUEST['curve_tol'];
	$curveTolerance = str_replace(' ', '', $curveTolerance);
	if($curveTolerance != '')
		$curveTolerance = ' -t ' . $curveTolerance . ' ';

	$lineTolerance = $_REQUEST['line_tol'];
	$lineTolerance = str_replace(' ', '', $lineTolerance);
	if($lineTolerance != '')
		$lineTolerance = ' -s ' . $lineTolerance . ' ';

	$bgColor = $_REQUEST['bg_col'];
	$bgColor = str_replace(' ', '', $bgColor);
	if($bgColor != '')
		$bgColor = ' -c "' . $bgColor . '" ';

	//execute nii2png python script
	exec("python ../python/nii2png.py -i ../upload/" . $file0->getFileName()  . " -c ../upload/" .  $file1->getFileName()  ." -o ../download/" );
	if($retType == '2' || $retType == '3')
		//execute png2svg python script
		exec("python ../python/png2svg.py -i ../download/png/ -o ../download/" . $curveTolerance . $lineTolerance . $bgColor);

	//source and destination of zip archive
	$source = "../download/";
    $destination = "../download.zip";

    //zip the source folder recursively
    if (!extension_loaded('zip') || !file_exists($source)) {
        echo 'error';
    }

    $zip = new ZipArchive();
    if ($zip->open($destination, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
    	$source = str_replace('\\', '/', realpath($source));

	    if (is_dir($source) === true) {
	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

	        foreach ($files as $file) {
	            $file = str_replace('\\', '/', $file);
	            //ignore "." and ".." folders
	            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
	                continue;
	            $file = realpath($file);
	            if (is_dir($file) === true) {
	                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
	            }
	            else if (is_file($file) === true) {
	                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	            }
	        }
	    }
	    else if (is_file($source) === true) {
	        $zip->addFromString(basename($source), file_get_contents($source));
	    }
	}
    $zip->close();

    //download the zipped archive
    header("Content-disposition: attachment; filename=download.zip");
	header("Content-type: application/zip");
	readfile("../download.zip");
?>