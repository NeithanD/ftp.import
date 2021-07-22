<?php

namespace FTP\Import;

use Bitrix\Main\Application;

class Base
{
    const EXCHANGE_GROUP_CODE = "1c_exchnage";

    protected $arParams = [];
    protected $arResult = [];
    protected $arMessages = [];

    protected $instance = false;
    protected $request = false;
    
    public function __construct($arParams)
    {
        if (!$this->instance)
            $this->instance = Application::getInstance();
        
        $context = $this->instance->getContext();
        
        if (!$this->request)
            $this->request = $context->getRequest();
        
        $this->arParams = $arParams;
    }
    

    protected function setError($message, $object = false)
    {
        $this->arMessages[] = [
            'type' => 'error',
            'message' => $message,
            'object' => $object
        ];
    }
    

    protected function setWarning($message, $object = false)
    {
        $this->arMessages[] = [
            'type' => 'waring',
            'message' => $message,
            'object' => $object
        ];
    }
    
    protected function setResult($message, $object = false)
    {
        if (!$object)
            $this->arResult[] = $message;
        else
            $this->arResult[] = [
                'message' => $message,
                'object' => $object
            ];
    }


    protected function checkAuth()
    {
        
        if (!$GLOBALS['USER']->IsAuthorized())
        {
            $this->setError("The username or password is incorrect", "user");
            
            return false;
        }
        
        $arGroup = \CGroup::GetList(($by="id"), ($order="desc"), ['STRING_ID' => self::EXCHANGE_GROUP_CODE])->fetch();
        
        if (!in_array($arGroup['ID'], $GLOBALS['USER']->GetUserGroupArray()))
        {
            $this->setError("User not in group \"".self::EXCHANGE_GROUP_CODE."\"", "user");
            return false;
        }
        
        return true;
    }
    
    protected function postRequest()
    {
        if(function_exists("file_get_contents"))
            $postData = file_get_contents('php://input');
        elseif(isset($GLOBALS["HTTP_RAW_POST_DATA"]))
            $postData = &$GLOBALS["HTTP_RAW_POST_DATA"];
        
        if (!empty($postData))
        {
            return json_decode($postData, true);
        }
        return false;
    }
    

    protected function setResponse()
    {
            return (!empty($this->arMessages)) ? [
                'status' => 'error',
                'errors' => $this->arMessages
            ] : [
                'status' => 'success',
                'result' => $this->arResult
            ];
    }
}

