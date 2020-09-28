<?php
/**
 * 
 * Yamaha Music Finder File Format Generator for MIDI files
 * 
 * Put the files in the USB stick like this:
 * folder/author/title.mid
 * 
 * USAGE:
 * 
 * php /script_path/midi_to_mdf.php path_midi_files
 * 
 * http://www.jososoft.dk/yamaha/articles/mff.htm
 * 
 * 
 */

$usbpath=realpath(getcwd());
if (!empty($argv[1]) && is_dir($argv[1]))
{
    $path=realpath($argv[1]);
//----------------------------READ ALL FILES----------------------------------->
    $list=getDirContents("$path");
    $allmidi=array();
    foreach($list as $item)
    {
        $ext=GetFileExtension($item);
        if ($ext == "mid" || $ext == "kar")
        {
            $rec=array();
            $rec['filename']="I:".str_replace("$usbpath","",$item);
            $rec['author']=basename(dirname($rec['filename']));
            $allmidi[]=$rec;
        }
    }
    //print_r($usbpath);
    //print_r($allmidi);
//----------------------------READ ALL FILES-----------------------------------<
    $data="";
    $rows="";
    //record, 92 byte.
    $count=0;
    $str_files="";
    echo "\n".count($allmidi)." midi\n";
    foreach($allmidi as $midi)
    {
        //any row 23 x 4 byte (92)
        $row="\x00\x00";   //00
        $row.=IntToWord($count);  //Record serial number (0-indexed) 
        $row.="\xFF\xFD";  //Style number
        $row.="\xFF\xFF";  //Time signature
        $row.="\xFF\xFF";  //Tempo
        $row.="\x00";  //Fav, S1 and S2 ***) "Fav" = "Fav" = Favorites (value = 1); "S1" = Search 1 (value = 2); and "S2" = Search 2 (value = 4). Values are added. E.g. value = 5 means: Favorites = Yes and Search 2 = Yes 
        //----------------title---------------------------------------------------->
        $titolo=substr(basename($midi['filename']),0,32);
        if (strlen($titolo) < 32)
        {
            for($i=strlen($titolo); $i < 32; $i++)
            {
                $titolo[$i]="\x00";
            }
        }
        $row.=$titolo; //title 32 bytes
        //----------------title----------------------------------------------------<
        //----------------keyword-------------------------------------------------->
        $keyword=substr(basename($midi['author']),0,32);
        if (strlen($keyword) < 32)
        {
            for($i=strlen($keyword); $i < 32; $i++)
            {
                $keyword[$i]="\x00";
            }
        }
        $row.="ANY\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"; //Genre
        $row.=$keyword; //Parole chiave 32 bytes
        //----------------keyword--------------------------------------------------<
        $row.="\x00";  //Intro/Next setting - Which intro will be played at start; "Next" = Which part will follow. Values are added
        $rows.=$row;
        //files-------------------------------------------------------------------->
        $LenFilename=IntToWord(strlen($midi['filename']) + 4); //lunghezza nome file + 4
        $str_files.="FPdt";
        $str_files.="$LenFilename".IntToWord($count)."\x01".$midi['filename']."\x00";
        //files--------------------------------------------------------------------<
        $count++;
        if ($count > 4000)
            break;
    }
    $str_files.="FPed";
    $numrecord=IntToWord($count);
    $header="MDB-100-100-3000"; //16
    $header.="CVP-705\x00"."v1.04\x00\x00\x00"; //16
    $header.="\x00\x00".$numrecord;

    //"\x00\x00\x00\x00\x00\x00\x00\x00"."\x16\0x0D\0x04\0x04";
    $countx=strlen($str_files) - 4;
    $headerFiles="FPhd"."\x00".IntTodWord($countx);
    $contents=$header.$rows.$headerFiles.$str_files;
    $filename="midi_".basename($path).".mfd";
    if (file_exists("./Mfd") && is_dir("./Mfd"))
    {
        file_put_contents("./Mfd/$filename",$contents);        
    }
    else
    {
        file_put_contents("./$filename",$contents);
    }
    echo "\n$filename created\n";
}

//die ("Data;".$contents);
/**
 * 
 * @param type $filename
 * @return string
 */
function GetFileExtension($filename)
{
    if (!strstr($filename,"."))
        return "";
    $tmp=explode(".",$filename);
    $extension=$tmp[count($tmp) - 1];
    return $extension;
}

/**
 * 
 * @param type $dir
 * @param type $filter
 * @param type $results
 * @return type
 */
function getDirContents($dir,$filter='',&$results=array())
{
    $files=scandir($dir);
    foreach($files as $key=> $value)
    {
        $path=realpath($dir.DIRECTORY_SEPARATOR.$value);

        if (!is_dir($path))
        {
            if (empty($filter) || preg_match($filter,$path))
            {
                $results[]=$path;
            }
        }
        elseif ($value!= "." && $value!= "..")
        {
            getDirContents($path,$filter,$results);
        }
    }

    return $results;
}
/**
 * 
 * @param type $value
 * @return type
 */
function IntToWord($value)
{
    $str=sprintf("%04X",$value);
    $left=$str[0].$str[1];
    $right=$str[2].$str[3];
    return hex2bin($left).hex2bin($right);
}
/**
 * 
 * @param type $value
 * @return type
 */
function IntTodWord($value)
{
    $str=sprintf("%06X",$value);
    $left=$str[0].$str[1];
    $right=$str[2].$str[3];
    $right2=$str[4].$str[5];
    return hex2bin($left).hex2bin($right).hex2bin($right2);
}
