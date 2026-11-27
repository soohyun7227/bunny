<?php
// 더블U 총회 의견 게시판 (이름 공개 방명록) — 모두가 읽고 쓰고, 공감 누를 수 있음
// GET            → 글 목록 JSON (최신순)
// POST a=add     n=이름 m=내용 t=작성자 토큰(브라우저에 저장, 본인 글 삭제용)
// POST a=like    id=글번호
// POST a=del     id=글번호 t=작성자 토큰  (또는 k=회장 키)
$KEY  = 'fe7a42e29a1fce104f17';
$DATA = __DIR__.'/ideas-data.php';     // 첫 줄에 exit 이 있어 주소로 열어도 내용이 안 보임
header('X-Robots-Tag: noindex');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
date_default_timezone_set('Asia/Seoul');

function out($x,$code=200){http_response_code($code);exit(json_encode($x,JSON_UNESCAPED_UNICODE));}
function pub($all){$o=[];foreach(array_reverse($all) as $r)$o[]=['id'=>$r['id'],'d'=>$r['d'],'n'=>$r['n'],'m'=>$r['m'],'l'=>$r['l']];return $o;}

$fp=fopen($DATA,'c+'); if(!$fp)out(['ok'=>false],500);
flock($fp, $_SERVER['REQUEST_METHOD']==='POST'?LOCK_EX:LOCK_SH);
$raw=stream_get_contents($fp); $nl=strpos($raw,"\n");
$all=$nl===false?[]:(json_decode(substr($raw,$nl+1),true)?:[]);

if($_SERVER['REQUEST_METHOD']!=='POST'){flock($fp,LOCK_UN);out(['ok'=>true,'list'=>pub($all)]);}

$a=(string)($_POST['a']??'');
$save=function($all)use($fp){ftruncate($fp,0);rewind($fp);fwrite($fp,"<?php exit; ?>\n".json_encode($all,JSON_UNESCAPED_UNICODE));fflush($fp);};

if($a==='add'){
  $n=trim(preg_replace('/\s+/u',' ',(string)($_POST['n']??''))); $m=trim((string)($_POST['m']??'')); $t=(string)($_POST['t']??'');
  if($n===''||mb_strlen($n)>12||$m===''||mb_strlen($m)>500||strlen($t)<16||strlen($t)>64)out(['ok'=>false,'e'=>'bad'],400);
  if(count($all)>=500||strlen($raw)>1500000)out(['ok'=>false,'e'=>'full'],507);
  $last=end($all); if($last&&$last['n']===$n&&$last['m']===$m)out(['ok'=>true,'list'=>pub($all)]);   // 두 번 누름 방지
  $id=$all?max(array_column($all,'id'))+1:1;
  $all[]=['id'=>$id,'d'=>date('Y-m-d H:i'),'n'=>$n,'m'=>$m,'l'=>0,'o'=>hash('sha256',$t)];
  $save($all); out(['ok'=>true,'id'=>$id,'list'=>pub($all)]);
}
$id=(int)($_POST['id']??0); $i=null; foreach($all as $k=>$r)if($r['id']===$id)$i=$k;
if($i===null)out(['ok'=>false,'e'=>'none'],404);
if($a==='like'){ $all[$i]['l']=min(999,$all[$i]['l']+1); $save($all); out(['ok'=>true,'l'=>$all[$i]['l']]); }
if($a==='del'){
  $t=(string)($_POST['t']??''); $k=(string)($_POST['k']??'');
  $okOwner=$t!==''&&hash_equals($all[$i]['o'],hash('sha256',$t)); $okPres=$k!==''&&hash_equals($KEY,$k);
  if(!$okOwner&&!$okPres)out(['ok'=>false,'e'=>'deny'],403);
  array_splice($all,$i,1); $save($all); out(['ok'=>true,'list'=>pub($all)]);
}
out(['ok'=>false],400);
