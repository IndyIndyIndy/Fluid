<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Unit\Core\Parser\Interceptor;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Parser\Interceptor\Escape;
use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\EscapingNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Tests\AccessibleObjectInterface;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;

class EscapeTest extends UnitTestCase
{
    /**
     * @var Escape&MockObject&AccessibleObjectInterface
     */
    private $escapeInterceptor;

    /**
     * @var AbstractViewHelper|MockObject
     */
    private $mockViewHelper;

    /**
     * @var ViewHelperNode|MockObject
     */
    private $mockNode;

    /**
     * @var ParsingState|MockObject
     */
    private $mockParsingState;

    public function setUp(): void
    {
        $this->escapeInterceptor = $this->getAccessibleMock(Escape::class, []);
        $this->mockViewHelper = $this->getMockBuilder(AbstractViewHelper::class)->disableOriginalConstructor()->getMock();
        $this->mockNode = $this->getMockBuilder(ViewHelperNode::class)->disableOriginalConstructor()->getMock();
        $this->mockParsingState = $this->getMockBuilder(ParsingState::class)
            ->onlyMethods([])->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function processDoesNotDisableEscapingInterceptorByDefault(): void
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects(self::once())->method('isChildrenEscapingEnabled')->willReturn(true);
        $this->mockNode->expects(self::once())->method('getUninitializedViewHelper')->willReturn($this->mockViewHelper);

        self::assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        self::assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processDisablesEscapingInterceptorIfViewHelperDisablesIt(): void
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects(self::once())->method('isChildrenEscapingEnabled')->willReturn(false);
        $this->mockNode->expects(self::once())->method('getUninitializedViewHelper')->willReturn($this->mockViewHelper);

        self::assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        self::assertFalse($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processReenablesEscapingInterceptorOnClosingViewHelperTagIfItWasDisabledBefore(): void
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER;
        $this->mockViewHelper->expects(self::any())->method('isOutputEscapingEnabled')->willReturn(false);
        $this->mockNode->expects(self::any())->method('getUninitializedViewHelper')->willReturn($this->mockViewHelper);

        $this->escapeInterceptor->_set('childrenEscapingEnabled', false);
        $this->escapeInterceptor->_set('viewHelperNodesWhichDisableTheInterceptor', [$this->mockNode]);

        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        self::assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processWrapsCurrentViewHelperInEscapeNode(): void
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OBJECTACCESSOR;
        $mockNode = $this->getMock(ObjectAccessorNode::class, [], [], false, false);
        $actualResult = $this->escapeInterceptor->process($mockNode, $interceptorPosition, $this->mockParsingState);
        self::assertInstanceOf(EscapingNode::class, $actualResult);
    }
}
