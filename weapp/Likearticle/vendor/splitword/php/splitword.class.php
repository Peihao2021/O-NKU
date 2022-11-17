<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-06-28
 */

/**
 * Unicode编码词典的php分词器
 *
 *  1、只适用于php5，必要函数 iconv
 *  2、本程序是使用RMM逆向匹配算法进行分词的，词库需要特别编译，本类里提供了 MakeDict() 方法
 *  3、简单操作流程： SetSource -> StartAnalysis -> Get***Result
 *  4、对主词典使用特殊格式进行编码, 不需要载入词典到内存操作
 */

//常量定义
define('_SP_', chr(0xFF).chr(0xFE)); 
define('UCS2', 'ucs-2be');
class splitword
{
    
    //hash算法选项
    var $mask_value = 0xFFFF;
    
    //输入和输出的字符编码（只允许 utf-8、gbk/gb2312/gb18030、big5 三种类型）  
    var $sourceCharSet = 'utf-8';
    var $targetCharSet = 'utf-8';
    
    //生成的分词结果数据类型 1 为全部， 2为 词典词汇及单个中日韩简繁字符及英文， 3 为词典词汇及英文
    var $resultType = 1;
    
    //句子长度小于这个数值时不拆分，notSplitLen = n(个汉字) * 2 + 1
    var $notSplitLen = 5;
    
    //把英文单词全部转小写
    var $toLower = FALSE;
    
    //使用最大切分模式对二元词进行消岐
    var $differMax = FALSE;
    
    //尝试合并单字
    var $unitWord = TRUE;
    
    //初始化类时直接加载词典
    var $loadInit = TRUE;
    
    //使用热门词优先模式进行消岐
    var $differFreq = FALSE;
    
    //被转换为unicode的源字符串
    var $sourceString = '';
    
    //附加词典
    var $addonDic = array();
    var $addonDicFile = 'words_addons.dic';
    
    //主词典 
    var $dicStr = '';
    var $mainDic = array();
    var $mainDicHand = FALSE;
    var $mainDicInfos = array();
    var $mainDicFile = 'base_dic_full.dic';
    //是否直接载入词典（选是载入速度较慢，但解析较快；选否载入较快，但解析较慢，需要时才会载入特定的词条）
    var $mainDicFileZip = 'base_dic_full.zip';
    var $isLoadAll = FALSE;
    var $isUnpacked = FALSE;
    
    //主词典词语最大长度 x / 2
    var $dicWordMax = 14;
    //粗分后的数组（通常是截取句子等用途）
    var $simpleResult = array();
    //最终结果(用空格分开的词汇列表)
    var $finallyResult = '';
    
    //是否已经载入词典
    var $isLoadDic = FALSE;
    //系统识别或合并的新词
    var $newWords = array();
    var $foundWordStr = '';
    //词库载入时间
    var $loadTime = 0;
    
    /**
     * 构造函数
     * @param $source_charset
     * @param $target_charset
     * @param $load_alldic 
     * @param $source
     *
     * @return void
     */
    function __construct($source_charset='utf-8', $target_charset='utf-8', $load_all=TRUE, $source='')
    {
        $this->SetSource( $source, $source_charset, $target_charset );
        $this->isLoadAll = $load_all;
        if(file_exists(SPLITWORD_DATA.$this->mainDicFile)) $this->isUnpacked = TRUE;
        if($this->loadInit) $this->LoadDict();
    }
    
    function splitword($source_charset='utf-8', $target_charset='utf-8', $load_all=TRUE, $source='')
    {
        $this->__construct($source_charset, $target_charset, $load_all, $source);
    }
    
   /**
    * 析构函数
    */
    function __destruct()
    {
        if( $this->mainDicHand !== FALSE )
        {
            @fclose( $this->mainDicHand );
        }
    }
    
    /**
     * 根据字符串计算key索引
     * @param $key
     * @return short int
     */
    function _get_index( $key )
    {
        $l = strlen($key);
        $h = 0x238f13af;
        while ($l--)
        {
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }
        return ($h % $this->mask_value);
    }
    
    /**
     * 从文件获得词
     * @param $key
     * @param $type (类型 word 或 key_groups)
     * @return short int
     */
    function GetWordInfos( $key, $type='word' )
    {
        if( !$this->mainDicHand )
        {
            $this->mainDicHand = fopen($this->mainDicFile, 'r');
        }
        $p = 0;
        $keynum = $this->_get_index( $key );
        if( isset($this->mainDicInfos[ $keynum ]) )
        {
            $data = $this->mainDicInfos[ $keynum ];
        }
        else
        {
            //rewind( $this->mainDicHand );
            $move_pos = $keynum * 8;
            fseek($this->mainDicHand, $move_pos, SEEK_SET);
            $dat = fread($this->mainDicHand, 8);
            $arr = unpack('I1s/n1l/n1c', $dat);
            if( $arr['l'] == 0 )
            {
                return FALSE;
            }
            fseek($this->mainDicHand, $arr['s'], SEEK_SET);
            $data = @unserialize(fread($this->mainDicHand, $arr['l']));
            $this->mainDicInfos[ $keynum ] = $data;
       }
       if( !is_array($data) || !isset($data[$key]) ) 
       {
           return FALSE;
       }
       return ($type=='word' ? $data[$key] : $data);
    }
    
    /**
     * 设置源字符串
     * @param $source
     * @param $source_charset
     * @param $target_charset
     *
     * @return bool
     */
    function SetSource( $source, $source_charset='utf-8', $target_charset='utf-8' )
    {
        $this->sourceCharSet = strtolower($source_charset);
        $this->targetCharSet = strtolower($target_charset);
        $this->simpleResult = array();
        $this->finallyResult = array();
        $this->finallyIndex = array();
        if( $source != '' )
        {
            $rs = TRUE;
            if( preg_match("/^utf/", $source_charset) ) {
                $this->sourceString = @iconv('utf-8', UCS2, $source);
            }
            else if( preg_match("/^gb/", $source_charset) ) {
                $this->sourceString = @iconv('utf-8', UCS2, iconv('gb18030', 'utf-8', $source));
            }
            else if( preg_match("/^big/", $source_charset) ) {
                $this->sourceString = @iconv('utf-8', UCS2, iconv('big5', 'utf-8', $source));
            }
            else {
                $rs = FALSE;
            }
        }
        else
        {
           $rs = FALSE;
        }
        return $rs;
    }
    
    /**
     * 设置结果类型(只在获取finallyResult才有效)
     * @param $rstype 1 为全部， 2去除特殊符号
     *
     * @return void
     */
    function SetResultType( $rstype )
    {
        $this->resultType = $rstype;
    }
    
    /**
     * 载入词典
     *
     * @return void
     */
    function LoadDict( $maindic='' )
    {
        $this->addonDicFile = SPLITWORD_DATA.$this->addonDicFile;
        $this->mainDicFile = SPLITWORD_DATA.$this->mainDicFile;
        $this->mainDicFileZip = SPLITWORD_DATA.$this->mainDicFileZip;
        $startt = microtime(TRUE);
        //正常读取文件
        $dicAddon = $this->addonDicFile;
        if($maindic=='' || !file_exists($maindic) )
        {
            $dicWords = $this->mainDicFile ;
        }
        else
        {
            $dicWords = $maindic;
            $this->mainDicFile = $maindic;
        }

        //加载主词典（只打开）
        if($this->isUnpacked){
            $this->mainDicHand = fopen($dicWords, 'r');
        }else{
            $inportDict_return = $this->InportDict($this->mainDicFileZip);
            if (true !== $inportDict_return) {
                return $inportDict_return;
            }
        }
        
        //载入副词典
        $hw = '';
        $ds = file($dicAddon);
        foreach($ds as $d)
        {
            $d = trim($d);
            if($d=='') continue;
            $estr = substr($d, 1, 1);
            if( $estr==':' ) {
                $hw = substr($d, 0, 1);
            }
            else
            {
                $spstr = _SP_;
                $spstr = iconv(UCS2, 'utf-8', $spstr);
                $ws = explode(',', $d);
                $wall = iconv('utf-8', UCS2, join($spstr, $ws));
                $ws = explode(_SP_, $wall);
                foreach($ws as $estr)
                {
                    $this->addonDic[$hw][$estr] = strlen($estr);
                }
            }
        }
        $this->loadTime = microtime(TRUE) - $startt;
        $this->isLoadDic = TRUE;
    }
    
   /**
    * 检测某个词是否存在
    */
    function IsWord( $word )
    {
         $winfos = $this->GetWordInfos( $word );
         return ($winfos !== FALSE);
    }
    
    /**
     * 获得某个词的词性及词频信息
     * @parem $word unicode编码的词
     * @return void
     */
     function GetWordProperty($word)
     {
        if( strlen($word)<4 )
        {
            return '/s';
        }
        $infos = $this->GetWordInfos($word);
        return isset($infos[1]) ? "/{$infos[1]}{$infos[0]}" : "/s";
     }
    
    /**
     * 指定某词的词性信息（通常是新词）
     * @parem $word unicode编码的词
     * @parem $infos array('c' => 词频, 'm' => 词性);
     * @return void;
     */
    function SetWordInfos($word, $infos)
    {
        if( strlen($word)<4 )
        {
            return ;
        }
        if( isset($this->mainDicInfos[$word]) )
        {
            $this->newWords[$word]++;
            $this->mainDicInfos[$word]['c']++;
        }
        else
        {
            $this->newWords[$word] = 1;
            $this->mainDicInfos[$word] = $infos;
        }
    }
    
    /**
     * 开始执行分析
     * @parem bool optimize 是否对结果进行优化
     * @return bool
     */
    function StartAnalysis($optimize=TRUE)
    {
        if( !$this->isLoadDic )
        {
            $dict_return = $this->LoadDict();
            if (true !== $dict_return) {
                return $dict_return;
            }
        }
        $this->simpleResult = $this->finallyResult = array();
        $this->sourceString .= chr(0).chr(32);
        $slen = strlen($this->sourceString);
        $sbcArr = array();
        $j = 0;
        //全角与半角字符对照表
        for($i=0xFF00; $i < 0xFF5F; $i++)
        {
            $scb = 0x20 + $j;
            $j++;
            $sbcArr[$i] = $scb;
        }
        //对字符串进行粗分
        $onstr = '';
        $lastc = 1; //1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
        $s = 0;
        $ansiWordMatch = "[0-9a-z@#%\+\.-]";
        $notNumberMatch = "[a-z@#%\+]";
        for($i=0; $i < $slen; $i++)
        {
            $c = $this->sourceString[$i].$this->sourceString[++$i];
            $cn = hexdec(bin2hex($c));
            $cn = isset($sbcArr[$cn]) ? $sbcArr[$cn] : $cn;
            //ANSI字符
            if($cn < 0x80)
            {
                if( preg_match('/'.$ansiWordMatch.'/i', chr($cn)) )
                {
                    if( $lastc != 2 && $onstr != '') {
                        $this->simpleResult[$s]['w'] = $onstr;
                        $this->simpleResult[$s]['t'] = $lastc;
                        $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 2;
                    $onstr .= chr(0).chr($cn);
                }
                else
                {
                    if( $onstr != '' )
                    {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if( $lastc==2 )
                        {
                            if( !preg_match('/'.$notNumberMatch.'/i', iconv(UCS2, 'utf-8', $onstr)) ) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if( $lastc != 4 ) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                    }
                    $onstr = '';
                    $lastc = 3;
                    if($cn < 31)
                    {
                        continue;
                    }
                    else
                    {
                        $this->simpleResult[$s]['w'] = chr(0).chr($cn);
                        $this->simpleResult[$s]['t'] = 3;
                        $s++;
                    }
                }
            }
            //普通字符
            else
            {
                //正常文字
                if( ($cn>0x3FFF && $cn < 0x9FA6) || ($cn>0xF8FF && $cn < 0xFA2D)
                    || ($cn>0xABFF && $cn < 0xD7A4) || ($cn>0x3040 && $cn < 0x312B) )
                {
                    if( $lastc != 1 && $onstr != '')
                    {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if( $lastc==2 )
                        {
                            if( !preg_match('/'.$notNumberMatch.'/i', iconv(UCS2, 'utf-8', $onstr)) ) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if( $lastc != 4 ) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 1;
                    $onstr .= $c;
                }
                //特殊符号
                else
                {
                    if( $onstr != '' )
                    {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if( $lastc==2 )
                        {
                            if( !preg_match('/'.$notNumberMatch.'/i', iconv(UCS2, 'utf-8', $onstr)) ) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if( $lastc != 4 ) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                    }
                    
                    //检测书名
                    if( $cn == 0x300A )
                    {
                        $tmpw = '';
                        $n = 1;
                        $isok = FALSE;
                        $ew = chr(0x30).chr(0x0B);
                        while(TRUE)
                        {
                            if(!isset($this->sourceString[$i+$n]) && !isset($this->sourceString[$i+$n+1]))
                            break;
                            $w = $this->sourceString[$i+$n].$this->sourceString[$i+$n+1];
                            if( $w == $ew )
                            {
                                $this->simpleResult[$s]['w'] = $c;
                                $this->simpleResult[$s]['t'] = 5;
                                $s++;
                        
                                $this->simpleResult[$s]['w'] = $tmpw;
                                $this->newWords[$tmpw] = 1;
                                if( !isset($this->newWords[$tmpw]) )
                                {
                                    $this->foundWordStr .= $this->_out_string_encoding($tmpw).'/nb, ';
                                    $this->SetWordInfos($tmpw, array('c'=>1, 'm'=>'nb'));
                                }
                                $this->simpleResult[$s]['t'] = 13;
                                
                                $s++;

                                //最大切分模式对书名继续分词
                                if( $this->differMax )
                                {
                                    $this->simpleResult[$s]['w'] = $tmpw;
                                    $this->simpleResult[$s]['t'] = 21;
                                    $this->_deep_analysis($tmpw, $lastc, $s, $optimize);
                                    $s++;
                                }
                                
                                $this->simpleResult[$s]['w'] = $ew;
                                $this->simpleResult[$s]['t'] =  5;
                                $s++;
                        
                                $i = $i + $n + 1;
                                $isok = TRUE;
                                $onstr = '';
                                $lastc = 5;
                                break;
                            }
                            else
                            {
                                $n = $n+2;
                                $tmpw .= $w;
                                if( strlen($tmpw) > 60 )
                                {
                                    break;
                                }
                            }
                        }//while
                        if( !$isok )
                        {
                            $this->simpleResult[$s]['w'] = $c;
                              $this->simpleResult[$s]['t'] = 5;
                              $s++;
                              $onstr = '';
                            $lastc = 5;
                        }
                        continue;
                    }
                    
                    $onstr = '';
                    $lastc = 5;
                    if( $cn==0x3000 )
                    {
                        continue;
                    }
                    else
                    {
                        $this->simpleResult[$s]['w'] = $c;
                        $this->simpleResult[$s]['t'] = 5;
                        $s++;
                    }
                }//2byte symbol
                
            }//end 2byte char
        
        }//end for
        
        //处理分词后的结果
        $this->_sort_finally_result();

        return true;
    }
    
    /**
     * 深入分词
     * @parem $str
     * @parem $ctype (2 英文类， 3 中/韩/日文类)
     * @parem $spos   当前粗分结果游标
     * @return bool
     */
    function _deep_analysis( &$str, $ctype, $spos, $optimize=TRUE )
    {

        //中文句子
        if( $ctype==1 )
        {
            $slen = strlen($str);
            //小于系统配置分词要求长度的句子
            if( $slen < $this->notSplitLen )
            {
                $tmpstr = '';
                $lastType = 0;
                if( $spos > 0 ) $lastType = $this->simpleResult[$spos-1]['t'];
                if($slen < 5)
                {
                      //echo iconv(UCS2, 'utf-8', $str).'<br/>';
                      if( $lastType==4 && ( isset($this->addonDic['u'][$str]) || isset($this->addonDic['u'][substr($str, 0, 2)]) ) )
                      {
                              $str2 = '';
                              if( !isset($this->addonDic['u'][$str]) && isset($this->addonDic['s'][substr($str, 2, 2)]) )
                              {
                                     $str2 = substr($str, 2, 2);
                                     $str  = substr($str, 0, 2);
                              }
                              $ww = $this->simpleResult[$spos - 1]['w'].$str;
                              $this->simpleResult[$spos - 1]['w'] = $ww;
                              $this->simpleResult[$spos - 1]['t'] = 4;
                              if( !isset($this->newWords[$this->simpleResult[$spos - 1]['w']]) )
                              {
                                     $this->foundWordStr .= $this->_out_string_encoding( $ww ).'/mu, ';
                                     $this->SetWordInfos($ww, array('c'=>1, 'm'=>'mu'));
                              }
                              $this->simpleResult[$spos]['w'] = '';
                              if( $str2 != '' )
                              {
                                     $this->finallyResult[$spos-1][] = $ww;
                                     $this->finallyResult[$spos-1][] = $str2;
                              }
                       }
                       else {
                              $this->finallyResult[$spos][] = $str;
                       }
                }
                else
                {
                      $this->_deep_analysis_cn( $str, $ctype, $spos, $slen, $optimize );
                }
            }
            //正常长度的句子，循环进行分词处理
            else
            {
                $this->_deep_analysis_cn( $str, $ctype, $spos, $slen, $optimize );
            }
        }
        //英文句子，转为小写
        else
        {
            if( $this->toLower ) {
                $this->finallyResult[$spos][] = strtolower($str);
            }
            else {
                $this->finallyResult[$spos][] = $str;
            }
        }
    }
    
    /**
     * 中文的深入分词
     * @parem $str
     * @return void
     */
    function _deep_analysis_cn( &$str, $lastec, $spos, $slen, $optimize=TRUE )
    {
        $quote1 = chr(0x20).chr(0x1C);
        $tmparr = array();
        $hasw = 0;
        //如果前一个词为 “ ， 并且字符串小于3个字符当成一个词处理。
        if( $spos > 0 && $slen < 11 && $this->simpleResult[$spos-1]['w']==$quote1 )
        {
            $tmparr[] = $str;
            if( !isset($this->newWords[$str]) )
            {
                $this->foundWordStr .= $this->_out_string_encoding($str).'/nq, ';
                $this->SetWordInfos($str, array('c'=>1, 'm'=>'nq'));
            }
            if( !$this->differMax )
            {
                $this->finallyResult[$spos][] = $str;
                return ;
            }
        }
        //进行切分
        for($i=$slen-1; $i > 0; $i -= 2)
        {
            //单个词
            $nc = $str[$i-1].$str[$i];
            //是否已经到最后两个字
            if( $i <= 2 )
            {
                $tmparr[] = $nc;
                $i = 0;
                break;
            }
            $isok = FALSE;
            $i = $i + 1;
            for($k=$this->dicWordMax; $k>1; $k=$k-2)
            {
                if($i < $k) continue;
                $w = substr($str, $i-$k, $k);
                if( strlen($w) <= 2 )
                {
                    $i = $i - 1;
                    break;
                }
                if( $this->IsWord( $w ) )
                {
                    $tmparr[] = $w;
                    $i = $i - $k + 1;
                    $isok = TRUE;
                    break;
                }
            }
            //echo '<hr />';
            //没适合词
            if(!$isok) $tmparr[] = $nc;
        }
        $wcount = count($tmparr);
        if( $wcount==0 ) return ;
        $this->finallyResult[$spos] = array_reverse($tmparr);
        //优化结果(岐义处理、新词、数词、人名识别等)
        if( $optimize )
        {
            $this->_optimize_result( $this->finallyResult[$spos], $spos );
        }
    }
    
    /**
    * 对最终分词结果进行优化（把simpleresult结果合并，并尝试新词识别、数词合并等）
    * @parem $optimize 是否优化合并的结果
    * @return bool
    */
    //t = 1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
    function _optimize_result( &$smarr, $spos )
    {
        $newarr = array();
        $prePos = $spos - 1;
        $arlen = count($smarr);
        $i = $j = 0;
        //检测数量词
        if( $prePos > -1 && !isset($this->finallyResult[$prePos]) )
        {
            $lastw = $this->simpleResult[$prePos]['w'];
            $lastt = $this->simpleResult[$prePos]['t'];
              if( ($lastt==4 || isset( $this->addonDic['c'][$lastw] )) && isset( $this->addonDic['u'][$smarr[0]] ) )
              {
                 $this->simpleResult[$prePos]['w'] = $lastw.$smarr[0];
                 $this->simpleResult[$prePos]['t'] = 4;
                 if( !isset($this->newWords[ $this->simpleResult[$prePos]['w'] ]) )
                 {
                     $this->foundWordStr .= $this->_out_string_encoding( $this->simpleResult[$prePos]['w'] ).'/mu, ';
                     $this->SetWordInfos($this->simpleResult[$prePos]['w'], array('c'=>1, 'm'=>'mu'));
                 }
                 $smarr[0] = '';
                 $i++;
              }
       }
       for(; $i < $arlen; $i++)
       {
            
            if( !isset( $smarr[$i+1] ) )
            {
                $newarr[$j] = $smarr[$i];
                break;
            }
            $cw = $smarr[$i];
            $nw = $smarr[$i+1];
            $ischeck = FALSE;
            //检测数量词
            if( isset( $this->addonDic['c'][$cw] ) && isset( $this->addonDic['u'][$nw] ) )
            {
                //最大切分时保留合并前的词
                if($this->differMax)
                {
                        $newarr[$j] = chr(0).chr(0x28);
                        $j++;
                        $newarr[$j] = $cw;
                        $j++;
                        $newarr[$j] = $nw;
                        $j++;
                        $newarr[$j] = chr(0).chr(0x29);
                        $j++;
                }
                $newarr[$j] = $cw.$nw;
                if( !isset($this->newWords[$newarr[$j]]) )
                {
                    $this->foundWordStr .= $this->_out_string_encoding( $newarr[$j] ).'/mu, ';
                    $this->SetWordInfos($newarr[$j], array('c'=>1, 'm'=>'mu'));
                }
                $j++; $i++; $ischeck = TRUE;
            }
            //检测前导词(通常是姓)
            else if( isset( $this->addonDic['n'][ $smarr[$i] ] ) )
            {
                $is_rs = FALSE;
                //词语是副词或介词或频率很高的词不作为人名
                if( strlen($nw)==4 )
                {
                    $winfos = $this->GetWordInfos($nw);
                    if(isset($winfos['m']) && ($winfos['m']=='r' || $winfos['m']=='c' || $winfos['c']>500) )
                    {
                         $is_rs = TRUE;
                    }
                }
                if( !isset($this->addonDic['s'][$nw]) && strlen($nw)<5 && !$is_rs )
                {
                    $newarr[$j] = $cw.$nw;
                    //echo iconv(UCS2, 'utf-8', $newarr[$j])."<br />";
                    //尝试检测第三个词
                    if( strlen($nw)==2 && isset($smarr[$i+2]) && strlen($smarr[$i+2])==2 && !isset( $this->addonDic['s'][$smarr[$i+2]] ) )
                    {
                        $newarr[$j] .= $smarr[$i+2];
                        $i++;
                    }
                    if( !isset($this->newWords[$newarr[$j]]) )
                    {
                        $this->SetWordInfos($newarr[$j], array('c'=>1, 'm'=>'nr'));
                        $this->foundWordStr .= $this->_out_string_encoding($newarr[$j]).'/nr, ';
                    }
                    //为了防止错误，保留合并前的姓名
                    if(strlen($nw)==4)
                    {
                        $j++;
                        $newarr[$j] = chr(0).chr(0x28);
                        $j++;
                        $newarr[$j] = $cw;
                        $j++;
                        $newarr[$j] = $nw;
                        $j++;
                        $newarr[$j] = chr(0).chr(0x29);
                    }
                    
                    $j++; $i++; $ischeck = TRUE;
                }
            }
            //检测后缀词(地名等)
            else if( isset($this->addonDic['a'][$nw]) )
            {
                $is_rs = FALSE;
                //词语是副词或介词不作为前缀
                if( strlen($cw)>2 )
                {
                    $winfos = $this->GetWordInfos($cw);
                    if(isset($winfos['m']) && ($winfos['m']=='a' || $winfos['m']=='r' || $winfos['m']=='c' || $winfos['c']>500) )
                    {
                         $is_rs = TRUE;
                    }
                }
                if( !isset($this->addonDic['s'][$cw]) && !$is_rs )
                {
                    $newarr[$j] = $cw.$nw;
                    if( !isset($this->newWords[$newarr[$j]]) )
                    {
                        $this->foundWordStr .= $this->_out_string_encoding($newarr[$j]).'/na, ';
                        $this->SetWordInfos($newarr[$j], array('c'=>1, 'm'=>'na'));
                    }
                    $i++; $j++; $ischeck = TRUE;
                }
            }
            //新词识别（暂无规则）
            else if($this->unitWord)
            {
                if(strlen($cw)==2 && strlen($nw)==2 
                && !isset($this->addonDic['s'][$cw]) && !isset($this->addonDic['t'][$cw]) && !isset($this->addonDic['a'][$cw]) 
                && !isset($this->addonDic['s'][$nw]) && !isset($this->addonDic['c'][$nw]))
                {
                    $newarr[$j] = $cw.$nw;
                    //尝试检测第三个词
                    if( isset($smarr[$i+2]) && strlen($smarr[$i+2])==2 && (isset( $this->addonDic['a'][$smarr[$i+2]] ) || isset( $this->addonDic['u'][$smarr[$i+2]] )) )
                    {
                        $newarr[$j] .= $smarr[$i+2];
                        $i++;
                    }
                    if( !isset($this->newWords[$newarr[$j]]) )
                    {
                        $this->foundWordStr .= $this->_out_string_encoding($newarr[$j]).'/ms, ';
                        $this->SetWordInfos($newarr[$j], array('c'=>1, 'm'=>'ms'));
                    }
                    $i++; $j++; $ischeck = TRUE;
                }
            }
            
            //不符合规则
            if( !$ischeck )
            {
                $newarr[$j] = $cw;
                  //二元消岐处理——最大切分模式
                if( $this->differMax && !isset($this->addonDic['s'][$cw]) && strlen($cw) < 5 && strlen($nw) < 7)
                {
                    $slen = strlen($nw);
                    $hasDiff = FALSE;
                    for($y=2; $y <= $slen-2; $y=$y+2)
                    {
                        $nhead = substr($nw, $y-2, 2);
                        $nfont = $cw.substr($nw, 0, $y-2);
                        if( $this->IsWord( $nfont.$nhead ) )
                        {
                            if( strlen($cw) > 2 ) $j++;
                            $hasDiff = TRUE;
                            $newarr[$j] = $nfont.$nhead;
                        }
                    }
                }
                $j++;
            }
            
       }//end for
       $smarr =  $newarr;
    }
    
    /**
    * 转换最终分词结果到 finallyResult 数组
    * @return void
    */
    function _sort_finally_result()
    {
        $newarr = array();
        $i = 0;
        foreach($this->simpleResult as $k=>$v)
        {
            if( empty($v['w']) ) continue;
            if( isset($this->finallyResult[$k]) && count($this->finallyResult[$k]) > 0 )
            {
                foreach($this->finallyResult[$k] as $w)
                {
                    if(!empty($w))
                    {
                        $newarr[$i]['w'] = $w;
                        $newarr[$i]['t'] = 20;
                        $i++;
                    }
                }
            }
            else if($v['t'] != 21)
            {
                $newarr[$i]['w'] = $v['w'];
                $newarr[$i]['t'] = $v['t'];
                $i++;
            }
        }
        $this->finallyResult = $newarr;
        $newarr = '';
      }
    
    /**
     * 把uncode字符串转换为输出字符串
     * @parem str
     * return string
     */
     function _out_string_encoding( &$str )
     {
        $rsc = $this->_source_result_charset();
        if( $rsc==1 ) {
            $rsstr = iconv(UCS2, 'utf-8', $str);
        }
        else if( $rsc==2 ) {
            $rsstr = iconv('utf-8', 'gb18030', iconv(UCS2, 'utf-8', $str) );
        }
        else{
            $rsstr = iconv('utf-8', 'big5', iconv(UCS2, 'utf-8', $str) );
        }
        return $rsstr;
     }
    
    /**
     * 获取最终结果字符串（用空格分开后的分词结果）
     * @return string
     */
     function GetFinallyResult($spword=' ', $word_meanings=FALSE)
     {
        $rsstr = '';
        foreach($this->finallyResult as $v)
        {
            if( $this->resultType==2 && ($v['t']==3 || $v['t']==5) )
            {
                continue;
            }
            $m = '';
            if( $word_meanings )
            {
                $m = $this->GetWordProperty($v['w']);
            }
            $w = $this->_out_string_encoding($v['w']);
            if( $w != ' ' )
            {
                if($word_meanings) {
                    $rsstr .= $spword.$w.$m;
                }
                else {
                    $rsstr .= $spword.$w;
                }
            }
        }
        return $rsstr;
     }
     
    /**
     * 获取粗分结果，不包含粗分属性
     * @return array()
     */
     function GetSimpleResult()
     {
        $rearr = array();
        foreach($this->simpleResult as $k=>$v)
        {
            if( empty($v['w']) ) continue;
            $w = $this->_out_string_encoding($v['w']);
            if( $w != ' ' ) $rearr[] = $w;
        }
        return $rearr;
     }
     
    /**
     * 获取粗分结果，包含粗分属性（1中文词句、2 ANSI词汇（包括全角），3 ANSI标点符号（包括全角），4数字（包括全角），5 中文标点或无法识别字符）
     * @return array()
     */
     function GetSimpleResultAll()
     {
        $rearr = array();
        foreach($this->simpleResult as $k=>$v)
        {
            $w = $this->_out_string_encoding($v['w']);
            if( $w != ' ' )
            {
                $rearr[$k]['w'] = $w;
                $rearr[$k]['t'] = $v['t'];
            }
        }
        return $rearr;
     }
     
    /**
     * 获取索引hash数组
     * @return array('word'=>count,...)
     */
     function GetFinallyIndex()
     {
        $rearr = array();
        foreach($this->finallyResult as $v)
        {
            if( $this->resultType==2 && ($v['t']==3 || $v['t']==5) )
            {
                continue;
            }
            $w = $this->_out_string_encoding($v['w']);
            if( $w == ' ' )
            {
                continue;
            }
            if( isset($rearr[$w]) )
            {
                 $rearr[$w]++;
            }
            else
            {
                 $rearr[$w] = 1;
            }
        }
        return $rearr;
     }
     
    /**
     * 获得保存目标编码
     * @return int
     */
     function _source_result_charset()
     {
        if( preg_match("/^utf/", $this->targetCharSet) ) {
           $rs = 1;
        }
        else if( preg_match("/^gb/", $this->targetCharSet) ) {
           $rs = 2;
        }
        else if( preg_match("/^big/", $this->targetCharSet) ) {
           $rs = 3;
        }
        else {
            $rs = 4;
        }
        return $rs;
     }
     
     /**
     * 编译词典
     * @parem $sourcefile utf-8编码的文本词典数据文件<参见范例dict/not-build/base_dic_full.txt>
     * 注意, 需要PHP开放足够的内存才能完成操作
     * @return void
     */
     function MakeDict( $source_file, $target_file='' )
     {
        $target_file = ($target_file=='' ? $this->mainDicFile : $target_file);
        $allk = array();
        $fp = fopen($source_file, 'r');
        while( $line = fgets($fp, 512) )
        {
            if( $line[0]=='@' ) continue;
            list($w, $r, $a) = explode(',', $line);
            $a = trim( $a );
            $w = iconv('utf-8', UCS2, $w);
            $k = $this->_get_index( $w );
            if( isset($allk[ $k ]) )
                $allk[ $k ][ $w ] = array($r, $a);
            else
                $allk[ $k ][ $w ] = array($r, $a);
        }
        fclose( $fp );
        $fp = fopen($target_file, 'w');
        $heade_rarr = array();
        $alldat = '';
        $start_pos = $this->mask_value * 8;
        foreach( $allk as $k => $v )
        {
            $dat  = serialize( $v );
            $dlen = strlen($dat);
            $alldat .= $dat;
        
            $heade_rarr[ $k ][0] = $start_pos;
            $heade_rarr[ $k ][1] = $dlen;
            $heade_rarr[ $k ][2] = count( $v );
        
            $start_pos += $dlen;
        }
        unset( $allk );
        for($i=0; $i < $this->mask_value; $i++)
        {
            if( !isset($heade_rarr[$i]) )
            {
                $heade_rarr[$i] = array(0, 0, 0);
            }
            fwrite($fp, pack("Inn", $heade_rarr[$i][0], $heade_rarr[$i][1], $heade_rarr[$i][2]));
        }
        fwrite( $fp, $alldat);
        fclose( $fp );
     }
     
     /**
     * 导出词典的词条
     * @parem $targetfile 保存位置
     * @return void
     */
     function ExportDict( $targetfile )
     {
        if( !$this->mainDicHand )
        {
            $this->mainDicHand = fopen($this->mainDicFile, 'rw');
        }
        $fp = fopen($targetfile, 'w');
        for($i=0; $i <= $this->mask_value; $i++)
        {
            $move_pos = $i * 8;
            fseek($this->mainDicHand, $move_pos, SEEK_SET);
            $dat = fread($this->mainDicHand, 8);
            $arr = unpack('I1s/n1l/n1c', $dat);
            if( $arr['l'] == 0 )
            {
                continue;
            }
            fseek($this->mainDicHand, $arr['s'], SEEK_SET);
            $data = @unserialize(fread($this->mainDicHand, $arr['l']));
            if( !is_array($data) ) continue;
            foreach($data as $k => $v)
            {
                $w = iconv(UCS2, 'utf-8', $k);
                fwrite($fp, "{$w},{$v[0]},{$v[1]}\n");
            }
        }
        fclose( $fp );
        return TRUE;
     }
     
    function InportDict( $targetfile )
    {
        if(!ini_set('memory_limit', '128M')) {
            return ['code'=>0, 'msg'=>'检测词库解压失败，请到手工下载词库包，并解压拷贝 base_dic_full.dic 文件到 weapp/Splitword/vendor/splitword/data/ 目录下！<br/>下载地址：<a href="http://pan.baidu.com/s/1TJGa25_zXb1uiZ-LkdmPMQ" target="_blank">http://pan.baidu.com/s/1TJGa25_zXb1uiZ-LkdmPMQ</a>'];
        }
        require_once('zip.class.php');
        $zip = new zip();
        //echo $targetfile;
        $unpackagefile = array_keys($zip->Extract($targetfile,SPLITWORD_DATA));
        //exit();
        $this->MakeDict(SPLITWORD_DATA.$unpackagefile[0]);
        unlink(SPLITWORD_DATA.$unpackagefile[0]);
        return true;
    }
}

?> 
