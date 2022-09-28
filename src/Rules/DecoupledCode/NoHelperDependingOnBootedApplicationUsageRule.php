<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\DecoupledCode;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class NoHelperDependingOnBootedApplicationUsageRule implements Rule
{
    private const GLOBAL_HELPERS_ALTERNATIVES = [
        'logger'        => 'Inject Psr\Log\LoggerInterface and use method "debug" or the class itself when currently called without parameters',
        'info'          => 'Inject Psr\Log\LoggerInterface and use method "info"',
        'app_path'      => 'Inject Illuminate\Contracts\Foundation\Application and use method "path"',
        'base_path'     => 'Inject Illuminate\Contracts\Foundation\Application and use method "basePath"',
        'config_path'   => 'Inject Illuminate\Contracts\Foundation\Application and use method "configPath"',
        'database_path' => 'Inject Illuminate\Contracts\Foundation\Application and use method "databasePath"',
        'resource_path' => 'Inject Illuminate\Contracts\Foundation\Application and use method "resourcePath"',
        'public_path'   => 'Inject Illuminate\Contracts\Foundation\Application and use method "publicPath"',
        'lang_path'     => 'Inject Illuminate\Contracts\Foundation\Application and use method "langPath"',
        'storage_path'  => 'Inject Illuminate\Contracts\Foundation\Application and use method "storagePath"',
        'resolve'       => 'Inject Illuminate\Contracts\Foundation\Application and use method "make"',
        'app'           => 'Inject Illuminate\Contracts\Foundation\Application and use method "make"',
        'abort'         => 'Inject Illuminate\Contracts\Foundation\Application and use method "abort"',
        'abort_if'      => 'Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement',
        'abort_unless'  => 'Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement',
        '__'            => 'Inject Illuminate\Contracts\Translation\Translator and use method "get"',
        'trans'         => 'Inject Illuminate\Contracts\Translation\Translator and use method "get"',
        'trans_choice'  => 'Inject Illuminate\Contracts\Translation\Translator and use method "choice"',
        'action'        => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "action"',
        'asset'         => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset"',
        'secure_asset'  => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset" with the second parameter set to "true"',
        'route'         => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "route"',
        'url'           => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url", "current" for the current url, "full" for the full url or "previous" for the previous url',
        'secure_url'    => 'Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url" with the third parameter set to "true"',
        'config'        => 'Inject Illuminate\Contracts\Config\Repository and use method "all" or "get"',
        'report'        => 'Inject Illuminate\Contracts\Debug\ExceptionHandler and use method "report"',
        'rescue'        => 'Inject Illuminate\Contracts\Debug\ExceptionHandler and use method "report" within a try-catch',
        'response'      => 'Inject Illuminate\Contracts\Routing\ResponseFactory and use the class itself or method "make" when originally called with arguments',
        'auth'          => 'Inject Illuminate\Contracts\Auth\Factory and use the class itself or method "guard" when originally called with arguments',
        'cookie'        => 'Inject Illuminate\Contracts\Cookie\Factory and use the class itself or method "make" when originally called with arguments',
        'encrypt'       => 'Inject Illuminate\Contracts\Encryption\Encrypter and use method "encrypt"',
        'decrypt'       => 'Inject Illuminate\Contracts\Encryption\Encrypter and use method "decrypt"',
        'bcrypt'        => 'Inject Illuminate\Contracts\Hashing\Hasher and use "->driver(\'bcrypt\')->make()"',
        'broadcast'     => 'Inject Illuminate\Contracts\Broadcasting\Factory and use method "event"',
        'dispatch'      => 'Inject Illuminate\Contracts\Bus\QueueingDispatcher and use method "dispatch"',
        'event'         => 'Inject Illuminate\Contracts\Events\Dispatcher and use method "dispatch"',
        'policy'        => 'Inject Illuminate\Contracts\Auth\Access\Gate and use method "getPolicyFor"',
        'view'          => 'Inject Illuminate\Contracts\View\Factory and use method "make"',
        'validator'     => 'Inject Illuminate\Contracts\Validation\Factory and use method "make"',
        'cache'         => 'Inject Illuminate\Contracts\Cache\Factory and use method "get"',
        'mix'           => 'Inject Illuminate\Foundation\Mix and invoke the class: "$mix()"',
        'request'       => 'Inject Illuminate\Http\Request',
        'old'           => 'Inject Illuminate\Http\Request and use method "old"',
        'redirect'      => 'Inject Illuminate\Routing\Redirector and use method "to"',
        'to_route'      => 'Inject Illuminate\Routing\Redirector and use method "route"',
        'back'          => 'Inject Illuminate\Routing\Redirector and use method "back"',
        'session'       => 'Inject Illuminate\Session\SessionManager and use the class itself or method "get" when originally called with arguments',
        'csrf_token'    => 'Inject Illuminate\Session\SessionManager and use method "token"',
        'csrf_field'    => 'Inject Illuminate\Session\SessionManager and use "new HtmlString(\'<input type="hidden" name="_token" value="\' . $sessionManager->token() . \'">\')"',
        'env'           => 'Set the environment key in a configuration file so configuration caching doesn\'t break your application, inject Illuminate\Config\Repository and use method "get"',
    ];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Name) {
            return [];
        }

        $functionName = strtolower($node->name->toString());

        if (! array_key_exists($functionName, self::GLOBAL_HELPERS_ALTERNATIVES)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Usage of the global function "'.$functionName.'" is highly dependent on a '.
                                      'booted application and makes this code tightly coupled. Instead, '.
                                      self::GLOBAL_HELPERS_ALTERNATIVES[$functionName].'.')
                            ->line($node->getLine())
                            ->build(),
        ];
    }
}
