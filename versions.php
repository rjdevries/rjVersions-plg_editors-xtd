<?php
/*------------------------------------------------------------------------
# plg_editors_xtd - rjVersions plugin
# ------------------------------------------------------------------------
# author    Ronald J. de Vries
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.rjdev.nl
# Technical Support:  Forum - http://www.rjdev.nl
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.html.parameter' ); // needed in front-end
jimport( 'joomla.user.helper' );

/**
 * Editor Versions button
  */
class plgButtonVersions extends JPlugin
{
    var $params;
    var $id;
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @param 	object $subject The object to observe
     * @param 	array  $config  An array that holds the plugin configuration
     * @since 1.5
     */
    function plgButtonVersions(& $subject, $config) {
	parent::__construct($subject, $config);
        $this->params = new JParameter( $config['params'] );
        $this->loadLanguage( );		
    }

/**
* Versions button
* @return array A two element array of ( imageName, textToInsert )
*/
    function onDisplay($name) {
	
        // Show botton only in the back-end article editor
        $app = JFactory::getApplication();
        if($app->isAdmin() != 1 || $app->scope != 'com_content') { return 0; } ;

         /*
         * Javascript to insert the content
         * View element calls jSelectVersion when an version is clicked
         * jSelectVersion creates the content tag, sends it to the editor,
         * and closes the select frame.
         */
        $js = "
            
            function ReplaceVersion(value, editor) {

                // TinyMCE
                if (window.parent.tinyMCE) {
                    tinyMCE.execInstanceCommand(editor, 'mceSetContent',false,value);
                    return 0;
                }

                // Codemirror
                if (Joomla.editors.instances[editor]) {
                    Joomla.editors.instances[editor].setCode(value);
                    return 0;
                }

                // None
                if (document.getElementByIdee(editor)) {
                    document.getElementById(editor).value = value;
                    return 0;
                }

                // CKEDITOR (not tested)
                if (CKEDITOR.instances[editor]) {
                    CKEDITOR.instances[editor].setData(value);
                    return 0;
                }

                // JCE
                if (WFEditor) {
                    WFEditor.setContent(editor,value);
                }

            }

            function jSelectVersion(value) {
                var tag = value;
                var editor = '".$name."';
                ReplaceVersion(tag, editor);
                SqueezeBox.close();
            }
        ";

        $doc = JFactory::getDocument();
        $doc->addScriptDeclaration($js);

        JHtml::_('behavior.modal');

// Create the link to the component
        if(isset($id) <= 0){
            $id = JRequest::getVar('id',0);
        }
        $id = (int)$id;
        $language_id = 0;
        $stub =	"index.php";
        $link = $stub . '?option=com_versions&language_id='.$language_id.'&tmpl=component&ename='.$name.'&id=' . $id;

// Get the popup-screen width and height from the plugin settings
        // Defaults in case params are not set.
        $popup_height = 500;
        $popup_width = 600;
        // Get params
        if(@$this->params){
            if(@$this->params->get('popup_width') > 0){ $popup_width = (int)@$this->params->get('popup_width'); }
            if(@$this->params->get('popup_height') > 0){ $popup_height = (int)@$this->params->get('popup_height'); }
        }

        
//show the number of versions available
        $db = JFactory::getDbo();
        $query	= $db->getQuery(true);
	$query->select('count(id)');
	$query->from('#__versions');
        $query->where('id = '.$id);
        $query->order('#__versions.vid');
        $db->setQuery($query);

        $count = (int)$db->loadResult();
        if($count > 0){
                $linktext =  JText::_('PLG_EDITORS-XTD_BUTTON') . " ($count)";
        }else{
                $linktext =  JText::_('PLG_EDITORS-XTD_BUTTON') . " (0)";
        }

// Create the button
        JHTML::_('behavior.modal');
        $button = new JObject();
        $button->set('modal', true);
        $button->set('link', $link);
        $button->set('text', $linktext);
        $button->set('name', 'pagebreak');
        $button->set('options', "{handler: 'iframe',size: {x: $popup_width, y: $popup_height}}");
        return $button;

    }
	
}
