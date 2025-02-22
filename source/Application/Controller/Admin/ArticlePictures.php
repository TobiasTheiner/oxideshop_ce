<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article picture manager.
 * Collects information about article's used pictures, there is posibility to
 * upload any other picture, etc.
 * Admin Menu: Manage Products -> Articles -> Pictures.
 */
class ArticlePictures extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Loads article information - pictures, passes data to template engine
     * engine, returns name of template file "article_pictures".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData["edit"] = $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->load($soxId);
            $oArticle = $this->updateArticle($oArticle);

            // variant handling
            if ($oArticle->oxarticles__oxparentid->value) {
                $oParentArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                $oParentArticle->load($oArticle->oxarticles__oxparentid->value);
                $this->_aViewData["parentarticle"] = $oParentArticle;
                $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
            }
        }

        $this->_aViewData["iPicCount"] = Registry::getConfig()->getConfigParam('iPicCount');

        if ($this->getViewConfig()->isAltImageServerConfigured()) {
            $config = Registry::getConfig();

            if ($config->getConfigParam('sAltImageUrl')) {
                $this->_aViewData["imageUrl"] = $config->getConfigParam('sAltImageUrl');
            } else {
                $this->_aViewData["imageUrl"] = $config->getConfigParam('sSSLAltImageUrl');
            }
        }

        return "article_pictures";
    }

    /**
     * Saves (uploads) pictures to server.
     *
     * @return mixed
     */
    public function save()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        parent::save();

        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        if ($oArticle->load($this->getEditObjectId())) {
            $oArticle->assign(Registry::getRequest()->getRequestEscapedParameter("editval"));
            Registry::getUtilsFile()->processFiles($oArticle);

            // Show that no new image added
            if (Registry::getUtilsFile()->getNewFilesCounter() == 0) {
                $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
                $oEx->setMessage('NO_PICTURES_CHANGES');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false);
            }

            $oArticle->save();
        }
    }

    /**
     * Deletes selected master picture and all other master pictures
     * where master picture index is higher than currently deleted index.
     * Also deletes custom icon and thumbnail.
     *
     * @return null
     */
    public function deletePicture()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        $sOxId = $this->getEditObjectId();
        $iIndex = Registry::getRequest()->getRequestEscapedParameter("masterPicIndex");

        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $oArticle->load($sOxId);

        if ($iIndex == "ICO") {
            // deleting main icon
            $this->deleteMainIcon($oArticle);
        } elseif ($iIndex == "TH") {
            // deleting thumbnail
            $this->deleteThumbnail($oArticle);
        } else {
            $iIndex = (int) $iIndex;
            if ($iIndex > 0) {
                // deleting master picture
                $this->resetMasterPicture($oArticle, $iIndex, true);
            }
        }

        $oArticle->save();
    }

    /**
     * Deletes selected master picture and all pictures generated
     * from master picture
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle       article object
     * @param int                                         $iIndex         master picture index
     * @param bool                                        $blDeleteMaster if TRUE - deletes and unsets master image file
     */
    protected function resetMasterPicture($oArticle, $iIndex, $blDeleteMaster = false)
    {
        if ($this->canResetMasterPicture($oArticle, $iIndex)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteArticleMasterPicture($oArticle, $iIndex, $blDeleteMaster);
            }

            if ($blDeleteMaster) {
                //reseting master picture field
                $oArticle->{"oxarticles__oxpic" . $iIndex} = new \OxidEsales\Eshop\Core\Field();
            }

            // cleaning oxzoom fields
            if (isset($oArticle->{"oxarticles__oxzoom" . $iIndex})) {
                $oArticle->{"oxarticles__oxzoom" . $iIndex} = new \OxidEsales\Eshop\Core\Field();
            }

            if ($iIndex == 1) {
                $this->cleanupCustomFields($oArticle);
            }
        }
    }

    /**
     * Deletes main icon file
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     */
    protected function deleteMainIcon($oArticle)
    {
        if ($this->canDeleteMainIcon($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteMainIcon($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxicon = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Deletes thumbnail file
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     */
    protected function deleteThumbnail($oArticle)
    {
        if ($this->canDeleteThumbnail($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteThumbnail($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxthumb = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Cleans up article custom fields oxicon and oxthumb. If there is custom
     * icon or thumb picture, leaves records untouched.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     */
    protected function cleanupCustomFields($oArticle)
    {
        $sIcon = $oArticle->oxarticles__oxicon->value;
        $sThumb = $oArticle->oxarticles__oxthumb->value;

        if ($sIcon == "nopic.jpg") {
            $oArticle->oxarticles__oxicon = new \OxidEsales\Eshop\Core\Field();
        }

        if ($sThumb == "nopic.jpg") {
            $oArticle->oxarticles__oxthumb = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Method is used for overloading to update article object.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function updateArticle($oArticle)
    {
        return $oArticle;
    }

    /**
     * Checks if possible to reset master picture.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     * @param int                                         $masterPictureIndex
     *
     * @return bool
     */
    protected function canResetMasterPicture($oArticle, $masterPictureIndex)
    {
        return (bool) $oArticle->{"oxarticles__oxpic" . $masterPictureIndex}->value;
    }

    /**
     * Checks if possible to delete main icon of article.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return bool
     */
    protected function canDeleteMainIcon($oArticle)
    {
        return (bool) $oArticle->oxarticles__oxicon->value;
    }

    /**
     * Checks if possible to delete thumbnail of article.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return bool
     */
    protected function canDeleteThumbnail($oArticle)
    {
        return (bool) $oArticle->oxarticles__oxthumb->value;
    }
}
