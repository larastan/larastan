<?php

declare(strict_types=1);

namespace Rules\Data\DecoupledCode;

class HelperDependingOnBootedApplication
{
    public function foo(): void
    {
        app_path();
        base_path();
        config_path();
        database_path();
        resource_path();
        public_path();
        lang_path();
        storage_path();
        resolve();
        app();
        abort_if();
        abort_unless();
        __();
        trans();
        trans_choice();
        action();
        asset();
        secure_asset();
        route();
        url();
        secure_url();
        redirect();
        to_route();
        back();
        config();
        logger();
        info();
        rescue();
        request();
        old();
        response();
        mix();
        auth();
        cookie();
        encrypt();
        decrypt();
        bcrypt();
        session();
        csrf_token();
        csrf_field();
        broadcast();
        dispatch();
        event();
        policy();
        view();
        validator();
        cache();
        env();
        abort();
    }
}
