<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;

/**
 * APlayer for Typecho | 在 Typecho 中使用 APlayer 播放在线音乐吧～
 *
 * @package APlayer for Typecho | Meting
 * @author METO
 * @version 2.0.4
 * @dependence 14.10.10-*
 * @link https://github.com/MoePlayer/APlayer-Typecho
 *
 */

define('METING_VERSION','2.0.4');

class Meting_Plugin extends Typecho_Widget implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Meting_Plugin::install();
        Helper::addAction('metingapi', 'Meting_Action');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx=array('Meting_Plugin','playerReplace');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx=array('Meting_Plugin','playerReplace');
        Typecho_Plugin::factory('Widget_Archive')->header=array('Meting_Plugin','header');
        Typecho_Plugin::factory('Widget_Archive')->footer=array('Meting_Plugin','footer');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('Meting_Plugin', 'addButton');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('Meting_Plugin', 'addButton');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Meting_Plugin::uninstall();
        Helper::removeAction("metingapi");
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'theme', null, '#ad7a86',
            _t('播放器颜色'),
            _t('播放器默认的主题颜色，支持如 #372e21、#75c、red，该设定会被[Meting]标签中的theme属性覆盖，默认为 #ad7a86'));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'height', null, '340px',
            _t('播放器列表最大高度'),
            _t(''));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoplay', array('true'=>_t('是'),'false'=>_t('否')),'false',
            _t('全局自动播放'),
            _t(''));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Radio(
            'mode', array('circulation'=>_t('循环'),'single'=>_t('单曲'),'order'=>_t('列表'),'random'=>_t('随机')),'circulation',
            _t('全局播放模式'),
            _t(''));
        $form->addInput($t);
        $t= new Typecho_Widget_Helper_Form_Element_Radio(
            'preload', array('auto'=>_t('自动'),'none'=>_t('不加载'),'metadata'=>_t('加载元数据')), 'auto',
            _t('预加载属性'),
            _t(''));
        $form->addInput($t);
        $t= new Typecho_Widget_Helper_Form_Element_Radio(
            'bitrate', array('128'=>_t('流畅品质'),'192'=>_t('清晰品质'),'320'=>_t('高品质')), '192',
            _t('默认音质'),
            _t(''));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'api', null, Typecho_Common::url('action/metingapi',Helper::options()->index)."?server=:server&type=:type&id=:id&r=:r",
            _t('云解析地址'),
            _t('示例：https://api.i-meto.com/meting/api?server=:server&type=:type&id=:id&r=:r'));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'salt', null, md5(time()."Meting"),
            _t('接口保护'),
            _t('加盐保护 API 接口不被滥用，自动生成无需设置。'));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Textarea(
            'cookie', null, '',
            _t('网易云音乐 Cookie (高级)'),
            _t('如果您是网易云音乐的会员，可以将您的 cookie 填入此处来获取云盘等付费资源，听歌将不会计入下载次数。<br><b>如果不知道这是什么意思，忽略即可。</b>'));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Radio(
            'clean', array(_t('关闭'), _t('清除所有缓存')), 0,
            _t('保存时清除所有缓存'));
        $form->addInput($t);
    }

    /**
     * 手动保存配置句柄
     * @param $config array 插件配置
     * @param $is_init bool 是否初始化
     */
    public static function configHandle($config, $is_init)
    {
        if($is_init!=true){
            if($config['api']==""){
                $config['api']=Typecho_Common::url('action/metingapi',Helper::options()->index)."?server=:server&type=:type&id=:id&r=:r";
            }
            if($config['clean']==1){
                self::clean();
                $config['clean']=0;
            }
        }

        Helper::configPlugin('Meting', $config);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function header(){
        $api=Typecho_Widget::widget('Widget_Options')->plugin('Meting')->api;
        $dir=Helper::options()->pluginUrl.'/Meting/assets';
        $ver=METING_VERSION;
        echo "<script type=\"text/javascript\" src=\"{$dir}/APlayer.min.js?v={$ver}\"></script>\n";
        echo "<script>var meting_api=\"{$api}\";</script>";
    }

    public static function footer(){
        $dir=Helper::options()->pluginUrl.'/Meting/assets';
        $ver=METING_VERSION;
        echo "<script type=\"text/javascript\" src=\"{$dir}/Meting.min.js?v={$ver}\"></script>\n";
    }

    public static function playerReplace($data,$widget,$last){
        $text=empty($last)?$data:$last;
        if($widget instanceof Widget_Archive){
            $data=$text;
            $pattern=self::get_shortcode_regex(array('Meting'));
            $text=preg_replace_callback("/$pattern/",array('Meting_Plugin','parseCallback'),$data);
        }
        return $text;
    }

    public static function parseCallback($matches){
        $setting=self::shortcode_parse_atts(htmlspecialchars_decode($matches[3]));
        $matches[5]=htmlspecialchars_decode($matches[5]);
        $pattern=self::get_shortcode_regex(array('Music'));
        preg_match_all("/$pattern/",$matches[5],$all);
        if(sizeof($all[3]))return Meting_Plugin::parseMusic($all[3],$setting);
    }

    public static function parseMusic($matches,$setting){
        $data=array();
        $str="";
        foreach($matches as $vo){
            $t=self::shortcode_parse_atts(htmlspecialchars_decode($vo));
            $player=array(
                'theme'         => Typecho_Widget::widget('Widget_Options')->plugin('Meting')->theme?:'red',
                'preload'       => Typecho_Widget::widget('Widget_Options')->plugin('Meting')->preload?:'auto',
                'autoplay'      => Typecho_Widget::widget('Widget_Options')->plugin('Meting')->autoplay?:'false',
                'listmaxheight' => Typecho_Widget::widget('Widget_Options')->plugin('Meting')->height?:'340px',
                'mode'          => Typecho_Widget::widget('Widget_Options')->plugin('Meting')->mode?:'circulation',
            );
            if(isset($t['server'])){
                if(!in_array($t['server'],array('netease','tencent','xiami','baidu','kugou')))continue;
                if(!in_array($t['type'],array('search','album','playlist','artist','song')))continue;
                $data=$t;

                $str.="<div class=\"aplayer\" data-id=\"{$data['id']}\" data-server=\"{$data['server']}\" data-type=\"{$data['type']}\"";
                if(is_array($setting))foreach($setting as $key=>$vo)$player[$key]=$vo;
                foreach($player as $key=>$vo)$str.=" data-{$key}=\"{$vo}\"";
                $str.="></div>\n";
            }
            else{
                $data=$t;

                $str.="<div class=\"aplayer\" data-title=\"{$data['title']}\" data-author=\"{$data['author']}\" data-url=\"{$data['url']}\" data-pic=\"{$data['pic']}\" data-lrc=\"{$data['lrc']}\"";
                if(is_array($setting))foreach($setting as $key=>$vo)$player[$key]=$vo;
                foreach($player as $key=>$vo)$str.=" data-{$key}=\"{$vo}\"";
                $str.="></div>\n";
            }
        }
        return $str;
    }

    public static function addButton(){
        $url=Typecho_Common::url('action/metingapi',Helper::options()->index).'?do=parse';
        $dir=Helper::options()->pluginUrl.'/Meting/assets/editer.js?v='.METING_VERSION;
        echo "<script type=\"text/javascript\">var murl='{$url}';</script>
              <script type=\"text/javascript\" src=\"{$dir}\"></script>";
    }

    # https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L508
    private static function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
            }
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    # https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L254
    private static function get_shortcode_regex( $tagnames = null ) {
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
        return '\[(\[?)('.$tagregexp.')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';
    }

    public static function install(){
        $db=Typecho_Db::get();
        $dbname=$db->getPrefix().'metingv1';
        try{
            $db->query("CREATE TABLE IF NOT EXISTS {$dbname} (
                        id VARCHAR(32) PRIMARY KEY     NOT NULL UNIQUE,
                        value                  TEXT    NOT NULL,
                        last                   int     NOT NULL
                    )");
        }catch(Typecho_Db_Exception $e){
            $code=$e->getCode();
            throw new Typecho_Plugin_Exception('插件启用失败。错误号：'.$code);
        }
    }

    private function clean(){
        $db=Typecho_Db::get();
        $dbname=$db->getPrefix().'metingv1';
        $delete=$db->delete($dbname);
        $db->query($delete);
    }

    public static function uninstall(){
        $db=Typecho_Db::get();
        $dbname=$db->getPrefix().'metingv1';
        try{
            $db->query("DROP TABLE IF EXISTS {$dbname}");
        }catch(Typecho_Db_Exception $e){
            $code=$e->getCode();
            throw new Typecho_Plugin_Exception('插件禁用失败。错误号：'.$code);
        }
    }
}
