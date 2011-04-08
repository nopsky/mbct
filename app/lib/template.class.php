<?php
/**
* Discuz模板类 2009年10月22日
*
* @package functions
* @copyright Copyright (c) 2007-2008 (http://hi.baidu.com/nopsky)
* @author NopSky <NopSky@163.com>
* @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
*/
class template  {
    var $inajax=0;
    var $timestamp;
    var $tplrefresh=1;

    public function template($file, $tpldir='', $tplref='1') {
        $file .= $this->inajax && ($file == 'header' || $file == 'footer') ? '_ajax' : '';
        $this->timestamp = time();
        $tpldir = $tpldir ? APP_T.'/'.$tpldir : APP_T;
        $this->tplrefresh = $tplref;
        $tplfile = $tpldir.$file.'.htm';
        $objfile = APP_C.'/tpl/'.$file.'.tpl.php';
        if(!file_exists($tplfile)) {
            exit("$tplfile is not exists");
        }
        $timecompare = file_exists($objfile) ? filemtime($objfile) : '0';
        @$this->checktplrefresh($tplfile, $tplfile, $timecompare, $tpldir);
        return $objfile;
    }

    public function checktplrefresh($maintpl, $subtpl, $timecompare, $tpldir) {
        if(empty($timecompare) || $this->tplrefresh == 1 || ($this->tplrefresh > 1 && !($this->timestamp % $this->tplrefresh))) {
            if(empty($timecompare) || @filemtime($subtpl) > $timecompare) {
                $this->parse_template($maintpl, $tpldir);
                return TRUE;
            }
        }
        return     FALSE;
    }

    public function parse_template($tplfile, $tpldir) {
        $nest = 6;
        $file = basename($tplfile, '.htm');
        $objfile = APP_CACHE_DIR.'tpl/'.$file.'.tpl.php';
        if(!@$fp = fopen($tplfile, 'r')) {
            exit("Current template file './$tpldir/$file.htm' not found or have no access!");
        }

        $template = @fread($fp, filesize($tplfile));
        fclose($fp);
        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = str_replace("{LF}", "<?=\"\\n\"?>", $template);
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
        $template = preg_replace("/$var_regexp/es", "self::addquote('<?=\\1?>')", $template);
        $template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "self::addquote('<?=\\1?>')", $template);
        
        $template = "<? if(!defined('APP_DIR')) exit('Access Denied');?>\n$template";
        
        $template = preg_replace("/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/ies", "self::retemplate('\\1')", $template);
        $template = preg_replace("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/ies", "self::retemplate('\\1')", $template);
        $template = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/ies", "self::stripvtags('<? \\1 ?>','')", $template);
        $template = preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies", "self::stripvtags('<? echo \\1; ?>','')", $template);
        $template = preg_replace("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies", "self::stripvtags('\\1<? } elseif(\\2) { ?>\\3','')", $template);
        $template = preg_replace("/([\n\r\t]*)\{else\}([\n\r\t]*)/is", "\\1<? } else { ?>\\2", $template);
        for($i = 0; $i < $nest; $i++) {
        $template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "self::stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>','\\3<?php } } ?>')", $template);
        $template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "self::stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\\4<?php } } ?>')", $template);
        $template = preg_replace("/\{if\s+(.+?)\}(.+?)\{\/if\}/ies", "self::stripvtags('<?php if(\\1) { ?>','\\2<?php } ?>')", $template);
        }
        
        $template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
    
        if(!@$fp = fopen($objfile, 'w')) {
            exit(DATA_DIR." not found or have no access!");
        }

        $template = preg_replace("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e", "self::transamp('\\0')", $template);
        $template = preg_replace("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ise", "self::stripscriptamp('\\1', '\\2')", $template);
        $template = preg_replace("/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/ies", "self::stripblock('\\1', '\\2')", $template);
        
        
        flock($fp, 2);
        fwrite($fp, $template);
        fclose($fp);
    }

    public function transamp($str) {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

    public function addquote($var) {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    public function retemplate($var) {
        $obj = $this->template($var);
        return "<?php \n include_once('$obj');\n?>";
    }

    public function stripvtags($expr, $statement) {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr.$statement;
    }

    public function stripscriptamp($s, $extra) {
        $extra = str_replace('\\"', '"', $extra);
        $s = str_replace('&amp;', '&', $s);
        return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
    }

    public function stripblock($var, $s) {
        $s = str_replace('\\"', '"', $s);
        $s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
        preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
        $constadd = '';
        $constary[1] = array_unique($constary[1]);
        foreach($constary[1] as $const) {
            $constadd .= '$__'.$const.' = '.$const.';';
        }
        $s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
        $s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
        $s = str_replace('<?', "\nEOF;\n", $s);
        return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
    }
}