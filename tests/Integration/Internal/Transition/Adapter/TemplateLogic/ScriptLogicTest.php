<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\ScriptLogic;
use PHPUnit\Framework\TestCase;

class ScriptLogicTest extends TestCase
{

    /** @var Config */
    private $config;

    /** @var int */
    private $oldIDebug;

    /** @var ScriptLogic */
    private $scriptLogic;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setup(): void
    {
        parent::setUp();
        $this->config = Registry::getConfig();
        $this->oldIDebug = $this->config->getConfigParam("iDebug");
        $this->config->setConfigParam("iDebug", -1);

        $this->scriptLogic = new ScriptLogic();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        $this->config->setConfigParam("iDebug", $this->oldIDebug);
        parent::tearDown();
    }

    public function testIncludeFileNotExists(): void
    {
        $this->config->setConfigParam("iDebug", 0);

        $this->scriptLogic->include('somescript.js');

        $this->assertNull($this->config->getGlobalParameter('includes'));
    }

    public function testIncludeFileExists(): void
    {
        $includes = $this->config->getGlobalParameter('includes');

        $this->scriptLogic->include('http://someurl/src/js/libs/jquery.min.js', 3);
        $this->assertArrayHasKey(3, $this->config->getGlobalParameter('includes'));
        $this->assertTrue(in_array('http://someurl/src/js/libs/jquery.min.js', $this->config->getGlobalParameter('includes')[3]));

        $this->config->setGlobalParameter('includes', $includes);
    }

    public function testAddNotDynamic(): void
    {
        $scripts = $this->config->getGlobalParameter('scripts');

        $this->scriptLogic->add('oxidadd');
        $this->assertTrue(in_array('oxidadd', $this->config->getGlobalParameter('scripts')));

        $this->config->setGlobalParameter('scripts', $scripts);
    }

    public function testAddDynamic(): void
    {
        $scripts = $this->config->getGlobalParameter('scripts_dynamic');

        $this->scriptLogic->add('oxidadddynamic', true);
        $this->assertTrue(in_array('oxidadddynamic', $this->config->getGlobalParameter('scripts_dynamic')));

        $this->config->setGlobalParameter('scripts_dynamic', $scripts);
    }

    /**
     * @param string $script
     * @param string $output
     *
     * @dataProvider addWidgetProvider
     */
    public function testRenderAddWidget(string $script, string $output): void
    {
        $scripts = $this->config->getGlobalParameter('scripts');

        $output = "<script type='text/javascript'>window.addEventListener('load', function() { WidgetsHandler.registerFunction('$output', 'somewidget'); }, false )</script>";

        $this->scriptLogic->add($script);
        $this->assertEquals($output, $this->scriptLogic->render('somewidget', true));

        $this->config->setGlobalParameter('scripts', $scripts);
    }

    /**
     * @return array
     */
    public function addWidgetProvider(): array
    {
        return [
            ['oxidadd', 'oxidadd'],
            ['"oxidadd"', '"oxidadd"'],
            ["'oxidadd'", "\\'oxidadd\\'"],
            ["oxid\r\nadd", 'oxid\nadd'],
            ["oxid\nadd", 'oxid\nadd'],
        ];
    }

    public function testRenderIncludeWidget(): void
    {
        $includes = $this->config->getGlobalParameter('includes');

        $this->scriptLogic->include('http://someurl/src/js/libs/jquery.min.js');

        $output = <<<HTML
<script type='text/javascript'>
    window.addEventListener('load', function() {
        WidgetsHandler.registerFile('http://someurl/src/js/libs/jquery.min.js', 'somewidget');
    }, false)
</script>
HTML;

        $this->assertEquals($output, $this->scriptLogic->render('somewidget', true));

        $this->config->setGlobalParameter('includes', $includes);
    }
}
