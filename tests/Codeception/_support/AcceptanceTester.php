<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Codeception;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Home;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends AcceptanceActor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Open shop first page.
     * @return Home
     */
    public function openShop(): Home
    {
        $I = $this;
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);
        $I->waitForPageLoad();
        return $homePage;
    }

    /**
     * Login with existing user and open shop first page.
     * @return Home
     */
    public function loginShopWithExistingUser(): Home
    {
        $homePage = $this->openShop();
        $user = Fixtures::get('existingUser');
        return $homePage->loginUser($user['userLoginName'], $user['userPassword']);
    }
}
