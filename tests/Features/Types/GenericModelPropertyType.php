<?php

declare(strict_types=1);

namespace Tests\Features\Types;

use App\User;

class GenericModelPropertyType
{
    public function testBuilderCreate(): void
    {
        User::create([
            'password' => 'hello',
            'email' => 'some-email@laravel.com',
        ]);
    }

    public function testGenericModelPropertySimple(): void
    {
        $this->showFirstUserProperty('id');
        $this->showFirstUserProperty('password');
        $this->showFirstUserProperty('email');
    }

    public function testGenericModelPropertyUnion(): void
    {
        $this->doSomethingWithTemplateUnion('id');
        $this->doSomethingWithUnion('everything-should-be-allowed');
    }

    public function testTemplateUnionProperty(): void
    {
        $this->doSomethingWithTemplateUnion('id');
        $this->doSomethingWithTemplateUnion('password');
        $this->doSomethingWithTemplateUnion('active');
    }

    public function testTemplateIntersectionProperty(): void
    {
        $this->doSomethingWithTemplateIntersection('id');
        $this->doSomethingWithTemplateUnion('password');
    }

    public function testTemplateSelf(): void
    {
        (new User)->printPropertySelf('id');
    }

    public function testTemplateStatic(): void
    {
        (new User)->printPropertyStatic('id');
    }

    public function testTemplateMixed(): void
    {
        $this->doSomethingWithTemplateMixed('anything should be allowed');
    }

    /**
     * @param model-property<mixed> $arg
     * @return void
     */
    private function doSomethingWithTemplateMixed($arg)
    {
    }

    /**
     * @param model-property<User|\App\Account> $arg
     * @return void
     */
    private function doSomethingWithTemplateUnion($arg)
    {
    }

    /**
     * @param model-property<User&\App\Account> $arg
     * @return void
     */
    private function doSomethingWithTemplateIntersection($arg)
    {
    }

    /**
     * @param string|model-property<User> $arg
     * @return void
     */
    public function doSomethingWithUnion($arg): void
    {

    }

    /**
     * @phpstan-param model-property<\App\User> $property
     * @param string $property
     * @return void
     */
    private function showFirstUserProperty(string $property): void
    {
        echo User::query()->firstOrFail()[$property];
    }
}
