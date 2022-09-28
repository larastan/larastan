<?php

declare(strict_types=1);

namespace Rules\DecoupledCode;

use NunoMaduro\Larastan\Rules\DecoupledCode\NoHelperDependingOnBootedApplicationUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoHelperDependingOnBootedApplicationUsageRule> */
class NoHelperDependingOnBootedApplicationUsageRuleTest extends RuleTestCase
{
    public function testNoFalsePositives(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/DecoupledCode/DecoupledFunctionCall.php',
            ],
            []
        );
    }

    public function testHelpersDependingOnBootedApplication(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/DecoupledCode/HelperDependingOnBootedApplication.php',
            ],
            [
                ['Usage of the global function "app_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "path".', 11],
                ['Usage of the global function "base_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "basePath".', 12],
                ['Usage of the global function "config_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "configPath".', 13],
                ['Usage of the global function "database_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "databasePath".', 14],
                ['Usage of the global function "resource_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "resourcePath".', 15],
                ['Usage of the global function "public_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "publicPath".', 16],
                ['Usage of the global function "lang_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "langPath".', 17],
                ['Usage of the global function "storage_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "storagePath".', 18],
                ['Usage of the global function "resolve" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "make".', 19],
                ['Usage of the global function "app" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "make".', 20],
                ['Usage of the global function "abort_if" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement.', 21],
                ['Usage of the global function "abort_unless" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement.', 22],
                ['Usage of the global function "__" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "translate".', 23],
                ['Usage of the global function "trans" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "translate".', 24],
                ['Usage of the global function "trans_choice" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "choice".', 25],
                ['Usage of the global function "action" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "action".', 26],
                ['Usage of the global function "asset" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset".', 27],
                ['Usage of the global function "secure_asset" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset" with the second parameter set to "true".', 28],
                ['Usage of the global function "route" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "route".', 29],
                ['Usage of the global function "url" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url", "current" for the current url, "full" for the full url or "previous" for the previous url.', 30],
                ['Usage of the global function "secure_url" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url" with the third parameter set to "true".', 31],
                ['Usage of the global function "redirect" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "to".', 32],
                ['Usage of the global function "to_route" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "route".', 33],
                ['Usage of the global function "back" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "back".', 34],
                ['Usage of the global function "config" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Config\Repository and use method "all" or "get".', 35],
                ['Usage of the global function "logger" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Log\LogManager and use method "debug" or the class itself when currently called without parameters.', 36],
                ['Usage of the global function "info" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Log\LogManager and use method "info".', 37],
                ['Usage of the global function "rescue" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Debug\ExceptionHandler and use method "report" within a try-catch.', 38],
                ['Usage of the global function "request" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Http\Request.', 39],
                ['Usage of the global function "old" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Http\Request and use method "old".', 40],
                ['Usage of the global function "response" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\ResponseFactory and use the class itself or method "make" when originally called with arguments.', 41],
                ['Usage of the global function "mix" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Foundation\Mix and invoke the class: "$mix()".', 42],
                ['Usage of the global function "auth" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Auth\Factory and use the class itself or method "guard" when originally called with arguments.', 43],
                ['Usage of the global function "cookie" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Cookie\Factory and use the class itself or method "make" when originally called with arguments.', 44],
                ['Usage of the global function "encrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Encryption\Encrypter and use method "encrypt".', 45],
                ['Usage of the global function "decrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Encryption\Encrypter and use method "decrypt".', 46],
                ['Usage of the global function "bcrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Hashing\HashManager and use "->driver(\'bcrypt\')->make()".', 47],
                ['Usage of the global function "session" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use the class itself or method "get" when originally called with arguments.', 48],
                ['Usage of the global function "csrf_token" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use method "token".', 49],
                ['Usage of the global function "csrf_field" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use "new HtmlString(\'<input type="hidden" name="_token" value="\' . $sessionManager->token() . \'">\')".', 50],
                ['Usage of the global function "broadcast" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Broadcasting\BroadcastManager and use method "event".', 51],
                ['Usage of the global function "dispatch" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Bus\Dispatcher and use method "dispatch".', 52],
                ['Usage of the global function "event" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Events\Dispatcher and use method "dispatch".', 53],
                ['Usage of the global function "policy" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Auth\Access\Gate and use method "getPolicyFor".', 54],
                ['Usage of the global function "view" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\View\Factory and use method "make".', 55],
                ['Usage of the global function "validator" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Validation\Factory and use method "make".', 56],
                ['Usage of the global function "cache" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Cache\CacheManager and use method "get".', 57],
                ['Usage of the global function "env" is highly dependent on a booted application and makes this code tightly coupled. Instead, Set the environment key in a configuration file so configuration caching doesn\'t break your application, inject Illuminate\Config\Repository and use method "get".', 58],
                ['Usage of the global function "abort" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort".', 59],
            ]
        );
    }

    protected function getRule(): Rule
    {
        return new NoHelperDependingOnBootedApplicationUsageRule();
    }
}
