<?php
// 더블U 익명 건의함 — 이름·IP·기기 정보는 저장하지 않음 (날짜와 내용만)
// 보기: box.php?k=비밀키
$KEY  = 'fe7a42e29a1fce104f17';
$DATA = __DIR__.'/box-data.php';   // 첫 줄에 exit 이 있어 주소로 열어도 내용이 안 보임
header('X-Robots-Tag: noindex');

function rows($f){ if(!is_file($f))return []; $o=[];
  foreach(file($f,FILE_IGNORE_NEW_LINES) as $i=>$l){ if($i===0)continue; $j=json_decode($l,true); if($j)$o[]=$j; } return $o; }

if($_SERVER['REQUEST_METHOD']==='POST'){
  header('Content-Type: application/json; charset=utf-8');
  $m=trim((string)($_POST['m']??''));
  if($m===''||mb_strlen($m)>1000){http_response_code(400);exit('{"ok":false}');}
  if(is_file($DATA)&&filesize($DATA)>2000000){http_response_code(507);exit('{"ok":false}');}
  if(!is_file($DATA))file_put_contents($DATA,"<?php exit; ?>\n",LOCK_EX);
  $all=rows($DATA); if($all&&end($all)['m']===$m)exit('{"ok":true}');   // 같은 내용 두 번 누름 방지
  date_default_timezone_set('Asia/Seoul');
  file_put_contents($DATA,json_encode(['d'=>date('Y-m-d'),'m'=>$m],JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND|LOCK_EX);
  exit('{"ok":true}');
}

if(!isset($_GET['k'])||!hash_equals($KEY,(string)$_GET['k'])){http_response_code(404);exit('Not Found');}
$list=array_reverse(rows($DATA));
$e=fn($s)=>nl2br(htmlspecialchars($s,ENT_QUOTES,'UTF-8'));
?><!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>익명 건의함</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/galmuri@2.40.3/dist/galmuri.min.css">
<style>
body{margin:0;background:#1b2230;color:#2a1916;font-family:Galmuri11,monospace;padding:20px 16px 40px}
h1{color:#f6ecd6;font-size:22px;margin:0 0 4px;text-align:center}
.sub{color:#b9c6dc;font-size:12px;text-align:center;margin:0 0 18px}
.n{max-width:520px;margin:0 auto 12px;background:#f6ecd6;border:3px solid #2a1916;box-shadow:0 4px 0 #2a1916;padding:12px 14px}
.n time{display:block;font-size:11px;color:#8a7560;margin-bottom:6px}
.n p{margin:0;font-family:-apple-system,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;font-size:15px;line-height:1.6;word-break:keep-all;white-space:normal}
.empty{color:#b9c6dc;text-align:center;margin-top:40px}
</style></head><body>
<h1>📮 익명 건의함</h1><p class="sub">총 <?=count($list)?>통 · 회장님만 보는 페이지</p>
<?php if(!$list): ?><p class="empty">아직 도착한 의견이 없어요</p><?php endif; ?>
<?php foreach($list as $r): ?><div class="n"><time><?=$e($r['d'])?></time><p><?=$e($r['m'])?></p></div><?php endforeach; ?>
</body></html>
