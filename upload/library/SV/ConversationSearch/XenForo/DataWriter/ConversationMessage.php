<?php

class SV_ConversationSearch_XenForo_DataWriter_ConversationMessage extends XFCP_SV_ConversationSearch_XenForo_DataWriter_ConversationMessage
{
    const OPTION_INDEX_FOR_SEARCH = 'indexForSearch';

    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();
        $defaultOptions[self::OPTION_INDEX_FOR_SEARCH] = true;
        return $defaultOptions;
    }

    protected function _postSave()
    {
        parent::_postSave();
        if ($this->getOption(self::OPTION_INDEX_FOR_SEARCH))
        {
            $this->_insertIntoSearchIndex();
        }
    }

    protected function _postDelete()
    {
        parent::_postDelete();
        $this->_deleteFromSearchIndex();
    }

    protected function _insertIntoSearchIndex()
    {
        $dataHandler = $this->_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $viewingUser = XenForo_Visitor::getInstance()->toArray();
        $conversationModel = $this->_getConversationModel();
        $conversation = $conversationModel->getConversationById($this->get('conversation_id'), $viewingUser['user_id']);

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $this->getMergedData(), $conversation);
    }

    protected function _deleteFromSearchIndex()
    {
        $dataHandler = $this->_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
    }

    public function _getSearchDataHandler()
    {
        return XenForo_Search_DataHandler_Abstract::create('SV_ConversationSearch_Search_DataHandler_ConversationMessage');
    }

    protected function _getConversationModel()
    {
        return $this->getModelFromCache('XenForo_Model_Conversation');
    }
}