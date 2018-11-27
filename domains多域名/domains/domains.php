<?php
/*

Plugin Name: 多域名主题
Plugin URI: http://www.2zzt.com
Description: 不同域名显示不同主题来展示，常被运用在同一个WP内，中英文网站，手机网站的实现！当然，首先，你得解析多个域名到这个wordpress!
Author: 疯狂的大叔
Version: 6.13
Author URI: http://www.2zzt.com
*/


//$useServerName = true; // If you have issues resolving the server name, try uncommenting this line by removing the first two slashes
if ($useServerName){
    $domainTheme = new domainTheme($_SERVER['SERVER_NAME']);
}else{
    $domainTheme = new domainTheme($_SERVER['HTTP_HOST']);
}
class domainTheme {   
    var $blogdescription;
    var $currentdomain;
    var $stylesheet;
    var $template;
    var $blogname;
    var $siteurl;
    var $options;
    var $home;
    var $uri;
            
    // Based on the current domain name, load the associated theme data
    function domainTheme($domain){
        
        // Get default settings
        $this->blogdescription = get_option("blogdescription");
        $this->stylesheet = get_option("stylesheet");
        $this->template = get_option("template");
        $this->blogname = get_option("blogname");
        $this->siteurl = get_option("siteurl"); // WordPress url
        $this->home = get_option("home"); // Blog address url
        $this->currentdomain = strtolower($domain);
        
        // Load domain option settings
        global $wp_version;
        if ($wp_version=="2.6"){
            
            // 2.6 fix, this version unserializes data for some odd reason.
            $this->options = get_option("domainTheme_options");
        }else{
            $this->options = unserialize(get_option("domainTheme_options"));
        }
        
        if (gettype($this->options)!="array"){
            $this->options = array();
        }
        
        // Locate the matching index for the current domain
        foreach($this->options as $dt){
            if ($this->currentdomain==$dt['url']){
                
                // Update the settings for the matching domain
                $this->blogdescription = $dt['blogdescription'];
                $this->blogname = $dt['blogname'];
                $this->stylesheet = $dt['theme'];
                $this->template = $dt['theme'];
                $url = $this->getLeftMost($this->siteurl,'//').'//'.$dt['url'];
                $this->siteurl = $this->delLeftMost($this->siteurl,'//');
                if (strpos($this->siteurl,'/')>0){
                    $url .= '/'.$this->delLeftMost($this->siteurl, '/');
                }
                $this->siteurl = $url;
                
                $url = $this->getLeftMost($this->home,'//').'//'.$dt['url'];
                $this->home = $this->delLeftMost($this->home,'//');
                if (strpos($this->home,'/')>0){
                    $url .= '/'.$this->delLeftMost($this->home, '/');
                }
                $this->home = $url;
            }   
        }

        
        // Apply filters and actions
        add_filter('pre_option_blogdescription', array(&$this, 'getBlogdescription'));
        add_filter('pre_option_stylesheet', array(&$this, 'getStylesheet'));
        add_filter('pre_option_template', array(&$this, 'getTemplate'));
        add_filter('pre_option_blogname', array(&$this, 'getBlogname'));
        add_filter('pre_option_siteurl', array(&$this, 'getSiteurl'));
        add_filter('pre_option_home', array(&$this, 'getHome'));
        add_action('admin_menu', array(&$this, 'displayAdminMenu'));
        
        // Specify uri for admin panels
        $this->uri = '?page=' . $this->getRightMost(__FILE__, 'plugins/');
    }
    
    // Common string functions
    function getRightMost($sSrc, $sSrch) {        
        for ($i = strlen($sSrc); $i >= 0; $i = $i - 1) {
            $f = strpos($sSrc, $sSrch, $i);
            if ($f !== FALSE) {
               return substr($sSrc,$f + strlen($sSrch), strlen($sSrc));
            }
        }
        return $sSrc;
    }
    function delLeftMost($sSource, $sSearch) {
      for ($i = 0; $i < strlen($sSource); $i = $i + 1) {
        $f = strpos($sSource, $sSearch, $i);
        if ($f !== FALSE) {
           return substr($sSource,$f + strlen($sSearch), strlen($sSource));
           break;
        }
      }
      return $sSource;
    }
    function getLeftMost($sSource, $sSearch) {
      for ($i = 0; $i < strlen($sSource); $i = $i + 1) {
        $f = strpos($sSource, $sSearch, $i);
        if ($f !== FALSE) {
           return substr($sSource,0, $f);
           break;
        }
      }
      return $sSource;
    }    function getThemeTitleByTemplate($template){
        
        // Return descriptive name for a given template name
        $themes = get_themes();
        foreach($themes as $theme){
            if ($template==$theme["Template"]){
                break;
            }
        }
        return $theme["Title"];
    }
    function displayAdminMenu(){
        add_options_page('多域名主题设置', '多域名多主题', 8, __FILE__, array(&$this, 'createAdminPanel'));
    }
    
    // Return modified data based on the current domain name
    function getBlogdescription(){
        return $this->blogdescription;
    }
    function getStylesheet(){
        return $this->stylesheet;
    }
    function getTemplate(){
        return $this->template;
    }
    function getBlogname(){
        return $this->blogname;
    }
    function getSiteurl(){
        return $this->siteurl;
    }
    function getHome(){
        return $this->home;
    }
    
    // Create the administration panel
    function createAdminPanel(){
        
        // Check if we need to add a domain
        if ($_GET['action']=="addDomain"){
            $domain['url']=strtolower($_POST['domain']);
            $domain['theme']=$_POST['theme'];
            $domain['blogname']=stripslashes($_POST['blogname']);
            $domain['blogdescription']=stripslashes($_POST['blogdescription']);
            array_push($this->options, $domain);

            update_option("domainTheme_options", serialize($this->options));
        }
        
        // Check if we need to edit a domain
        if ($_GET['action']=="editDomain"){
            $id = $_GET['id'];
            $this->options[$id]['url']=strtolower($_POST['domain']);
            $this->options[$id]['theme']=$_POST['theme'];
            $this->options[$id]['blogname']=stripslashes($_POST['blogname']);
            $this->options[$id]['blogdescription']=stripslashes($_POST['blogdescription']);
            update_option("domainTheme_options", serialize($this->options));
        }
        
        // Check if we need to delete one or more domains
        if ($_GET['action']=="del" && $_POST['chkDelete']){
            foreach(array_reverse($_POST['chkDelete']) as $id){
                array_splice($this->options,$id,1);
            }
            update_option("domainTheme_options", serialize($this->options));
        }
        
        // Check if we should display the edit panel
        if ($_GET['action']=="domainProps"){
            $id = $_GET['id'];
            echo '<div class="wrap">
                    <form name="editDomain" id="editDomain" action="'.$this->uri.'&action=editDomain&id='.$id.'" method="post">
                    <h2>' . __('编辑设置') . '</h2>
                    <br class="clear" />
                    <div class="tablenav">
                        <br class="clear" />
                    </div>
                    <br class="clear" />
                    <table class="form-table">
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="domain">域名</label></th>
                            <td><input name="domain" id="domain" type="text" value="'.$this->options[$id]['url'].'" size="40" /><br />
                            填写域名来进行设置</td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="theme">主题</label></th>
                            <td>
                                <select name="theme" id="theme" class="postform" >';
                                $themes = get_themes();
                                foreach($themes as $theme){
                                    if ($theme["Template"]==$this->options[$id]['theme']){
                                        echo '<option value="'.$theme["Template"].'" selected>'.$theme["Name"].'</option>';
                                    }else{
                                        echo '<option value="'.$theme["Template"].'">'.$theme["Name"].'</option>';
                                    }
                                }
            echo '              </select>
                                <br />
                                选择一款主题来作为显示
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="blogname">网站名</label></th>
                            <td><input name="blogname" id="blogname" type="text" value="'.htmlspecialchars ($this->options[$id]['blogname']).'" size="40" /><br />
                            填写主题所要显示的网站名称</td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="blogname">网站副标题</label></th>
                            <td><input name="blogdescription" id="blogdescription" type="text" value="'.htmlspecialchars ($this->options[$id]['blogdescription']).'" size="45" /><br />
                            填写主题所要显示的网站副标题</td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button" name="submit" value="保存设置" /></p>
                    </form>
                 </div>';
            return;
        }
        
        // Inject the javascript for delete check all option
        echo '<script language="Javascript">
                (function($){
                    $(function(){
                        $("#chkAll").click(function(){
                            c=this.checked;
                            $(".chkDelete").each(function(i){
                                this.checked=c;
                            })
                        });
                    })
                })(jQuery);
              </script>';
        
        // Create the list
        echo '<div class="wrap">
                <form name="domainList" id="domainList" action="'.$this->uri.'&action=del'.'" method="post">
                <h2>' . __('多域名多主题') . ' </h2>
                <div>更精美的WordPress主题，尽在【<a href="http://www.2zzt.com/">爱找主题</a>】</div>
                <br class="clear" />
                <div class="tablenav">
                    <div class="alignleft">
                        <input type="submit" value="删除" name="deleteit" class="button-secondary delete" />
                    </div>
                    <br class="clear" />
                </div>
                <br class="clear" />
                <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col" class="check-column"><input type="checkbox" id="chkAll" /></th>
                        <th scope="col">域名</th>
                        <th scope="col">主题名</th>
                        <th scope="col">网站标题</th>
                        <th scope="col">网站副标题</th>
                    </tr>
                </thead>
                <tbody id="the-list" class="list:domain">
                    <tr id="domain-default" class="alternate">             
                        <th scope="row" class="check-column"><input type="checkbox" class="chkDefault" disabled /></th>
                        <td><a href="options-general.php"/>'.$this->currentdomain.'</a></td>
                        <td>'.$this->getThemeTitleByTemplate($this->template).'</td>
                        <td>'.$this->blogname.'</td>
                        <td>'.$this->blogdescription.'</td>
                    </tr>';
        $i=0;
        foreach($this->options as $domain){
            echo'   <tr id="domain-'.$i.'" ';
            if (!fmod($i,2)){
                echo '>';
            }else{
                echo 'class="alternate">'; 
            }
            echo'       <th scope="row" class="check-column"><input type="checkbox" name="chkDelete[]" class="chkDelete" value="'.$i.'" /></th>
                        <td><a href="'.$this->uri.'&action=domainProps&id='.$i.'"/>'.$domain['url'].'</a></td>
                        <td>'.$this->getThemeTitleByTemplate($domain['theme']).'</td>
                        <td>'.$domain['blogname'].'</td>
                        <td>'.$domain['blogdescription'].'</td>
                    </tr>
                </tbody>
                ';            
            $i++;
        }
        
        // Create the add form
        echo '  </table>
                </form>
                <div class="tablenav">
                    <br class="clear" />
                </div>
                </div>
                <br class="clear" />
                <br class="clear" />
                <div class="wrap">
                    <h2>添加多域名</h2>
                    <form name="addDomain" id="addDomain" action="'.$this->uri.'&action=addDomain" method="post">
                        <table class="form-table">
                            <tr class="form-field">
                                <th scope="row" valign="top"><label for="domain">域名</label></th>
                                <td><input name="domain" id="domain" type="text" value="" size="40" /><br />
                                访问其他主题所必须要的域名，例如：cdn.2zzt.com，不可以和主域名相同！</td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row" valign="top"><label for="theme">主题</label></th>
                                <td>
                                    <select name="theme" id="theme" class="postform" >';
                                    $themes = get_themes();
                                    foreach($themes as $theme){
                                        echo '<option value="'.$theme["Template"].'">'.$theme["Name"].'</option>';
                                    }
         echo '                     </select>
                                    <br />
                                    选择一个主题，用该域名来显示！
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row" valign="top"><label for="blogname">网站标题</label></th>
                                <td><input name="blogname" id="blogname" type="text" value="" size="40" /><br />
                                如果要设置中英文的网站，那么就需要修改网站的标题为英文，其他情况根据实际需求填写吧！</td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row" valign="top"><label for="blogname">网站副标题</label></th>
                                <td><input name="blogdescription" id="blogdescription" type="text" value="" size="45" /><br />
                                根据实际需求填写吧！</td>
                            </tr>
                        </table>
                    <p class="submit"><input type="submit" class="button" name="submit" value="添加" /></p>
                    </form>
                </div>
        ';
    }
}       
?>
