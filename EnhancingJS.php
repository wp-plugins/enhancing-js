<?php
/*
Plugin Name: Enhancing JavaScript
Plugin URI: http://firegoby.theta.ne.jp/wp/enhancingjs
Description: Add & Edit custom JavaScript throught WordPress Dashboard with visual editor.
Author: Takayuki Miyauchi (THETA NETWORKS Co,.Ltd)
Version: 0.4
Author URI: http://firegoby.theta.ne.jp/
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

require_once(dirname(__FILE__).'/addrewriterules.class.php');

new EnhancingJS();


class EnhancingJS{

    private $title      = '';
    private $name       = 'EnhancingJS';
    private $role       = 'edit_theme_options';
    private $basedir    = null;

    function __construct()
    {
        new AddRewriteRules(
            $this->name.'.js$',
            $this->name,
            array(&$this, 'get_js')
        );
        $this->basedir = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
        add_action('admin_menu', array(&$this, 'add_admin_page'));
        add_action('admin_head', array(&$this, 'admin_head'));
        add_action('wp_head', array(&$this, 'wp_head'));
        add_action('wp_print_scripts', array(&$this, 'wp_print_scripts'));
    }

    private function conditional_get($time = 0)
    {
        $last_modified = gmdate('D, d M Y H:i:s T', $time);
        $etag = md5($last_modified);
        header('Last-Modified: '.$last_modified);
        header('ETag: "'.$etag.'"');
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if (preg_match("/{$etag}/", $_SERVER['HTTP_IF_NONE_MATCH'])) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
    }

    public function wp_print_scripts()
    {
        if (!is_admin()) {
            $scripts = $this->get_enqueue();
            foreach ($scripts as $s) {
                wp_enqueue_script($s);
            }
        }
    }

    private function get_js_url()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            $url = get_bloginfo('url').'/'.$this->name.'.js';
        } else {
            $url = get_bloginfo('url').'/?'.$this->name.'=true';
        }
        return $url;
    }

    public function wp_head()
    {
        $css = "<script type=\"text/javascript\" src=\"%s\"></script>";
        echo "<!-- Enhancing JS Plugin -->\n";
        echo sprintf($css, $this->get_js_url())."\n";
    }

    public function get_js()
    {
        header('Content-type: text/javascript');
        $this->conditional_get(get_option('EnhancingJS.last_modified', 0));
        echo $this->get_js_src();
        exit;
    }

    private function get_js_src()
    {
        if($js = trim(get_option('EnhancingJS'))){
            $js = str_replace(array("\r\n", "\r"), "\n", $js);
            $css = stripslashes($js);
        } else {
            $css = "/* {$this->title} */\n";
        }
        return $css;
    }

    public function add_admin_page(){
        load_plugin_textdomain(
            $this->name, 
            PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/langs', 
            dirname(plugin_basename(__FILE__)).'/langs'
        );
        $this->title = __('Enhancing JS', $this->name);
        add_theme_page(
            $this->title,
            $this->title,
            $this->role,
            $this->name,
            array(&$this, 'admin_page')
        );
    }

    public function admin_head()
    {
        if (isset($_GET['page']) && $_GET['page'] == $this->name) {
            $script = '<script src="%s" type="%s" charset="%s"></script>';
            echo sprintf($script, $this->basedir.'/codemirror/codemirror.js', 'text/javascript', 'UTF-8')."\n";
            echo sprintf($script, $this->basedir.'/js/jseditor.js', 'text/javascript', 'UTF-8')."\n";
            $css = "<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />";
            echo sprintf($css, $this->basedir.'/css/style.css');
        }
    }

    public function admin_page(){
        global $wp_rewrite;

        echo '<div class="wrap">';
        echo '<form id="edit_css" action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        echo '<input type="hidden" id="action" name="action" value="save">';
        echo '<div id="icon-themes" class="icon32"><br /></div>';

        echo '<h2>'.$this->title.'</h2>';
        if ( isset($_POST['action']) && $_POST['action'] == 'save' ){
            $this->save_enqueue();
            update_option('EnhancingJS', $_POST['EnhancingJS']);
            update_option('EnhancingJS.last_modified', time());
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>".__("Saved.")."</strong></p></div>";
        }

        $url = $this->get_js_url();
        echo "<p><a href=\"{$url}\">{$url}</a></p>";
        echo '<div id="editor" class="stuffbox">';
        echo '<textarea id="EnhancingJS" name="EnhancingJS" js="width:90%;height:300px;">';
        echo $this->get_js_src();
        echo '</textarea>';
        echo '</div><!--end #editor-->';

        echo '<p>';
        echo '<p><input type="submit" class="button-primary" value="'.__('Save Changes').'" /></p>';
        echo '</p>';
        echo "<script type=\"text/javascript\">\n";
        echo "  var obj = document.getElementById('EnhancingJS');\n";
        echo "  var btn = [\n";
        echo "    ['".__('Undo', $this->name)."', 'undo'],\n";
        echo "    ['".__('Redo', $this->name)."', 'redo'],\n";
        echo "    ['".__('Search', $this->name)."', 'search'],\n";
        echo "    ['".__('Replace', $this->name)."', 'replace']\n";
        echo "  ];\n";
        echo "  new JSEditor('EnhancingJS', '".$this->basedir."', btn);";
        echo "</script>";
        $this->get_registered_scripts();
        echo '<p><input type="submit" class="button-primary" value="'.__('Save Changes').'" /></p>';
        echo '</form>';
        echo "</wrap>";
    }

    private function get_registered_scripts()
    {
        $wp_scripts = new WP_Scripts();
        wp_default_scripts($wp_scripts);

        echo "<h3 style=\"margin-top:2em;\">Enqueue Scripts</h3>";
        echo "<p>".__("Please check, if you want to load automatically on theme.", $this->name)."</p>";
        echo "<table class=\"widefat\" cellspacing=\"0\">";
        echo "<thead>";
        echo "<tr>";
        echo "<th scope=\"col\" class=\"manage_column check-column\"><input type=\"checkbox\"></th>";
        echo "<th scope=\"col\" class=\"manage_column\">".__('Handle', $this->name)."</th>";
        echo "<th scope=\"col\" class=\"manage_column\">".__("Version", $this->name)."</th>";
        echo "<th scope=\"col\" class=\"manage_column\">".__("Deps", $this->name)."</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        sort($wp_scripts->registered);
        $saved = $this->get_enqueue();
        foreach ($wp_scripts->registered as $r) {
            if (preg_match("/^\/wp-admin/", $r->src)) {
                continue;
            }
            echo "<tr>";
            if (in_array($r->handle, $saved)) {
                echo "<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"load[]\" value=\"{$r->handle}\" checked=\"checked\" /></th>";
            } else {
                echo "<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"load[]\" value=\"{$r->handle}\" /></th>";
            }
            echo "<td>{$r->handle}<br />{$r->src}</td>";
            echo "<td>{$r->ver}</td>";
            $deps = join(", ", $r->deps);
            echo "<td>{$deps}</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }

    private function save_enqueue()
    {
        if (isset($_POST['load']) && is_array($_POST['load']) && $_POST['load']) {
            update_option('EnhancingJS.load', $_POST['load']);
        } else {
            update_option('EnhancingJS.load', array());
        }
    }

    private function get_enqueue()
    {
        $load = get_option('EnhancingJS.load');
        if (is_array($load)) {
            return $load;
        } else {
            return array();
        }
    }
}

?>
