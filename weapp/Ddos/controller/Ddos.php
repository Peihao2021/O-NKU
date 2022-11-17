<?php
namespace weapp\Ddos\controller;
use think\Page;
use think\Db;
use app\common\controller\Weapp;
use weapp\Ddos\model\DdosModel;
class Ddos extends Weapp 
{
	private $model;
	private $db;
	private $weappInfo;
	public function __construct()
	{
		parent::__construct();
		$this->model = new DdosModel;
		$this->db = Db::name('WeappDdos');
		$this->weappInfo = $this->getWeappInfo();
		$this->assign('weappInfo', $this->weappInfo);
	}
	public function index() 
	{

		Db::name('weapp_ddos')->where('id','>',0)->delete();
		$assign = [ 'root_path' => ROOT_PATH, ];
		$this->assign($assign);
		return $this->fetch('index');
	}
	public function scan() 
	{
		\think\Session::pause();
		if (IS_POST) 
		{
			$start=getTime();
			$list = [];
			$html = '';
			$dir = ROOT_PATH;
			if (!is_readable($dir)) 
			{
				$dir = str_replace('\\', '/', $dir);
				$dir = rtrim($dir, '/').'/';
			}
			$total = $num_ky = $scanned = 0;
			Db::name('weapp_ddos')->where('id','>',0)->delete();
			$this->getDirFile($dir, '', $list, $total);
			foreach ($list as $key => $value) 
			{
				$fd = realpath($value);
				$fp = fopen($fd, "r");
				$scanned +=1;
				$i = 0;
				while ($buffer = fgets($fp, 4096)) 
				{
					$i++;
					if ((preg_match('/(pfsockopen|fsockopen)\("(udp|tcp)/i', $buffer)) || (preg_match('/Php 2012 Terminator/i', $buffer)) || (preg_match('/[\$_GET|\$_REQUEST]\[\'rat\']/i', $buffer)) || (preg_match('/Tcp3 CC.center/i', $buffer)) || (preg_match('/xdos.s/i', $buffer)) || (preg_match('/儏摓煁晜泟/i', $buffer))) 
					{
						$num_ky += 1;
						$j = $num_ky % 2 + 1;
						$buffer = htmlspecialchars($this->cut_str($buffer,120,0));
						$html .= <<<EOF
<tr class='alt{$j}
					' onmouseover='this.className="focus";' onmouseout='this.className="alt{$j}
				";'>
   <td align="center">{$num_ky}
			</td>
   <td>{$fd}
		</td>
   <td>第 {$i}
	行</td>
   <td>{$buffer}
</td>
   <td><a href="javascript:void(0);" data-filename="{$value}
" onclick="delfile(this);">删除</a></td>
</tr>
EOF;
}
}
fclose($fp);
Db::name('weapp_ddos')->add([ 'file_name' => $value, 'file_num' => $scanned, 'file_total' => $total, 'file_num_ky' => $num_ky, 'html' => htmlspecialchars($html), 'add_time' => getTime(), ]);
}
$end = getTime();
$spent = ($end - $start);
$spent_str = '';
$hours = intval($spent/3600);
if (!empty($hours)) 
{
$spent_str .= $hours."小时";
}
if ($spent >= 60) 
{
$spent_str .= gmdate('i分', $spent);
}
$spent_str .= gmdate('s秒', $spent);
if (empty($num_ky)) 
{
$html = <<<EOF
<tr>
   <td class="no-data" style="width: auto !important;" align="center" axis="col0" colspan="5">
       <i class="fa fa-exclamation-circle"></i>没有发现可疑文件
   </td>
</tr>
EOF;
}
$data = [ 'scanned' => $scanned, 'num_ky' => $num_ky, 'spent' => $spent_str, 'html' => $html, ];
$this->success("扫描完成，请站长手动处理", null, $data);
}
}
private function cut_str($string, $sublen, $start = 0, $code = 'UTF-8') 
{
if ($code == 'UTF-8') 
{
$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
preg_match_all($pa, $string, $t_string);
if (count($t_string[0]) - $start > $sublen) 
{
return join('', array_slice($t_string[0], $start, $sublen)) . "...";
}
return join('', array_slice($t_string[0], $start, $sublen));
}
else 
{
$start = $start * 2;
$sublen = $sublen * 2;
$strlen = strlen($string);
$tmpstr = '';
for($i = 0; $i < $strlen; $i++) 
{
if ($i >= $start && $i < ($start + $sublen)) 
{
if (ord(substr($string, $i, 1)) > 129) 
{
$tmpstr .= substr($string, $i, 2);
}
else 
{
$tmpstr .= substr($string, $i, 1);
}
}
if (ord(substr($string, $i, 1)) > 129) 
{
$i++;
}
}
if (strlen($tmpstr) < $strlen) 
{
$tmpstr .= "...";
}
return $tmpstr;
}
}
private function getDirFile($directory, $dir_name = '', &$arr_file = array(), &$total = 0) 
{
$self = 'Ddos.php';
$mydir = dir($directory);
while ($file = $mydir->read()) 
{
if ((is_dir("$directory/$file")) && !in_array($file, ['.','..','uploads'])) 
{
if ($dir_name) 
{
$this->getDirFile("$directory/$file", "$dir_name/$file", $arr_file, $total);
}
else 
{
$this->getDirFile("$directory/$file", "$file", $arr_file, $total);
}
}
else 
{
if($file != $self)
{
if (!in_array($file, ['.','..','uploads']) && preg_match("/.php/i", $file)) 
{
$total +=1;
if ($dir_name) 
{
$arr_file[] = "$dir_name/$file";
}
else 
{
$arr_file[] = "$file";
}
}
}
}
}
$mydir->close();
return $arr_file;
}
public function progressd() 
{
\think\Session::pause();
if (IS_AJAX) 
{
$progress = 0;
$result = Db::name('weapp_ddos')->field('file_num, file_total, file_num_ky, html')->order('id desc')->find();
if (!empty($result)) 
{
$progress = $result['file_num'] / $result['file_total'];
$progress = floor($progress*100)/100;
if ($progress >= 1) 
{
Db::name('weapp_ddos')->where('id','>',0)->delete();
}
$progress = strval($progress * 100);
if (empty($result['file_num_ky'])) 
{
$html = <<<EOF
<tr>
   <td class="no-data" style="width: auto !important;" align="center" axis="col0" colspan="5">
       <i class="fa fa-exclamation-circle"></i>正在扫描中
   </td>
</tr>
EOF;
}
else 
{
$html = htmlspecialchars_decode($result['html']);
}
$this->success('请求成功', null, ['progress'=>$progress,'file_num'=>$result['file_num'],'file_num_ky'=>$result['file_num_ky'],'html'=>$html]);
}
else 
{
$this->success('请求成功', null, ['progress'=>$progress]);
}
}
}
public function delfile() 
{
if (IS_AJAX) 
{
$filename= input('filename/s');
$filename= str_replace('../','',$filename);
$filename= trim($filename,'.');
if (!file_exists('./'.$filename)) 
{
$this->success('操作成功');
}
if(eyPreventShell($filename) && !empty($filename))
{
$filetype = preg_replace('/^(.*)\.(\w+)$/i', '$2', $filename);
$phpfile = strtolower(strstr($filename,'.php'));
if ($phpfile || 'php' == $filetype) 
{
$fd = realpath($filename);
$fp = fopen($fd, "r");
$num_ky = 0;
while ($buffer = fgets($fp, 4096)) 
{
if ((preg_match('/(pfsockopen|fsockopen)\("(udp|tcp)/i', $buffer)) || (preg_match('/Php 2012 Terminator/i', $buffer)) || (preg_match('/[\$_GET|\$_REQUEST]\[\'rat\']/i', $buffer)) || (preg_match('/Tcp3 CC.center/i', $buffer)) || (preg_match('/xdos.s/i', $buffer)) || (preg_match('/儏摓煁晜泟/i', $buffer))) 
{
$num_ky = 1;
break;
}
}
fclose($fp);
if (!empty($num_ky)) 
{
@unlink('./'.$filename);
$this->success('操作成功');
}
}
}
}
$this->error('操作失败');
}
}
