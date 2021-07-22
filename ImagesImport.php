<?php

namespace FTP\Import;

require_once (__DIR__ . "/Base.php");

class ImagesImport extends Base
{

    protected $connect = false;

   
    protected function connect()
    {
        if ($this->connect)
            return true;
        
        $this->connect = @\ftp_connect($this->arParams['ftpHost']);
        if (!$this->connect)
            return false;
        
        if (@\ftp_login($this->connect, $this->arParams['ftpLogin'], $this->arParams['ftpPasswd']))
        {
            \ftp_pasv($this->connect, true);
            return true;
        }
        
        return false;
    }
    
    protected function close()
    {
        if ($this->connect)
            \ftp_close($this->connect);
    }

    protected function deleteTempFiles()
    {

        if ($this->connect())
        {
            $arDelete = \ftp_nlist($this->connect, "****************");
            foreach($arDelete as $deleteFile)
            {
                unlink("/****************".$deleteFile);
            }
        }
    }


    protected function getFilesList()
    {
        $arResult = [];

        if ($this->connect())
        {

            $arFiles = \ftp_nlist($this->connect, $this->arParams['ftpPath']);
            
            if (!empty($arFiles))
            {
                foreach ($arFiles as $file)
                {

                    if ($file == "." || $file == "..")
                        continue;
                    
                    $ext = end(explode(".", $file));
                    if (in_array($ext, ['jpg', 'jpeg', 'png']))
                    {
                        $bufName = explode("-", str_replace("." . $ext, "", $file));
                        $article = str_replace('***********', '', $bufName[0]);


                        if (isset($bufName[1]) && !is_numeric($bufName[1]))
                        {
                            $article = str_replace("." . $ext, "", $file);
                        }

                        $arResult[$article][] = $file;                        
                    }
                }

            }

        }

        return $arResult;
    }

    protected function getFile($file)
    {
      
        $name =  str_replace('/******/', '', $file);
        if ($this->connect())
        {
            $handle = fopen($this->arParams['tmpDir'] . $name, 'w');

            if (ftp_fget($this->connect, $handle, $file, FTP_BINARY, 0))
            {
                return $this->arParams['tmpDir'] . $name;
            }
        }
       
        return false;
    }

    public function runListener()
    {

        if (!\CModule::IncludeModule('iblock') || !\CModule::IncludeModule('catalog'))
            return;
        
        $arFiles = $this->getFilesList();

     
        if (!empty($arFiles))
        {
            $counter = 0;
            foreach ($arFiles as $article => $arArticleFiles)
            {
                rsort($arArticleFiles);

				if(strpos($article, '_'))
				{

                    $moreArticle = substr($article, 0, -2);

                    $arSelect = Array("ID");
                    $arFilter = Array("IBLOCK_ID"=>array($this->arParams['iblockId'], 20), "=PROPERTY_ARTICLE"=>$moreArticle);
                    $countItems = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

                    while($ob = $countItems->GetNextElement())
                    {
                        $arFields = $ob->GetFields();
                        $ELEMENT_ID = $arFields['ID'];

                        foreach($arFiles as $key => $arProp)
                        {
                            if(is_int(strpos($arProp[0], '_')))
                            {

                                if(str_replace('**********', '', substr($arProp[0], 0, -6)) == $moreArticle)
                                {

                                        $PROPERTY_VALUE[] = [
                                            'VALUE' => \CFile::MakeFileArray(str_replace('***********', '', $arProp[0])),
                                            'DESCRIPTION' => ""
                                        ];
                                }
                            }
                        };

                        \CIBlockElement::SetPropertyValuesEx($ELEMENT_ID, false, array('MORE_PHOTOS' => $PROPERTY_VALUE));

                        unset($PROPERTY_VALUE);
                    }

                }

                $arSelect = Array("*");
                $arFilter = Array("IBLOCK_ID"=>array($this->arParams['iblockId'], 20), "PROPERTY_ARTICLE"=>$article);
                $countItems = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect)->SelectedRowsCount();

                if ($countItems == 1)
                {
                   $arItem = \CIBlockElement::GetList(Array(), Array('IBLOCK_ID' => array($this->arParams['iblockId'], 20), '=PROPERTY_ARTICLE' => $article), false, array('*') )->fetch();

                   if ($arItem)
                   {
                        $arMorePhotos = [];
                        $previewFile = !empty($arItem['PREVIEW_PICTURE']) ? \CFile::GetPath($_SERVER['DOCUMENT_ROOT'] . $arItem['PREVIEW_PICTURE']) : false;
                        $arFields = $arRemoveFiles = [];
                        $bFirstPhoto = true;
                        foreach ($arArticleFiles as $file)
                        {
                            $bufFile = $this->getFile($file);
                            if ($bFirstPhoto)
                            {
                                if (!$previewFile || md5_file($bufFile) != md5_file($previewFile))
                                {
                                    $arFields['PREVIEW_PICTURE'] = $arFields['DETAIL_PICTURE'] = \CFile::MakeFileArray($bufFile);

                                }
                            }
                            else
                            {
                                    $arMorePhotos[] = [
                                            'VALUE' => \CFile::MakeFileArray($bufFile),
                                            "DESCRIPTION" => ""
                                            ];
                            }

                            $arRemoveFiles[] = $bufFile;
                            $bFirstPhoto = false;
                        }



                        if (!empty($arFields))
                        {
                            $obElement = new \CIBlockElement;
                            if ($obElement->Update($arItem['ID'], $arFields))
                            {
                               $this->arResult['successUpdates']++;
                               echo '<br>Success update item #' . $arItem['ID'] . PHP_EOL;
                            }
                            else
                            {
                                        echo '<br Error update item #' . $arItem['ID'] . ' - ' . $obElement->LAST_ERROR . PHP_EOL;
                            }

                        }
                   }
                }
                
        $counter++;

            }
            
               
        }
        $this->deleteTempFiles();
        $this->close();
    }
    
    
}