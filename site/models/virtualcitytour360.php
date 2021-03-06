<?php
/**
 * @version     2.0
 * @package     com_virtualcitytour360
 * @copyright   Copyright (C) 2011 - 2012 URENIO Research Unit. All rights reserved.
 * @license     GNU Affero General Public License version 3 or later; see LICENSE.txt
 * @author      URENIO Research Unit
 */
 
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
jimport('joomla.application.component.helper');
jimport('joomla.application.categories');


JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_virtualcitytour360/tables');

/**
 * Model
 */
class Virtualcitytour360ModelVirtualcitytour360 extends JModelList
{
	//protected $_item;
	private $_categories = null;
	private $_parent = null;

	protected function populateState()
	{
		$app = JFactory::getApplication();
	
		//set filter status in state
		$value = $app->getUserStateFromRequest($this->context.'.filter_status', 'status', array());
		$this->setState('filter_status', $value);
		//set filter category in state
		$value = $app->getUserStateFromRequest($this->context.'.filter_category', 'cat', array());
		$this->setState('filter_category', $value);
	
	
		// List state information
		// $value = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$value = $app->getUserStateFromRequest($this->context.'.list.limit', 'limit', 0); //set 0 as default do not use admin configuration...
		$this->setState('list.limit', $value);
	
		$value = $app->getUserStateFromRequest($this->context.'.limitstart', 'limitstart', 0);
		//$value = JRequest::getUInt('limitstart', 0);
		$this->setState('list.start', $value);
	
		//$orderCol	= JRequest::getCmd('filter_order', 'a.ordering'); 		//set default to reported ?? actually is the same as ordering ...
		$orderCol = $app->getUserStateFromRequest($this->context.'.filter_order', 'filter_order', 'a.ordering');
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'a.ordering';
		}
		$this->setState('list.ordering', $orderCol);
	
		//$listOrder	=  JRequest::getCmd('filter_order_Dir', 'DESC');			//set default DESC
		$listOrder = $app->getUserStateFromRequest($this->context.'.filter_order_Dir', 'filter_order_Dir', 'DESC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'DESC';
		}
		$this->setState('list.direction', $listOrder);
	
		$params = $app->getParams();
		$this->setState('params', $params);
		//TODO: If sometimes need multiple layouts I could use the layout state...
		//$this->setState('layout', JRequest::getCmd('layout'));
	}
	
	function getCategories($recursive = false)
	{
        $_categories = JCategories::getInstance('Virtualcitytour360');
        $this->_parent = $_categories->get();
        if(is_object($this->_parent))
        {
            $this->_items = $this->_parent->getChildren($recursive);
        }
        else
        {
            $this->_items = false;
        }
        return $this->loadCats($this->_items);
	}
		
	protected function loadCats($cats = array())
    {
        if(is_array($cats))
        {
            $i = 0;
            $return = array();
            foreach($cats as $JCatNode)
            {
                $return[$i]->title = $JCatNode->title;
                $return[$i]->parentid = $JCatNode->parent_id;
                $return[$i]->path = $JCatNode->get('path');
                $return[$i]->id = $JCatNode->id;
				$params = new JRegistry();
				$params->loadJSON($JCatNode->params);
				$return[$i]->image = $params->get('image');

				if($JCatNode->hasChildren())
                    $return[$i]->children = $this->loadCats($JCatNode->getChildren());
                else
                    $return[$i]->children = false;

                $i++;
            }
            return $return;
        }
        return false;
    }
	
	function getItems()
	{
		// Invoke the parent getItems method to get the main list
		$items = &parent::getItems();

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++) {
			$item = &$items[$i];
			if (!isset($this->_params)) {
				$params = new JRegistry();
				$params->loadJSON($item->params);
				$item->params = $params;
			}
		}
		return $items;	
	}

	protected function getListQuery()
	{
		$user	= JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());


		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$query->select(
			$this->getState(
				'list.select',
				'a.*, #__categories.title as category, catid, #__categories.path, #__categories.parent_id'
			)
		);
		$query->from('`#__virtualcitytour360` AS a');
		$query->leftJoin('#__categories on catid=#__categories.id');		
		$query->where('a.state = 1');
		
		return $query;
	}	
	
}