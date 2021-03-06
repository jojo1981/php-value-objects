<?php

namespace Test\SolidPhp\ValueObjects\Type;

use BadMethodCallException;
use SolidPhp\ValueObjects\Type\ClassType;
use PHPUnit\Framework\TestCase;
use SolidPhp\ValueObjects\Type\Type;
use Test\SolidPhp\ValueObjects\Type\TestObjects\ExistingClass;
use Test\SolidPhp\ValueObjects\Type\TestObjects\ExistingInterface;
use Test\SolidPhp\ValueObjects\Type\TestObjects\ExistingTrait;

class ClassTypeTest extends TestCase
{
    /**
     * @dataProvider getCasesForFromInstance
     * @param object $instance
     * @param Type $expectedType
     */
    public function testFromInstance($instance, Type $expectedType): void
    {
        $this->assertSame($expectedType, ClassType::fromInstance($instance));
    }

    public function getCasesForFromInstance(): array
    {
        return [
            'plain object' => [new ExistingClass(), Type::of(ExistingClass::class)],
            'anonymous object' => [$this->getAnonymousClassInstance(), Type::of(\get_class($this->getAnonymousClassInstance()))]
        ];
    }

    /**
     * @dataProvider getCasesForFromFullyQualifiedClassName
     * @param string $fullyQualifiedClassName
     * @param bool $expectedResult
     */
    public function testFromFullyQualifiedClassName(string $fullyQualifiedClassName, bool $expectedResult): void
    {
        if ($expectedResult) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(\InvalidArgumentException::class);
        }

        ClassType::fromFullyQualifiedClassName($fullyQualifiedClassName);
    }

    public function getCasesForFromFullyQualifiedClassName(): array
    {
        return [
            'existing class'     => [ExistingClass::class, true],
            'non-existing class' => ['NonExistingClass', false],
            'existing interface' => [ExistingInterface::class, false],
            'existing trait'     => [ExistingTrait::class, false]
        ];
    }

    public function testCallStaticMethod(): void
    {
        $class = ClassType::fromFullyQualifiedClassName(ClassWithStaticMethod::class);
        $this->assertEquals('foo', $class->callStaticMethod('staticMethod', 'foo'));
        $this->assertEquals('foo', $class->staticMethod('foo'));

        $this->expectException(BadMethodCallException::class);
        $class->callStaticMethod('nonExistingStaticMethod');

        $this->expectException(BadMethodCallException::class);
        $class->nonExistingStaticMethod();

        $anonymousClass = ClassType::of($this->getAnonymousClassWithStaticMethodInstance());
        $this->assertEquals('foo', $anonymousClass->callStaticMethod('staticMethod', 'foo'));
        $this->assertEquals('foo', $anonymousClass->staticMethod('foo'));

        $this->expectException(BadMethodCallException::class);
        $anonymousClass->callStaticMethod('nonExistingStaticMethod');

        $this->expectException(BadMethodCallException::class);
        $anonymousClass->nonExistingStaticMethod();
    }

    public function testGetReflectionClass(): void
    {
        $reflectionClass = ClassType::fromFullyQualifiedClassName(ExistingClass::class)->getReflectionClass();
        $this->assertInstanceOf(\ReflectionClass::class, $reflectionClass);
        $this->assertEquals(ExistingClass::class, $reflectionClass->getName());
    }

    public function testFromCaller(): void
    {
        $this->assertEquals(Type::of(ClassThatCallsFromCaller::class), ClassThatCallsFromCaller::getType());
    }

    private function getAnonymousClassInstance()
    {
        return new class() {};
    }

    private function getAnonymousClassWithStaticMethodInstance()
    {
        return new class() {
            public static function staticMethod($argument)
            {
                return $argument;
            }
        };
    }
}

class ClassWithStaticMethod
{
    public static function staticMethod($argument)
    {
        return $argument;
    }
}

class ClassThatCallsFromCaller
{
    public static function getType(): ClassType
    {
        return self::callFromCaller();
    }

    public static function callFromCaller(): ClassType
    {
        return ClassType::fromCaller();
    }
}
