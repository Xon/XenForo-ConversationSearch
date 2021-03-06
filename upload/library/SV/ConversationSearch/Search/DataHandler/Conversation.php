<?php

class SV_ConversationSearch_Search_DataHandler_Conversation extends XenForo_Search_DataHandler_Abstract
{
    var $enabled = false;

    public function __construct()
    {
        // use the proxy class existence as a cheap check for if this addon is enabled.
        $this->_getConversationModel();
        $this->enabled = class_exists('XFCP_SV_ConversationSearch_XenForo_Model_Conversation', false);
    }

    protected $_conversationModel = null;

    /**
     * Inserts into (or replaces a record) in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_insertIntoIndex()
     */
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        if (!($this->enabled)) return;
        //$threadModel = $this->_getThreadModel();

        //if ($threadModel->isRedirect($data) || !$threadModel->isVisible($data))
        //{
        //	return;
        //}

        $metadata = array();
        $metadata['conversation'] = $data['conversation_id'];
        if (!empty($data['prefix_id']))
        {
            $metadata['prefix'] = $data['prefix_id'];
        }

        if (!isset($data['all_recipients']))
        {
            $data['all_recipients'] = $this->_getConversationModel()->getConversationRecipientsForSearch($data['conversation_id']);
        }
        $metadata['recipients'] = array_keys($data['all_recipients']);

        $indexer->insertIntoIndex(
            'conversation', $data['conversation_id'],
            $data['title'], '',
            $data['start_date'], $data['user_id'], $data['conversation_id'], $metadata
        );
    }

    /**
     * Updates a record in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_updateIndex()
     */
    protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
    {
        if (!($this->enabled)) return;
        $indexer->updateIndex('conversation', $data['conversation_id'], $fieldUpdates);
    }

    /**
     * Deletes one or more records from the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
     */
    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        if (!($this->enabled)) return;
        $conversationIds = array();
        foreach ($dataList AS $data)
        {
            $conversationIds[] = is_array($data) ? $data['conversation_id'] : $data;
        }

        $indexer->deleteFromIndex('conversation', $conversationIds);
    }

    /**
     * Rebuilds the index for a batch.
     *
     * @see XenForo_Search_DataHandler_Abstract::rebuildIndex()
     */
    public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
    {
        if (!($this->enabled)) return false;
        $conversationIds = $this->_getConversationModel()->getConversationIdsInRange($lastId, $batchSize);
        if (!$conversationIds)
        {
            return false;
        }

        $this->quickIndex($indexer, $conversationIds);

        return max($conversationIds);
    }

    /**
     * Rebuilds the index for the specified content.

     * @see XenForo_Search_DataHandler_Abstract::quickIndex()
     */
    public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
    {
        if (!($this->enabled)) return false;
        $conversations = $this->_getConversationModel()->getConversationsByIds($contentIds);

        foreach ($conversations AS $conversation)
        {
            $this->insertIntoIndex($indexer, $conversation);
        }

        return true;
    }

    public function getInlineModConfiguration()
    {
        return array();
    }

    /**
     * Gets the type-specific data for a collection of results of this content type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getDataForResults()
     */
    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        if (!($this->enabled)) return array();
        return $this->_getConversationModel()->getConversationsForUserByIdsWithMessage($viewingUser['user_id'], array_unique($ids));
    }

    /**
     * Determines if this result is viewable.
     *
     * @see XenForo_Search_DataHandler_Abstract::canViewResult()
     */
    public function canViewResult(array $result, array $viewingUser)
    {
        if (!($this->enabled)) return false;
        return $this->_getConversationModel()->canViewConversation($result, $viewingUser);
    }

    /**
     * Prepares a result for display.
     *
     * @see XenForo_Search_DataHandler_Abstract::prepareResult()
     */
    public function prepareResult(array $result, array $viewingUser)
    {
        if (!($this->enabled)) return $result;
        return $this->_getConversationModel()->prepareConversation($result);
    }

    public function addInlineModOption(array &$result)
    {
        return array();
    }

    /**
     * Gets the date of the result (from the result's content).
     *
     * @see XenForo_Search_DataHandler_Abstract::getResultDate()
     */
    public function getResultDate(array $result)
    {
        return $result['start_date'];
    }

    /**
     * Renders a result to HTML.
     *
     * @see XenForo_Search_DataHandler_Abstract::renderResult()
     */
    public function renderResult(XenForo_View $view, array $result, array $search)
    {
        if (!($this->enabled)) return null;
        return $view->createTemplateObject('search_result_conversation', array(
            'conversation' => $result,
            'conversation_message' => $result,
            'search' => $search,
            'enableInlineMod' => $this->_inlineModEnabled
        ));
    }

    public function getSearchContentTypes()
    {
        return array('conversation');
    }

    protected function _getConversationModel()
    {
        if (!$this->_conversationModel)
        {
            $this->_conversationModel = XenForo_Model::create('XenForo_Model_Conversation');
        }
        return $this->_conversationModel;
    }
}