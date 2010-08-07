<?php
/**
 * Command Line Interface (CLI) Plugin
 *     Typeset transcripts of interactive sessions with mimumum effort.
 * Syntax:
 *     <cli prompt="$ " continue="> " comment="#">
 *     user@host:~/somedir $ ls \
 *     > # List directory
 *     file1 file2
 *     </cli>
 *   prompt --- [optional] prompt character used. '$ ' is default - note the space.
 *   comment --- [optional] comment character used. '#' is default - note no space.
 *   continue --- [optional] regex of shell continuation '/^> /' is the default.
 *   The defaults above match Bourne shell ${PS1} and ${PS2} prompts and comment
 *
 * Acknowledgements:
 *  Borrows heavily from the boxes plugin!
 *  Support for continuation added by Andy Webber
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Chris P. Jobling <C.P.Jobling@Swansea.ac.uk>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_cli extends DokuWiki_Syntax_Plugin {
    
    var $prompt_str = '$ ';
    var $prompt_cont = '/^> /'; // this is a regex
    var $prompt_continues = false;
    var $comment_str = '#';
    
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Chris P. Jobling',
            'email'  => 'C.P.Jobling@Swansea.ac.uk',
            'date'   => '200r87-02-07',
            'name'   => 'Command Line Interface (CLI) Plugin',
            'desc'   => 'Renders transcripts of command line interactions, e.g. for shell and dynamic language interpretor tutorials',
            'url'    => 'http://eehope.swan.ac.uk/dokuwiki/plugins:cli',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'protected';
    }
 
    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
//        return array();
//    }
 
    // override default accepts() method to allow nesting
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
    }
    
    /**
     * What about paragraphs? (optional)
     */
    function getPType(){
        return 'block';
    }
 
    /**
     * Where to sort in?
     */ 
    function getSort(){
        return 601;
    }
 
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<cli(?:"[^"]*"|\'[^\']*\'|[^\'">])*>(?=.*?</cli>)',$mode,'plugin_cli');
    }
 
    function postConnect() {
      $this->Lexer->addExitPattern('</cli>','plugin_cli');
    }
 
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
          case DOKU_LEXER_ENTER :
            $args = substr($match, 4, -1);
            return array($state, $args);
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
          case DOKU_LEXER_EXIT :
            return array($state, '');
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array();
    }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
              list($state, $match) = $data;
	      switch ($state) {
	      	case DOKU_LEXER_ENTER :
                    $args = $match;
                    $result = $this->_process_args($args);
                    $renderer->doc .= "<pre class='cli'>";
                    break;
		case DOKU_LEXER_UNMATCHED : 
                  $this->_render_conversation($match, $renderer);
                  break;
		case DOKU_LEXER_EXIT :
		  $renderer->doc .= "</pre>";
                  break;
              }
              return true;
        }
        return false;
    }
    
    function _process_args($args) {
        // process args to CLI tag: sets $comment_str and $prompt_str
        $a_match = false;
        if ($args) {
            if (preg_match('/prompt\s*=\s*"([^"]*)"|\'([^\']*)\'/', $args, $matches)) {
                $this->prompt_str = $matches[1];
                $a_match = true;
            }
            if (preg_match('/continue\s*=\s*"([^"]*)"|\'([^\']*)\'/', $args, $matches)) {
                $this->prompt_cont = $matches[1];
                $a_match = true;
            }
            if (preg_match('/comment\s*=\s*"([^"]*)"|\'([^\']*)\'/', $args, $matches)) {

                $this->comment_str = $matches[1];
                $a_match = true;
            }
        }
        return $a_match;
    }
    
    function _render_conversation($match, &$renderer) {
        $promppt_continues = false;
        $lines = preg_split('/\n\r|\n|\r/',$match);
        if ( trim($lines[0]) === "" ) unset( $lines[0] );
        if ( trim($lines[count($lines)]) === "" ) unset( $lines[count($lines)] );
        foreach ($lines as $line) {
            $index = strpos($line, $this->prompt_str);
            if ($index === false) {   
                if ($this->prompt_continues) {
                  if (preg_match($this->prompt_cont, $line, $promptc) === 0) $this->prompt_continues = false;
                }
                if ($this->prompt_continues) {
                    // format prompt
                    $renderer->doc .= "<span class='cli_prompt'>" . $renderer->_xmlEntities($promptc[0]) . "</span>";
                    // Split line into command + optional comment (only end-of-line comments supported)
                    $command =  preg_split($this->prompt_cont, $line);
                    $commands = explode($this->comment_str, $command[1]);
                    // Render command
                    $renderer->doc .= "<span class='cli_command'>" . $renderer->_xmlEntities($commands[0]) . "</span>";
                    // Render comment if there is one
                    if ($commands[1]) {
                        $renderer->doc .= "<span class='cli_comment'>" .
                            $renderer->_xmlEntities($this->comment_str . $commands[1]) . "</span>";
                  }
                  $renderer->doc .= "<br />";
                } else {
                  // render as output
                  $renderer->doc .= "<span class='cli_output'>" . $renderer->_xmlEntities($line) . "</span><br />";
                  $this->prompt_continues=false;
                }
            } else {
                $this->prompt_continues = true;
                // format prompt
                $prompt = substr($line, 0, $index) . $this->prompt_str;
                $renderer->doc .= "<span class='cli_prompt'>" . $renderer->_xmlEntities($prompt) . "</span>";
                // Split line into command + optional comment (only end-of-line comments supported)
                $commands = explode($this->comment_str, substr($line, $index + strlen($this->prompt_str)));
                // Render command
                $renderer->doc .= "<span class='cli_command'>" . $renderer->_xmlEntities($commands[0]) . "</span>";
                // Render comment if there is one
                if ($commands[1]) {
                    $renderer->doc .= "<span class='cli_comment'>" .
                        $renderer->_xmlEntities($this->comment_str . $commands[1]) . "</span>";
                }
                $renderer->doc .= "<br />";
            }
        }
    }
}
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
